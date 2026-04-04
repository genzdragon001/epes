<?php include 'db_connect.php';

switch ($_SESSION['login_type']) {
    case 0:
        echo "<script>
            alert('Invalid Credential');
            window.location.href = 'index.php';
        </script>";
        exit;
}

$current_semester = '';
$current_year = '';
$qry = $conn->query("SELECT semester, year FROM rating_period ORDER BY id DESC LIMIT 1");
if ($qry && $qry->num_rows > 0) {
    $row = $qry->fetch_assoc();
    $current_semester = $row['semester'];
    $current_year = $row['year'];
}
?>

<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-calendar-alt"></i> Rating Period Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h6 class="card-title"><i class="fa fa-info-circle"></i> Current Semester & Year</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Semester:</strong></td>
                                    <td><?php echo htmlspecialchars($current_semester) ?: '<span class="text-muted">Not Set</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Academic Year:</strong></td>
                                    <td><?php echo htmlspecialchars($current_year) ?: '<span class="text-muted">Not Set</span>'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h6 class="card-title"><i class="fa fa-edit"></i> Update Semester</h6>
                        </div>
                        <div class="card-body">
                            <form onsubmit="event.preventDefault(); update_semester();">
                                <div class="form-group">
                                    <label for="semester">Semester</label>
                                    <select name="semester" id="semester" class="form-control" required>
                                        <option value="" disabled>Select Semester</option>
                                        <option value="1st Semester" <?php echo ($current_semester == "1st Semester") ? "selected" : ""; ?>>1st Semester</option>
                                        <option value="2nd Semester" <?php echo ($current_semester == "2nd Semester") ? "selected" : ""; ?>>2nd Semester</option>
                                        <option value="Summer" <?php echo ($current_semester == "Summer") ? "selected" : ""; ?>>Summer</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="year">Academic Year</label>
                                    <select name="year" id="year" class="form-control" required>
                                        <option value="" disabled>Select Academic Year</option>
                                        <?php
                                        $currentYear = date("Y");
                                        $startYear = $currentYear - 1;
                                        $endYear = $currentYear + 9;

                                        for ($y = $startYear; $y < $endYear; $y++) {
                                            $academicYear = $y . "-" . ($y + 1);
                                            $selected = ($academicYear == $current_year) ? "selected" : "";
                                            echo "<option value='" . htmlspecialchars($academicYear) . "' $selected>" . htmlspecialchars($academicYear) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success btn-block"><i class="fa fa-save"></i> Update Semester</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function update_semester() {
    let semester = $("#semester").val();
    let year = $("#year").val();

    if (!semester || !year) {
        alert_toast("Please fill out all fields", "danger");
        return;
    }

    start_load();
    $.ajax({
        url: "ajax.php?action=update_semester",
        method: "POST",
        data: {
            semester: semester,
            year: year
        },
        success: function (resp) {
            if (resp == 1) {
                alert_toast("Semester updated successfully", "success");
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                alert_toast("Error updating semester: " + resp, "danger");
                end_load();
            }
        },
        error: function () {
            alert_toast("AJAX error occurred", "danger");
            end_load();
        }
    });
}
</script>

<style>
.card-header { background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%); color: white; }
</style>
