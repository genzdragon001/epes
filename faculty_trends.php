<?php
/**
 * Unified Performance Trends
 *   - Faculty Comparison: X-axis = faculty (in a department), one line per rating
 *     period, Y-axis = weighted IPCR score. Supervisor comparison view.
 *   - Individual Trend:   X-axis = rating periods, lines = IPCR / DP / OPCR for
 *     one selected faculty.
 *
 * Access: Admin, Dean, Department Head. (Plain faculty redirected away.)
 *
 * NOTE: This file is included inside index.php (which already loads the local
 * Font Awesome 5 and Chart.js v2 via header.php / footer.php). It must NOT emit
 * its own <!DOCTYPE>/<head>/<body> or load any CDN assets — doing so corrupts
 * the surrounding AdminLTE document and (when the CDN is unreachable) turns
 * every icon on the page into a missing-glyph square.
 */
include 'db_connect.php';
require_once 'includes/period_builder.php';
require_once 'includes/rating_functions.php';

// ---- Auth ----
if (!isset($_SESSION['login_id'])) { header('location:login.php'); exit; }
$login_type        = $_SESSION['login_type'] ?? -1;
$login_id          = intval($_SESSION['login_id'] ?? 0);
$is_evaluator_flag = !empty($_SESSION['is_evaluator']);
$is_admin          = ($login_type == 2);

// Plain faculty (not an evaluator) may not view analytics
if ($login_type == 0 && !$is_evaluator_flag) { header('location:index.php'); exit; }

// ---- Role / department resolution ----
$eval_dept_id = 0;
$is_dean = false;
if ($is_evaluator_flag) {
    $eval_role = $_SESSION['evaluator_role'] ?? '';
    $is_dean = ($eval_role === 'dean');
    $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ?");
    $stmt->bind_param("i", $login_id);
    $stmt->execute();
    $stmt->bind_result($eval_dept_id);
    $stmt->fetch(); $stmt->close();
} elseif ($login_type == 1) {
    $stmt = $conn->prepare("SELECT type, department_id FROM evaluator_list WHERE id=?");
    $stmt->bind_param("i", $login_id);
    $stmt->execute();
    $stmt->bind_result($eval_type, $eval_dept_id, $eval_desig_id);
    $stmt->fetch(); $stmt->close();
    $is_dean = ($eval_type == 1);
}
$is_dept_head = !$is_dean && !$is_admin;

// ---- Departments (dean/admin selector) ----
$departments = [];
$dq = $conn->query("SELECT id, department FROM department_list ORDER BY department");
while ($dq && $d = $dq->fetch_assoc()) $departments[] = $d;

// Selected department (dept head locked to own)
if ($is_dept_head) {
    $sel_dept = (int)$eval_dept_id;
} else {
    $sel_dept = isset($_GET['dept']) ? intval($_GET['dept']) : (count($departments) ? (int)$departments[0]['id'] : 0);
}
if ($is_dept_head && $sel_dept != $eval_dept_id) $sel_dept = (int)$eval_dept_id;

// ---- Periods (ascending) ----
$raw_periods = [];
$rp_qry = $conn->query("SELECT * FROM rating_period ORDER BY year DESC, semester DESC");
while ($rp_qry && $r = $rp_qry->fetch_assoc()) $raw_periods[] = $r;

$period_map = [];
foreach ($raw_periods as $p) {
    $k = $p['semester'] . '|' . $p['year'];
    if (!isset($period_map[$k])) $period_map[$k] = ['semester' => $p['semester'], 'year' => $p['year']];
}
$all_periods = array_values($period_map);
usort($all_periods, function($a, $b) {
    if ($a['year'] != $b['year']) return $a['year'] <=> $b['year'];
    $order = ['1st Semester' => 1, '2nd Semester' => 2, 'Summer' => 3];
    return ($order[$a['semester']] ?? 9) <=> ($order[$b['semester']] ?? 9);
});

// Range for comparison view
$range = isset($_GET['range']) ? $_GET['range'] : '3';
$compare_periods = ($range === 'all') ? $all_periods : array_slice($all_periods, -max(1, intval($range)));

// View toggle
$view = isset($_GET['view']) ? $_GET['view'] : 'comparison';
if (!in_array($view, ['comparison', 'individual'])) $view = 'comparison';

// ---- Per-period code builder (mirrors period_builder logic) ----
function ft_codes_for($conn, $semester, $year, $raw) {
    $codes = [];
    $key = $semester . '|' . $year;
    foreach ($raw as $p) {
        if (($p['semester'] . '|' . $p['year']) === $key && !empty($p['code'])) $codes[] = $p['code'];
    }
    $codes[] = epes_short_code($semester, $year);
    $codes[] = $semester . ' ' . $year;
    $sem_compact = str_replace(' ', '', $semester);
    $like = $conn->real_escape_string($sem_compact . '-' . $year);
    $short = epes_short_code($semester, $year);
    foreach (['task_progress', 'ratings', 'mov_uploads'] as $tbl) {
        $q = $conn->query("SELECT DISTINCT rating_period FROM $tbl WHERE rating_period <> '' AND (rating_period LIKE '%$like%' OR rating_period LIKE '%$short%')");
        while ($q && $r = $q->fetch_assoc()) $codes[] = $r['rating_period'];
    }
    return array_values(array_unique(array_filter($codes)));
}
function ft_label($semester, $year) { return str_replace(' Semester', '', $semester) . ' ' . $year; }

// Solid + translucent palette pairs (Chart.js v2 has no 8-digit-hex fill support)
$palette      = ['#4361ee','#1abc9c','#f39c12','#9b59b6','#e74c3c','#16a085'];
$palette_fill = ['rgba(67,97,238,.13)','rgba(26,188,156,.13)','rgba(243,156,18,.13)','rgba(155,89,182,.13)','rgba(231,76,60,.13)','rgba(22,160,133,.13)'];

// ---- Faculty in selected department ----
$faculty = [];
if ($sel_dept > 0) {
    $fq = $conn->prepare("SELECT e.id, e.firstname, e.lastname, e.designation_id, e.position_id
                          FROM employee_list e WHERE e.department_id = ? AND e.id != ?
                          ORDER BY e.lastname, e.firstname");
    $fq->bind_param("ii", $sel_dept, $login_id);
    $fq->execute();
    $res = $fq->get_result();
    while ($row = $res->fetch_assoc()) $faculty[] = $row;
    $fq->close();
}

$dept_name = '';
if ($sel_dept > 0) {
    $dd = $conn->query("SELECT department FROM department_list WHERE id=$sel_dept LIMIT 1")->fetch_assoc();
    $dept_name = $dd['department'] ?? '';
}

function ft_adjClass($s) {
    if ($s === null) return '';
    if ($s >= 4.76) return 'b-out';
    if ($s >= 3.61) return 'b-vs';
    if ($s >= 2.61) return 'b-sat';
    if ($s >= 1.61) return 'b-uns';
    return 'b-poor';
}

// ===== Build COMPARISON data (faculty x period, IPCR) =====
$comp_series = [];   // period_label => [faculty_id => score|null]
$comp_datasets = [];
$comp_fac_labels = [];
$comp_dept_avg = [];
if ($view === 'comparison') {
    foreach ($compare_periods as $p) {
        $codes = ft_codes_for($conn, $p['semester'], $p['year'], $raw_periods);
        $in = implode("','", array_map([$conn, 'real_escape_string'], $codes));
        $pf = " AND rating_period IN ('$in')";
        $pl = ft_label($p['semester'], $p['year']);
        $comp_series[$pl] = [];
        foreach ($faculty as $f) {
            $s = computeWeightedRating($conn, (int)$f['id'], (int)$f['position_id'], (int)$f['designation_id'], '', $pf);
            $comp_series[$pl][(int)$f['id']] = ($s !== null && floatval($s) > 0) ? round(floatval($s), 2) : null;
        }
    }
    $comp_period_labels = array_keys($comp_series);
    $comp_fac_labels = array_map(fn($f) => $f['lastname'] . ', ' . $f['firstname'], $faculty);
    $i = 0;
    foreach ($comp_period_labels as $pl) {
        $data = [];
        foreach ($faculty as $f) { $v = $comp_series[$pl][(int)$f['id']]; $data[] = ($v === null) ? null : $v; }
        $comp_datasets[] = [
            'label' => $pl, 'data' => $data,
            'borderColor' => $palette[$i % count($palette)],
            'backgroundColor' => $palette_fill[$i % count($palette_fill)],
            'tension' => 0.3, 'pointRadius' => 5, 'pointHoverRadius' => 7, 'fill' => false, 'spanGaps' => true
        ];
        $i++;
    }
    // dept average (dashed)
    foreach ($comp_period_labels as $pl) {
        $vals = array_filter($comp_series[$pl], fn($v) => $v !== null);
        $comp_dept_avg[] = count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
    }
    $comp_datasets[] = [
        'label' => 'Dept Average', 'data' => $comp_dept_avg,
        'borderColor' => '#34465c', 'borderDash' => [6,4], 'backgroundColor' => 'transparent',
        'tension' => 0.3, 'pointRadius' => 4, 'pointHoverRadius' => 6, 'fill' => false, 'spanGaps' => true
    ];
}

// ===== Build INDIVIDUAL data (one faculty, IPCR/DP/OPCR across periods) =====
$ind_faculty_id = 0;
$ind_faculty = null;
$ind_ipcr = $ind_dp = $ind_opcr = [];   // aligned to all_periods
$ind_has_cascade = false;
if ($view === 'individual') {
    // default to requested / first faculty in dept
    $req_f = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;
    if ($req_f > 0 && !empty($faculty)) {
        foreach ($faculty as $f) { if ((int)$f['id'] === $req_f) { $ind_faculty = $f; break; } }
    }
    if (!$ind_faculty && !empty($faculty)) $ind_faculty = $faculty[0];
    if ($ind_faculty) {
        $ind_faculty_id = (int)$ind_faculty['id'];
        $fdept = (int)$sel_dept;

        // IPCR per period
        $ipcr_by_pid = [];
        foreach ($all_periods as $p) {
            $pid = $p['semester'] . '|' . $p['year'];
            $codes = ft_codes_for($conn, $p['semester'], $p['year'], $raw_periods);
            $in = implode("','", array_map([$conn, 'real_escape_string'], $codes));
            $pf = " AND rating_period IN ('$in')";
            $s = computeWeightedRating($conn, $ind_faculty_id, (int)$ind_faculty['position_id'], (int)$ind_faculty['designation_id'], '', $pf);
            $ipcr_by_pid[$pid] = ($s !== null && floatval($s) > 0) ? round(floatval($s), 2) : null;
        }

        // DP per period (cascading_ratings level='DP', department=fdept)
        $dp_by_pid = [];
        if ($fdept > 0) {
            $sr = $conn->query("SELECT cr.source_period_id, rp.semester, rp.year, cr.overall_rating
                                FROM cascading_ratings cr INNER JOIN rating_period rp ON rp.id = cr.source_period_id
                                WHERE cr.level='DP' AND cr.department_id=$fdept
                                ORDER BY rp.year ASC, rp.semester ASC");
            while ($sr && $row = $sr->fetch_assoc()) {
                $dp_by_pid[$row['semester'] . '|' . $row['year']] = (float)$row['overall_rating'];
                $ind_has_cascade = true;
            }
        }
        // OPCR per period (level='OPCR', department=0)
        $opcr_by_pid = [];
        $sr2 = $conn->query("SELECT cr.source_period_id, rp.semester, rp.year, cr.overall_rating
                             FROM cascading_ratings cr INNER JOIN rating_period rp ON rp.id = cr.source_period_id
                             WHERE cr.level='OPCR' AND cr.department_id=0
                             ORDER BY rp.year ASC, rp.semester ASC");
        while ($sr2 && $row = $sr2->fetch_assoc()) {
            $opcr_by_pid[$row['semester'] . '|' . $row['year']] = (float)$row['overall_rating'];
            $ind_has_cascade = true;
        }

        // align to all_periods
        foreach ($all_periods as $p) {
            $pid = $p['semester'] . '|' . $p['year'];
            $ind_ipcr[]  = $ipcr_by_pid[$pid] ?? null;
            $ind_dp[]    = $dp_by_pid[$pid] ?? null;
            $ind_opcr[]  = $opcr_by_pid[$pid] ?? null;
        }
    }
}
$ind_labels = array_map(fn($p) => ft_label($p['semester'], $p['year']), $all_periods);
?>
<div class="ft-page">
  <h1 class="page-title"><i class="fas fa-chart-line" style="color:var(--epes-blue);"></i> Performance Trends</h1>
  <p class="page-sub">Compare faculty IPCR ratings across rating periods — as a department-wide comparison or a single-faculty trend.</p>

  <div class="filterbar">
    <?php if (!$is_dept_head): ?>
    <div>
      <label>Department</label>
      <select id="deptSel" onchange="applyFilters()">
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($d['id'] == $sel_dept) ? 'selected' : '' ?>><?= htmlspecialchars($d['department']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <div class="seg" id="viewSeg">
      <button class="<?= $view=='comparison'?'active':'' ?>" onclick="setView('comparison')"><i class="fas fa-users"></i> Faculty Comparison</button>
      <button class="<?= $view=='individual'?'active':'' ?>" onclick="setView('individual')"><i class="fas fa-user"></i> Individual Trend</button>
    </div>

    <?php if ($view === 'comparison'): ?>
    <div>
      <label>Periods</label>
      <select id="rangeSel" onchange="applyFilters()">
        <option value="3" <?= $range=='3'?'selected':'' ?>>Last 3 Periods</option>
        <option value="4" <?= $range=='4'?'selected':'' ?>>Last 4 Periods</option>
        <option value="all" <?= $range=='all'?'selected':'' ?>>All Periods</option>
      </select>
    </div>
    <?php endif; ?>

    <?php if ($view === 'individual' && !empty($faculty)): ?>
    <div>
      <label>Faculty</label>
      <select id="facSel" onchange="applyFilters()">
        <?php foreach ($faculty as $f): ?>
          <option value="<?= $f['id'] ?>" <?= ((int)$f['id'] == $ind_faculty_id) ? 'selected' : '' ?>><?= htmlspecialchars($f['lastname'] . ', ' . $f['firstname']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <div class="spacer"></div>
    <span class="hint"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($dept_name) ?> · <?= $is_dept_head ? 'Department Head' : 'Supervisor' ?> view</span>
  </div>

  <?php if (empty($faculty)): ?>
    <div class="card"><div class="card-body"><div class="empty">No faculty found in this department.</div></div></div>
  <?php elseif ($view === 'comparison'): ?>
  <!-- ============ COMPARISON VIEW ============ -->
  <div class="card">
    <div class="card-head">
      <div class="ic"><i class="fas fa-users"></i></div>
      <div>
        <h4>IPCR Rating by Faculty &amp; Rating Period</h4>
        <small><?= htmlspecialchars($dept_name) ?> · <?= count($compare_periods) ?> period(s) compared</small>
      </div>
    </div>
    <div class="card-body">
      <div class="chart-scroll">
        <div class="chart-inner" id="chartInner"><canvas id="trendChart"></canvas></div>
      </div>
      <div class="chips" id="chartChips"></div>
      <div class="bands">
        <span><b>Outstanding</b> 4.76–5.00</span>
        <span><b>Very Satisfactory</b> 3.61–4.75</span>
        <span><b>Satisfactory</b> 2.61–3.60</span>
        <span><b>Unsatisfactory</b> 1.61–2.60</span>
        <span><b>Poor</b> 1.00–1.60</span>
      </div>
      <p class="footnote">Scores are weighted IPCR ratings (Strategic + Core + Support) per faculty per period via <code>computeWeightedRating()</code>. A gap means no rated tasks were submitted that period.</p>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="ic" style="background:rgba(26,188,156,.12);color:var(--epes-teal);"><i class="fas fa-table"></i></div>
      <h4>Detailed Scores</h4>
    </div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
      <table>
        <thead><tr>
          <th style="text-align:left;">Faculty</th>
          <?php foreach ($comp_period_labels as $pl): ?><th><?= htmlspecialchars($pl) ?></th><?php endforeach; ?>
          <th>Avg</th><th>Trend</th>
        </tr></thead>
        <tbody>
        <?php foreach ($faculty as $f):
          $fid = (int)$f['id'];
          $scores = array_map(fn($pl) => $comp_series[$pl][$fid], $comp_period_labels);
          $valid = array_filter($scores, fn($v) => $v !== null);
          $avg = count($valid) ? round(array_sum($valid)/count($valid), 2) : null;
          $first = $last = null;
          foreach ($scores as $s) { if ($s !== null && $first === null) $first = $s; }
          foreach (array_reverse($scores) as $s) { if ($s !== null && $last === null) $last = $s; }
          $trend = '—'; $tcol = '#bbb';
          if ($first !== null && $last !== null) {
            $d = $last - $first;
            if ($d > 0.05) { $trend='▲ up'; $tcol='#27ae60'; }
            elseif ($d < -0.05) { $trend='▼ down'; $tcol='#e74c3c'; }
            else { $trend='▬ flat'; $tcol='#7a8aa3'; }
          }
        ?>
          <tr>
            <td class="name"><?= htmlspecialchars($f['lastname'] . ', ' . $f['firstname']) ?></td>
            <?php foreach ($scores as $s): ?>
              <td><span class="badge <?= ft_adjClass($s) ?>"><?= $s === null ? '—' : number_format($s, 2) ?></span></td>
            <?php endforeach; ?>
            <td><b><?= $avg === null ? '—' : number_format($avg, 2) ?></b></td>
            <td style="color:<?= $tcol ?>;font-weight:700;"><?= $trend ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php else: ?>
  <!-- ============ INDIVIDUAL VIEW ============ -->
  <div class="card">
    <div class="card-head">
      <div class="ic"><i class="fas fa-user"></i></div>
      <div>
        <h4>Trend Analysis: <?= $ind_faculty ? htmlspecialchars($ind_faculty['lastname'] . ', ' . $ind_faculty['firstname']) : '' ?></h4>
        <small><?= htmlspecialchars($dept_name) ?></small>
      </div>
    </div>
    <div class="card-body">
      <?php if (!$ind_faculty): ?>
        <div class="empty">Select a faculty member.</div>
      <?php elseif (empty(array_filter($ind_ipcr, fn($v)=>$v!==null)) && !$ind_has_cascade): ?>
        <div class="alert alert-warning">No ratings available yet. Run the cascade from the Rating Periods page to populate DP/OPCR, and ensure this faculty has submitted/rated tasks.</div>
      <?php else: ?>
      <div class="chart-scroll">
        <div class="chart-inner" style="min-width:600px;"><canvas id="trendChart"></canvas></div>
      </div>
      <p class="footnote">
        <strong>IPCR</strong>: this faculty's weighted rating.
        <strong>DP</strong>: department average (cascaded).
        <strong>OPCR</strong>: office average (cascaded).
      </p>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($ind_faculty && ($ind_has_cascade || !empty(array_filter($ind_ipcr, fn($v)=>$v!==null)))): ?>
  <div class="card">
    <div class="card-head">
      <div class="ic" style="background:rgba(26,188,156,.12);color:var(--epes-teal);"><i class="fas fa-table"></i></div>
      <h4>Detailed Scores</h4>
    </div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
      <table>
        <thead><tr>
          <th style="text-align:left;">Period</th>
          <th>IPCR</th><th>DP</th><th>OPCR</th>
          <th>IPCR Rating</th><th>DP Rating</th><th>OPCR Rating</th>
        </tr></thead>
        <tbody>
        <?php foreach ($ind_labels as $i => $lab):
          $ip = $ind_ipcr[$i] ?? null; $dp = $ind_dp[$i] ?? null; $op = $ind_opcr[$i] ?? null;
          $ip_str = $ip !== null ? number_format($ip,2) : '—';
          $dp_str = $dp !== null ? number_format($dp,2) : '—';
          $op_str = $op !== null ? number_format($op,2) : '—';
        ?>
          <tr>
            <td class="name"><strong><?= htmlspecialchars($lab) ?></strong></td>
            <td class="badge <?= ft_adjClass($ip) ?>"><?= $ip_str ?></td>
            <td class="badge <?= ft_adjClass($dp) ?>"><?= $dp_str ?></td>
            <td class="badge <?= ft_adjClass($op) ?>"><?= $op_str ?></td>
            <td><?= $ip !== null ? getAdjectivalRating($ip) : '' ?></td>
            <td><?= $dp !== null ? getAdjectivalRating($dp) : '' ?></td>
            <td><?= $op !== null ? getAdjectivalRating($op) : '' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<style>
  .ft-page *{box-sizing:border-box;}
  .ft-page{
    --epes-blue:#4361ee; --epes-teal:#1abc9c; --epes-green:#27ae60;
    --epes-amber:#f39c12; --epes-red:#e74c3c; --epes-dark:#1a1a2e;
    --epes-muted:#7a8aa3; --epes-bg:#f4f6f9; --epes-card:#ffffff;
    --epes-border:#e6eaf0;
    font-family:'Source Sans Pro',Arial,sans-serif;color:#2c3e50;
  }
  .ft-page .page-title{font-size:1.5rem;font-weight:700;color:var(--epes-dark);margin:0 0 2px;}
  .ft-page .page-sub{color:var(--epes-muted);font-size:.9rem;margin:0 0 14px;}

  .ft-page .filterbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;background:var(--epes-card);
    border:1px solid var(--epes-border);border-radius:10px;padding:12px 14px;margin-bottom:16px;}
  .ft-page .filterbar label{font-size:.78rem;color:var(--epes-muted);font-weight:600;margin-right:2px;}
  .ft-page .filterbar select{border:1px solid var(--epes-border);border-radius:8px;padding:7px 10px;font-size:.82rem;background:#fff;}
  .ft-page .seg{display:flex;gap:6px;background:#eef1f6;border-radius:8px;padding:4px;}
  .ft-page .seg button{border:1px solid transparent;background:transparent;padding:6px 12px;border-radius:6px;font-size:.82rem;font-weight:600;color:#5b6b85;cursor:pointer;}
  .ft-page .seg button:hover{background:#e3e8f0;}
  .ft-page .seg button.active{background:var(--epes-blue);color:#fff;}
  .ft-page .spacer{flex:1;}
  .ft-page .hint{font-size:.78rem;color:var(--epes-muted);}

  .ft-page .card{background:var(--epes-card);border:1px solid var(--epes-border);border-radius:12px;
    box-shadow:0 1px 4px rgba(20,30,60,.06);overflow:hidden;margin-bottom:16px;}
  .ft-page .card-head{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--epes-border);flex-wrap:wrap;}
  .ft-page .card-head .ic{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;
    background:rgba(67,97,238,.12);color:var(--epes-blue);}
  .ft-page .card-head h4{margin:0;font-size:1rem;font-weight:700;color:var(--epes-dark);}
  .ft-page .card-head small{color:var(--epes-muted);font-weight:500;}
  .ft-page .card-body{padding:16px;}

  .ft-page .chart-scroll{overflow-x:auto;}
  .ft-page .chart-inner{position:relative;height:420px;min-width:600px;}
  .ft-page .chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;}
  .ft-page .chip{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;color:#46556b;
    background:#f3f5f9;border:1px solid var(--epes-border);border-radius:20px;padding:4px 10px;}
  .ft-page .chip .dot{width:10px;height:10px;border-radius:50%;}
  .ft-page .chip .dash{width:18px;height:0;border-top:2px dashed #34465c;}

  .ft-page .bands{display:flex;flex-wrap:wrap;gap:14px;margin-top:10px;font-size:.78rem;color:var(--epes-muted);}
  .ft-page .bands span b{color:#34465c;}

  .ft-page table{width:100%;border-collapse:collapse;font-size:.82rem;}
  .ft-page th,.ft-page td{padding:9px 10px;text-align:center;border-bottom:1px solid var(--epes-border);}
  .ft-page th{background:var(--epes-dark);color:#fff;font-weight:600;}
  .ft-page tbody tr:nth-child(even){background:#fafbfd;}
  .ft-page td.name{text-align:left;font-weight:600;color:#34465c;white-space:nowrap;}
  .ft-page .badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:.72rem;font-weight:700;color:#fff;min-width:46px;}
  .ft-page .b-out{background:var(--epes-green);} .ft-page .b-vs{background:#2ecc71;} .ft-page .b-sat{background:var(--epes-blue);}
  .ft-page .b-uns{background:var(--epes-amber);} .ft-page .b-poor{background:var(--epes-red);}
  .ft-page .footnote{font-size:.76rem;color:var(--epes-muted);margin-top:8px;line-height:1.5;}
  .ft-page .empty{padding:30px;text-align:center;color:var(--epes-muted);}
</style>

<?php if (!empty($faculty)): ?>
<script>
/* Chart init is deferred to DOM-ready so the local Chart.js (loaded by footer.php) exists. */
function setView(v){ document.getElementById('viewSeg').querySelectorAll('button').forEach(b=>b.classList.remove('active')); applyFilters(v); }
function applyFilters(forceView){
  var dept = document.getElementById('deptSel') ? document.getElementById('deptSel').value : '';
  var range = document.getElementById('rangeSel') ? document.getElementById('rangeSel').value : '<?= $range ?>';
  var fac = document.getElementById('facSel') ? document.getElementById('facSel').value : '';
  var v = forceView || '<?= $view ?>';
  var url = 'index.php?page=faculty_trends&view=' + encodeURIComponent(v) + '&range=' + encodeURIComponent(range);
  if (dept) url += '&dept=' + encodeURIComponent(dept);
  if (fac) url += '&faculty_id=' + encodeURIComponent(fac);
  window.location.href = url;
}

jQuery(function($){
  var facLabels = <?= json_encode($comp_fac_labels) ?>;
  var datasets  = <?= json_encode($comp_datasets) ?>;
  var palette   = ['#4361ee','#1abc9c','#f39c12','#9b59b6','#e74c3c','#16a085'];
  var periodLabels = <?= json_encode($comp_period_labels ?? []) ?>;

  <?php if ($view === 'comparison'): ?>
  // widen chart if many faculty
  document.getElementById('chartInner').style.minWidth = Math.max(600, facLabels.length * 78) + 'px';
  var ctx = document.getElementById('trendChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: { labels: facLabels, datasets: datasets },
    options: {
      responsive: true, maintainAspectRatio: false,
      hover: { mode: 'index', intersect: false },
      tooltips: { mode: 'index', intersect: false,
        callbacks: { label: function(c){ return ' ' + c.dataset.label + ': ' + (c.yLabel == null ? '—' : parseFloat(c.yLabel).toFixed(2)); } } },
      legend: { display: false },
      scales: {
        yAxes: [{ ticks: { min: 0, max: 5 }, scaleLabel: { display: true, labelString: 'IPCR Score (0–5)' }, gridLines: { color: '#eef1f6' } }],
        xAxes: [{ scaleLabel: { display: true, labelString: 'Faculty' }, gridLines: { display: false },
                 ticks: { maxRotation: 60, minRotation: 45, autoSkip: false, fontSize: 11 } }]
      }
    }
  });
  var chips = document.getElementById('chartChips');
  periodLabels.forEach(function(p, i){
    var c = document.createElement('span'); c.className = 'chip';
    c.innerHTML = '<span class="dot" style="background:' + palette[i % palette.length] + '"></span>' + p;
    chips.appendChild(c);
  });
  var avg = document.createElement('span'); avg.className = 'chip';
  avg.innerHTML = '<span class="dash"></span>Dept Average';
  chips.appendChild(avg);

  <?php else: ?>
  // Individual: IPCR / DP / OPCR across periods
  var indLabels = <?= json_encode($ind_labels) ?>;
  var indData = [
    { label:'IPCR', data:<?= json_encode($ind_ipcr) ?>, borderColor:'#1a5276', backgroundColor:'rgba(26,82,118,.1)', tension:.3, pointRadius:5, pointHoverRadius:7, fill:false },
    { label:'DP',   data:<?= json_encode($ind_dp) ?>,   borderColor:'#117a65', backgroundColor:'rgba(17,122,101,.1)', tension:.3, pointRadius:5, pointHoverRadius:7, fill:false },
    { label:'OPCR', data:<?= json_encode($ind_opcr) ?>, borderColor:'#a04000', backgroundColor:'rgba(160,64,0,.1)', tension:.3, pointRadius:5, pointHoverRadius:7, fill:false }
  ];
  var ctxI = document.getElementById('trendChart').getContext('2d');
  new Chart(ctxI, {
    type: 'line',
    data: { labels: indLabels, datasets: indData },
    options: {
      responsive: true, maintainAspectRatio: false,
      hover: { mode: 'index', intersect: false },
      tooltips: { mode: 'index', intersect: false,
        callbacks: { label: function(c){ return ' ' + c.dataset.label + ': ' + (c.yLabel == null ? '—' : parseFloat(c.yLabel).toFixed(2)); } } },
      legend: { position: 'top' },
      scales: {
        yAxes: [{ ticks: { min: 0, max: 5 }, scaleLabel: { display: true, labelString: 'Score' }, gridLines: { color: '#eef1f6' } }],
        xAxes: [{ scaleLabel: { display: true, labelString: 'Rating Period' }, gridLines: { display: false } }]
      }
    }
  });
  <?php endif; ?>
});
</script>
<?php endif; ?>
