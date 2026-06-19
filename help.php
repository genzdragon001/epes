<?php
/**
 * Help & Training Materials
 * User manual, FAQ, step-by-step guides, glossary — matching manuscript specs
 */
include 'db_connect.php';

// Seed help_docs table if empty
$check = $conn->query("SELECT COUNT(*) as c FROM help_docs");
if ($check && $check->fetch_assoc()['c'] == 0) {
    require_once 'help_system.php';
    initializeHelpContent();
}

// Get help articles from DB
$articles = [];
$cats = [];
$qry = $conn->query("SELECT * FROM help_docs WHERE is_active = 1 ORDER BY category, `order`");
while ($row = $qry->fetch_assoc()) {
    $articles[$row['category']][] = $row;
    if (!in_array($row['category'], $cats)) $cats[] = $row['category'];
}

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
                    <a href="index.php?page=help&cat=FAQ" class="btn btn-outline-info btn-sm btn-block mb-1">
                        <i class="fa fa-question-circle"></i> Frequently Asked Questions
                    </a>
                    <a href="index.php?page=help&cat=Glossary" class="btn btn-outline-secondary btn-sm btn-block">
                        <i class="fa fa-book"></i> Glossary of Terms
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
                    
                    <?php elseif ($active_cat == 'FAQ'): ?>
                    <!-- ===== FAQ SECTION (matching manuscript) ===== -->
                    <h4><i class="fa fa-question-circle"></i> Frequently Asked Questions</h4>
                    <hr>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="card mb-2">
                            <div class="card-header" id="faq1">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse1">
                                        <strong>Q1: Can I edit a submitted report?</strong>
                                    </button>
                                </h6>
                            </div>
                            <div id="faqCollapse1" class="collapse" data-parent="#faqAccordion">
                                <div class="card-body">
                                    <p><strong>A:</strong> Yes, if the evaluation period is still open. Go to <strong>Targets</strong> page, find your submitted task, and click <strong>Re-upload</strong>. The system will warn you about overwriting the existing submission and let you confirm the replacement.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-2">
                            <div class="card-header" id="faq2">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse2">
                                        <strong>Q2: Who can see my evaluation results?</strong>
                                    </button>
                                </h6>
                            </div>
                            <div id="faqCollapse2" class="collapse" data-parent="#faqAccordion">
                                <div class="card-body">
                                    <p><strong>A:</strong> Only authorized personnel — your assigned Program Head/Immediate Supervisor, your Dean, and the System Administrator. Other faculty members cannot view your ratings. The system enforces role-based access controls.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-2">
                            <div class="card-header" id="faq3">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse3">
                                        <strong>Q3: What if I miss the submission deadline?</strong>
                                    </button>
                                </h6>
                            </div>
                            <div id="faqCollapse3" class="collapse" data-parent="#faqAccordion">
                                <div class="card-body">
                                    <p><strong>A:</strong> Contact your Program Head immediately. Late submissions may be flagged in the system. Your evaluator can still accept and rate late submissions at their discretion, but it may affect your timeliness rating.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-2">
                            <div class="card-header" id="faq4">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse4">
                                        <strong>Q4: Is my data secure?</strong>
                                    </button>
                                </h6>
                            </div>
                            <div id="faqCollapse4" class="collapse" data-parent="#faqAccordion">
                                <div class="card-body">
                                    <p><strong>A:</strong> Yes. The system uses encrypted connections (SSL/TLS), role-based access controls, and secure password hashing (bcrypt). All data is stored in a protected database with daily automated backups. The system complies with the <strong>Data Privacy Act of 2012 (RA 10173)</strong>.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-2">
                            <div class="card-header" id="faq5">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse5">
                                        <strong>Q5: How are my ratings calculated?</strong>
                                    </button>
                                </h6>
                            </div>
                            <div id="faqCollapse5" class="collapse" data-parent="#faqAccordion">
                                <div class="card-body">
                                    <p><strong>A:</strong> Your evaluator rates each task on three criteria: <strong>Efficiency</strong> (resource utilization), <strong>Timeliness</strong> (meeting deadlines), and <strong>Quality</strong> (work quality). Each criterion is scored 1-5. The average of E+T+Q divided by 3 gives your per-task rating. Your overall IPCR rating is the weighted average across all function categories (Strategic, Core, Support) based on your position's percentage allocation.</p>
                                    <p><strong>Rating Scale:</strong></p>
                                    <table class="table table-sm table-bordered" style="font-size:0.85rem;">
                                        <tr><td>4.75 – 5.00</td><td><strong>OUTSTANDING</strong></td></tr>
                                        <tr><td>3.61 – 4.74</td><td><strong>VERY SATISFACTORY</strong></td></tr>
                                        <tr><td>2.61 – 3.60</td><td><strong>SATISFACTORY</strong></td></tr>
                                        <tr><td>1.61 – 2.60</td><td><strong>UNSATISFACTORY</strong></td></tr>
                                        <tr><td>Below 1.61</td><td><strong>POOR</strong></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-2">
                            <div class="card-header" id="faq6">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse6">
                                        <strong>Q6: What happens if I get 3 consecutive low ratings?</strong>
                                    </button>
                                </h6>
                            </div>
                            <div id="faqCollapse6" class="collapse" data-parent="#faqAccordion">
                                <div class="card-body">
                                    <p><strong>A:</strong> The system automatically flags faculty who receive 3 consecutive IPCR ratings of <strong>SATISFACTORY (2.60) or below</strong>. This triggers an <strong>Intervention Flag</strong> visible to Deans and Administrators. The flag indicates that the faculty member may need additional support, mentoring, or performance improvement planning.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-2">
                            <div class="card-header" id="faq7">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse7">
                                        <strong>Q7: How do I generate my IPCR form?</strong>
                                    </button>
                                </h6>
                            </div>
                            <div id="faqCollapse7" class="collapse" data-parent="#faqAccordion">
                                <div class="card-body">
                                    <p><strong>A:</strong> Go to <strong>IPCR Form</strong> in the sidebar menu. Select your rating period from the dropdown. The system displays your complete IPCR with all ratings, task accomplishments, and overall score. Use <strong>Print Preview</strong> for a printer-friendly version or <strong>Download PDF</strong> to save a digital copy.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-2">
                            <div class="card-header" id="faq8">
                                <h6 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse8">
                                        <strong>Q8: What browsers are supported?</strong>
                                    </button>
                                </h6>
                            </div>
                            <div id="faqCollapse8" class="collapse" data-parent="#faqAccordion">
                                <div class="card-body">
                                    <p><strong>A:</strong> The system works best on modern browsers: <strong>Google Chrome</strong>, <strong>Mozilla Firefox</strong>, and <strong>Microsoft Edge</strong>. The interface is mobile-responsive and works on tablets and smartphones. If you experience display issues, try clearing your browser cache or switching browsers.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($active_cat == 'Glossary'): ?>
                    <!-- ===== GLOSSARY (matching manuscript) ===== -->
                    <h4><i class="fa fa-book"></i> Glossary of Terms</h4>
                    <hr>
                    
                    <table class="table table-sm table-bordered">
                        <thead class="bg-dark text-white">
                            <tr><th width="25%">Term</th><th>Definition</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>IPCR</strong></td>
                                <td><strong>Individual Performance Commitment and Review</strong> — A standard tool required by the Civil Service Commission (CSC) under the Strategic Performance Management System (SPMS). It documents individual faculty performance targets, accomplishments, and ratings for a given evaluation period.</td>
                            </tr>
                            <tr>
                                <td><strong>DPCR</strong></td>
                                <td><strong>Department Performance Commitment and Review</strong> — Aggregates individual IPCR ratings at the department level. Shows per-department averages for Efficiency, Timeliness, and Quality.</td>
                            </tr>
                            <tr>
                                <td><strong>OPCR</strong></td>
                                <td><strong>Office Performance Commitment and Review</strong> — Office-wide aggregate of all faculty IPCR ratings. Used for institutional reporting and strategic planning.</td>
                            </tr>
                            <tr>
                                <td><strong>SPMS</strong></td>
                                <td><strong>Strategic Performance Management System</strong> — The CSC-mandated framework governing performance evaluation in Philippine government agencies and SUCs. Defines the four-stage cycle: Planning, Monitoring, Review, and Rewarding.</td>
                            </tr>
                            <tr>
                                <td><strong>Dashboard</strong></td>
                                <td>The main interface showing user-specific data including submission status, evaluation results, performance charts, and system notifications.</td>
                            </tr>
                            <tr>
                                <td><strong>Evaluation Period</strong></td>
                                <td>A designated timeframe (semester + academic year) for submitting and reviewing performance reports. The system supports 1st Semester, 2nd Semester, and Summer periods.</td>
                            </tr>
                            <tr>
                                <td><strong>Rating</strong></td>
                                <td>A quantitative assessment of performance based on institutional metrics. Each task is rated on three criteria: Efficiency, Timeliness, and Quality (scale 1-5).</td>
                            </tr>
                            <tr>
                                <td><strong>Efficiency (E)</strong></td>
                                <td>Measures how well resources (time, materials, budget) were utilized to accomplish the task. 5 = optimal resource use, 1 = wasteful.</td>
                            </tr>
                            <tr>
                                <td><strong>Timeliness (T)</strong></td>
                                <td>Measures whether the task was completed within the expected timeframe or deadline. 5 = ahead of schedule, 1 = severely delayed.</td>
                            </tr>
                            <tr>
                                <td><strong>Quality (Q)</strong></td>
                                <td>Measures the standard of work output — accuracy, completeness, and adherence to requirements. 5 = exceptional quality, 1 = unacceptable.</td>
                            </tr>
                            <tr>
                                <td><strong>Adjectival Rating</strong></td>
                                <td>The descriptive label corresponding to a numerical score: Outstanding (4.75-5.00), Very Satisfactory (3.61-4.74), Satisfactory (2.61-3.60), Unsatisfactory (1.61-2.60), Poor (below 1.61).</td>
                            </tr>
                            <tr>
                                <td><strong>MOV</strong></td>
                                <td><strong>Means of Verification</strong> — Supporting documents (PDF, DOCX, images, spreadsheets) uploaded as evidence of task completion.</td>
                            </tr>
                            <tr>
                                <td><strong>MFO</strong></td>
                                <td><strong>Major Final Output</strong> — The primary deliverables expected from a faculty member based on their position and designation.</td>
                            </tr>
                            <tr>
                                <td><strong>KRA</strong></td>
                                <td><strong>Key Result Area</strong> — Broad performance categories such as Instruction, Research, Extension, and Production.</td>
                            </tr>
                            <tr>
                                <td><strong>Cascading</strong></td>
                                <td>The process of aggregating individual IPCR ratings upward: IPCR → DP (per-department averages) and IPCR → OPCR (office-wide average). Both DP and OPCR are computed directly from individual IPCR data.</td>
                            </tr>
                            <tr>
                                <td><strong>Intervention Flag</strong></td>
                                <td>An automatic alert triggered when a faculty member receives 3 consecutive IPCR ratings of Satisfactory (2.60) or below. Indicates need for performance review and support.</td>
                            </tr>
                            <tr>
                                <td><strong>COS</strong></td>
                                <td><strong>Contract of Service</strong> — Faculty hired on a contractual basis. COS faculty have a separate renewal recommendation workflow.</td>
                            </tr>
                            <tr>
                                <td><strong>Percentage Allocation</strong></td>
                                <td>The weight distribution across function categories (Strategic, Core, Support) that determines how each category contributes to the overall rating. Varies by position and designation.</td>
                            </tr>
                            <tr>
                                <td><strong>Program Head / Immediate Supervisor</strong></td>
                                <td>The evaluator responsible for reviewing faculty submissions, assigning ratings, and providing feedback. Typically a Department Chair or senior faculty member.</td>
                            </tr>
                            <tr>
                                <td><strong>Dean</strong></td>
                                <td>The college-level administrator who oversees all department performance, reviews recommendations, and has access to DPCR/OPCR reports and analytics.</td>
                            </tr>
                        </tbody>
                    </table>
                    
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
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fa fa-question-circle fa-2x text-warning"></i>
                                    <h6 class="mt-2">FAQ</h6>
                                    <p class="small text-muted">Answers to common questions about the system</p>
                                    <a href="index.php?page=help&cat=FAQ" class="btn btn-sm btn-warning">View</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fa fa-book fa-2x text-success"></i>
                                    <h6 class="mt-2">Glossary</h6>
                                    <p class="small text-muted">Definitions of key terms and acronyms</p>
                                    <a href="index.php?page=help&cat=Glossary" class="btn btn-sm btn-success">View</a>
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
