<?php
// Simple MySQL connectivity test through Apache
require_once __DIR__ . '/db_connect.php';

$result = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema='epes_db'");
$row = $result->fetch_assoc();
echo "OK: " . $row['cnt'] . " tables in epes_db\n";
echo "Connected via: " . $conn->host_info . "\n";
