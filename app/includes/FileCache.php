<?php
/**
 * FileCache — A lightweight file-based caching class.
 *
 * Stores JSON-encoded data in the app/cache/ directory.
 * Avoids repeated database hits for read-heavy, infrequently changing data.
 *
 * Usage:
 *   $cache = new FileCache();
 *   $data = $cache->get('departments');
 *   if ($data === null) {
 *       $data = fetchFromDB();
 *       $cache->set('departments', $data, 3600); // cache for 1 hour
 *   }
 *   $cache->delete('departments'); // invalidate manually
 */
class FileCache {
    private $cacheDir;
    private $defaultTtl;

    /**
     * @param string $cacheDir  Absolute path to the cache directory.
     * @param int    $defaultTtl Default time-to-live in seconds (default: 3600 = 1 hour).
     */
    public function __construct($cacheDir = null, $defaultTtl = 3600) {
        if ($cacheDir === null) {
            // Resolve relative to this file's location: app/includes/ -> app/cache/
            $cacheDir = dirname(__DIR__) . '/cache';
        }
        $this->cacheDir = rtrim($cacheDir, '/\\');
        $this->defaultTtl = $defaultTtl;

        // Ensure directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Retrieve an item from the cache.
     * Returns null if the item is missing or expired.
     *
     * @param string $key  Cache key.
     * @return mixed|null  The cached data or null on a miss.
     */
    public function get($key) {
        $file = $this->filePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $entry = json_decode($content, true);
        if (!$entry || !isset($entry['expires_at'], $entry['data'])) {
            return null;
        }

        // Check expiry
        if (time() > $entry['expires_at']) {
            @unlink($file); // clean up expired file
            return null;
        }

        return $entry['data'];
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key   Cache key.
     * @param mixed  $data  Data to cache (will be JSON-encoded).
     * @param int|null $ttl Time-to-live in seconds. Null uses the default TTL.
     * @return bool         True on success.
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTtl;
        $entry = json_encode([
            'expires_at' => time() + $ttl,
            'created_at' => time(),
            'data'       => $data,
        ]);

        return file_put_contents($this->filePath($key), $entry, LOCK_EX) !== false;
    }

    /**
     * Delete a specific cache entry.
     *
     * @param string $key Cache key.
     * @return bool True if deleted, false if it did not exist.
     */
    public function delete($key) {
        $file = $this->filePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return false;
    }

    /**
     * Flush the entire cache directory.
     * Useful after bulk admin operations (e.g. adding/editing doctors).
     *
     * @return void
     */
    public function flush() {
        $files = glob($this->cacheDir . '/*.cache.json');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Check whether a given cache key currently has a valid (non-expired) entry.
     *
     * @param string $key Cache key.
     * @return bool
     */
    public function has($key) {
        return $this->get($key) !== null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function filePath($key) {
        // Sanitise the key so it is safe to use as a filename
        $safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $key);
        return $this->cacheDir . '/' . $safe . '.cache.json';
    }
}
