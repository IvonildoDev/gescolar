<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = ""; // Default XAMPP has no password
$database = "school_system";

// Create MySQLi connection (leave this part for compatibility with existing code)
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create PDO connection for login.php and other scripts that use PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}

// Remove this output from production code
// echo "Connection successful! Database is working.";

// Test query - remove or comment this out in production
// $result = $conn->query("SELECT COUNT(*) as count FROM professors");
// if ($result) {
//     $row = $result->fetch_assoc();
//     echo "<br>Found " . $row['count'] . " professors in the database.";
// }

// Don't close the connections here so they can be used by other scripts
// $conn->close();
