<?php
// php/config/db_connect.php

// Error reporting for development
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

function getDBConnection() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'watchvault';
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return null;
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>