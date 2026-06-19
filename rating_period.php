<?php include 'db_connect.php';

switch ($_SESSION['login_type']) {
    case 0:
        echo "<script>
            alert('Invalid Credential');
            window.location.href = 'index.php';
        </script>";
        exit;
}

// Fetch latest period — one row governs IPCR, DP, and OPCR
$period = null;
$qry = $conn->query("SELECT * FROM rating_period ORDER BY id DESC LIMIT 1");
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
?>

<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-calendar-alt"></i> Rating Period Settings</h5>
        </div>
        <div class="card-body">

            <div class="row">
                <!-- ==================== PERIOD SETTING ==================== -->
                <div class="col-md-6">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h6 class="card-title"><i class="fa fa-cog"></i> Current Period</h6>
                        </div>
                        <div class="card-body">
                            <form onsubmit="event.preventDefault(); update_period();">
                                <div class="form-group">
                                    <label>Semester</label>
                                    <select name="period_semester" id="period_semester" class="form-control" required>
                                        <option value="">Select Semester</option>
                                        <option value="1st Semester" <?= ($period['semester'] ?? '') == '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
                                        <option value="2nd Semester" <?= ($period['semester'] ?? '') == '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
                                        <option value="Summer" <?= ($period['semester'] ?? '') == 'Summer' ? 'selected' : '' ?>>Summer</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Academic Year</label>
                                    <select name="period_year" id="period_year" class="form-control" required>
                                        <option value="">Select Year</option>
                                        <?php
                                        $cy = date("Y");
                                        for ($y = $cy - 1; $y < $cy + 10; $y++) {
                                            $ay = $y . "-" . ($y + 1);
                                            $sel = ($period['year'] ?? '') == $ay ? 'selected' : '';
                                            echo "<option value='$ay' $sel>$ay</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Start Date</label>
                                            <input type="date" name="period_start" id="period_start" class="form-control"
                                                   value="<?= $period['start_date'] ?? '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>End Date</label>
                                            <input type="date" name="period_end" id="period_end" class="form-control"
                                                   value="<?= $period['end_date'] ?? '' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" name="period_auto_cascade" id="period_auto_cascade"
                                               class="form-check-input" value="1"
                                               <?= ($period['auto_cascade'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Auto-cascade: IPCR ratings → DP (per-department) + OPCR (office-wide)</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-save"></i> Save Period (IPCR + DP + OPCR)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ==================== PERIOD STATUS ==================== -->
                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h6 class="card-title"><i class="fa fa-info-circle"></i> Status Overview</h6>
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
                                    <td><strong>Date Range</strong></td>
                                    <td>
                                        <?php if ($period['start_date'] || $period['end_date']): ?>
                                            <?= $period['start_date'] ? date('M d, Y', strtotime($period['start_date'])) : '—' ?>
                                            &mdash;
                                            <?= $period['end_date'] ? date('M d, Y', strtotime($period['end_date'])) : '—' ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Auto-Cascade</strong></td>
                                    <td><?= $period['auto_cascade'] ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-secondary">Disabled</span>' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Applies To</strong></td>
                                    <td>
                                        <span class="badge badge-primary">IPCR</span>
                                        <span class="badge badge-warning">DP</span>
                                        <span class="badge badge-danger">OPCR</span>
                                    </td>
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
                                <i class="fa fa-exclamation-triangle"></i> No rating period has been set. Please configure one above.
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
                    <h6 class="card-title"><i class="fa fa-history"></i> Period History</h6>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-hover">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>#</th>
                                <th>Semester</th>
                                <th>Year</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Cascade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $hist_qry = $conn->query("SELECT * FROM rating_period ORDER BY id DESC");
                            if ($hist_qry && $hist_qry->num_rows > 0):
                                $num = 1;
                                while ($h = $hist_qry->fetch_assoc()):
                                    $is_current = ($period && $period['id'] == $h['id']);
                            ?>
                            <tr class="<?= $is_current ? 'table-info' : '' ?>">
                                <td><?= $num++ ?></td>
                                <td><?= htmlspecialchars($h['semester']) ?></td>
                                <td><?= htmlspecialchars($h['year']) ?></td>
                                <td><?= $h['start_date'] ? date('M d, Y', strtotime($h['start_date'])) : '—' ?></td>
                                <td><?= $h['end_date'] ? date('M d, Y', strtotime($h['end_date'])) : '—' ?></td>
                                <td><?= $h['auto_cascade'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' ?></td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr><td colspan="6" class="text-center text-muted">No periods yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function update_period() {
    var semester = $('#period_semester').val();
    var year = $('#period_year').val();
    var startDate = $('#period_start').val();
    var endDate = $('#period_end').val();
    var autoCascade = $('#period_auto_cascade').is(':checked') ? 1 : 0;

    if (!semester || !year) {
        alert_toast("Please fill out semester and year", "danger");
        return;
    }

    start_load();
    $.ajax({
        url: "ajax.php?action=update_period",
        method: "POST",
        data: {
            period_type: 'ALL',
            semester: semester,
            year: year,
            start_date: startDate,
            end_date: endDate,
            auto_cascade: autoCascade
        },
        success: function(resp) {
            if (resp == 1) {
                alert_toast("Period updated (IPCR + DP + OPCR)", "success");
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                alert_toast("Error: " + resp, "danger");
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
.card-header { background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%); color: white; }
</style>
