<?php 
include 'db_connect.php'; 
session_start();

$id = intval($_GET['id']);
$mov = $conn->query("SELECT m.*, 
    COALESCE(t.major_output, t.success_indicators) as target_name,
    t.success_indicators,
    t.category,
    t.mfo,
    CONCAT(e.lastname, ', ', e.firstname, ' ', e.middlename) as faculty_name
    FROM mov_uploads m
    LEFT JOIN task_list t ON m.target_id = t.id
    LEFT JOIN employee_list e ON m.faculty_id = e.id
    WHERE m.id = $id")->fetch_assoc();

if (!$mov) {
    echo "MOV not found";
    exit;
}

$file_size = $mov['file_size'];
$size_units = ['B', 'KB', 'MB', 'GB'];
$size_index = 0;
while ($file_size >= 1024 && $size_index < count($size_units) - 1) {
    $file_size /= 1024;
    $size_index++;
}
$formatted_size = round($file_size, 2) . ' ' . $size_units[$size_index];

$file_path = $mov['file_path'] . '.' . $mov['file_type'];
$file_type = strtolower($mov['file_type']);
?>

<div class="container-fluid">
    <!-- File Preview Only -->
    <div class="card card-outline card-primary mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fa fa-file"></i> <?php echo htmlspecialchars($mov['file_name']); ?>
            </h5>
        </div>
        <div class="card-body text-center" style="min-height: 500px; background: #f5f5f5;">
            <?php 
            $image_types = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            if (in_array($file_type, $image_types)): 
            ?>
                <!-- Image Preview -->
                <img src="<?php echo $file_path; ?>" alt="MOV File" style="max-width: 100%; max-height: 600px;">
            <?php elseif ($file_type == 'pdf'): ?>
                <!-- PDF Preview -->
                <iframe src="<?php echo $file_path; ?>" style="width: 100%; height: 600px; border: 1px solid #ddd;"></iframe>
            <?php else: ?>
                <!-- File Preview Not Available -->
                <div style="padding: 100px 20px;">
                    <i class="fa fa-file-o" style="font-size: 100px; color: #ccc;"></i>
                    <h4 class="mt-3">Preview not available for this file type</h4>
                    <p class="text-muted">File: <?php echo htmlspecialchars($mov['file_name']); ?></p>
                    <p class="text-muted">Type: <?php echo strtoupper($file_type); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="text-center mb-3">
        <a href="<?php echo $file_path; ?>" download class="btn btn-primary btn-lg">
            <i class="fa fa-download"></i> Download File
        </a>
        <button type="button" class="btn btn-secondary btn-lg ml-2" data-dismiss="modal">
            <i class="fa fa-times"></i> Close
        </button>
    </div>
</div>

<style>
dl {
    margin-bottom: 10px;
}
dt {
    font-weight: 600;
    color: #6c757d;
    font-size: 12px;
    text-transform: uppercase;
}
dd {
    margin-left: 0;
    margin-bottom: 15px;
    font-size: 14px;
}
.card-body {
    padding: 1.25rem;
}
</style>
