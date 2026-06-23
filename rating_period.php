<?php include 'db_connect.php';

switch ($_SESSION['login_type']) {
    case 0:
        echo "<script>
            alert('Invalid Credential');
            window.location.href = 'index.php';
        </script>";
        exit;
}

// Fetch active period
$period = null;
$qry = $conn->query("SELECT * FROM rating_period WHERE is_active = 1 LIMIT 1");
if ($qry && $qry->num_rows > 0) {
    $period = $qry->fetch_assoc();
}

// Get cascading stats for display
$cascade_qry = $conn->query("
    SELECT level, COUNT(*) as cnt, AVG(overall_rating) as avg_overall
    FROM cascading_ratings
    GROUP BY level
");
$cascade_stats = [];
while ($row = $cascade_qry->fetch_assoc()) {
    $cascade_stats[$row['level']] = $row;
}

// Count intervention flags
$intervention_qry = $conn->query("SELECT COUNT(*) as cnt FROM intervention_flags WHERE acknowledged = 0");
$intervention_count = $intervention_qry->fetch_assoc()['cnt'] ?? 0;

// Fetch all periods for history
$all_periods = [];
$hist_qry = $conn->query("SELECT * FROM rating_period ORDER BY year DESC, semester DESC");
while ($row = $hist_qry->fetch_assoc()) {
    $all_periods[] = $row;
}
?>

<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-calendar-alt"></i> Rating Period Settings</h5>
        </div>
        <div class="card-body">

            <div class="row">
                <!-- ==================== PERIOD FORM ==================== -->
                <div class="col-md-6">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h6 class="card-title"><i class="fa fa-cog"></i> <span id="formTitle">Set Rating Period</span></h6>
                        </div>
                        <div class="card-body">
                            <form onsubmit="event.preventDefault(); save_period();">
                                <input type="hidden" name="period_id" id="period_id" value="0">
                                <div class="form-group">
                                    <label>Semester</label>
                                    <select name="period_semester" id="period_semester" class="form-control" required onchange="autoFillDates()">
                                        <option value="">Select Semester</option>
                                        <option value="1st Semester">1st Semester (Jul–Dec designated / Aug–Dec non-desig)</option>
                                        <option value="2nd Semester">2nd Semester (Jan–Jun designated / Jan–May non-desig)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Academic Year</label>
                                    <select name="period_year" id="period_year" class="form-control" required onchange="autoFillDates()">
                                        <option value="">Select Year</option>
                                        <?php
                                        $cy = date("Y");
                                        for ($y = $cy - 2; $y < $cy + 10; $y++) {
                                            $ay = $y . "-" . ($y + 1);
                                            echo "<option value='$ay'>$ay</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Designated Start <small class="text-muted">(Dept Head, Dean, Director)</small></label>
                                            <input type="date" name="period_start" id="period_start" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Designated End <small class="text-muted">(Dept Head, Dean, Director)</small></label>
                                            <input type="date" name="period_end" id="period_end" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Non-Designated Start <small class="text-muted">(COS + no designation)</small></label>
                                            <input type="date" name="non_desig_start" id="non_desig_start" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Non-Designated End <small class="text-muted">(COS + no designation)</small></label>
                                            <input type="date" name="non_desig_end" id="non_desig_end" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" name="period_auto_cascade" id="period_auto_cascade"
                                               class="form-check-input" value="1">
                                        <label class="form-check-label">Auto-cascade: IPCR → DP + OPCR</label>
                                    </div>
                                </div>
                                <div class="d-flex" style="gap: 8px;">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="fa fa-save"></i> <span id="saveBtnLabel">Save Period</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="clearForm()" id="clearBtn" style="display:none;">
                                        <i class="fa fa-times"></i> Clear
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ==================== ACTIVE PERIOD STATUS ==================== -->
                <div class="col-md-6">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h6 class="card-title"><i class="fa fa-check-circle"></i> Active Period</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($period): ?>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <td width="40%"><strong>Semester</strong></td>
                                    <td><?= htmlspecialchars($period['semester']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Academic Year</strong></td>
                                    <td><?= htmlspecialchars($period['year']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Code</strong></td>
                                    <td><code><?= htmlspecialchars($period['code']) ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Designated Faculty</strong><br><small class="text-muted">(Dept Head, Dean, Director)</small></td>
                                    <td>
                                        <?= $period['start_date'] ? date('M d, Y', strtotime($period['start_date'])) : '—' ?>
                                        &mdash;
                                        <?= $period['end_date'] ? date('M d, Y', strtotime($period['end_date'])) : '—' ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Non-Designated / COS</strong><br><small class="text-muted">(no designation + Contract of Service)</small></td>
                                    <td>
                                        <?= $period['non_desig_start_date'] ? date('M d, Y', strtotime($period['non_desig_start_date'])) : ($period['start_date'] ? date('M d, Y', strtotime($period['start_date'])) : '—') ?>
                                        &mdash;
                                        <?= $period['non_desig_end_date'] ? date('M d, Y', strtotime($period['non_desig_end_date'])) : ($period['end_date'] ? date('M d, Y', strtotime($period['end_date'])) : '—') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Auto-Cascade</strong></td>
                                    <td><?= $period['auto_cascade'] ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-secondary">Disabled</span>' ?></td>
                                </tr>
                                <?php if (!empty($cascade_stats)): ?>
                                <tr>
                                    <td><strong>Cascade Status</strong></td>
                                    <td>
                                        <?php if (isset($cascade_stats['DP'])): ?>
                                            <span class="badge badge-warning">DP: <?= $cascade_stats['DP']['cnt'] ?> dept(s), avg <?= number_format($cascade_stats['DP']['avg_overall'], 2) ?></span><br>
                                        <?php endif; ?>
                                        <?php if (isset($cascade_stats['OPCR'])): ?>
                                            <span class="badge badge-danger">OPCR: <?= $cascade_stats['OPCR']['cnt'] ?>, avg <?= number_format($cascade_stats['OPCR']['avg_overall'], 2) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($intervention_count > 0): ?>
                                <tr>
                                    <td><strong>Intervention Flags</strong></td>
                                    <td><span class="badge badge-warning"><?= $intervention_count ?> unacknowledged</span></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                            <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> No active rating period. Create one using the form.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== CASCADE BUTTON ==================== -->
            <div class="card card-outline card-success mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0"><i class="fa fa-arrow-circle-up"></i> Cascading: IPCR → DP + OPCR</h6>
                    <button class="btn btn-sm btn-success" onclick="trigger_cascade()">
                        <i class="fa fa-calculator"></i> Compute Now
                    </button>
                </div>
                <div class="card-body">
                    <div id="cascade_result">
                        <p class="text-muted">Click "Compute Now" to aggregate all individual faculty IPCR ratings into department DP (per-department averages) and office OPCR (office-wide average). Also checks for 3-consecutive-low intervention flags.</p>
                    </div>
                </div>
            </div>

            <!-- ==================== PERIOD HISTORY ==================== -->
            <div class="card card-outline card-dark mt-3">
                <div class="card-header">
                    <h6 class="card-title"><i class="fa fa-history"></i> All Rating Periods</h6>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-hover">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>#</th>
                                <th>Semester</th>
                                <th>AY</th>
                                <th>Code</th>
                                <th>Designated</th>
                                <th>Non-Desig / COS</th>
                                <th>Cascade</th>
                                <th>Active</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_periods)):
                                $num = 1;
                                foreach ($all_periods as $h):
                                    $is_active = ($h['is_active'] == 1);
                            ?>
                            <tr class="<?= $is_active ? 'table-success' : '' ?>">
                                <td><?= $num++ ?></td>
                                <td><?= htmlspecialchars($h['semester']) ?></td>
                                <td><?= htmlspecialchars($h['year']) ?></td>
                                <td><code><?= htmlspecialchars($h['code']) ?></code></td>
                                <td>
                                    <?= $h['start_date'] ? date('M d, Y', strtotime($h['start_date'])) : '—' ?>
                                    &ndash;
                                    <?= $h['end_date'] ? date('M d, Y', strtotime($h['end_date'])) : '—' ?>
                                </td>
                                <td>
                                    <?= $h['non_desig_start_date'] ? date('M d, Y', strtotime($h['non_desig_start_date'])) : ($h['start_date'] ? date('M d, Y', strtotime($h['start_date'])) : '—') ?>
                                    &ndash;
                                    <?= $h['non_desig_end_date'] ? date('M d, Y', strtotime($h['non_desig_end_date'])) : ($h['end_date'] ? date('M d, Y', strtotime($h['end_date'])) : '—') ?>
                                </td>
                                <td><?= $h['auto_cascade'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' ?></td>
                                <td><?= $is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">—</span>' ?></td>
                                <td>
                                    <button class="btn btn-xs btn-info" onclick="editPeriod(<?= $h['id'] ?>)">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <tr><td colspan="9" class="text-center text-muted">No periods yet. Create one above.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Store all periods as JSON for edit functionality
var allPeriods = <?= json_encode($all_periods) ?>;

function autoFillDates() {
    var semester = $('#period_semester').val();
    var year = $('#period_year').val();
    if (!semester || !year) return;
    
    var parts = year.split('-');
    var startYear = parseInt(parts[0]);
    var endYear = parseInt(parts[1]);
    
    if (semester === '1st Semester') {
        // Designated: Jul 1 – Dec 31 | Non-desig/COS: Aug 1 – Dec 31
        $('#period_start').val(startYear + '-07-01');
        $('#period_end').val(startYear + '-12-31');
        $('#non_desig_start').val(startYear + '-08-01');
        $('#non_desig_end').val(startYear + '-12-31');
    } else if (semester === '2nd Semester') {
        // Designated: Jan 1 – Jun 30 | Non-desig/COS: Jan 1 – May 31
        $('#period_start').val(endYear + '-01-01');
        $('#period_end').val(endYear + '-06-30');
        $('#non_desig_start').val(endYear + '-01-01');
        $('#non_desig_end').val(endYear + '-05-31');
    }
}

function editPeriod(id) {
    var p = allPeriods.find(function(item) { return item.id == id; });
    if (!p) return;
    
    $('#period_id').val(p.id);
    $('#period_semester').val(p.semester);
    $('#period_year').val(p.year);
    $('#period_start').val(p.start_date || '');
    $('#period_end').val(p.end_date || '');
    $('#non_desig_start').val(p.non_desig_start_date || '');
    $('#non_desig_end').val(p.non_desig_end_date || '');
    $('#period_auto_cascade').prop('checked', p.auto_cascade == 1);
    
    $('#formTitle').text('Edit Rating Period');
    $('#saveBtnLabel').text('Update Period');
    $('#clearBtn').show();
    
    // Scroll to form
    $('html, body').animate({ scrollTop: 0 }, 300);
}

function clearForm() {
    $('#period_id').val(0);
    $('#period_semester').val('');
    $('#period_year').val('');
    $('#period_start').val('');
    $('#period_end').val('');
    $('#non_desig_start').val('');
    $('#non_desig_end').val('');
    $('#period_auto_cascade').prop('checked', false);
    
    $('#formTitle').text('Set Rating Period');
    $('#saveBtnLabel').text('Save Period');
    $('#clearBtn').hide();
}

function save_period() {
    var semester = $('#period_semester').val();
    var year = $('#period_year').val();
    var periodId = $('#period_id').val();
    var startDate = $('#period_start').val();
    var endDate = $('#period_end').val();
    var nonDesigStart = $('#non_desig_start').val();
    var nonDesigEnd = $('#non_desig_end').val();
    var autoCascade = $('#period_auto_cascade').is(':checked') ? 1 : 0;
    var code = semester.replace(/ /g, '') + '-' + year;

    if (!semester || !year) {
        alert_toast("Please fill out semester and year", "danger");
        return;
    }

    start_load();
    $.ajax({
        url: "ajax.php?action=update_period",
        method: "POST",
        data: {
            period_id: periodId,
            semester: semester,
            year: year,
            code: code,
            start_date: startDate,
            end_date: endDate,
            non_desig_start_date: nonDesigStart,
            non_desig_end_date: nonDesigEnd,
            auto_cascade: autoCascade
        },
        success: function(resp) {
            if (resp == 1) {
                alert_toast("Period saved successfully", "success");
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                alert_toast("Error saving period", "danger");
                end_load();
            }
        },
        error: function() {
            alert_toast("AJAX error occurred", "danger");
            end_load();
        }
    });
}

function trigger_cascade() {
    start_load();
    $.ajax({
        url: "ajax.php?action=cascade_compute",
        method: "POST",
        data: {},
        success: function(resp) {
            try {
                var result = typeof resp === 'string' ? JSON.parse(resp) : resp;
                var html = '<div class="alert alert-info"><h6>Cascade Results:</h6><ul>';
                if (result.status === 'success') {
                    html += '<li><strong>IPCR &rarr; DP:</strong> ' + result.dp_count + ' department(s) computed</li>';
                    html += '<li><strong>DP &rarr; OPCR:</strong> ' + result.opcr_count + ' office-level computed</li>';
                    html += '<li><strong>Intervention flags:</strong> ' + result.intervention_count + ' faculty flagged (3 consecutive low IPCR)</li>';
                } else {
                    html += '<li>' + (result.message || 'No cascade computed') + '</li>';
                }
                html += '</ul></div>';
                $('#cascade_result').html(html);
            } catch(e) {
                $('#cascade_result').html('<div class="alert alert-success">Cascade computed: ' + resp + '</div>');
            }
            end_load();
        },
        error: function(xhr, status, err) {
            $('#cascade_result').html('<div class="alert alert-danger">Error: ' + err + '</div>');
            end_load();
        }
    });
}
</script>

<style>
</style>
