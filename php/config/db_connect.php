<?php
/**
 * Database Connection Configuration
 * WatchVault - XAMPP MySQL Connection
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'watchvault'); //Database name

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return false;
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

/**
 * Close database connection
 */
function closeDBConnection() {
    global $conn;
    if ($conn !== null) {
        $conn->close();
        $conn = null;
    }
}

?>

