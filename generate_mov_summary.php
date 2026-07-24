<?php
session_start();
include 'db_connect.php';

$faculty_id = intval($_SESSION['login_id'] ?? 0);
$period = $_GET['period'] ?? '';
$target_id = isset($_GET['target_id']) ? intval($_GET['target_id']) : 0;

// Stored rating_period uses "Semester Year" (space). Callers may pass a pipe or
// underscore (e.g. "1st Semester|2025-2026"); normalize so the lookup matches the
// value stored in mov_uploads.rating_period.
$period = str_replace(['|', '_'], ' ', $period);

// Get faculty info (first name first format)
$stmt_fac = $conn->prepare("SELECT CONCAT(e.firstname, ' ', e.middlename, ' ', e.lastname) as name,
    e.position_id, p.position, e.department_id, e.designation_id, d.designation, e.evaluator_id
    FROM employee_list e
    LEFT JOIN position_list p ON e.position_id = p.id
    LEFT JOIN designation_list d ON e.designation_id = d.id
    WHERE e.id = ?");
$stmt_fac->bind_param("i", $faculty_id);
$stmt_fac->execute();
$faculty = $stmt_fac->get_result()->fetch_assoc();

// Determine if faculty is a Department Head (designation_id = 2)
$is_dept_head = (intval($faculty['designation_id'] ?? 0) === 2);

// Get evaluator / "Noted by" info (Department Head)
$evaluator_name = 'N/A';
if (!empty($faculty['evaluator_id'])) {
    $evaluator = $conn->query("SELECT CONCAT(firstname, ' ', middlename, ' ', lastname) as name
        FROM evaluator_list WHERE id = {$faculty['evaluator_id']}")->fetch_assoc();
    if ($evaluator) {
        $evaluator_name = $evaluator['name'];
    }
}

// Get Dean's name (employee with designation_id = 1)
$dean_name = 'N/A';
$dean_q = $conn->query("SELECT CONCAT(e.firstname, ' ', e.middlename, ' ', e.lastname) as name
    FROM employee_list e
    WHERE e.designation_id = 1 LIMIT 1");
if ($dean_q) {
    $dean = $dean_q->fetch_assoc();
    if ($dean) $dean_name = $dean['name'];
}

// Get Department Head's name (employee with designation_id = 2), if faculty is not the dept head
$dept_head_name = 'N/A';
if (!$is_dept_head) {
    $dh_q = $conn->query("SELECT CONCAT(e.firstname, ' ', e.middlename, ' ', e.lastname) as name
        FROM employee_list e
        WHERE e.designation_id = 2 LIMIT 1");
    if ($dh_q) {
        $dh = $dh_q->fetch_assoc();
        if ($dh) $dept_head_name = $dh['name'];
    }
}

// Get target info
$target = null;
if ($target_id > 0) {
    $target = $conn->query("SELECT COALESCE(major_output, success_indicators) as name,
        category, mfo, success_indicators, targets_measures, timeliness, quality, efficiency,
        quality_scale, timeliness_scale, efficiency_scale,
        (SELECT GROUP_CONCAT(deadline ORDER BY deadline SEPARATOR '|') FROM target_deadlines WHERE target_id = $target_id) as deadlines
        FROM task_list WHERE id = $target_id")->fetch_assoc();
}

$has_timeliness = ($target && isset($target['timeliness']) && strtolower($target['timeliness']) === 'applicable');
$has_efficiency = ($target && isset($target['efficiency']) && strtolower($target['efficiency']) === 'applicable');
$has_quality = ($target && isset($target['quality']) && strtolower($target['quality']) === 'applicable');

// Convert literal \r\n (stored as text in DB) to real newlines for display
$fix_nl = function($s) { return str_replace(["\\r\\n", "\\n", "\\r"], "\n", $s); };

// Build dynamic legend sections from the target's scale definitions
$legend_sections = [];
if ($has_quality && !empty(trim($target['quality_scale'] ?? ''))) {
    $legend_sections[] = ['label' => 'Quality', 'text' => trim($fix_nl($target['quality_scale']))];
}
if ($has_timeliness && !empty(trim($target['timeliness_scale'] ?? ''))) {
    $legend_sections[] = ['label' => 'Timeliness', 'text' => trim($fix_nl($target['timeliness_scale']))];
}
if ($has_efficiency && !empty(trim($target['efficiency_scale'] ?? ''))) {
    $legend_sections[] = ['label' => 'Efficiency', 'text' => trim($fix_nl($target['efficiency_scale']))];
}

// ---- MOV rows (filtered by rating_period + faculty + optional target) ----
$where = "WHERE m.faculty_id = $faculty_id";
if (!empty($period)) {
    $where .= " AND m.rating_period = '$period'";
}
if ($target_id > 0) {
    $where .= " AND m.target_id = $target_id";
}
$mov_q = $conn->query("SELECT m.*,
        COALESCE(t.major_output, t.success_indicators) as target_name,
        t.category, t.mfo
    FROM mov_uploads m
    LEFT JOIN task_list t ON m.target_id = t.id
    $where
    ORDER BY m.date_submitted DESC");
$movs = $mov_q ? $mov_q->fetch_all(MYSQLI_ASSOC) : [];

// ---- Efficiency average for this target/period (powers the Efficiency column) ----
$eff_avg = null;
if ($target_id > 0) {
    $stmt_eff = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt
        FROM efficiency_attendance WHERE target_id = ? AND faculty_id = ? AND rating_period = ?");
    $stmt_eff->bind_param("iis", $target_id, $faculty_id, $period);
    $stmt_eff->execute();
    $eff_row = $stmt_eff->get_result()->fetch_assoc();
    if ($eff_row && $eff_row['cnt'] > 0) {
        $eff_avg = round(floatval($eff_row['avg_rating']), 2);
    }
}

// ---- Per-MOV computations (timeliness vs deadline) ----
function mov_timeliness($date_submitted, $deadline_ts) {
    if (empty($date_submitted)) return 1;            // No submission
    if ($deadline_ts === null) return null;          // No deadline -> not rated
    $days_diff = ($deadline_ts - strtotime($date_submitted)) / 86400;
    if ($days_diff > 0) return 5;                    // Before deadline
    if ($days_diff == 0) return 3;                   // On deadline
    return 2;                                        // Beyond deadline
}

function rating_pill($val) {
    if ($val === null) return '<span class="rating-na">—</span>';
    $cls = 'r' . $val;
    return '<span class="rating-pill ' . $cls . '">' . $val . '</span>';
}

$rows = [];
$total_timeliness = 0;
$timeliness_count = 0;
foreach ($movs as $m) {
    // Resolve the deadline: match the submitted month/year to a target deadline.
    $deadline_display = '—';
    $deadline_ts = null;
    $deadlines = !empty($target['deadlines']) ? explode('|', $target['deadlines']) : [];
    if (!empty($deadlines)) {
        $sub_month = date('n', strtotime($m['date_submitted']));
        $sub_year = date('Y', strtotime($m['date_submitted']));
        foreach ($deadlines as $dl) {
            if (!empty($dl) && date('n', strtotime($dl)) == $sub_month && date('Y', strtotime($dl)) == $sub_year) {
                $deadline_display = date('M d, Y', strtotime($dl));
                $deadline_ts = strtotime($dl);
                break;
            }
        }
        // No deadline matches the submission's month/year: leave as "—" rather
        // than fall back to an unrelated deadline (which would be misleading).
    }

    $date_display = !empty($m['date_submitted'])
        ? date('M d, Y', strtotime($m['date_submitted']))
        : '—';

    $timeliness_computed = mov_timeliness($m['date_submitted'], $deadline_ts);
    // Prefer stored manual override; fall back to computed value
    $timeliness = ($m['timeliness_rating'] !== null && $m['timeliness_rating'] !== '')
        ? intval($m['timeliness_rating'])
        : $timeliness_computed;
    if ($timeliness !== null) {
        $total_timeliness += $timeliness;
        $timeliness_count++;
    }

    $quality = ($m['quality_rating'] !== null && $m['quality_rating'] !== '')
        ? intval($m['quality_rating'])
        : null;

    $mov_attached = !empty($m['file_name'])
        ? $m['file_name']
        : (!empty($m['file_path']) ? basename($m['file_path']) : '—');

    $rows[] = [
        'mov_id'    => $m['id'],
        'title'     => $m['title'] ?: ($m['target_name'] ?? 'MOV'),
        'date'      => $date_display,
        'deadline'  => $deadline_display,
        'timeliness'=> $timeliness,
        'quality'   => $quality,
        'efficiency'=> $has_efficiency ? ($eff_avg !== null ? $eff_avg : null) : null,
        'mov'       => $mov_attached,
        'submitted' => !empty($m['file_path']) || !empty($m['file_name']),
        'eff_sub'   => !empty($m['efficiency_submitted']),
    ];
}

$avg_timeliness = $timeliness_count > 0 ? round($total_timeliness / $timeliness_count, 2) : null;
$avg_efficiency = ($has_efficiency && $eff_avg !== null) ? $eff_avg : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MOV Summary Report</title>
<style>
    @media print {
        .no-print { display: none !important; }
        .print-only { display: inline !important; }
        body { margin: 0.4in; }
    }
    .print-only { display: none; }
    body {
        font-family: Arial, "Helvetica Neue", sans-serif;
        margin: 20px;
        font-size: 11px;
        color: #000;
        background: #fff;
    }
    .sheet {
        max-width: 1000px;
        margin: 0 auto;
        border: 1px solid #000;
        padding: 18px 24px 24px;
    }
    .sheet-title {
        text-align: center;
        font-weight: bold;
        font-size: 15px;
        margin: 2px 0;
    }
    .sheet-period {
        text-align: center;
        font-size: 13px;
        margin-bottom: 10px;
    }
    .name-row {
        font-size: 12px;
        margin-bottom: 8px;
    }
    .name-row b { display: inline-block; min-width: 64px; }
    .name-row .kv { margin-right: 18px; }
    table.sum {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-top: 6px;
    }
    table.sum th, table.sum td {
        border: 1px solid #000;
        padding: 6px 8px;
        text-align: left;
        vertical-align: middle;
    }
    table.sum th {
        background: #007bff;
        color: #fff;
        font-weight: bold;
        text-align: center;
    }
    table.sum td.c, table.sum th.c { text-align: center; }
    .rating-pill {
        display: inline-block;
        min-width: 22px;
        padding: 2px 7px;
        border-radius: 3px;
        font-weight: bold;
        color: #fff;
        text-align: center;
    }
    .r5 { background: #28a745; } .r4 { background: #5cb85c; } .r3 { background: #ffc107; color: #000; }
    .r2 { background: #fd7e14; } .r1 { background: #dc3545; } .rating-na { color: #6c757d; font-weight: bold; }
    tr.total-row td { background: #e9ecef; font-weight: bold; }
    .certify { font-size: 11px; margin: 12px 0 4px; font-style: italic; }
    .legend {
        margin-top: 0;
        font-size: 10px;
        border: 1px solid #000;
        padding: 8px 10px;
        background: #f8f9fa;
        page-break-inside: avoid;
    }
    .legend h4 { margin: 0 0 4px; font-size: 11px; }
    .legend p { margin: 1px 0; }
    .sign {
        margin-top: 10px;
        font-size: 11px;
        page-break-inside: avoid;
    }
    .sign-block { margin-bottom: 24px; }
    .sign-block .label { font-size: 10px; }
    .sign-block .name { font-weight: bold; text-transform: uppercase; }
    .sign-block .role { font-size: 9px; color: #888; }
    .no-print { margin-top: 18px; text-align: center; }
    .no-print button { padding: 8px 22px; font-size: 13px; cursor: pointer; margin: 4px; }
</style>
</head>
<body>
<div class="sheet">
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:6px;">
        <img src="assets/dist/img/debesmscat_logo.png" alt="DEBESMSCAT Logo" style="width:70px; height:70px; flex-shrink:0;">
        <div style="text-align:center; flex:1;">
            <div style="font-size:11px;">Republic of the Philippines</div>
            <div style="font-weight:bold; font-size:13px;">DR. EMILIO B. ESPINOSA SR. MEMORIAL STATE COLLEGE OF AGRICULTURE AND TECHNOLOGY</div>
            <div style="font-size:10px;">DEBESMSCAT, Cabitan, Mandaon, Masbate</div>
        </div>
    </div>
    <hr style="border:none; border-top:1px solid #000; margin:4px 0 8px;">
    <div class="sheet-title">SUMMARY OF <?php echo htmlspecialchars(strtoupper($target ? $target['name'] : 'MEANS OF VERIFICATION (MOV) SUBMISSIONS')); ?></div>
    <div class="sheet-period"><?php echo htmlspecialchars($period ?: 'All Periods'); ?></div>

    <div class="name-row">
        <div><b>NAME:</b> <?php echo htmlspecialchars($faculty['name'] ?? 'N/A'); ?></div>
        <div><b>Position:</b> <?php echo htmlspecialchars($faculty['position'] ?? 'N/A'); ?></div>
        <div><b>Designation:</b> <?php echo htmlspecialchars($faculty['designation'] ?? 'N/A'); ?></div>
    </div>

    <table class="sum" id="mov_table">
        <thead>
            <tr>
                <th class="c" style="width:34px;">#</th>
                <th>Subject / MOV</th>
                <?php if ($has_timeliness): ?>
                <th class="c" style="width:110px;">Submission Date</th>
                <th class="c" style="width:100px;">Deadline</th>
                <th class="c" style="width:80px;">Timeliness</th>
                <?php endif; ?>
                <?php if ($has_quality): ?>
                <th class="c" style="width:80px;">Quality</th>
                <?php endif; ?>
                <?php if ($has_efficiency): ?>
                <th class="c" style="width:90px;">Submitted</th>
                <th class="c" style="width:60px;">Efficiency</th>
                <?php endif; ?>
                <th>MOV Attached</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
            <tr><td class="c" colspan="<?php
                $ncol = 2; // # + Subject
                if ($has_timeliness) $ncol += 3;
                if ($has_quality) $ncol += 1;
                if ($has_efficiency) $ncol += 2;
                $ncol += 1; // MOV Attached
                echo $ncol;
            ?>">No MOVs uploaded for this period<?php echo $target_id ? ' / target' : ''; ?>.</td></tr>
            <?php else: ?>
                <?php $n = 1; foreach ($rows as $r): ?>
                <tr>
                    <td class="c"><?php echo $n++; ?></td>
                    <td><?php echo htmlspecialchars($r['title']); ?></td>
                    <?php if ($has_timeliness): ?>
                    <td class="c"><?php echo htmlspecialchars($r['date']); ?></td>
                    <td class="c"><?php echo htmlspecialchars($r['deadline']); ?></td>
                    <td class="c">
                        <select class="form-control form-control-sm rating-select no-print" data-dim="timeliness" data-mov-id="<?php echo $r['mov_id']; ?>" style="width:60px; padding:2px; font-size:11px;">
                            <?php for ($v = 5; $v >= 1; $v--): ?>
                            <option value="<?php echo $v; ?>" <?php echo ($r['timeliness'] === $v) ? 'selected' : ''; ?>><?php echo $v; ?></option>
                            <?php endfor; ?>
                        </select>
                        <span class="print-only rating-val"><?php echo $r['timeliness'] !== null ? $r['timeliness'] : '—'; ?></span>
                    </td>
                    <?php endif; ?>
                    <?php if ($has_quality): ?>
                    <td class="c">
                        <select class="form-control form-control-sm rating-select no-print" data-dim="quality" data-mov-id="<?php echo $r['mov_id']; ?>" style="width:60px; padding:2px; font-size:11px;">
                            <option value="">—</option>
                            <?php for ($v = 5; $v >= 1; $v--): ?>
                            <option value="<?php echo $v; ?>" <?php echo ($r['quality'] === $v) ? 'selected' : ''; ?>><?php echo $v; ?></option>
                            <?php endfor; ?>
                        </select>
                        <span class="print-only rating-val"><?php echo $r['quality'] !== null ? $r['quality'] : '—'; ?></span>
                    </td>
                    <?php endif; ?>
                    <?php if ($has_efficiency): ?>
                    <td class="c">
                        <input type="checkbox" class="eff-check no-print" data-mov-id="<?php echo $r['mov_id']; ?>" <?php echo $r['eff_sub'] ? 'checked' : ''; ?>>
                        <span class="print-only eff-submitted"><?php echo $r['eff_sub'] ? 'Yes' : 'No'; ?></span>
                    </td>
                    <td class="c eff-cell">—</td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($r['mov']); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td class="c" colspan="<?php
                        $span = 2; // # + Subject
                        if ($has_timeliness) $span += 2; // Submission Date + Deadline (NOT Timeliness)
                        echo $span;
                    ?>">Rating</td>
                    <?php if ($has_timeliness): ?>
                    <td class="c" id="avg_timeliness"><?php echo $avg_timeliness !== null ? $avg_timeliness : '—'; ?></td>
                    <?php endif; ?>
                    <?php if ($has_quality): ?>
                    <td class="c" id="avg_quality">—</td>
                    <?php endif; ?>
                    <?php if ($has_efficiency): ?>
                    <td class="c" id="eff_count">0/0</td>
                    <td class="c" id="eff_pct">—</td>
                    <?php endif; ?>
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="certify">I certify the correctness of the above data.</div>

    <div style="display:flex; gap:14px; align-items:flex-start; margin-top:10px;">
    <div style="flex:1; min-width:0;">

    <div class="sign">
        <div class="sign-block">
            <span class="label">Prepared by:</span><br><br> <span class="name"><?php echo htmlspecialchars($faculty['name'] ?? 'N/A'); ?></span>
            <div class="role"><?php echo $is_dept_head ? 'Department Head' : 'Faculty Member'; ?></div>
        </div>
        <?php if (!$is_dept_head): ?>
        <div class="sign-block">
            <span class="label">Noted by:</span><br><br> <span class="name"><?php echo htmlspecialchars($dept_head_name); ?></span>
            <div class="role">Department Head</div>
        </div>
        <?php endif; ?>
        <div class="sign-block">
            <span class="label">Approved by:</span><br><br> <span class="name"><?php echo htmlspecialchars($dean_name); ?></span>
            <div class="role">Dean</div>
        </div>
    </div>

    </div>
    <div style="flex:0 0 300px; min-width:0;">
    <?php if (!empty($legend_sections)): ?>
    <div class="legend">
        <?php foreach ($legend_sections as $i => $sec): ?>
        <?php if ($i > 0): ?><hr style="margin:6px 0; border:none; border-top:1px solid #ccc;"><?php endif; ?>
        <h4><?php echo htmlspecialchars($sec['label']); ?></h4>
        <p><?php echo nl2br(htmlspecialchars($sec['text'])); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    </div>
    </div>
</div>

<div class="no-print">
    <button onclick="window.print()">🖨️ Print Report</button>
    <button onclick="window.close()">✕ Close</button>
</div>
<script>
(function() {
    // Timeliness + Quality averages
    function avgSelect(dim) {
        var selects = document.querySelectorAll('.rating-select[data-dim="' + dim + '"]');
        var sum = 0, cnt = 0;
        selects.forEach(function(s) {
            var v = parseInt(s.value, 10);
            if (!isNaN(v)) { sum += v; cnt++; }
        });
        return cnt > 0 ? (Math.round(sum / cnt * 100) / 100) : null;
    }

    function updateAverages() {
        var at = avgSelect('timeliness');
        var elT = document.getElementById('avg_timeliness');
        if (elT) elT.textContent = at !== null ? at : '—';

        var aq = avgSelect('quality');
        var elQ = document.getElementById('avg_quality');
        if (elQ) elQ.textContent = aq !== null ? aq : '—';
    }

    // Efficiency: count checked / total, compute percentage
    function updateEfficiency() {
        var checks = document.querySelectorAll('.eff-check');
        if (checks.length === 0) return;
        var checked = 0, total = checks.length;
        checks.forEach(function(c) {
            if (c.checked) checked++;
            // Update per-row eff cell
            var row = c.closest('tr');
            var cell = row.querySelector('.eff-cell');
            if (cell) cell.textContent = c.checked ? '✓' : '✗';
        });
        var pct = total > 0 ? Math.round(checked / total * 100) : 0;
        var elC = document.getElementById('eff_count');
        var elP = document.getElementById('eff_pct');
        if (elC) elC.textContent = checked + '/' + total;
        if (elP) elP.textContent = pct + '%';
    }

    // Wire up events — also sync print-only spans + persist to DB
    function persist(movId, dim, value) {
        var fd = new FormData();
        fd.append('mov_id', movId);
        fd.append('dim', dim);
        fd.append('value', value);
        fetch('ajax.php?action=save_mov_rating', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
            .then(function(r){ return r.text(); })
            .catch(function(e){ /* silent */ });
    }
    document.querySelectorAll('.rating-select').forEach(function(s) {
        s.addEventListener('change', function() {
            var val = s.value || '';
            var span = s.parentElement.querySelector('.rating-val');
            if (span) span.textContent = val === '' ? '—' : val;
            persist(s.dataset.movId, s.dataset.dim, val);
            updateAverages();
        });
    });
    document.querySelectorAll('.eff-check').forEach(function(c) {
        c.addEventListener('change', function() {
            var span = c.parentElement.querySelector('.eff-submitted');
            if (span) span.textContent = c.checked ? 'Yes' : 'No';
            persist(c.dataset.movId, 'efficiency', c.checked ? '1' : '0');
            updateEfficiency();
        });
    });

    // Initial computation
    updateAverages();
    updateEfficiency();
})();
</script>
</body>
</html>
