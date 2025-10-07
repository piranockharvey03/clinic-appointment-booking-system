<?php
// User’s raw password (from signup form)
$password = "Michaelchen2025";

// Hash it (using bcrypt by default)
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "Hashed password: " . $hash;
