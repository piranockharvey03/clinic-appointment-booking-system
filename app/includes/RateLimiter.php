<?php
class RateLimiter {
    private $conn;
    private $maxAttempts;
    private $lockoutMinutes;

    public function __construct($conn, $maxAttempts = 5, $lockoutMinutes = 15) {
        $this->conn = $conn;
        $this->maxAttempts = $maxAttempts;
        $this->lockoutMinutes = $lockoutMinutes;
    }

    /**
     * Checks if the given IP is currently locked out.
     * @param string $ip The IP address to check
     * @return bool True if locked out, false otherwise
     */
    public function isLockedOut($ip) {
        $query = "SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt
                  FROM login_attempts
                  WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("si", $ip, $this->lockoutMinutes);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['attempts'] >= $this->maxAttempts) {
            return true;
        }
        return false;
    }

    /**
     * Records a failed login attempt.
     * @param string $ip The IP address
     * @param string $email The email attempted
     */
    public function recordFailedAttempt($ip, $email) {
        $query = "INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ss", $ip, $email);
            $stmt->execute();
        }
    }

    /**
     * Clears all recorded attempts for a given IP.
     * Should be called upon successful login.
     * @param string $ip The IP address
     */
    public function clearAttempts($ip) {
        $query = "DELETE FROM login_attempts WHERE ip_address = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("s", $ip);
            $stmt->execute();
        }
    }
}
?>
