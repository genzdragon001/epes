<?php
/**
 * Help Documentation System
 * In-app help and user manual
 */

require_once 'config.php';
require_once 'db_connect.php';

class HelpSystem {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get help article by ID
     */
    public function getArticle($id) {
        $stmt = $this->db->prepare("SELECT * FROM help_docs WHERE id = ? AND is_active = 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $article = $result->fetch_assoc();
        $stmt->close();
        return $article;
    }
    
    /**
     * Get all articles by category, filtered by role
     */
    public function getArticlesByCategory($category = null, $role = 'all') {
        if ($category) {
            $stmt = $this->db->prepare("SELECT * FROM help_docs WHERE category = ? AND is_active = 1 AND (target_role = 'all' OR target_role = ?) ORDER BY `order`");
            $stmt->bind_param('ss', $category, $role);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM help_docs WHERE is_active = 1 AND (target_role = 'all' OR target_role = ?) ORDER BY category, `order`");
            $stmt->bind_param('s', $role);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $articles = [];
        while($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }
        $stmt->close();
        return $articles;
    }
    
    /**
     * Search articles
     */
    public function searchArticles($query, $role = 'all') {
        $search = "%{$query}%";
        $stmt = $this->db->prepare("
            SELECT * FROM help_docs 
            WHERE (title LIKE ? OR content LIKE ?) AND is_active = 1 AND (target_role = 'all' OR target_role = ?)
            ORDER BY category, `order`
        ");
        $stmt->bind_param('sss', $search, $search, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $articles = [];
        while($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }
        $stmt->close();
        return $articles;
    }
    
    /**
     * Get all categories visible to a role
     */
    public function getCategories($role = 'all') {
        $stmt = $this->db->prepare("SELECT DISTINCT category FROM help_docs WHERE category IS NOT NULL AND is_active = 1 AND (target_role = 'all' OR target_role = ?) ORDER BY category");
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        $stmt->close();
        return $categories;
    }
}

/**
 * Initialize default help content
 */
function initializeHelpContent() {
    $help_items = [
        // === ALL USERS ===
        [
            'category' => 'Getting Started',
            'title' => 'System Overview',
            'target_role' => 'all',
            'content' => '
                <h3>Welcome to EPES</h3>
                <p>The Employee Performance Evaluation System (EPES) is designed to streamline faculty performance documentation and evaluation at DEBESMSCAT.</p>
                <h4>Key Features:</h4>
                <ul>
                    <li>Digital IPCR submission and tracking</li>
                    <li>Automated performance calculations</li>
                    <li>Real-time progress monitoring</li>
                    <li>Comprehensive analytics and reporting</li>
                    <li>Evidence-based decision making support</li>
                </ul>
            ',
            'order' => 1
        ],
        [
            'category' => 'Getting Started',
            'title' => 'User Roles and Permissions',
            'target_role' => 'all',
            'content' => '
                <h3>User Roles</h3>
                <p>EPES has three main user roles:</p>
                <h4>1. Faculty Member</h4>
                <ul>
                    <li>Submit tasks and accomplishments</li>
                    <li>View performance ratings</li>
                    <li>Generate IPCR reports</li>
                    <li>Track progress in real-time</li>
                </ul>
                <h4>2. Evaluator/Department Head</h4>
                <ul>
                    <li>Review and verify faculty submissions</li>
                    <li>Provide ratings and feedback</li>
                    <li>Generate department reports</li>
                    <li>Monitor faculty performance</li>
                </ul>
                <h4>3. Administrator</h4>
                <ul>
                    <li>Manage users and roles</li>
                    <li>Configure system settings</li>
                    <li>Generate institutional reports</li>
                    <li>Oversee evaluation periods</li>
                </ul>
            ',
            'order' => 2
        ],
        
        // === FACULTY TRAINING ===
        [
            'category' => 'Faculty Training',
            'title' => 'How to Submit Tasks',
            'target_role' => 'faculty',
            'content' => '
                <h3>Submitting Your Tasks</h3>
                <ol>
                    <li>Navigate to <strong>Targets</strong> menu</li>
                    <li>Select the task you want to submit</li>
                    <li>Click <strong>Submit File</strong></li>
                    <li>Upload your supporting documents (PDF, DOC, DOCX, images)</li>
                    <li>Add progress notes if needed</li>
                    <li>Click <strong>Submit</strong></li>
                </ol>
                <h4>File Requirements:</h4>
                <ul>
                    <li>Maximum file size: 10MB</li>
                    <li>Allowed formats: PDF, DOC, DOCX, JPG, PNG, XLS, XLSX</li>
                    <li>Ensure files are clearly labeled</li>
                </ul>
            ',
            'order' => 3
        ],
        [
            'category' => 'Faculty Training',
            'title' => 'Viewing Your Performance',
            'target_role' => 'faculty',
            'content' => '
                <h3>Performance Dashboard</h3>
                <p>Your dashboard shows:</p>
                <ul>
                    <li><strong>Total Targets:</strong> Number of tasks assigned to your position</li>
                    <li><strong>Submitted:</strong> Tasks you have submitted</li>
                    <li><strong>For Verification:</strong> Tasks awaiting evaluator review</li>
                    <li><strong>Verified:</strong> Tasks approved by your evaluator</li>
                </ul>
                <h3>Performance Metrics:</h3>
                <ul>
                    <li><strong>Efficiency:</strong> How well you utilize resources</li>
                    <li><strong>Timeliness:</strong> Meeting deadlines</li>
                    <li><strong>Quality:</strong> Quality of work submitted</li>
                </ul>
            ',
            'order' => 4
        ],
        [
            'category' => 'Faculty Training',
            'title' => 'Understanding Your IPCR Rating',
            'target_role' => 'faculty',
            'content' => '
                <h3>Your IPCR Rating</h3>
                <p>Go to <strong>Rating</strong> in the sidebar to view your complete IPCR form. It shows:</p>
                <ul>
                    <li>Strategic, Core, and Support function breakdowns</li>
                    <li>Per-task ratings (Efficiency, Timeliness, Quality)</li>
                    <li>Weighted averages by function category</li>
                    <li>Overall adjectival rating</li>
                </ul>
                <h4>Export Options:</h4>
                <ul>
                    <li><strong>Print Preview</strong> — printer-friendly version</li>
                    <li><strong>Download PDF</strong> — save a digital copy</li>
                    <li><strong>Download Excel</strong> — spreadsheet format</li>
                </ul>
                <h4>Rating Scale:</h4>
                <table class="table table-sm table-bordered" style="font-size:0.85rem;">
                    <tr><td>4.75 – 5.00</td><td><strong>OUTSTANDING</strong></td></tr>
                    <tr><td>3.61 – 4.74</td><td><strong>VERY SATISFACTORY</strong></td></tr>
                    <tr><td>2.61 – 3.60</td><td><strong>SATISFACTORY</strong></td></tr>
                    <tr><td>1.61 – 2.60</td><td><strong>UNSATISFACTORY</strong></td></tr>
                    <tr><td>Below 1.61</td><td><strong>POOR</strong></td></tr>
                </table>
            ',
            'order' => 5
        ],
        
        // === EVALUATOR TRAINING ===
        [
            'category' => 'Evaluator Training',
            'title' => 'How to Evaluate Faculty',
            'target_role' => 'evaluator',
            'content' => '
                <h3>Evaluating Faculty Submissions</h3>
                <ol>
                    <li>Go to <strong>Faculty List</strong> in the sidebar</li>
                    <li>Click <strong>Check Evaluation</strong> on a faculty member</li>
                    <li>Review submitted tasks grouped by category (Strategic, Core, Support)</li>
                    <li>View uploaded MOV files by clicking <strong>View</strong></li>
                    <li>Rate each task on three criteria:</li>
                    <ul>
                        <li><strong>Efficiency</strong> (1-5) — resource utilization</li>
                        <li><strong>Quality</strong> (1-5) — work quality</li>
                        <li><strong>Timeliness</strong> (1-5) — meeting deadlines</li>
                    </ul>
                    <li>Set status to <strong>For Verification</strong> or <strong>Verified</strong></li>
                    <li>Add evaluator comments at the bottom</li>
                </ol>
                <h4>Rating Guidelines:</h4>
                <ul>
                    <li>5 - Outstanding: Exceeds all expectations</li>
                    <li>4 - Very Satisfactory: Exceeds most expectations</li>
                    <li>3 - Satisfactory: Meets expectations</li>
                    <li>2 - Unsatisfactory: Partially meets expectations</li>
                    <li>1 - Poor: Fails to meet expectations</li>
                </ul>
            ',
            'order' => 6
        ],
        [
            'category' => 'Evaluator Training',
            'title' => 'Understanding DPCR Reports',
            'target_role' => 'evaluator',
            'content' => '
                <h3>Department Performance Commitment and Review</h3>
                <p>The DPCR aggregates individual IPCR ratings at the department level.</p>
                <ol>
                    <li>Go to <strong>DPCR Forms</strong> in the sidebar</li>
                    <li>Select your department and rating period</li>
                    <li>View per-department averages for Efficiency, Timeliness, and Quality</li>
                    <li>Export as PDF or Excel for reporting</li>
                </ol>
                <p><strong>Note:</strong> Program Heads/Department Heads can view DPCR for their own department. Deans can view all DPCRs and the OPCR.</p>
            ',
            'order' => 7
        ],
        [
            'category' => 'Evaluator Training',
            'title' => 'COS Faculty Recommendations',
            'target_role' => 'evaluator',
            'content' => '
                <h3>Contract of Service Renewal Recommendations</h3>
                <p>For COS faculty, evaluators must provide renewal recommendations:</p>
                <ol>
                    <li>Navigate to <strong>Recommendation</strong> menu</li>
                    <li>Review faculty performance data</li>
                    <li>System calculates overall score automatically</li>
                    <li>Provide recommendation:</li>
                    <ul>
                        <li><strong>Recommended</strong> — For renewal</li>
                        <li><strong>Not Recommended</strong> — Not for renewal</li>
                    </ul>
                    <li>Add statement of reason</li>
                    <li>Submit for Dean review</li>
                </ol>
            ',
            'order' => 8
        ],
        
        // === ADMIN TRAINING ===
        [
            'category' => 'Admin Training',
            'title' => 'Managing Users',
            'target_role' => 'admin',
            'content' => '
                <h3>User Management</h3>
                <h4>Adding New Faculty:</h4>
                <ol>
                    <li>Go to <strong>Employees > Add New</strong></li>
                    <li>Fill in required information</li>
                    <li>Assign department and position</li>
                    <li>Assign evaluator</li>
                    <li>Save and notify user</li>
                </ol>
                <h4>Adding New Evaluator:</h4>
                <ol>
                    <li>Go to <strong>Evaluators > Add New</strong></li>
                    <li>Fill in required information</li>
                    <li>Set type: Program Head (0) or Dean (1)</li>
                    <li>Assign designation if applicable</li>
                    <li>Save</li>
                </ol>
                <h4>Resetting Password:</h4>
                <ol>
                    <li>Go to user list</li>
                    <li>Click <strong>Reset Password</strong></li>
                    <li>System sends reset link via email</li>
                </ol>
            ',
            'order' => 9
        ],
        [
            'category' => 'Admin Training',
            'title' => 'Configuring Rating Periods',
            'target_role' => 'admin',
            'content' => '
                <h3>Setting Rating Period</h3>
                <ol>
                    <li>Navigate to <strong>Rating Period</strong></li>
                    <li>Select semester:</li>
                    <ul>
                        <li>1st Semester</li>
                        <li>2nd Semester</li>
                        <li>Summer</li>
                    </ul>
                    <li>Enter academic year (e.g., 2024-2025)</li>
                    <li>Set as active period</li>
                </ol>
                <p><strong>Note:</strong> Only one rating period can be active at a time.</p>
            ',
            'order' => 10
        ],
        [
            'category' => 'Admin Training',
            'title' => 'Percentage Allocation Setup',
            'target_role' => 'admin',
            'content' => '
                <h3>Setting Percentage Allocations</h3>
                <p>Percentage allocations determine how each function category contributes to the overall IPCR rating.</p>
                <ol>
                    <li>Go to <strong>Percentage Allocation</strong> in the sidebar</li>
                    <li>For each rank group, set:</li>
                    <ul>
                        <li><strong>Strategic Functions</strong> — for designated positions only</li>
                        <li><strong>Core Functions</strong> — TER + Instruction + Research + Extension</li>
                        <li><strong>Support Functions</strong> — remaining percentage</li>
                    </ul>
                    <li>Grand Total must equal 100%</li>
                    <li>Click <strong>Save All Changes</strong></li>
                </ol>
                <p><strong>Rules:</strong></p>
                <ul>
                    <li>TER + Instruction = Instructions Total (must be 100% of instruction sub-items)</li>
                    <li>Instructions + Research + Extension = Core Total (must be 100% of core sub-items)</li>
                    <li>Strategic + Core + Support = 100% (grand total)</li>
                </ul>
            ',
            'order' => 11
        ],
        
        // === ALL USERS ===
        [
            'category' => 'Reports',
            'title' => 'Generating IPCR Report',
            'target_role' => 'all',
            'content' => '
                <h3>Individual Performance Commitment and Review</h3>
                <ol>
                    <li>Go to <strong>Rating</strong> in the sidebar</li>
                    <li>Your IPCR form displays automatically with all verified ratings</li>
                    <li>Use the action buttons:</li>
                    <ul>
                        <li><strong>Print Preview</strong> — opens a printer-friendly window</li>
                        <li><strong>Download PDF</strong> — save as PDF via browser print dialog</li>
                        <li><strong>Download Excel</strong> — spreadsheet format</li>
                    </ul>
                </ol>
                <p>The IPCR form includes:</p>
                <ul>
                    <li>Performance ratings by criterion (E, T, Q)</li>
                    <li>Task accomplishments with MOV references</li>
                    <li>Overall score and adjectival rating</li>
                    <li>Signature sections (Conforme, Reviewed by, Approved by)</li>
                </ul>
            ',
            'order' => 12
        ],
        [
            'category' => 'Reports',
            'title' => 'Generating OPCR Summary',
            'target_role' => 'all',
            'content' => '
                <h3>Office Performance Commitment and Review</h3>
                <p>For Deans and Administrators:</p>
                <ol>
                    <li>Go to <strong>OPCR Summary</strong> in the sidebar</li>
                    <li>Select rating period</li>
                    <li>View office-wide performance summary</li>
                    <li>Export as PDF or Excel</li>
                </ol>
                <p>The OPCR summary includes:</p>
                <ul>
                    <li>Department performance metrics</li>
                    <li>Faculty count and participation</li>
                    <li>Overall averages</li>
                    <li>Comparative analysis</li>
                </ul>
            ',
            'order' => 13
        ],
        
        // === ALL USERS ===
        [
            'category' => 'FAQ',
            'title' => 'Frequently Asked Questions',
            'target_role' => 'all',
            'content' => '
                <h3>Frequently Asked Questions</h3>
                
                <h4>Q1: Can I edit a submitted report?</h4>
                <p><strong>A:</strong> Yes, if the evaluation period is still open. Go to <strong>Targets</strong> page, find your submitted task, and click <strong>Re-upload</strong>. The system will warn you about overwriting the existing submission and let you confirm the replacement.</p>
                
                <h4>Q2: Who can see my evaluation results?</h4>
                <p><strong>A:</strong> Only authorized personnel — your assigned Program Head/Immediate Supervisor, your Dean, and the System Administrator. Other faculty members cannot view your ratings. The system enforces role-based access controls.</p>
                
                <h4>Q3: What if I miss the submission deadline?</h4>
                <p><strong>A:</strong> Contact your Program Head immediately. Late submissions may be flagged in the system. Your evaluator can still accept and rate late submissions at their discretion, but it may affect your timeliness rating.</p>
                
                <h4>Q4: Is my data secure?</h4>
                <p><strong>A:</strong> Yes. The system uses encrypted connections (SSL/TLS), role-based access controls, and secure password hashing (bcrypt). All data is stored in a protected database with daily automated backups. The system complies with the <strong>Data Privacy Act of 2012 (RA 10173)</strong>.</p>
                
                <h4>Q5: How are my ratings calculated?</h4>
                <p><strong>A:</strong> Your evaluator rates each task on three criteria: <strong>Efficiency</strong> (resource utilization), <strong>Timeliness</strong> (meeting deadlines), and <strong>Quality</strong> (work quality). Each criterion is scored 1-5. The average of E+T+Q divided by 3 gives your per-task rating. Your overall IPCR rating is the weighted average across all function categories (Strategic, Core, Support) based on your position\'s percentage allocation.</p>
                <p><strong>Rating Scale:</strong></p>
                <table class="table table-sm table-bordered" style="font-size:0.85rem;">
                    <tr><td>4.75 – 5.00</td><td><strong>OUTSTANDING</strong></td></tr>
                    <tr><td>3.61 – 4.74</td><td><strong>VERY SATISFACTORY</strong></td></tr>
                    <tr><td>2.61 – 3.60</td><td><strong>SATISFACTORY</strong></td></tr>
                    <tr><td>1.61 – 2.60</td><td><strong>UNSATISFACTORY</strong></td></tr>
                    <tr><td>Below 1.61</td><td><strong>POOR</strong></td></tr>
                </table>
                
                <h4>Q6: What happens if I get 3 consecutive low ratings?</h4>
                <p><strong>A:</strong> The system automatically flags faculty who receive 3 consecutive IPCR ratings of <strong>SATISFACTORY (2.60) or below</strong>. This triggers an <strong>Intervention Flag</strong> visible to Deans and Administrators. The flag indicates that the faculty member may need additional support, mentoring, or performance improvement planning.</p>
                
                <h4>Q7: How do I generate my IPCR form?</h4>
                <p><strong>A:</strong> Go to <strong>Rating</strong> in the sidebar menu. The system displays your complete IPCR with all ratings, task accomplishments, and overall score. Use <strong>Print Preview</strong> for a printer-friendly version or <strong>Download PDF</strong> to save a digital copy.</p>
                
                <h4>Q8: What browsers are supported?</h4>
                <p><strong>A:</strong> The system works best on modern browsers: <strong>Google Chrome</strong>, <strong>Mozilla Firefox</strong>, and <strong>Microsoft Edge</strong>. The interface is mobile-responsive and works on tablets and smartphones. If you experience display issues, try clearing your browser cache or switching browsers.</p>
            ',
            'order' => 15
        ],
        [
            'category' => 'Glossary',
            'title' => 'Glossary of Terms',
            'target_role' => 'all',
            'content' => '
                <h3>Glossary of Terms</h3>
                <table class="table table-sm table-bordered">
                    <thead class="bg-dark text-white">
                        <tr><th width="25%">Term</th><th>Definition</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>IPCR</strong></td><td><strong>Individual Performance Commitment and Review</strong> — A standard tool required by the Civil Service Commission (CSC) under the Strategic Performance Management System (SPMS). It documents individual faculty performance targets, accomplishments, and ratings for a given evaluation period.</td></tr>
                        <tr><td><strong>DPCR</strong></td><td><strong>Department Performance Commitment and Review</strong> — Aggregates individual IPCR ratings at the department level. Shows per-department averages for Efficiency, Timeliness, and Quality.</td></tr>
                        <tr><td><strong>OPCR</strong></td><td><strong>Office Performance Commitment and Review</strong> — Office-wide aggregate of all faculty IPCR ratings. Used for institutional reporting and strategic planning.</td></tr>
                        <tr><td><strong>SPMS</strong></td><td><strong>Strategic Performance Management System</strong> — The CSC-mandated framework governing performance evaluation in Philippine government agencies and SUCs. Defines the four-stage cycle: Planning, Monitoring, Review, and Rewarding.</td></tr>
                        <tr><td><strong>Dashboard</strong></td><td>The main interface showing user-specific data including submission status, evaluation results, performance charts, and system notifications.</td></tr>
                        <tr><td><strong>Evaluation Period</strong></td><td>A designated timeframe (semester + academic year) for submitting and reviewing performance reports. The system supports 1st Semester, 2nd Semester, and Summer periods.</td></tr>
                        <tr><td><strong>Rating</strong></td><td>A quantitative assessment of performance based on institutional metrics. Each task is rated on three criteria: Efficiency, Timeliness, and Quality (scale 1-5).</td></tr>
                        <tr><td><strong>Efficiency (E)</strong></td><td>Measures how well resources (time, materials, budget) were utilized to accomplish the task. 5 = optimal resource use, 1 = wasteful.</td></tr>
                        <tr><td><strong>Timeliness (T)</strong></td><td>Measures whether the task was completed within the expected timeframe or deadline. 5 = ahead of schedule, 1 = severely delayed.</td></tr>
                        <tr><td><strong>Quality (Q)</strong></td><td>Measures the standard of work output — accuracy, completeness, and adherence to requirements. 5 = exceptional quality, 1 = unacceptable.</td></tr>
                        <tr><td><strong>Adjectival Rating</strong></td><td>The descriptive label corresponding to a numerical score: Outstanding (4.75-5.00), Very Satisfactory (3.61-4.74), Satisfactory (2.61-3.60), Unsatisfactory (1.61-2.60), Poor (below 1.61).</td></tr>
                        <tr><td><strong>MOV</strong></td><td><strong>Means of Verification</strong> — Supporting documents (PDF, DOCX, images, spreadsheets) uploaded as evidence of task completion.</td></tr>
                        <tr><td><strong>MFO</strong></td><td><strong>Major Final Output</strong> — The primary deliverables expected from a faculty member based on their position and designation.</td></tr>
                        <tr><td><strong>KRA</strong></td><td><strong>Key Result Area</strong> — Broad performance categories such as Instruction, Research, Extension, and Production.</td></tr>
                        <tr><td><strong>Cascading</strong></td><td>The process of aggregating individual IPCR ratings upward: IPCR → DP (per-department averages) and IPCR → OPCR (office-wide average). Both DP and OPCR are computed directly from individual IPCR data.</td></tr>
                        <tr><td><strong>Intervention Flag</strong></td><td>An automatic alert triggered when a faculty member receives 3 consecutive IPCR ratings of Satisfactory (2.60) or below. Indicates need for performance review and support.</td></tr>
                        <tr><td><strong>COS</strong></td><td><strong>Contract of Service</strong> — Faculty hired on a contractual basis. COS faculty have a separate renewal recommendation workflow.</td></tr>
                        <tr><td><strong>Percentage Allocation</strong></td><td>The weight distribution across function categories (Strategic, Core, Support) that determines how each category contributes to the overall rating. Varies by position and designation.</td></tr>
                        <tr><td><strong>Program Head / Immediate Supervisor</strong></td><td>The evaluator responsible for reviewing faculty submissions, assigning ratings, and providing feedback. Typically a Department Chair or senior faculty member.</td></tr>
                        <tr><td><strong>Dean</strong></td><td>The college-level administrator who oversees all department performance, reviews recommendations, and has access to DPCR/OPCR reports and analytics.</td></tr>
                    </tbody>
                </table>
            ',
            'order' => 16
        ],
        
        // === ALL USERS ===
        [
            'category' => 'Troubleshooting',
            'title' => 'Common Issues',
            'target_role' => 'all',
            'content' => '
                <h3>Frequently Encountered Problems</h3>
                <h4>Cannot Login</h4>
                <ul>
                    <li>Verify email and password</li>
                    <li>Check if account is activated</li>
                    <li>Use "Forgot Password" if needed</li>
                    <li>Contact admin if account is locked</li>
                </ul>
                <h4>File Upload Failed</h4>
                <ul>
                    <li>Check file size (max 10MB)</li>
                    <li>Verify file format is allowed</li>
                    <li>Ensure stable internet connection</li>
                </ul>
                <h4>Cannot Submit Task</h4>
                <ul>
                    <li>Ensure task is assigned to you</li>
                    <li>Check if rating period is active</li>
                    <li>Verify all required fields are filled</li>
                </ul>
            ',
            'order' => 14
        ]
    ];
    
    $db = getDB();
    foreach($help_items as $item) {
        $stmt = $db->prepare("
            INSERT INTO help_docs (category, target_role, title, content, `order`, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->bind_param('ssssi', $item['category'], $item['target_role'], $item['title'], $item['content'], $item['order']);
        $stmt->execute();
        $stmt->close();
    }
}
