<?php
/**
 * Web-triggered backup endpoint
 * Called via: curl http://localhost/epes/backup_trigger.php?key=CHANGE_ME
 */
require_once __DIR__ . '/db_connect.php';

// Simple key-based auth (change this!)
$auth_key = 'epes_backup_2025';
if (!isset($_GET['key']) || $_GET['key'] !== $auth_key) {
    http_response_code(403);
    die("Forbidden");
}

// Run the backup
ob_start();
require __DIR__ . '/backup_run.php';
$output = ob_get_clean();

header('Content-Type: text/plain');
echo $output;
