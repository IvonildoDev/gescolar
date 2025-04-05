<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Check if managers table exists
try {
    $tableExists = $conn->query("SHOW TABLES LIKE 'managers'");

    if (!$tableExists) {
        echo "Error checking if table exists: " . $conn->error . "<br>";
    } else if ($tableExists->num_rows == 0) {
        // Create managers table
        $createTable = $conn->query("
            CREATE TABLE managers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100),
                username VARCHAR(50) UNIQUE,
                password VARCHAR(255)
            )
        ");

        if (!$createTable) {
            echo "Error creating table: " . $conn->error . "<br>";
        } else {
            // Insert a default manager
            $insertManager = $conn->query("
                INSERT INTO managers (name, username, password) 
                VALUES ('Admin', 'admin', 'admin')
            ");

            if (!$insertManager) {
                echo "Error inserting default manager: " . $conn->error . "<br>";
            } else {
                echo "Managers table created with default admin user.<br>";
                echo "Username: admin<br>";
                echo "Password: admin<br>";
            }
        }
    } else {
        echo "Managers table already exists.<br>";

        // For debugging: show existing managers
        $managers = $conn->query("SELECT id, name, username FROM managers");
        if ($managers) {
            echo "<br>Current managers:<br>";
            while ($row = $managers->fetch_assoc()) {
                echo "ID: " . $row['id'] . ", Name: " . $row['name'] . ", Username: " . $row['username'] . "<br>";
            }
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
