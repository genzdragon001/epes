<?php
/**
 * download_target_template.php
 * Streams a CSV template for bulk-adding targets (task_list rows).
 * Admin-only. Includes a header row, three example rows, and a
 * reference block explaining accepted values.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_connect.php';

// Admin only (login_type 2 = Admin)
if (($_SESSION['login_type'] ?? null) != 2) {
    http_response_code(403);
    die('Forbidden: admin access required.');
}

// Pull valid designation / rank names so the template documents real options
$desigs = [];
$dq = $conn->query("SELECT designation FROM designation_list WHERE id > 0 ORDER BY designation ASC");
while ($r = $dq->fetch_assoc()) $desigs[] = $r['designation'];

$ranks = [];
$rq = $conn->query("SELECT position FROM position_list ORDER BY id ASC");
while ($r = $rq->fetch_assoc()) $ranks[] = $r['position'];

$filename = 'epes_target_template.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 BOM so Excel opens accents correctly
fwrite($out, "\xEF\xBB\xBF");

// ---- Column headers (exact keys the importer expects) ----
$headers = [
    'category',            // strategic | core | support   (REQUIRED)
    'sub_category',        // instructions | research | extension  (REQUIRED only when category=core)
    'designation',         // designation name, or blank = All
    'academic_rank',       // academic rank/position name, or blank = All
    'mfo',                 // Major Function Output number 0-4 (optional, default 0)
    'success_indicators',  // REQUIRED
    'targets_measures',    // REQUIRED
    'major_output',        // optional short label
    'deadline',            // YYYY-MM-DD (optional)
    'quality',             // Applicable | Not Applicable (default Applicable)
    'timeliness',          // Applicable | Not Applicable (default Applicable)
    'efficiency',          // Applicable | Not Applicable (default Applicable)
    'is_active',           // 1 | 0 (default 1)
];
fputcsv($out, $headers);

// ---- Example rows ----
fputcsv($out, [
    'core', 'instructions', '', '', '1',
    'Teaching effectiveness rating of at least 4.0',
    'Attain a mean rating of 4.0 in student/peer evaluation',
    'Teaching Effectiveness', '2026-06-30',
    'Applicable', 'Applicable', 'Applicable', '1',
]);
fputcsv($out, [
    'core', 'research', '', 'Associate Professor I', '3',
    'At least 1 research published in a reputable journal',
    '1 published research paper within the rating period',
    'Research Publication', '2026-12-15',
    'Applicable', 'Not Applicable', 'Applicable', '1',
]);
fputcsv($out, [
    'strategic', '', 'Department Head|Dean', '', '0',
    'Submission of departmental strategic plan',
    'Approved strategic plan submitted on or before deadline',
    'Strategic Planning', '2026-05-31',
    'Applicable', 'Applicable', 'Applicable', '1',
]);

// ---- Reference block (commented; importer ignores lines starting with #) ----
fputcsv($out, []);
fputcsv($out, ['# ===== REFERENCE (delete these # lines before / they are ignored on import) ====']);
fputcsv($out, ['# category', 'strategic | core | support']);
fputcsv($out, ['# sub_category', 'required only when category=core: instructions | research | extension']);
fputcsv($out, ['# designation', 'blank = All. Multiple: separate with | (e.g. Department Head|Dean). Valid: ' . implode(' ; ', $desigs)]);
fputcsv($out, ['# academic_rank', 'blank = All. Valid: ' . implode(' ; ', $ranks)]);
fputcsv($out, ['# mfo', 'Major Function Output index 0-4 (grouping). Default 0']);
fputcsv($out, ['# quality/timeliness/efficiency', 'Applicable | Not Applicable (also accepts Yes/No/N/A). Default Applicable']);
fputcsv($out, ['# is_active', '1 = Active (default), 0 = Inactive']);

fclose($out);
exit;
