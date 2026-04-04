<?php 
include 'db_connect.php'; 

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

// Get targets from task_list with MOV counts from mov_uploads
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
    (SELECT COUNT(*) FROM mov_uploads m WHERE m.target_id = t.id AND m.faculty_id = $faculty_id) as mov_count
    FROM task_list t
    LEFT JOIN target_exemptions te ON t.id = te.task_id AND te.position_id = $position_id
    WHERE t.is_active = 1
    AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)
    AND (t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = $designation_id)
    AND te.id IS NULL
    $category_where
    ORDER BY t.category, t.sub_category, t.mfo";

$targets = $conn->query($target_query);
?>

<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-folder-open"></i> MOV Management by Target</h5>
            
        </div>
        <div class="card-body">
            <!-- Faculty Info Badge -->
            
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filter_period">Filter by Rating Period</label>
                    <select class="form-control" id="filter_period" onchange="loadTargetMOVs()">
                        <?php
                        $periods = $conn->query("SELECT id, semester, year FROM rating_period ORDER BY id DESC");
                        $first = true;
                        while ($p = $periods->fetch_assoc()) {
                            $period_value = $p['semester'] . ' ' . $p['year'];
                            $selected = $first ? 'selected' : '';
                            echo "<option value='{$period_value}' {$selected}>{$period_value}</option>";
                            $first = false;
                        }
                        ?>
                    </select>
                </div>
                
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered" id="target_mov_list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 10px;">#</th>
                            <th style="width: 10%;">Category</th>
                            <th style="width: 50%;">Target / Success Indicator</th>
                            <th class="text-center" style="width: 10%;">MOVs</th>
                            <th class="text-center" style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="target_list_body">
                        <?php 
                        $i = 1;
                        $current_category = '';
                        while ($row = $targets->fetch_assoc()): 
                            if ($current_category != $row['category']) {
                                echo '<tr class="table-active"><td colspan="5" class="font-weight-bold text-primary"><i class="fa fa-folder"></i> ' . strtoupper($row['category']) . ' FUNCTION</td></tr>';
                                $current_category = $row['category'];
                            }
                            
                            $mov_count = intval($row['mov_count']);
                            $badge_class = $mov_count > 0 ? 'badge-success' : 'badge-secondary';
                            $target_display = !empty($row['major_output']) ? $row['major_output'] : $row['success_indicators'];
                            if (strlen($target_display) > 80) {
                                $target_display = substr($target_display, 0, 80) . '...';
                            }
                        ?>
                        <tr class="target-row">
                            <td class="text-center font-weight-bold"><?php echo $i++; ?></td>
                            <td><span class="badge badge-info"><?php echo strtoupper($row['category']); ?></span></td>
                            <td>
                                <strong>
                                <?php echo htmlspecialchars($target_display); ?></strong>
                                <?php if (!empty($row['targets_measures'])): ?>
                                <br><small class="text-muted"><i class="fa fa-check"></i> <?php echo nl2br(htmlspecialchars($row['targets_measures'])); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="mov-count" style="font-size: 18px; font-weight: bold; color: #007bff;"><?php echo $mov_count; ?></span>
                                <br><small class="badge <?php echo $badge_class; ?>"><?php echo $mov_count; ?> MOV<?php echo $mov_count !== 1 ? 's' : ''; ?></small>
                            </td>
                            <td class="text-center">
                                <?php 
                                $has_timeliness = (isset($row['timeliness']) && strtolower($row['timeliness']) === 'applicable');
                                $has_quality = (isset($row['quality']) && strtolower($row['quality']) === 'applicable');
                                $has_efficiency = (isset($row['efficiency']) && strtolower($row['efficiency']) === 'applicable');
                                ?>
                                <div class="btn-group-vertical" style="min-width: 120px;">
                                    <?php if ($has_timeliness): ?>
                                    <button type="button" class="btn btn-sm btn-primary mb-1" 
                                        onclick="uploadMOVForTarget(<?php echo $row['id']; ?>, 'timeliness')" 
                                        title="Add Timeliness MOV">
                                        <i class="fa fa-clock"></i> Timeliness
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($has_quality): ?>
                                    <button type="button" class="btn btn-sm btn-success mb-1" 
                                        onclick="uploadMOVForTarget(<?php echo $row['id']; ?>, 'quality')" 
                                        title="Add Quality MOV">
                                        <i class="fa fa-star"></i> Quality
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($has_efficiency): ?>
                                    <button type="button" class="btn btn-sm btn-info mb-1" 
                                        onclick="uploadMOVForTarget(<?php echo $row['id']; ?>, 'efficiency')" 
                                        title="Add Efficiency MOV">
                                        <i class="fa fa-chart-line"></i> Efficiency
                                    </button>
                                    <?php endif; ?>
                                    <?php if (!$has_timeliness && !$has_quality && !$has_efficiency): ?>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="uploadMOVForTarget(<?php echo $row['id']; ?>)" 
                                        title="Add MOV">
                                        <i class="fa fa-plus"></i> Add MOV
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($mov_count > 0): ?>
                                <hr style="margin: 5px 0;">
                                <button type="button" class="btn btn-sm btn-secondary mb-1" 
                                    onclick="viewTargetMOVs(<?php echo $row['id']; ?>)" 
                                    title="View MOVs">
                                    <i class="fa fa-list"></i> View
                                </button>
                                <?php if (!($has_quality && !$has_timeliness && !$has_efficiency)): ?>
                                <button type="button" class="btn btn-sm btn-info mb-1" 
                                    onclick="generateTargetSummary(<?php echo $row['id']; ?>)" 
                                    title="Generate Summary">
                                    <i class="fa fa-file-alt"></i> Summary
                                </button>
                                <?php endif; ?>
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
.card-header { 
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
    color: white; 
}
.card-title { margin: 0; font-weight: 600; }
.table th { font-weight: 600; }
.badge-pending { background-color: #ffc107; color: #000; }
.badge-verified { background-color: #28a745; color: #fff; }
.badge-rejected { background-color: #dc3545; color: #fff; }
.alert-info { 
    border-left: 4px solid #17a2b8;
    background-color: #e8f4f8;
}
.target-row { cursor: pointer; transition: background-color 0.2s; }
.target-row:hover { background-color: #f8f9fa; }
.mov-count { 
    font-size: 18px; 
    font-weight: bold;
    color: #007bff;
}
</style>

<script>
$(document).ready(function(){
    // Table is already loaded from PHP
});

function loadTargetMOVs() {
    var period = $('#filter_period').val();
    var category = $('#filter_category').val();
    
    // Reload page with filters
    var url = 'index.php?page=mov_management';
    if (period) url += '&period=' + period;
    if (category) url += '&category=' + category;
    window.location.href = url;
}

function uploadMOVForTarget(target_id, type = '') {
    var url = 'manage_mov.php?target_id=' + target_id;
    if (type) {
        url += '&type=' + type;
    }
    uni_modal('<i class="fa fa-upload"></i> Upload MOV for Target', url, 'mid-large');
}

function viewTargetMOVs(target_id) {
    uni_modal('<i class="fa fa-list"></i> MOVs for Target', 'view_target_movs.php?target_id=' + target_id, 'large');
}

function generateTargetSummary(target_id) {
    var period = $('#filter_period').val();
    if (!period) {
        alert_toast('Please select a rating period first', 'warning');
        $('#filter_period').focus();
        return;
    }
    window.open('generate_mov_summary.php?period=' + period + '&target_id=' + target_id, '_blank');
}

function generateAllSummary() {
    var period = $('#filter_period').val();
    if (!period) {
        alert_toast('Please select a rating period first', 'warning');
        $('#filter_period').focus();
        return;
    }
    window.open('generate_mov_summary.php?period=' + period, '_blank');
}
</script>
