<?php
include 'db_connect.php';

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'renewal_recommendations'");
if ($result->num_rows == 0) {
    echo "Table does not exist. Creating...<br>";
    $sql = "CREATE TABLE `renewal_recommendations` (
      `id` int(30) NOT NULL AUTO_INCREMENT,
      `faculty_id` int(30) NOT NULL,
      `evaluator_id` int(30) NOT NULL,
      `rating_period` varchar(100) NOT NULL,
      `overall_score` decimal(5,2) NOT NULL,
      `instruction_ave` decimal(5,2) DEFAULT NULL,
      `support_ave` decimal(5,2) DEFAULT NULL,
      `total_tasks` int(11) NOT NULL DEFAULT 0,
      `verified_tasks` int(11) NOT NULL DEFAULT 0,
      `avg_efficiency` decimal(3,2) DEFAULT NULL,
      `avg_timeliness` decimal(3,2) DEFAULT NULL,
      `avg_quality` decimal(3,2) DEFAULT NULL,
      `recommendation_status` enum('Pending','Recommended','Not Recommended','For Review') DEFAULT 'Pending',
      `system_generated_reason` text NOT NULL,
      `dean_reason` text DEFAULT NULL,
      `dean_decision` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
      `dean_decision_date` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `faculty_id` (`faculty_id`),
      KEY `evaluator_id` (`evaluator_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "Table created successfully!<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
} else {
    echo "Table exists. Checking columns...<br>";
    $result = $conn->query("DESCRIBE renewal_recommendations");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    echo "Current columns: " . implode(", ", $columns) . "<br>";
    
    // Add missing columns
    $needed_cols = ['instruction_ave', 'support_ave'];
    foreach ($needed_cols as $col) {
        if (!in_array($col, $columns)) {
            echo "Adding column: $col<br>";
            $conn->query("ALTER TABLE renewal_recommendations ADD COLUMN $col decimal(5,2) DEFAULT NULL AFTER overall_score");
        }
    }
}

// Test insert
echo "<br>Testing insert...<br>";
$sql = "INSERT INTO renewal_recommendations 
    (faculty_id, evaluator_id, rating_period, overall_score, recommendation_status, system_generated_reason)
    VALUES (1, 1, '2024', 4.5, 'Recommended', 'Test reason')";
    
if ($conn->query($sql)) {
    echo "Test insert successful! Insert ID: " . $conn->insert_id . "<br>";
    // Delete test record
    $conn->query("DELETE FROM renewal_recommendations WHERE id = " . $conn->insert_id);
    echo "Test record deleted.<br>";
} else {
    echo "Test insert failed: " . $conn->error . "<br>";
}

echo "<br><a href='index.php'>Go back to system</a>";
?>
