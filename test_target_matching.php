<?php
session_start();
include 'db_connect.php';

echo "<h2>Target Assignment Debug Report</h2>";
echo "<hr>";

// Get current user's info
$faculty_id = $_SESSION['login_id'] ?? 0;
echo "<h3>Current Faculty: ID $faculty_id</h3>";

$faculty = $conn->query("SELECT e.position_id, e.designation_id, 
    CONCAT(e.lastname, ', ', e.firstname) as name,
    p.position, d.designation
    FROM employee_list e
    LEFT JOIN position_list p ON e.position_id = p.id
    LEFT JOIN designation_list d ON e.designation_id = d.id
    WHERE e.id = $faculty_id")->fetch_assoc();

echo "<strong>Name:</strong> {$faculty['name']}<br>";
echo "<strong>Position ID:</strong> {$faculty['position_id']} ({$faculty['position']})<br>";
echo "<strong>Designation ID:</strong> {$faculty['designation_id']} ({$faculty['designation']})<br><br>";

$position_id = $faculty['position_id'];
$designation_id = $faculty['designation_id'];

// Test different matching conditions
echo "<h3>Target Matching Analysis</h3>";

echo "<h4>1. Direct Position Match (task.designation_id = faculty.position_id)</h4>";
$q1 = $conn->query("SELECT COUNT(*) as c FROM task_list WHERE is_active=1 AND designation_id = $position_id");
echo "Count: " . $q1->fetch_assoc()['c'] . "<br>";

echo "<h4>2. Direct Designation Match (task.designation_id = faculty.designation_id)</h4>";
$q2 = $conn->query("SELECT COUNT(*) as c FROM task_list WHERE is_active=1 AND designation_id = $designation_id");
echo "Count: " . $q2->fetch_assoc()['c'] . "<br>";

echo "<h4>3. Percentage Allocation Match</h4>";
$q3 = $conn->query("SELECT COUNT(DISTINCT t.id) as c 
    FROM task_list t
    INNER JOIN percentage_allocation pa ON pa.category = t.category
    WHERE t.is_active=1 
    AND pa.is_active=1
    AND (pa.position_id = $position_id OR pa.position_id = 0)
    AND (pa.designation_id = $designation_id OR pa.designation_id = 0 OR pa.designation_id = 3)");
echo "Count: " . $q3->fetch_assoc()['c'] . "<br>";

echo "<h4>4. General Targets (designation_id IS NULL OR 0 OR 3)</h4>";
$q4 = $conn->query("SELECT COUNT(*) as c FROM task_list 
    WHERE is_active=1 AND (designation_id IS NULL OR designation_id = 0 OR designation_id = 3)");
echo "Count: " . $q4->fetch_assoc()['c'] . "<br>";

echo "<h4>5. Total Unique Targets for Faculty</h4>";
$q5 = $conn->query("SELECT DISTINCT t.id, t.category, t.mfo, 
    LEFT(COALESCE(t.major_output, t.success_indicators), 60) as target,
    t.designation_id as task_des_id,
    CASE 
        WHEN t.designation_id = $position_id THEN 'Position Match'
        WHEN t.designation_id = $designation_id THEN 'Designation Match'
        WHEN t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = 3 THEN 'General'
        ELSE 'Other'
    END as match_type
    FROM task_list t
    LEFT JOIN percentage_allocation pa ON (
        (pa.position_id = $position_id OR pa.position_id = 0)
        AND (pa.designation_id = $designation_id OR pa.designation_id = 0 OR pa.designation_id = 3)
        AND pa.category = t.category
        AND pa.is_active = 1
    )
    WHERE t.is_active = 1
    AND (
        t.designation_id = $position_id
        OR t.designation_id = $designation_id
        OR pa.id IS NOT NULL
        OR t.designation_id IS NULL
        OR t.designation_id = 0
        OR t.designation_id = 3
    )
    ORDER BY t.category, t.mfo");

echo "Total: " . $q5->num_rows . "<br><br>";

if ($q5->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Category</th><th>MFO</th><th>Target</th><th>Task DesID</th><th>Match Type</th></tr>";
    while ($row = $q5->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['category']}</td>";
        echo "<td>{$row['mfo']}</td>";
        echo "<td>{$row['target']}</td>";
        echo "<td>{$row['task_des_id']}</td>";
        echo "<td>{$row['match_type']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>Percentage Allocation Rules</h3>";
$pa_rules = $conn->query("SELECT position_id, designation_id, category, percentage 
    FROM percentage_allocation 
    WHERE is_active=1 
    AND (position_id = $position_id OR position_id = 0)
    AND (designation_id = $designation_id OR designation_id = 0 OR designation_id = 3)
    ORDER BY category, percentage DESC");

if ($pa_rules->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Pos ID</th><th>Des ID</th><th>Category</th><th>Percentage</th></tr>";
    while ($rule = $pa_rules->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$rule['position_id']}</td>";
        echo "<td>{$rule['designation_id']}</td>";
        echo "<td>{$rule['category']}</td>";
        echo "<td>{$rule['percentage']}%</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No percentage allocation rules found for this faculty.<br>";
}
?>
