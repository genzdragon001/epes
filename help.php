<?php
/**
 * Help & Training Materials
 * User manual, FAQ, step-by-step guides, glossary — matching manuscript specs
 */
include 'db_connect.php';

// Determine user role for filtering training content
$login_type = $_SESSION['login_type'] ?? -1;
$user_role = ($login_type == 0) ? 'faculty' : (($login_type == 1) ? 'evaluator' : (($login_type == 2) ? 'admin' : 'all'));

// Seed help_docs table if empty
$check = $conn->query("SELECT COUNT(*) as c FROM help_docs");
if ($check && $check->fetch_assoc()['c'] == 0) {
    require_once 'help_system.php';
    initializeHelpContent();
}

// Get help articles from DB — filtered by user role
$articles = [];
$cats = [];
$stmt = $conn->prepare("SELECT * FROM help_docs WHERE is_active = 1 AND (target_role = 'all' OR target_role = ?) ORDER BY category, `order`");
$stmt->bind_param('s', $user_role);
$stmt->execute();
$qry = $stmt->get_result();
while ($row = $qry->fetch_assoc()) {
    $articles[$row['category']][] = $row;
    if (!in_array($row['category'], $cats)) $cats[] = $row['category'];
}
$stmt->close();

$active_cat = $_GET['cat'] ?? ($cats[0] ?? '');
$active_article = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<div class="col-lg-12">
    <div class="row">
        <!-- Sidebar navigation -->
        <div class="col-md-3">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="fa fa-book"></i> Help Topics</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($cats as $cat): 
                            $is_active = ($active_cat == $cat && !$active_article);
                        ?>
                        <a href="index.php?page=help&cat=<?= urlencode($cat) ?>" 
                           class="list-group-item list-group-item-action <?= $is_active ? 'active' : '' ?>">
                            <strong><?= htmlspecialchars($cat) ?></strong>
                        </a>
                        <?php if (isset($articles[$cat])): ?>
                            <?php foreach ($articles[$cat] as $a): 
                                $is_art_active = ($active_article == $a['id']);
                            ?>
                            <a href="index.php?page=help&cat=<?= urlencode($cat) ?>&id=<?= $a['id'] ?>" 
                               class="list-group-item list-group-item-action pl-4 <?= $is_art_active ? 'active' : '' ?>"
                               style="font-size:0.9rem;">
                                <?= htmlspecialchars($a['title']) ?>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="card mt-3">
                <div class="card-header bg-dark text-white">
                    <h6 class="card-title mb-0"><i class="fa fa-link"></i> Quick Links</h6>
                </div>
                <div class="card-body p-2">
                    <?php if ($user_role == 'faculty' && in_array('Faculty Training', $cats)): ?>
                    <a href="index.php?page=help&cat=Faculty Training" class="btn btn-outline-success btn-sm btn-block mb-1">
                        <i class="fa fa-user-graduate"></i> Faculty Training
                    </a>
                    <?php elseif ($user_role == 'evaluator' && in_array('Evaluator Training', $cats)): ?>
                    <a href="index.php?page=help&cat=Evaluator Training" class="btn btn-outline-warning btn-sm btn-block mb-1">
                        <i class="fa fa-clipboard-check"></i> Evaluator Training
                    </a>
                    <?php elseif ($user_role == 'admin' && in_array('Admin Training', $cats)): ?>
                    <a href="index.php?page=help&cat=Admin Training" class="btn btn-outline-danger btn-sm btn-block mb-1">
                        <i class="fa fa-cogs"></i> Admin Training
                    </a>
                    <?php endif; ?>
                    <a href="index.php?page=help&cat=Troubleshooting" class="btn btn-outline-secondary btn-sm btn-block">
                        <i class="fa fa-tools"></i> Troubleshooting
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Content area -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fa fa-info-circle"></i> 
                        <?= $active_article ? 'Help Article' : 'Help & Training Materials' ?>
                    </h5>
                    <small class="text-muted">EPES User Manual v1.0</small>
                </div>
                <div class="card-body">
                    
                    <?php if ($active_article > 0): 
                        // Show specific article
                        $art = null;
                        foreach ($articles as $cat_arts) {
                            foreach ($cat_arts as $a) {
                                if ($a['id'] == $active_article) { $art = $a; break 2; }
                            }
                        }
                        if ($art):
                    ?>
                        <h4><?= htmlspecialchars($art['title']) ?></h4>
                        <hr>
                        <div class="help-content">
                            <?= $art['content'] ?>
                        </div>
                        <div class="mt-4">
                            <a href="index.php?page=help&cat=<?= urlencode($art['category']) ?>" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to <?= htmlspecialchars($art['category']) ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Article not found.</div>
                    <?php endif; ?>
                    
                    <?php elseif (isset($articles[$active_cat])): ?>
                    <!-- Category overview -->
                    <h4><?= htmlspecialchars($active_cat) ?></h4>
                    <hr>
                    <?php foreach ($articles[$active_cat] as $a): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <a href="index.php?page=help&cat=<?= urlencode($active_cat) ?>&id=<?= $a['id'] ?>" class="text-dark">
                                    <i class="fa fa-file-alt"></i> <?= htmlspecialchars($a['title']) ?>
                                </a>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="help-content" style="max-height: 200px; overflow: hidden; position: relative;">
                                <?= $a['content'] ?>
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 40px; background: linear-gradient(transparent, white);"></div>
                            </div>
                            <a href="index.php?page=help&cat=<?= urlencode($active_cat) ?>&id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary mt-2">
                                Read Full Article <i class="fa fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php else: ?>
                    <!-- Landing page -->
                    <div class="text-center py-4">
                        <i class="fa fa-book-open fa-4x text-info"></i>
                        <h4 class="mt-3">EPES Help & Training Materials</h4>
                        <p class="text-muted">Select a topic from the sidebar to view guides, FAQs, and reference materials.</p>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fa fa-play-circle fa-2x text-primary"></i>
                                    <h6 class="mt-2">Getting Started</h6>
                                    <p class="small text-muted">System overview, user roles, login and navigation</p>
                                    <a href="index.php?page=help&cat=Getting Started" class="btn btn-sm btn-primary">View</a>
                                </div>
                            </div>
                        </div>
                        <?php if ($user_role == 'faculty' && in_array('Faculty Training', $cats)): ?>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fa fa-user-graduate fa-2x text-success"></i>
                                    <h6 class="mt-2">Faculty Training</h6>
                                    <p class="small text-muted">Task submission, performance viewing, IPCR ratings</p>
                                    <a href="index.php?page=help&cat=Faculty Training" class="btn btn-sm btn-success">View</a>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($user_role == 'evaluator' && in_array('Evaluator Training', $cats)): ?>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fa fa-clipboard-check fa-2x text-warning"></i>
                                    <h6 class="mt-2">Evaluator Training</h6>
                                    <p class="small text-muted">Faculty evaluation, DPCR reports, COS recommendations</p>
                                    <a href="index.php?page=help&cat=Evaluator Training" class="btn btn-sm btn-warning">View</a>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($user_role == 'admin' && in_array('Admin Training', $cats)): ?>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fa fa-cogs fa-2x text-danger"></i>
                                    <h6 class="mt-2">Admin Training</h6>
                                    <p class="small text-muted">User management, rating periods, percentage allocation</p>
                                    <a href="index.php?page=help&cat=Admin Training" class="btn btn-sm btn-danger">View</a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fa fa-tools fa-2x text-secondary"></i>
                                    <h6 class="mt-2">Troubleshooting</h6>
                                    <p class="small text-muted">Common issues and how to resolve them</p>
                                    <a href="index.php?page=help&cat=Troubleshooting" class="btn btn-sm btn-secondary">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .help-content h3 { font-size: 1.2rem; margin-top: 1rem; color: #1a1a2e; }
    .help-content h4 { font-size: 1.05rem; margin-top: 0.8rem; color: #333; }
    .help-content ul, .help-content ol { padding-left: 1.5rem; }
    .help-content li { margin-bottom: 0.3rem; }
    .help-content table { font-size: 0.9rem; }
    .list-group-item.active { background: #17a2b8; border-color: #17a2b8; }
</style>
