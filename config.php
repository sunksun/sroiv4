<?php
// Database configuration
define('DB_SERVER', 'localhost');     // Database server
define('DB_USERNAME', 'root');        // Database username
define('DB_PASSWORD', '');            // Database password
define('DB_NAME', 'sroiv4');         // Database name

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");
