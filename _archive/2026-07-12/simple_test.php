<?php
echo "PHP works\n";
try {
    $conn = new mysqli('127.0.0.1', 'root', '', 'epes_db', 3306);
    if ($conn->connect_error) {
        echo "MySQL error: " . $conn->connect_error . "\n";
    } else {
        echo "MySQL OK: " . $conn->host_info . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
