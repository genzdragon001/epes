<?php
/**
 * Notifications Page — View all notifications, mark as read
 */
include 'db_connect.php';
require_once 'notification_helper.php';

if (!isset($_SESSION['login_id'])) {
    header('location:login.php');
    exit;
}

$user_id   = $_SESSION['login_id'];
$user_type = $_SESSION['login_type'];

// Handle mark-all-read
if (isset($_GET['mark_all']) && $_GET['mark_all'] == '1') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = ?");
    $stmt->bind_param('ii', $user_id, $user_type);
    $stmt->execute();
    $stmt->close();
    header('location:index.php?page=notifications');
    exit;
}

// Handle mark single
if (isset($_GET['mark_read']) && intval($_GET['mark_read']) > 0) {
    $nid = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $nid, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch all notifications (paginated)
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$total_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND user_type = ?");
$total_stmt->bind_param('ii', $user_id, $user_type);
$total_stmt->execute();
$total = $total_stmt->get_result()->fetch_assoc()['cnt'];
$total_stmt->close();
$total_pages = ceil($total / $per_page);

$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? AND user_type = ?
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bind_param('iiii', $user_id, $user_type, $per_page, $offset);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();

$unread_count = get_unread_count($conn, $user_id, $user_type);
?>

<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fa fa-bell"></i> Notifications
                <?php if ($unread_count > 0): ?>
                <span class="badge badge-danger ml-2"><?= $unread_count ?> unread</span>
                <?php endif; ?>
            </h5>
            <?php if ($unread_count > 0): ?>
            <a href="index.php?page=notifications&mark_all=1" class="btn btn-sm btn-outline-light">
                <i class="fa fa-check-double"></i> Mark All Read
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if ($notifications && $notifications->num_rows > 0): ?>
            <div class="list-group list-group-flush">
                <?php while ($n = $notifications->fetch_assoc()): 
                    $is_unread = ($n['is_read'] == 0);
                    $bg = $is_unread ? 'list-group-item-light' : '';
                    $icon = $n['type'] === 'Success' ? 'fa-check-circle text-success' : 
                           ($n['type'] === 'Warning' ? 'fa-exclamation-triangle text-warning' : 
                           ($n['type'] === 'Danger' ? 'fa-times-circle text-danger' : 'fa-info-circle text-info'));
                    $time = date('M d, Y h:i A', strtotime($n['created_at']));
                    $time_ago = time() - strtotime($n['created_at']);
                    if ($time_ago < 60) $relative = 'Just now';
                    elseif ($time_ago < 3600) $relative = floor($time_ago / 60) . ' min ago';
                    elseif ($time_ago < 86400) $relative = floor($time_ago / 3600) . ' hr ago';
                    else $relative = floor($time_ago / 86400) . ' days ago';
                ?>
                <div class="list-group-item <?= $bg ?> d-flex align-items-start py-3">
                    <i class="fas <?= $icon ?> fa-lg mr-3 mt-1" style="width: 20px;"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <strong><?= htmlspecialchars($n['title']) ?></strong>
                            <small class="text-muted" title="<?= $time ?>"><?= $relative ?></small>
                        </div>
                        <p class="mb-1"><?= htmlspecialchars($n['message']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <?php if ($n['link']): ?>
                            <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-arrow-right"></i> View
                            </a>
                            <?php else: ?>
                            <span></span>
                            <?php endif; ?>
                            <?php if ($is_unread): ?>
                            <a href="index.php?page=notifications&mark_read=<?= $n['id'] ?>&p=<?= $page ?>" 
                               class="text-muted" title="Mark as read">
                                <i class="fa fa-check"></i> Mark read
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="p-3 border-top">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=notifications&p=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fa fa-bell-slash fa-3x"></i>
                <h5 class="mt-3">No notifications yet</h5>
                <p>You'll see notifications here when faculty submit tasks or your submissions are verified.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
