<?php
/**
 * bulk_upload_targets.php
 * Parses an uploaded CSV of targets and inserts them into task_list.
 * Admin-only. Returns JSON: {status, inserted, failed, errors[], message}.
 *
 * Writes directly to the real task_list schema (the legacy save_task()
 * handler targets an outdated column layout, so it is intentionally not reused).
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/csrf_helper.php';

function respond($arr) { echo json_encode($arr); exit; }

// ---- Auth: admin only ----
if (($_SESSION['login_type'] ?? null) != 2) {
    http_response_code(403);
    respond(['status' => 'error', 'message' => 'Forbidden: admin access required.']);
}

// ---- CSRF / AJAX guard (mirror ajax.php's non-critical layer) ----
$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
$has_csrf = isset($_POST['csrf_token']) && validate_csrf_token($_POST['csrf_token']);
if (!$is_ajax && !$has_csrf) {
    http_response_code(403);
    respond(['status' => 'error', 'message' => 'CSRF validation failed.']);
}

// ---- File presence ----
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    respond(['status' => 'error', 'message' => 'No CSV file uploaded or upload error.']);
}

$tmp = $_FILES['csv_file']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    respond(['status' => 'error', 'message' => 'Please upload a .csv file.']);
}

$fh = fopen($tmp, 'r');
if (!$fh) {
    respond(['status' => 'error', 'message' => 'Unable to read uploaded file.']);
}

// ---- Build lookup maps (name -> id), case-insensitive ----
$desig_map = [];
$dq = $conn->query("SELECT id, designation FROM designation_list");
while ($r = $dq->fetch_assoc()) $desig_map[strtolower(trim($r['designation']))] = (int)$r['id'];

$rank_map = [];
$rq = $conn->query("SELECT id, position FROM position_list");
while ($r = $rq->fetch_assoc()) $rank_map[strtolower(trim($r['position']))] = (int)$r['id'];

$valid_categories = ['strategic', 'core', 'support'];
$valid_subcats    = ['instructions', 'research', 'extension'];

// Normalize an Applicable/Not Applicable field
function norm_applicable($v) {
    $v = strtolower(trim((string)$v));
    if ($v === '' ) return 'Applicable';
    if (in_array($v, ['applicable', 'yes', 'y', '1', 'true'])) return 'Applicable';
    if (in_array($v, ['not applicable', 'n/a', 'na', 'no', 'n', '0', 'false'])) return 'Not Applicable';
    return null; // invalid
}

// ---- Read header row ----
$header = fgetcsv($fh);
if ($header === false) {
    fclose($fh);
    respond(['status' => 'error', 'message' => 'CSV is empty.']);
}
// Strip BOM from first header cell
if (isset($header[0])) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
$header = array_map(function ($h) { return strtolower(trim($h)); }, $header);

$required_cols = ['category', 'success_indicators', 'targets_measures'];
foreach ($required_cols as $rc) {
    if (!in_array($rc, $header)) {
        fclose($fh);
        respond(['status' => 'error', 'message' => "CSV is missing required column: $rc. Download the template for the correct format."]);
    }
}
$idx = array_flip($header);
function cell($row, $idx, $key) {
    return isset($idx[$key], $row[$idx[$key]]) ? trim($row[$idx[$key]]) : '';
}

$created_by = (int)($_SESSION['login_id'] ?? 0);
$created_by = $created_by > 0 ? $created_by : null;

$inserted = 0;
$failed   = 0;
$errors   = [];
$rowsToInsert = [];
$lineNo = 1; // header consumed

// ---- Parse + validate all rows first ----
while (($row = fgetcsv($fh)) !== false) {
    $lineNo++;

    // Skip fully-empty lines
    if (count(array_filter($row, function ($c) { return trim((string)$c) !== ''; })) === 0) continue;

    // Skip reference/comment lines (# in first cell)
    if (isset($row[0]) && substr(ltrim($row[0]), 0, 1) === '#') continue;

    $category = strtolower(cell($row, $idx, 'category'));
    $sub_raw  = strtolower(cell($row, $idx, 'sub_category'));
    $success  = cell($row, $idx, 'success_indicators');
    $targets  = cell($row, $idx, 'targets_measures');
    $desig_nm = cell($row, $idx, 'designation');
    $rank_nm  = cell($row, $idx, 'academic_rank');
    $mfo_raw  = cell($row, $idx, 'mfo');
    $majout   = cell($row, $idx, 'major_output');
    $deadline = cell($row, $idx, 'deadline');
    $quality  = cell($row, $idx, 'quality');
    $timeli   = cell($row, $idx, 'timeliness');
    $effic    = cell($row, $idx, 'efficiency');
    $active_r = cell($row, $idx, 'is_active');

    $rowErr = [];

    // category
    if (!in_array($category, $valid_categories)) {
        $rowErr[] = "invalid category '$category' (use strategic/core/support)";
    }

    // sub_category: required for core, must be empty/valid otherwise
    $sub_category = null;
    if ($category === 'core') {
        if (!in_array($sub_raw, $valid_subcats)) {
            $rowErr[] = "core requires sub_category (instructions/research/extension), got '$sub_raw'";
        } else {
            $sub_category = $sub_raw;
        }
    } else {
        if ($sub_raw !== '' && !in_array($sub_raw, $valid_subcats)) {
            $rowErr[] = "sub_category '$sub_raw' only valid for core category";
        }
        $sub_category = ($sub_raw !== '') ? $sub_raw : null;
    }

    // required text
    if ($success === '') $rowErr[] = "success_indicators is required";
    if ($targets === '') $rowErr[] = "targets_measures is required";

    // designation (supports multiple, separated by |)
    $designation_ids = [];
    if ($desig_nm !== '') {
        foreach (explode('|', $desig_nm) as $dn) {
            $dn = trim($dn);
            if ($dn === '') continue;
            $k = strtolower($dn);
            if (isset($desig_map[$k])) $designation_ids[] = $desig_map[$k];
            else $rowErr[] = "unknown designation '$dn'";
        }
    }
    // Legacy column: use first designation if any, else 0 (All)
    $designation_id = !empty($designation_ids) ? $designation_ids[0] : 0;

    // academic rank
    $academic_rank_id = 0;
    if ($rank_nm !== '') {
        $k = strtolower($rank_nm);
        if (isset($rank_map[$k])) $academic_rank_id = $rank_map[$k];
        else $rowErr[] = "unknown academic_rank '$rank_nm'";
    }

    // mfo
    $mfo = 0;
    if ($mfo_raw !== '') {
        if (is_numeric($mfo_raw) && (int)$mfo_raw >= 0 && (int)$mfo_raw <= 4) $mfo = (int)$mfo_raw;
        else $rowErr[] = "mfo '$mfo_raw' must be a number 0-4";
    }

    // deadline
    $deadline_val = null;
    if ($deadline !== '') {
        $d = date_create($deadline);
        if ($d) $deadline_val = $d->format('Y-m-d');
        else $rowErr[] = "invalid deadline '$deadline' (use YYYY-MM-DD)";
    }

    // applicable enums
    $q = norm_applicable($quality);
    $t = norm_applicable($timeli);
    $e = norm_applicable($effic);
    if ($q === null) $rowErr[] = "invalid quality '$quality'";
    if ($t === null) $rowErr[] = "invalid timeliness '$timeli'";
    if ($e === null) $rowErr[] = "invalid efficiency '$effic'";

    // is_active
    $is_active = 1;
    if ($active_r !== '') {
        if (in_array(strtolower($active_r), ['1', 'active', 'yes', 'true'])) $is_active = 1;
        elseif (in_array(strtolower($active_r), ['0', 'inactive', 'no', 'false'])) $is_active = 0;
        else $rowErr[] = "invalid is_active '$active_r' (use 1/0)";
    }

    if (!empty($rowErr)) {
        $failed++;
        $errors[] = "Row $lineNo: " . implode('; ', $rowErr);
        continue;
    }

    $rowsToInsert[] = [
        'mfo' => $mfo,
        'designation_id' => $designation_id,
        'designation_ids' => $designation_ids,
        'academic_rank_id' => $academic_rank_id,
        'category' => $category,
        'sub_category' => $sub_category,
        'major_output' => ($majout !== '' ? $majout : null),
        'success_indicators' => $success,
        'targets_measures' => $targets,
        'deadline' => $deadline_val,
        'quality' => $q,
        'timeliness' => $t,
        'efficiency' => $e,
        'is_active' => $is_active,
    ];
}
fclose($fh);

if (empty($rowsToInsert)) {
    respond([
        'status' => 'error',
        'message' => 'No valid rows to import.',
        'inserted' => 0, 'failed' => $failed, 'errors' => $errors,
    ]);
}

// ---- Insert inside a transaction ----
$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO task_list
         (mfo, designation_id, academic_rank_id, category, sub_category, major_output,
          success_indicators, targets_measures, deadline, quality, timeliness, efficiency,
          is_active, created_by, date_created)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    if (!$stmt) throw new Exception($conn->error);

    $jstmt = $conn->prepare(
        "INSERT INTO task_designations (task_id, designation_id) VALUES (?, ?)"
    );
    if (!$jstmt) throw new Exception($conn->error);

    foreach ($rowsToInsert as $r) {
        $stmt->bind_param(
            'iiisssssssss' . 'ii',
            $r['mfo'], $r['designation_id'], $r['academic_rank_id'], $r['category'],
            $r['sub_category'], $r['major_output'], $r['success_indicators'],
            $r['targets_measures'], $r['deadline'], $r['quality'], $r['timeliness'],
            $r['efficiency'], $r['is_active'], $created_by
        );
        $stmt->execute();
        $newTaskId = $conn->insert_id;
        $inserted++;

        // Insert junction rows for all designations
        if (!empty($r['designation_ids'])) {
            foreach ($r['designation_ids'] as $did) {
                $jstmt->bind_param('ii', $newTaskId, $did);
                $jstmt->execute();
            }
        }
    }
    $stmt->close();
    $jstmt->close();
    $conn->commit();
} catch (Exception $ex) {
    $conn->rollback();
    respond([
        'status' => 'error',
        'message' => 'Database error during import (no rows saved): ' . $ex->getMessage(),
        'inserted' => 0, 'failed' => $failed, 'errors' => $errors,
    ]);
}

$msg = "Imported $inserted target(s).";
if ($failed > 0) $msg .= " Skipped $failed invalid row(s).";

respond([
    'status'   => 'success',
    'inserted' => $inserted,
    'failed'   => $failed,
    'errors'   => $errors,
    'message'  => $msg,
]);
