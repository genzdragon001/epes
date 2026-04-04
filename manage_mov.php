<?php
session_start();
include 'db_connect.php';

$faculty_id = $_SESSION['login_id'] ?? 0;
if ($faculty_id == 0) {
    echo "Error: User not logged in";
    exit;
}
$preselect_target_id = isset($_GET['target_id']) ? intval($_GET['target_id']) : 0;
$mov_type = isset($_GET['type']) ? $_GET['type'] : '';

// Get faculty details
$faculty = $conn->query("SELECT e.position_id, e.designation_id, e.department_id,
    CONCAT(e.lastname, ', ', e.firstname, ' ', e.middlename) as name
    FROM employee_list e 
    WHERE e.id = $faculty_id")->fetch_assoc();

$position_id = $faculty['position_id'] ?? 0;
$designation_id = $faculty['designation_id'] ?? 0;

// Get targets assigned to this faculty - SAME LOGIC as target_list.php
// First get percentage allocations for this faculty
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

// Build category filters based on allocations (same as target_list.php)
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
// COS faculty (position 19) should NOT have research and extension
$is_cos = ($position_id == 19);
$has_research = isset($allocations['core_research']) && $allocations['core_research'] > 0 && !$is_cos;
$has_extension = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && !$is_cos;
$has_support = isset($allocations['support']) && $allocations['support'] > 0;

if ($has_strategic) $cat_filters[] = "t.category = 'strategic'";
if ($has_instructions) $cat_filters[] = "(t.category = 'core' AND (t.sub_category IS NULL OR t.sub_category IN ('instructions','ter','instruction')))";
if ($has_research) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'research')";
if ($has_extension) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'extension')";
if ($has_support) $cat_filters[] = "t.category = 'support'";

$category_where = !empty($cat_filters) ? " AND (" . implode(" OR ", $cat_filters) . ")" : "";

$target_query = "SELECT DISTINCT t.id, 
    COALESCE(t.major_output, t.success_indicators) as target_display,
    t.major_output,
    t.success_indicators,
    t.category, 
    t.sub_category, 
    t.mfo,
    t.quality, 
    t.timeliness, 
    t.efficiency,
    COALESCE(td.deadline, 'N/A') as deadline
    FROM task_list t
    LEFT JOIN target_deadlines td ON t.id = td.target_id
    LEFT JOIN target_exemptions te ON t.id = te.task_id AND te.position_id = $position_id
    WHERE t.is_active = 1
    AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)
    AND (t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = $designation_id)
    AND te.id IS NULL
    $category_where
    ORDER BY t.category, t.sub_category, t.mfo";

$targets = $conn->query($target_query);
?>

<div class="container-fluid">
   
    
    <form id="mov-upload-form" action="upload_mov.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="faculty_id" value="<?php echo $faculty_id; ?>">
        <input type="hidden" name="mov_type" value="<?php echo $mov_type; ?>">
        
       
        
        <div class="form-group">
            <label for="title">MOV Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" required 
                placeholder="Enter MOV title (e.g., Course Syllabus, Research Paper, DTR)">
        </div>
        
        <div class="form-group">
            <label for="target_id">Associated Target <span class="text-danger">*</span></label>
            <select class="form-control" id="target_id" name="target_id" required <?php echo $preselect_target_id > 0 ? 'readonly' : ''; ?>>
                <option value="">-- Select Your Assigned Target --</option>
                <?php 
                if ($targets && $targets->num_rows > 0) {
                    $current_category = '';
                    while ($t = $targets->fetch_assoc()): 
                        if ($current_category != $t['category']) {
                            if ($current_category != '') echo '</optgroup>';
                            echo '<optgroup label="' . strtoupper($t['category']) . ' FUNCTION">';
                            $current_category = $t['category'];
                        }
                        $display = !empty($t['major_output']) ? $t['major_output'] : $t['target_display'];
                        $display = substr($display, 0, 100);
                        $selected = ($t['id'] == $preselect_target_id) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $t['id']; ?>" 
                            data-indicators="<?php echo htmlspecialchars($t['success_indicators']); ?>"
                            data-deadline="<?php echo htmlspecialchars($t['deadline']); ?>"
                            <?php echo $selected; ?>>
                            [MFO-<?php echo $t['mfo']; ?>] <?php echo $display; ?>
                        </option>
                    <?php 
                    endwhile; 
                    if ($current_category != '') echo '</optgroup>';
                } else {
                ?>
                    <option value="" disabled>No targets assigned to your position</option>
                <?php } ?>
            </select>
            <?php if ($preselect_target_id > 0): ?>
            <small class="form-text text-success">
                <i class="fa fa-check-circle"></i> Target pre-selected from MOV Management
            </small>
            <?php else: ?>
            <small class="form-text text-muted">
                <i class="fa fa-info-circle"></i> Only targets assigned to your position and designation are shown
            </small>
            <?php endif; ?>
        </div>
        
        <div class="form-group" id="success_indicators_display" style="display:none;">
            <label>Target Success Indicators:</label>
            <div class="alert alert-light border">
                <p class="mb-0" id="indicators_text"></p>
            </div>
        </div>
        
        <div class="form-group" id="deadline_display" style="display:none;">
            <label>Deadline:</label>
            <div class="alert alert-info border">
                <p class="mb-0" id="deadline_text"></p>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description / Remarks</label>
            <textarea class="form-control" id="description" name="description" rows="3" 
                placeholder="Briefly describe this MOV (optional)"></textarea>
        </div>
        
        <div class="form-group">
            <label for="document">Upload File <span class="text-danger">*</span></label>
            <div class="custom-file">
                <input type="file" class="custom-file-input" id="document" name="document" 
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" required>
                <label class="custom-file-label" for="document">Choose file</label>
            </div>
            <small class="form-text text-muted">
                Allowed formats: PDF, DOC, DOCX, XLS, XLSX, PNG, JPG (Max 10MB)
            </small>
        </div>
        
        <div class="form-group">
            <label for="date_submitted">Date of Submission <span class="text-danger">*</span></label>
            <input type="datetime-local" class="form-control" id="date_submitted" name="date_submitted" 
                value="<?php echo date('Y-m-d\TH:i'); ?>" required>
            <small class="form-text text-muted">
                <i class="fa fa-clock"></i> The actual date when you accomplished/submitted this MOV
            </small>
        </div>
        
        <div class="form-group">
            <label for="deadline">Deadline Date</label>
            <input type="date" class="form-control" id="deadline" name="deadline">
            <small class="form-text text-muted">
                <i class="fa fa-calendar"></i> Optional: Set a deadline for this MOV submission
            </small>
        </div>
        
        <div class="form-group efficiency-fields" style="display:none;">
            <label for="percentage">Percentage of Attendance <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="percentage" name="percentage" min="0" max="100" placeholder="0-100">
            <small class="form-text text-muted">
                <i class="fa fa-chart-line"></i> Your attendance percentage for this activity
            </small>
        </div>
        
        <div class="form-group efficiency-fields" style="display:none;">
            <label for="date_conducted">Date Conducted <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="date_conducted" name="date_conducted">
            <small class="form-text text-muted">
                <i class="fa fa-calendar"></i> When the activity was conducted
            </small>
        </div>
        
        <div class="form-group">
            <label for="rating_period">Rating Period <span class="text-danger">*</span></label>
            <select class="form-control" id="rating_period" name="rating_period" required>
                <option value="">-- Select Rating Period --</option>
                <?php
                $periods = $conn->query("SELECT id, semester, year FROM rating_period ORDER BY id DESC");
                while ($p = $periods->fetch_assoc()) {
                    $period_value = $p['semester'] . ' ' . $p['year'];
                    echo "<option value='{$period_value}'>{$period_value}</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="confirm_submission" required>
                <label class="custom-control-label" for="confirm_submission">
                    I confirm that this MOV accurately represents my work and is authentic
                </label>
            </div>
        </div>
    </form>
    
    <div class="text-right mt-3">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="submitMOV()">
            <i class="fa fa-upload"></i> Upload MOV
        </button>
    </div>
</div>

<script>
// File input label update
$('#document').on('change', function() {
    var fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').html(fileName);
});

// Show/hide fields based on mov_type
var movType = '<?php echo $mov_type; ?>';
if (movType === 'efficiency') {
    $('.efficiency-fields').show();
    $('#percentage').prop('required', true);
    $('#date_conducted').prop('required', true);
    $('#date_submitted').closest('.form-group').hide();
    $('#deadline').closest('.form-group').hide();
} else if (movType === 'quality') {
    $('#deadline').closest('.form-group').hide();
}

// Show success indicators and deadline when target is selected
$('#target_id').on('change', function() {
    var selectedOption = $(this).find('option:selected');
    var indicators = selectedOption.data('indicators');
    var deadline = selectedOption.data('deadline');
    
    if (indicators && indicators.trim() !== '') {
        $('#indicators_text').text(indicators);
        $('#success_indicators_display').fadeIn();
    } else {
        $('#success_indicators_display').fadeOut();
    }
    
    if (deadline) {
        $('#deadline_text').text(deadline);
        $('#deadline_display').fadeIn();
    } else {
        $('#deadline_display').fadeOut();
    }
});

function submitMOV() {
    if (!$('#confirm_submission').is(':checked')) {
        alert_toast('Please confirm your submission', 'warning');
        return;
    }
    
    if ($('#title').val().trim() === '') {
        alert_toast('Please enter MOV title', 'warning');
        $('#title').focus();
        return;
    }
    
    if ($('#target_id').val() === '') {
        alert_toast('Please select a target', 'warning');
        $('#target_id').focus();
        return;
    }
    
    if ($('#rating_period').val() === '') {
        alert_toast('Please select rating period', 'warning');
        $('#rating_period').focus();
        return;
    }
    
    if ($('#document')[0].files.length === 0) {
        alert_toast('Please select a file to upload', 'warning');
        return;
    }
    
    start_load();
    
    var formData = new FormData($('#mov-upload-form')[0]);
    
    $.ajax({
        url: 'upload_mov.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        success: function(resp) {
            if (resp == 1) {
                alert_toast('MOV uploaded successfully!', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else if (resp == 2) {
                end_load();
                alert_toast('File already exists for this target and period', 'warning');
            } else {
                end_load();
                alert_toast('Upload failed. Please try again.', 'danger');
            }
        },
        error: function() {
            end_load();
            alert_toast('Error occurred during upload', 'danger');
        }
    });
}
</script>

<style>
.custom-file-label::after {
    content: "Browse";
}
.optgroup {
    font-weight: bold;
    color: #007bff;
}
option[value=""][disabled] {
    color: #dc3545;
    font-style: italic;
}
.alert-info {
    border-left: 4px solid #17a2b8;
    background-color: #17a2b8 !important;
    color: white !important;
}
.alert-info strong, .alert-info small, .alert-info i {
    color: white !important;
}
</style>
