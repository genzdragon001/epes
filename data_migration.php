<?php
/**
 * Data Migration Tool — Import historical IPCR data from CSV
 * Maps spreadsheet columns to ratings table
 * Accessible by Admin (login_type=2) only
 */
include 'db_connect.php';

if (!isset($_SESSION['login_id']) || ($_SESSION['login_type'] ?? -1) != 2) {
    echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied. Admin only.</div></div>';
    return;
}

$step = $_GET['step'] ?? '1';
$error = '';
$success = '';
$preview_data = [];
$imported = 0;
$skipped = 0;
$errors = [];

// Step 2: Preview CSV
if ($step == '2' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Error code: ' . $file['error'];
        $step = '1';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'])) {
            $error = 'Only CSV files are accepted.';
            $step = '1';
        } else {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                $error = 'Could not read file.';
                $step = '1';
            } else {
                // Read header row
                $headers = fgetcsv($handle);
                if (!$headers || count($headers) < 3) {
                    $error = 'CSV must have at least 3 columns: employee_id, task_id, rating_period (plus E/T/Q values).';
                    $step = '1';
                } else {
                    // Store headers for mapping
                    $_SESSION['csv_headers'] = $headers;
                    $_SESSION['csv_path'] = $file['tmp_name'];
                    
                    // Preview first 10 rows
                    $row_num = 0;
                    while (($row = fgetcsv($handle)) !== false && $row_num < 10) {
                        $preview_data[] = $row;
                        $row_num++;
                    }
                    fclose($handle);
                    
                    if (empty($preview_data)) {
                        $error = 'CSV file contains no data rows.';
                        $step = '1';
                    }
                }
            }
        }
    }
}

// Step 3: Execute import
if ($step == '3' && isset($_SESSION['csv_path']) && isset($_SESSION['csv_headers'])) {
    $headers = $_SESSION['csv_headers'];
    $csv_path = $_SESSION['csv_path'];
    $dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] == '1';
    
    // Column mapping from form
    $map = [
        'employee_id'   => intval($_POST['col_employee_id'] ?? 0),
        'task_id'       => intval($_POST['col_task_id'] ?? 1),
        'rating_period' => intval($_POST['col_rating_period'] ?? 2),
        'efficiency'    => intval($_POST['col_efficiency'] ?? 3),
        'timeliness'    => intval($_POST['col_timeliness'] ?? 4),
        'quality'       => intval($_POST['col_quality'] ?? 5),
    ];
    
    $handle = fopen($csv_path, 'r');
    if ($handle) {
        fgetcsv($handle); // Skip header
        
        $conn->begin_transaction();
        
        while (($row = fgetcsv($handle)) !== false) {
            $emp_id   = intval($row[$map['employee_id']] ?? 0);
            $task_id  = intval($row[$map['task_id']] ?? 0);
            $period   = trim($row[$map['rating_period']] ?? '');
            $eff      = floatval($row[$map['efficiency']] ?? 0);
            $time     = floatval($row[$map['timeliness']] ?? 0);
            $qual     = floatval($row[$map['quality']] ?? 0);
            
            // Validate
            if ($emp_id <= 0 || $task_id <= 0 || empty($period)) {
                $errors[] = "Skipped row: invalid employee_id/task_id/period — " . json_encode($row);
                $skipped++;
                continue;
            }
            
            // Verify employee exists
            $check_emp = $conn->query("SELECT id FROM employee_list WHERE id = $emp_id LIMIT 1");
            if (!$check_emp || $check_emp->num_rows == 0) {
                $errors[] = "Skipped: employee_id $emp_id not found in database.";
                $skipped++;
                continue;
            }
            
            // Verify task exists
            $check_task = $conn->query("SELECT id FROM task_list WHERE id = $task_id LIMIT 1");
            if (!$check_task || $check_task->num_rows == 0) {
                $errors[] = "Skipped: task_id $task_id not found in database.";
                $skipped++;
                continue;
            }
            
            if ($dry_run) {
                $imported++;
                continue;
            }
            
            // Check for existing rating (upsert)
            $check = $conn->query("SELECT id FROM ratings WHERE employee_id = $emp_id AND task_id = $task_id AND rating_period = '$period' LIMIT 1");
            
            if ($check && $check->num_rows > 0) {
                $rating_id = $check->fetch_assoc()['id'];
                $stmt = $conn->prepare("UPDATE ratings SET efficiency = ?, timeliness = ?, quality = ?, period_type = 'IPCR' WHERE id = ?");
                $stmt->bind_param('dddi', $eff, $time, $qual, $rating_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO ratings (employee_id, task_id, efficiency, timeliness, quality, rating_period, period_type) VALUES (?, ?, ?, ?, ?, ?, 'IPCR')");
                $stmt->bind_param('iiddds', $emp_id, $task_id, $eff, $time, $qual, $period);
            }
            
            if ($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Failed: employee_id=$emp_id task_id=$task_id — " . $stmt->error;
                $skipped++;
            }
            $stmt->close();
        }
        fclose($handle);
        
        if ($dry_run) {
            $conn->rollback();
            $success = "Dry run complete: $imported rows would be imported, $skipped skipped.";
        } else {
            $conn->commit();
            $success = "Import complete: $imported rows imported, $skipped skipped.";
        }
        
        // Clean up session
        unset($_SESSION['csv_path'], $_SESSION['csv_headers']);
    }
}

// Get current stats for display
$total_ratings = $conn->query("SELECT COUNT(*) as c FROM ratings")->fetch_assoc()['c'];
$total_faculty = $conn->query("SELECT COUNT(DISTINCT employee_id) as c FROM ratings")->fetch_assoc()['c'];
$periods_with_data = [];
$pq = $conn->query("SELECT DISTINCT rating_period, COUNT(*) as cnt FROM ratings WHERE period_type='IPCR' GROUP BY rating_period ORDER BY rating_period DESC");
while ($p = $pq->fetch_assoc()) {
    $periods_with_data[] = $p;
}
?>

<div class="col-lg-12">
    <div class="card card-outline card-purple">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-database"></i> Data Migration — Import Historical IPCR
            </h5>
        </div>
        <div class="card-body">
            
            <!-- Current Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="info-box bg-gradient-info">
                        <span class="info-box-icon"><i class="fa fa-star"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Ratings</span>
                            <span class="info-box-number"><?= number_format($total_ratings) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-gradient-success">
                        <span class="info-box-icon"><i class="fa fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Rated Faculty</span>
                            <span class="info-box-number"><?= $total_faculty ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box bg-gradient-secondary">
                        <span class="info-box-icon"><i class="fa fa-calendar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Periods with Data</span>
                            <span class="info-box-number">
                                <?php foreach ($periods_with_data as $p): ?>
                                <span class="badge badge-light mr-1"><?= htmlspecialchars($p['rating_period']) ?> (<?= $p['cnt'] ?>)</span>
                                <?php endforeach; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($step == '1'): ?>
            <!-- Step 1: Upload CSV -->
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="fa fa-upload"></i> Step 1: Upload CSV File</h6>
                    <p class="text-muted">Prepare a CSV file with historical IPCR data. Expected columns:</p>
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="bg-dark text-white">
                            <tr><th>Column</th><th>Description</th><th>Example</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>employee_id</td><td>Faculty ID from employee_list</td><td>2</td></tr>
                            <tr><td>task_id</td><td>Task ID from task_list</td><td>4</td></tr>
                            <tr><td>rating_period</td><td>Period label</td><td>1st Semester-2023-2024</td></tr>
                            <tr><td>efficiency</td><td>Efficiency score (0-5)</td><td>3.50</td></tr>
                            <tr><td>timeliness</td><td>Timeliness score (0-5)</td><td>4.00</td></tr>
                            <tr><td>quality</td><td>Quality score (0-5)</td><td>3.75</td></tr>
                        </tbody>
                    </table>
                    
                    <form method="POST" enctype="multipart/form-data" action="index.php?page=data_migration&step=2">
                        <div class="form-group">
                            <label>Select CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        </div>
                        <button type="submit" class="btn btn-purple">
                            <i class="fa fa-arrow-right"></i> Preview &amp; Map Columns
                        </button>
                    </form>
                </div>
            </div>
            
            <?php elseif ($step == '2' && !empty($preview_data)): ?>
            <!-- Step 2: Map columns and preview -->
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="fa fa-columns"></i> Step 2: Map Columns &amp; Preview</h6>
                    
                    <form method="POST" action="index.php?page=data_migration&step=3">
                        <div class="row mb-3">
                            <?php
                            $fields = [
                                'employee_id' => 'Employee ID',
                                'task_id' => 'Task ID',
                                'rating_period' => 'Rating Period',
                                'efficiency' => 'Efficiency',
                                'timeliness' => 'Timeliness',
                                'quality' => 'Quality',
                            ];
                            foreach ($fields as $key => $label):
                            ?>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?= $label ?> Column</label>
                                    <select name="col_<?= $key ?>" class="form-control">
                                        <option value="">— Skip —</option>
                                        <?php foreach ($_SESSION['csv_headers'] as $i => $h): 
                                            $sel = '';
                                            if ($key == 'employee_id' && $i == 0) $sel = 'selected';
                                            elseif ($key == 'task_id' && $i == 1) $sel = 'selected';
                                            elseif ($key == 'rating_period' && $i == 2) $sel = 'selected';
                                            elseif ($key == 'efficiency' && $i == 3) $sel = 'selected';
                                            elseif ($key == 'timeliness' && $i == 4) $sel = 'selected';
                                            elseif ($key == 'quality' && $i == 5) $sel = 'selected';
                                        ?>
                                        <option value="<?= $i ?>" <?= $sel ?>>Col <?= $i ?>: <?= htmlspecialchars($h) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Preview table -->
                        <h6 class="mb-2">Preview (first <?= count($preview_data) ?> rows):</h6>
                        <div class="table-responsive mb-3" style="max-height: 300px;">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="bg-dark text-white">
                                    <tr>
                                        <?php foreach ($_SESSION['csv_headers'] as $h): ?>
                                        <th><?= htmlspecialchars($h) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($preview_data as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $cell): ?>
                                        <td><?= htmlspecialchars($cell) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" name="dry_run" id="dry_run" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="dry_run">
                                <strong>Dry Run</strong> — preview only, don't actually import (uncheck to import for real)
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-purple btn-lg">
                            <i class="fa fa-play"></i> Execute Import
                        </button>
                        <a href="index.php?page=data_migration&step=1" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
            
            <?php elseif ($step == '3'): ?>
            <!-- Step 3: Results -->
            <div class="text-center py-4">
                <i class="fa fa-check-circle fa-4x text-success"></i>
                <h4 class="mt-3">Import Complete</h4>
                <p><?= $imported ?> rows processed, <?= $skipped ?> skipped.</p>
                <?php if (!empty($errors)): ?>
                <div class="alert alert-warning text-left mt-3" style="max-height: 200px; overflow-y: auto;">
                    <strong>Warnings:</strong>
                    <ul class="mb-0">
                        <?php foreach (array_slice($errors, 0, 20) as $e): ?>
                        <li><small><?= htmlspecialchars($e) ?></small></li>
                        <?php endforeach; ?>
                        <?php if (count($errors) > 20): ?>
                        <li><small>... and <?= count($errors) - 20 ?> more</small></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <a href="index.php?page=data_migration&step=1" class="btn btn-purple mt-2">
                    <i class="fa fa-redo"></i> Import Another File
                </a>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<style>
    .card-purple .card-header {
        background: linear-gradient(135deg, #6f42c1 0%, #6610f2 100%);
        color: white;
    }
    .btn-purple {
        background: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }
    .btn-purple:hover {
        background: #5a32a3;
        color: white;
    }
</style>
