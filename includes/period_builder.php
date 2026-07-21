<?php
// Shared period-building logic — included by home.php, target_list.php,
// mov_management.php, status.php, rating.php, archives.php, etc.
// Builds $real_periods, $selected_period, $period_codes, $period_filter,
// $period_label, $selected_period_ids from the rating_period table.
//
// Relies on $conn (from db_connect.php).  Uses ?period= URL param and
// $_SESSION['view_period'] for persistence — same behaviour everywhere.

if (!function_exists('epes_short_code')) {
    function epes_short_code($semester, $year) {
        list($start, $end) = explode("-", $year);
        $short = substr($start, -2) . substr($end, -2);
        switch ($semester) {
            case '1st Semester': return "1-" . $short;
            case '2nd Semester': return "2-" . $short;
            case 'Summer':       return "S-" . $short;
            default:             return "1-" . $short;
        }
    }
}
if (!function_exists('epes_period_key')) {
    function epes_period_key($semester, $year) { return $semester . '|' . $year; }
}

// Build de-duplicated list of real periods (semester + year).
$raw_periods = [];
$rp_qry = $conn->query("SELECT * FROM rating_period ORDER BY year DESC, semester DESC");
while ($rp_qry && $r = $rp_qry->fetch_assoc()) $raw_periods[] = $r;

$real_periods = [];
foreach ($raw_periods as $p) {
    $k = epes_period_key($p['semester'], $p['year']);
    if (!isset($real_periods[$k])) {
        $real_periods[$k] = [
            'semester'  => $p['semester'],
            'year'      => $p['year'],
            'is_active' => ($p['is_active'] == 1),
        ];
    } else {
        $real_periods[$k]['is_active'] = $real_periods[$k]['is_active'] || ($p['is_active'] == 1);
    }
}
$real_periods = array_values($real_periods);

$active_period = null;
foreach ($real_periods as $rp) { if ($rp['is_active']) { $active_period = $rp; break; } }

// Determine selected period from ?period= or session, default to active
$req_key = $_GET['period'] ?? ($_SESSION['view_period'] ?? null);
$selected_period = null;
if ($req_key) {
    foreach ($real_periods as $rp) {
        if (epes_period_key($rp['semester'], $rp['year']) === $req_key) { $selected_period = $rp; break; }
    }
}
if (!$selected_period) $selected_period = $active_period ?: (count($real_periods) ? $real_periods[0] : null);
if ($selected_period) $_SESSION['view_period'] = epes_period_key($selected_period['semester'], $selected_period['year']);

// Collect rating_period IDs for the selected period (for cascade queries)
$selected_period_ids = [];
if ($selected_period) {
    $sel_key = epes_period_key($selected_period['semester'], $selected_period['year']);
    foreach ($raw_periods as $p) {
        if (epes_period_key($p['semester'], $p['year']) === $sel_key) $selected_period_ids[] = intval($p['id']);
    }
}

// Build period_codes — all code variants for the selected semester+year
$period_codes = [];
if ($selected_period) {
    $sel_sem = $conn->real_escape_string($selected_period['semester']);
    $sel_yr  = $conn->real_escape_string($selected_period['year']);
    $sel_key = epes_period_key($sel_sem, $sel_yr);

    // stored codes from rating_period table
    foreach ($raw_periods as $p) {
        if (epes_period_key($p['semester'], $p['year']) === $sel_key) $period_codes[] = $p['code'];
    }
    // short code (e.g. 1-2526)
    $period_codes[] = epes_short_code($sel_sem, $sel_yr);
    // "Semester Year" format (used by mov_uploads)
    $period_codes[] = $sel_sem . ' ' . $sel_yr;

    // data-driven: match semester+year (NOT year alone — that cross-matches semesters)
    $sem_compact = str_replace(' ', '', $sel_sem);
    $like = $conn->real_escape_string($sem_compact . '-' . $sel_yr);
    $short = epes_short_code($sel_sem, $sel_yr);
    $dq = $conn->query("SELECT DISTINCT rating_period FROM task_progress WHERE rating_period <> '' AND (rating_period LIKE '%$like%' OR rating_period LIKE '%$short%')");
    while ($dq && $r = $dq->fetch_assoc()) $period_codes[] = $r['rating_period'];
    $rq = $conn->query("SELECT DISTINCT rating_period FROM ratings WHERE rating_period <> '' AND (rating_period LIKE '%$like%' OR rating_period LIKE '%$short%')");
    while ($rq && $r = $rq->fetch_assoc()) $period_codes[] = $r['rating_period'];
    $mq = $conn->query("SELECT DISTINCT rating_period FROM mov_uploads WHERE rating_period <> '' AND (rating_period LIKE '%$like%' OR rating_period LIKE '%$short%' OR rating_period = '" . $conn->real_escape_string($sel_sem . ' ' . $sel_yr) . "')");
    while ($mq && $r = $mq->fetch_assoc()) $period_codes[] = $r['rating_period'];

    $period_codes = array_values(array_unique(array_filter($period_codes)));
}

// SQL fragment for scoping queries to the selected period
if (!empty($period_codes)) {
    $in = implode("','", array_map([$conn, 'real_escape_string'], $period_codes));
    $period_filter = " AND rating_period IN ('$in')";
} else {
    $period_filter = " AND 0";
}

$period_label = $selected_period ? ($selected_period['semester'] . ' ' . $selected_period['year']) : 'No period set';

// Active period code (for new submissions)
$active_period_code = '';
if ($active_period) {
    foreach ($raw_periods as $p) {
        if (epes_period_key($p['semester'], $p['year']) === epes_period_key($active_period['semester'], $active_period['year'])) {
            $active_period_code = $p['code'];
            break;
        }
    }
}