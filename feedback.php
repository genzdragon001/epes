<?php
/**
 * Feedback & Communication Module
 * Two-way communication between faculty and evaluators/deans
 * Uses enhanced comments table with rating_period, is_read, reply_to, created_at
 */
include 'db_connect.php';

if (!isset($_SESSION['login_id'])) {
    header('location:login.php');
    exit;
}

$login_id   = $_SESSION['login_id'];
$login_type = $_SESSION['login_type'];

// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_feedback') {
    $faculty_id    = intval($_POST['faculty_id'] ?? 0);
    $comment_text  = trim($_POST['comment_text'] ?? '');
    $rating_period = $_POST['rating_period'] ?? '';
    $reply_to      = intval($_POST['reply_to'] ?? 0);
    
    if ($faculty_id > 0 && !empty($comment_text) && !empty($rating_period)) {
        $stmt = $conn->prepare("
            INSERT INTO comments (employee_id, rater_id, comment_text, rating_period, reply_to)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iissi', $faculty_id, $login_id, $comment_text, $rating_period, $reply_to);
        $stmt->execute();
        $stmt->close();
        
        // Notify the other party
        require_once 'notification_helper.php';
        if ($login_type == 0) {
            // Faculty commenting — notify their evaluator
            $eq = $conn->query("SELECT evaluator_id FROM employee_list WHERE id = $faculty_id LIMIT 1");
            $eval_id = $eq ? $eq->fetch_assoc()['evaluator_id'] : 0;
            if ($eval_id > 0) {
                $fname = $_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname'];
                create_notification($conn, $eval_id, 1, 'New Feedback', "{$fname} left a comment on their evaluation.", 'Info', 'index.php?page=feedback&faculty_id=' . $faculty_id);
            }
        } else {
            // Evaluator/dean commenting — notify the faculty
            $ename = $_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname'];
            create_notification($conn, $faculty_id, 0, 'New Feedback', "{$ename} left a comment on your evaluation.", 'Info', 'index.php?page=feedback');
        }
    }
    // Redirect to avoid re-submission
    $redirect = 'index.php?page=feedback';
    if ($login_type != 0 && $faculty_id > 0) $redirect .= '&faculty_id=' . $faculty_id;
    header('location:' . $redirect);
    exit;
}

// Determine faculty_id context
$faculty_id = isset($_GET['faculty_id']) ? intval($_GET['faculty_id']) : 0;
if ($login_type == 0) {
    $faculty_id = $login_id; // Faculty always sees their own
}

// Get available rating periods
$periods = [];
$rp_qry = $conn->query("SELECT DISTINCT rating_period FROM ratings WHERE period_type = 'IPCR' ORDER BY rating_period DESC");
while ($row = $rp_qry->fetch_assoc()) {
    $periods[] = $row['rating_period'];
}
$selected_period = $_GET['period'] ?? ($periods[0] ?? '');

// Get faculty info
$faculty_name = '';
$faculty_dept = '';
if ($faculty_id > 0) {
    $fq = $conn->query("SELECT e.*, d.department FROM employee_list e LEFT JOIN department_list d ON e.department_id = d.id WHERE e.id = $faculty_id LIMIT 1");
    if ($fq && $fq->num_rows > 0) {
        $f = $fq->fetch_assoc();
        $faculty_name = $f['lastname'] . ', ' . $f['firstname'] . ' ' . $f['middlename'];
        $faculty_dept = $f['department'] ?? '';
    }
}

// Fetch comments for this faculty+period
$comments = [];
if ($faculty_id > 0 && !empty($selected_period)) {
    $stmt = $conn->prepare("
        SELECT c.*, 
               CASE WHEN c.rater_id = " . intval($login_id) . " THEN 'You' 
                    WHEN c.rater_id IN (SELECT id FROM evaluator_list) THEN CONCAT(ev.firstname, ' ', ev.lastname)
                    ELSE CONCAT(el.firstname, ' ', el.lastname)
               END as author_name,
               CASE WHEN c.rater_id IN (SELECT id FROM evaluator_list) THEN 'Evaluator'
                    ELSE 'Faculty'
               END as author_role
        FROM comments c
        LEFT JOIN evaluator_list ev ON c.rater_id = ev.id
        LEFT JOIN employee_list el ON c.rater_id = el.id
        WHERE c.employee_id = ? AND c.rating_period = ?
        ORDER BY c.reply_to ASC, c.created_at ASC
    ");
    $stmt->bind_param('is', $faculty_id, $selected_period);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();
    
    // Mark comments as read for current user
    $stmt = $conn->prepare("UPDATE comments SET is_read = 1 WHERE employee_id = ? AND rating_period = ? AND rater_id != ? AND is_read = 0");
    $stmt->bind_param('isi', $faculty_id, $selected_period, $login_id);
    $stmt->execute();
    $stmt->close();
}

// For evaluators/deans: get list of faculty they can comment on
$faculty_list = [];
if ($login_type != 0) {
    if ($login_type == 1) {
        // Dean: all faculty
        $fq = $conn->query("SELECT id, CONCAT(lastname, ', ', firstname, ' ', middlename) as name FROM employee_list ORDER BY lastname, firstname");
    } else {
        // Evaluator: their assigned faculty
        $fq = $conn->query("SELECT id, CONCAT(lastname, ', ', firstname, ' ', middlename) as name FROM employee_list WHERE evaluator_id = $login_id ORDER BY lastname, firstname");
    }
    while ($row = $fq->fetch_assoc()) {
        $faculty_list[] = $row;
    }
}

// Organize comments into threads
$threads = [];
$replies = [];
foreach ($comments as $c) {
    if ($c['reply_to'] == 0) {
        $threads[$c['id']] = $c;
    } else {
        $replies[$c['reply_to']][] = $c;
    }
}
?>

<div class="col-lg-12">
    <div class="card card-outline card-teal">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-comments"></i> Feedback &amp; Communication
                <?php if ($faculty_name): ?>
                <small class="text-white-50 ml-2">— <?= htmlspecialchars($faculty_name) ?></small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            
            <!-- Controls -->
            <div class="row mb-3">
                <?php if ($login_type != 0 && !empty($faculty_list)): ?>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Faculty Member</label>
                        <select id="faculty_select" class="form-control" onchange="updateView()">
                            <option value="">— Select Faculty —</option>
                            <?php foreach ($faculty_list as $fl): 
                                $sel = ($fl['id'] == $faculty_id) ? 'selected' : '';
                            ?>
                            <option value="<?= $fl['id'] ?>" <?= $sel ?>><?= htmlspecialchars($fl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Rating Period</label>
                        <select id="period_select" class="form-control" onchange="updateView()">
                            <?php foreach ($periods as $p): 
                                $sel = ($p == $selected_period) ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($p) ?>" <?= $sel ?>><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($periods)): ?>
                            <option value="">No periods available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <?php if ($faculty_id > 0 && !empty($selected_period)): ?>
            
            <!-- Comment Threads -->
            <div class="mb-4">
                <?php if (empty($threads)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa fa-comment-slash fa-2x"></i>
                    <p class="mt-2">No feedback yet for this period. Start the conversation below.</p>
                </div>
                <?php else: ?>
                <?php foreach ($threads as $thread): 
                    $is_mine = ($thread['rater_id'] == $login_id);
                    $align = $is_mine ? 'right' : 'left';
                    $bg    = $is_mine ? 'bg-primary text-white' : 'bg-light';
                    $time  = date('M d, Y h:i A', strtotime($thread['created_at']));
                ?>
                <div class="d-flex mb-3 <?= $is_mine ? 'justify-content-end' : 'justify-content-start' ?>">
                    <div class="feedback-bubble <?= $bg ?>" style="max-width: 70%; border-radius: 12px; padding: 12px 16px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="<?= $is_mine ? 'text-white' : '' ?>"><?= htmlspecialchars($thread['author_name']) ?></strong>
                            <small class="<?= $is_mine ? 'text-white-50' : 'text-muted' ?>"><?= $time ?></small>
                        </div>
                        <small class="<?= $is_mine ? 'text-white-50' : 'text-muted' ?> d-block mb-1"><?= htmlspecialchars($thread['author_role']) ?></small>
                        <p class="mb-2"><?= nl2br(htmlspecialchars($thread['comment_text'])) ?></p>
                        <button class="btn btn-sm <?= $is_mine ? 'btn-outline-light' : 'btn-outline-secondary' ?> reply-btn" 
                                onclick="showReplyForm(<?= $thread['id'] ?>)">
                            <i class="fa fa-reply"></i> Reply
                        </button>
                        
                        <!-- Reply form (hidden by default) -->
                        <div id="reply_form_<?= $thread['id'] ?>" class="mt-2" style="display:none;">
                            <form method="POST" class="mb-0">
                                <input type="hidden" name="action" value="send_feedback">
                                <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                                <input type="hidden" name="rating_period" value="<?= htmlspecialchars($selected_period) ?>">
                                <input type="hidden" name="reply_to" value="<?= $thread['id'] ?>">
                                <textarea name="comment_text" class="form-control form-control-sm mb-2" rows="2" 
                                          placeholder="Write your reply..."></textarea>
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fa fa-paper-plane"></i> Send Reply
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" 
                                        onclick="document.getElementById('reply_form_<?= $thread['id'] ?>').style.display='none'">
                                    Cancel
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Replies -->
                <?php if (isset($replies[$thread['id']])): ?>
                <?php foreach ($replies[$thread['id']] as $reply): 
                    $r_is_mine = ($reply['rater_id'] == $login_id);
                    $r_align = $r_is_mine ? 'right' : 'left';
                    $r_bg    = $r_is_mine ? 'bg-info text-white' : 'bg-light';
                    $r_time  = date('M d, Y h:i A', strtotime($reply['created_at']));
                ?>
                <div class="d-flex mb-2 <?= $r_is_mine ? 'justify-content-end' : 'justify-content-start' ?>" style="margin-<?= $r_is_mine ? 'right' : 'left' ?>: 30px;">
                    <div class="feedback-bubble <?= $r_bg ?>" style="max-width: 65%; border-radius: 10px; padding: 10px 14px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="<?= $r_is_mine ? 'text-white' : '' ?>" style="font-size:0.9rem;"><?= htmlspecialchars($reply['author_name']) ?></strong>
                            <small class="<?= $r_is_mine ? 'text-white-50' : 'text-muted' ?>"><?= $r_time ?></small>
                        </div>
                        <p class="mb-0" style="font-size:0.9rem;"><?= nl2br(htmlspecialchars($reply['comment_text'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- New Comment Form -->
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fa fa-pen"></i> 
                        <?= $login_type == 0 ? 'Send Message to Your Evaluator' : 'Leave Feedback for ' . htmlspecialchars($faculty_name) ?>
                    </h6>
                    <form method="POST">
                        <input type="hidden" name="action" value="send_feedback">
                        <input type="hidden" name="faculty_id" value="<?= $faculty_id ?>">
                        <input type="hidden" name="rating_period" value="<?= htmlspecialchars($selected_period) ?>">
                        <input type="hidden" name="reply_to" value="0">
                        <div class="form-group">
                            <textarea name="comment_text" class="form-control" rows="3" 
                                      placeholder="Type your feedback or message here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-teal">
                            <i class="fa fa-paper-plane"></i> Send
                        </button>
                    </form>
                </div>
            </div>
            
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fa fa-comments fa-4x"></i>
                <h5 class="mt-3">Select a faculty member and rating period</h5>
                <p>View and send feedback for a specific evaluation period.</p>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<style>
    .feedback-bubble {
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        word-wrap: break-word;
    }
    .card-teal .card-header {
        background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
        color: white;
    }
    .btn-teal {
        background: #20c997;
        border-color: #20c997;
        color: white;
    }
    .btn-teal:hover {
        background: #1baa80;
        color: white;
    }
</style>

<script>
function updateView() {
    var fid = document.getElementById('faculty_select') ? 
              document.getElementById('faculty_select').value : 
              <?= $login_type == 0 ? $login_id : 0 ?>;
    var period = document.getElementById('period_select').value;
    var url = 'index.php?page=feedback&period=' + encodeURIComponent(period);
    if (fid) url += '&faculty_id=' + fid;
    window.location.href = url;
}

function showReplyForm(threadId) {
    var form = document.getElementById('reply_form_' + threadId);
    if (form.style.display === 'none') {
        form.style.display = 'block';
        form.querySelector('textarea').focus();
    } else {
        form.style.display = 'none';
    }
}
</script>
