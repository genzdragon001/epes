<?php
include 'db_connect.php';
include 'includes/period_builder.php';

// Get faculty details
$faculty_id = $_SESSION['login_id'] ?? 0;
if ($faculty_id == 0) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong> You must be logged in to access this page.<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Login ID: " . ($_SESSION['login_id'] ?? 'NOT SET');
    echo "</div>";
    exit;
}
$faculty = $conn->query("SELECT e.position_id, e.designation_id, e.department_id
    FROM employee_list e
    WHERE e.id = $faculty_id")->fetch_assoc();

$position_id = $faculty['position_id'] ?? 0;
$designation_id = $faculty['designation_id'] ?? 0;
$is_cos = ($position_id == 19);

// Build MOV-specific period filter (uses m. alias for mov_uploads table)
if (!empty($period_codes)) {
    $in = implode("','", array_map([$conn, 'real_escape_string'], $period_codes));
    $mov_period_filter = " AND m.rating_period IN ('$in')";
} else {
    $mov_period_filter = " AND 0";
}

$period_label = $selected_period ? ($selected_period['semester'] . ' ' . $selected_period['year']) : 'No period set';
$sel_key = $selected_period ? epes_period_key($selected_period['semester'], $selected_period['year']) : '';

// Get percentage allocations
$allocations = [];
$alloc_qry = $conn->query("SELECT * FROM percentage_allocation
    WHERE position_id = $position_id
    AND (designation_id IS NULL OR designation_id = $designation_id)
    AND is_active = 1");
while ($row = $alloc_qry->fetch_assoc()) {
    $key = $row['category'];
    if ($row['sub_category']) {
        $key .= '_' . $row['sub_category'];
    }
    $allocations[$key] = floatval($row['percentage']);
}
// Normalize: map core_instruction -> core_instructions (plural) for consistency
if (isset($allocations['core_instruction']) && !isset($allocations['core_instructions'])) {
    $allocations['core_instructions'] = $allocations['core_instruction'];
}

// Build category filters
$cat_filters = [];
$has_strategic = isset($allocations['strategic']) && $allocations['strategic'] > 0;
if ($designation_id > 0) {
    $desig_qry = $conn->query("SELECT designation FROM designation_list WHERE id = $designation_id");
    if ($desig_qry && $desig_row = $desig_qry->fetch_assoc()) {
        if (stripos($desig_row['designation'], 'Head') !== false || stripos($desig_row['designation'], 'Director') !== false) {
            $has_strategic = true;
        }
    }
}
$has_instructions = isset($allocations['core_instructions']) && $allocations['core_instructions'] > 0;
$has_research = isset($allocations['core_research']) && $allocations['core_research'] > 0 && !$is_cos;
$has_extension = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && !$is_cos;
$has_support = isset($allocations['support']) && $allocations['support'] > 0;

if ($has_strategic) $cat_filters[] = "t.category = 'strategic'";
if ($has_instructions) $cat_filters[] = "(t.category = 'core' AND (t.sub_category IS NULL OR t.sub_category IN ('instructions','ter','instruction')))";
if ($has_research) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'research')";
if ($has_extension) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'extension')";
if ($has_support) $cat_filters[] = "t.category = 'support'";

$category_where = !empty($cat_filters) ? " AND (" . implode(" OR ", $cat_filters) . ")" : "";

// Get targets from task_list with MOV status breakdown from mov_uploads (period-scoped)
$target_query = "SELECT DISTINCT t.id,
    COALESCE(t.major_output, t.success_indicators) as target_display,
    t.major_output,
    t.success_indicators,
    t.targets_measures,
    t.category,
    t.sub_category,
    t.mfo,
    t.quality,
    t.timeliness,
    t.efficiency,
    t.quality_scale,
    t.timeliness_scale,
    t.efficiency_scale,
    (SELECT COUNT(*) FROM mov_uploads m WHERE m.target_id = t.id AND m.faculty_id = $faculty_id $mov_period_filter) as mov_count,
    (SELECT COUNT(*) FROM mov_uploads m WHERE m.target_id = t.id AND m.faculty_id = $faculty_id AND m.status = 'Verified' $mov_period_filter) as verified_count,
    (SELECT COUNT(*) FROM mov_uploads m WHERE m.target_id = t.id AND m.faculty_id = $faculty_id AND m.status = 'Pending' $mov_period_filter) as pending_count,
    (SELECT COUNT(*) FROM mov_uploads m WHERE m.target_id = t.id AND m.faculty_id = $faculty_id AND m.status = 'Rejected' $mov_period_filter) as rejected_count,
    (SELECT MAX(m.remarks) FROM mov_uploads m WHERE m.target_id = t.id AND m.faculty_id = $faculty_id AND m.status = 'Rejected' $mov_period_filter) as rejected_remarks
    FROM task_list t
    LEFT JOIN target_exemptions te ON t.id = te.task_id AND te.position_id = $position_id
    WHERE t.is_active = 1
    AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)
    AND " . task_designation_match($designation_id) . "
    AND te.id IS NULL
    $category_where
    ORDER BY t.category, t.sub_category, t.mfo";

$targets = $conn->query($target_query);

// Progress overview: count targets that have >=1 verified MOV this period
$total_targets = 0;
$verified_targets = 0;
$tmp = $conn->query("SELECT
    COUNT(DISTINCT t.id) as total,
    COUNT(DISTINCT CASE WHEN mv.verified_target = 1 THEN t.id END) as verified
    FROM task_list t
    LEFT JOIN target_exemptions te ON t.id = te.task_id AND te.position_id = $position_id
    LEFT JOIN (
        SELECT target_id, faculty_id, 1 as verified_target
        FROM mov_uploads m
        WHERE faculty_id = $faculty_id AND status = 'Verified' $mov_period_filter
        GROUP BY target_id, faculty_id
    ) mv ON mv.target_id = t.id AND mv.faculty_id = $faculty_id
    WHERE t.is_active = 1
    AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)
    AND " . task_designation_match($designation_id) . "
    AND te.id IS NULL
    $category_where");
if ($tmp && $row = $tmp->fetch_assoc()) {
    $total_targets = intval($row['total']);
    $verified_targets = intval($row['verified']);
}
$progress_pct = $total_targets > 0 ? round(($verified_targets / $total_targets) * 100) : 0;

// Category display labels + dot colors
$cat_meta = [
    'strategic' => ['label' => 'STRATEGIC FUNCTIONS', 'dot' => 'strategic'],
    'core'      => ['label' => 'CORE FUNCTIONS',      'dot' => 'core'],
    'support'   => ['label' => 'SUPPORT FUNCTIONS',   'dot' => 'support'],
];
?>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center flex-wrap">
                <h3 class="card-title mr-3 mb-0"><i class="fa fa-folder-open mr-1"></i> MOV Management</h3>
                <?php if (!empty($real_periods)): ?>
                <span class="badge badge-light p-2" style="font-size:0.85rem; background:#fff; color:#28a745;">
                    <i class="fa fa-calendar-alt mr-1"></i>
                    <?= htmlspecialchars($selected_period['semester']) ?> <?= htmlspecialchars($selected_period['year']) ?>
                    <?= !empty($selected_period['is_active']) ? '<span class="badge badge-success ml-1">Current</span>' : '' ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if (!empty($real_periods)): ?>
            <div class="card-tools ml-auto">
                <label class="mr-2 mb-0 text-muted" style="font-size:0.82rem;"><i class="fa fa-filter"></i> Period:</label>
                <select id="filter_period" class="form-control form-control-sm"
                        onchange="window.location.href='index.php?page=mov_management&period='+encodeURIComponent(this.value)"
                        style="width:auto; font-size:0.85rem; max-width:250px; display:inline-block;">
                    <?php foreach ($real_periods as $rp):
                        $pkey = epes_period_key($rp['semester'], $rp['year']);
                        $opt_label = $rp['semester'] . ' ' . $rp['year'] . (!empty($rp['is_active']) ? ' (Current)' : '');
                    ?>
                    <option value="<?= htmlspecialchars($pkey) ?>" <?= $pkey === $sel_key ? 'selected' : '' ?>><?= htmlspecialchars($opt_label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <div class="card-body">

            <!-- ===== PROGRESS OVERVIEW ===== -->
            <div class="row mb-3">
                <div class="col-md-7">
                    <div class="info-box bg-gradient-primary">
                        <span class="info-box-icon"><i class="fa fa-tasks"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">MOV Completion — <?= htmlspecialchars($period_label) ?></span>
                            <span class="info-box-number"><?= $verified_targets ?> of <?= $total_targets ?> targets verified</span>
                            <div class="progress mt-1">
                                <div class="progress-bar bg-success" style="width:<?= $progress_pct ?>%;"></div>
                            </div>
                            <small><?= $progress_pct ?>% complete for this period</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 d-flex flex-column justify-content-center">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                        </div>
                        <input type="text" id="target_search" class="form-control form-control-sm" placeholder="Search target...">
                    </div>
                    <small class="text-muted mt-1">Type to filter the list below.</small>
                </div>
            </div>

            <!-- ===== TABLE ===== -->
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered" id="target_mov_list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width:40px;">#</th>
                            <th>Target / Success Indicator</th>
                            <th class="text-center" style="width:170px;">MOVs</th>
                            <th class="text-center" style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="target_list_body">
                        <?php
                        $i = 1;
                        $current_category = '';
                        while ($row = $targets->fetch_assoc()):
                            if ($current_category != $row['category']) {
                                $meta = $cat_meta[$row['category']] ?? ['label' => strtoupper($row['category']) . ' FUNCTIONS', 'dot' => 'core'];
                                echo '<tr class="row-group"><td colspan="4"><span class="cat-dot ' . $meta['dot'] . '"></span>' . htmlspecialchars($meta['label']) . '</td></tr>';
                                $current_category = $row['category'];
                            }

                            $verified = intval($row['verified_count']);
                            $pending  = intval($row['pending_count']);
                            $rejected = intval($row['rejected_count']);
                            $total_mov = intval($row['mov_count']);
                            $remarks = $row['rejected_remarks'] ?? '';
                        ?>
                        <tr class="target-row" data-target-text="<?= htmlspecialchars(strtolower($row['target_display'])) ?>">
                            <td class="text-center font-weight-bold"><?php echo $i++; ?></td>
                            <td>
                                <span class="target-text"><?php echo htmlspecialchars($row['target_display']); ?></span>
                                <?php
                                $fix_nl = function($s) { return str_replace(["\\r\\n", "\\n", "\\r"], "\n", $s); };
                                if (!empty($row['targets_measures'])): ?>
                                <br><small class="text-muted"><i class="fa fa-check"></i> <?php echo nl2br(htmlspecialchars($fix_nl($row['targets_measures']))); ?></small>
                                <?php endif; ?>
                                <?php
                                $scale_parts = [];
                                if (($row['quality'] ?? '') == 'Applicable' && !empty(trim($row['quality_scale'] ?? ''))) {
                                    $scale_parts[] = '<span class="badge badge-quality">Q</span> ' . nl2br(htmlspecialchars(trim($fix_nl($row['quality_scale']))));
                                }
                                if (($row['timeliness'] ?? '') == 'Applicable' && !empty(trim($row['timeliness_scale'] ?? ''))) {
                                    $scale_parts[] = '<span class="badge badge-timeliness">T</span> ' . nl2br(htmlspecialchars(trim($fix_nl($row['timeliness_scale']))));
                                }
                                if (($row['efficiency'] ?? '') == 'Applicable' && !empty(trim($row['efficiency_scale'] ?? ''))) {
                                    $scale_parts[] = '<span class="badge badge-efficiency">E</span> ' . nl2br(htmlspecialchars(trim($fix_nl($row['efficiency_scale']))));
                                }
                                if ($scale_parts) {
                                    echo '<br><small class="text-muted rating-scale-inline">' . implode(' &nbsp; ', $scale_parts) . '</small>';
                                }
                                ?>
                            </td>
                            <td class="text-center align-middle">
                                <?php if ($total_mov == 0): ?>
                                    <span class="mov-none">— No MOV yet</span>
                                <?php else: ?>
                                    <?php if ($verified > 0): ?>
                                        <span class="mov-pill mov-verified"><i class="fa fa-check"></i> <?= $verified ?> Verified</span>
                                    <?php endif; ?>
                                    <?php if ($pending > 0): ?>
                                        <span class="mov-pill mov-pending"><i class="fa fa-clock"></i> <?= $pending ?> Pending</span>
                                    <?php endif; ?>
                                    <?php if ($rejected > 0): ?>
                                        <span class="mov-pill mov-rejected"><i class="fa fa-times"></i> <?= $rejected ?> Rejected</span>
                                        <?php if ($remarks): ?>
                                            <span class="muted d-block" style="font-size:.7rem;"><?= htmlspecialchars($remarks) ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-sm btn-primary btn-block-sm"
                                    onclick="uploadMOVForTarget(<?php echo $row['id']; ?>)"
                                    title="Upload MOV">
                                    <i class="fa fa-upload mr-1"></i> Upload MOV
                                </button>
                                <?php if ($total_mov > 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-block-sm"
                                    onclick="viewTargetMOVs(<?php echo $row['id']; ?>)"
                                    title="View MOVs">
                                    <i class="fa fa-list mr-1"></i> View
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info btn-block-sm"
                                    onclick="generateTargetSummary(<?php echo $row['id']; ?>)"
                                    title="Generate Summary">
                                    <i class="fa fa-file-alt mr-1"></i> Summary
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<style>
.cat-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:8px; vertical-align:middle; }
.cat-dot.strategic { background:#6f42c1; }
.cat-dot.core { background:#6c757d; }
.cat-dot.support { background:#17a2b8; }
.row-group td { background:#eef1f5; font-weight:600; color:#444; }
.target-text { font-weight:600; }
.mov-pill { display:inline-block; font-size:.78rem; padding:2px 8px; border-radius:12px; margin:1px; }
.mov-verified { background:#d4edda; color:#155724; }
.mov-pending { background:#fff3cd; color:#856404; }
.mov-rejected { background:#f8d7da; color:#721c24; }
.mov-none { color:#aaa; }
.btn-block-sm { display:block; width:100%; margin-bottom:4px; }
.target-row { transition: background-color 0.2s; }
.target-row:hover { background-color:#f8f9fa; }
.rating-scale-inline { display: block; margin-top: 2px; }
.rating-scale-inline .badge-quality,
.rating-scale-inline .badge-timeliness,
.rating-scale-inline .badge-efficiency { font-size: 0.6rem; font-weight: 700; padding: 1px 4px; border-radius: 3px; margin-right: 2px; vertical-align: middle; }
.rating-scale-inline .badge-quality { background: #28a745; color: #fff; }
.rating-scale-inline .badge-timeliness { background: #17a2b8; color: #fff; }
.rating-scale-inline .badge-efficiency { background: #6f42c1; color: #fff; }
</style>

<script>
$(document).ready(function(){
    // Search filter (client-side)
    $('#target_search').on('keyup', function(){
        var q = $(this).val().toLowerCase().trim();
        $('.target-row').each(function(){
            var text = $(this).data('target-text') || '';
            $(this).toggle(text.indexOf(q) > -1);
        });
        // hide empty group headers
        $('.row-group').each(function(){
            var $next = $(this).nextUntil('.row-group');
            var visible = $next.filter(':visible').length;
            $(this).toggle(visible > 0);
        });
    });
});

function uploadMOVForTarget(target_id, type = '') {
    var url = 'manage_mov.php?target_id=' + target_id + '&period=<?= htmlspecialchars($sel_key) ?>';
    if (type) { url += '&type=' + type; }
    uni_modal('<i class="fa fa-upload"></i> Upload MOV for Target', url, 'mid-large');
}

function viewTargetMOVs(target_id) {
    uni_modal('<i class="fa fa-list"></i> MOVs for Target', 'view_target_movs.php?target_id=' + target_id, 'large');
}

function generateTargetSummary(target_id) {
    var period = '<?= htmlspecialchars($sel_key) ?>';
    if (!period) {
        alert_toast('Please select a rating period first', 'warning');
        return;
    }
    window.open('generate_mov_summary.php?period=' + period + '&target_id=' + target_id, '_blank');
}
</script>
