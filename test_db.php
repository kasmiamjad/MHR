<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Include database configuration
    require_once 'config.php';
    
    echo "<h2>Database Connection Test</h2>";
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "Database connection successful<br>";
    
    // Check table structure
    $result = $conn->query("DESCRIBE events");
    if (!$result) {
        throw new Exception("Cannot describe table: " . $conn->error);
    }
    
    echo "<h3>Table Structure:</h3>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
    echo "</pre>";
    
    // Check for any data
    $result = $conn->query("SELECT COUNT(*) as count FROM events");
    if (!$result) {
        throw new Exception("Cannot count records: " . $conn->error);
    }
    $count = $result->fetch_assoc()['count'];
    echo "Total records in events table: " . $count . "<br>";
    
    // Show first few records
    if ($count > 0) {
        $result = $conn->query("SELECT * FROM events LIMIT 3");
        echo "<h3>Sample Records:</h3>";
        echo "<pre>";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
            echo "\n";
        }
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<pre>";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?>