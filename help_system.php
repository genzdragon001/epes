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
     * Get all articles by category
     */
    public function getArticlesByCategory($category = null) {
        if ($category) {
            $stmt = $this->db->prepare("SELECT * FROM help_docs WHERE category = ? AND is_active = 1 ORDER BY `order`");
            $stmt->bind_param('s', $category);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM help_docs WHERE is_active = 1 ORDER BY category, `order`");
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
    public function searchArticles($query) {
        $search = "%{$query}%";
        $stmt = $this->db->prepare("
            SELECT * FROM help_docs 
            WHERE (title LIKE ? OR content LIKE ?) AND is_active = 1
            ORDER BY category, `order`
        ");
        $stmt->bind_param('ss', $search, $search);
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
     * Get all categories
     */
    public function getCategories() {
        $result = $this->db->query("SELECT DISTINCT category FROM help_docs WHERE category IS NOT NULL AND is_active = 1 ORDER BY category");
        $categories = [];
        while($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        return $categories;
    }
}

/**
 * Initialize default help content
 */
function initializeHelpContent() {
    $help_items = [
        [
            'category' => 'Getting Started',
            'title' => 'System Overview',
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
        [
            'category' => 'Faculty Guide',
            'title' => 'How to Submit Tasks',
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
            'category' => 'Faculty Guide',
            'title' => 'Viewing Your Performance',
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
            'category' => 'Evaluator Guide',
            'title' => 'How to Verify Tasks',
            'content' => '
                <h3>Verifying Faculty Submissions</h3>
                <ol>
                    <li>Go to <strong>Faculty Evaluation</strong> menu</li>
                    <li>Select the faculty member</li>
                    <li>Review submitted tasks and evidence</li>
                    <li>Provide ratings for:</li>
                    <ul>
                        <li>Efficiency (1-5 scale)</li>
                        <li>Timeliness (1-5 scale)</li>
                        <li>Quality (1-5 scale)</li>
                    </ul>
                    <li>Add comments or feedback</li>
                    <li>Click <strong>Verify</strong> to approve</li>
                </ol>
                <h4>Rating Guidelines:</h4>
                <ul>
                    <li>5 - Outstanding</li>
                    <li>4 - Very Satisfactory</li>
                    <li>3 - Satisfactory</li>
                    <li>2 - Unsatisfactory</li>
                    <li>1 - Poor</li>
                </ul>
            ',
            'order' => 5
        ],
        [
            'category' => 'Evaluator Guide',
            'title' => 'Generating Recommendations',
            'content' => '
                <h3>Contract Renewal Recommendations</h3>
                <p>For COS faculty, evaluators must provide renewal recommendations:</p>
                <ol>
                    <li>Navigate to <strong>Recommendation</strong> menu</li>
                    <li>Review faculty performance data</li>
                    <li>System calculates overall score automatically</li>
                    <li>Provide recommendation:</li>
                    <ul>
                        <li><strong>Recommended</strong> - For renewal</li>
                        <li><strong>Not Recommended</strong> - Not for renewal</li>
                    </ul>
                    <li>Add statement of reason</li>
                    <li>Submit for Dean review</li>
                </ol>
            ',
            'order' => 6
        ],
        [
            'category' => 'Admin Guide',
            'title' => 'Managing Users',
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
                <h4>Resetting Password:</h4>
                <ol>
                    <li>Go to user list</li>
                    <li>Click <strong>Reset Password</strong></li>
                    <li>System sends reset link via email</li>
                </ol>
            ',
            'order' => 7
        ],
        [
            'category' => 'Admin Guide',
            'title' => 'Configuring Rating Period',
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
            'order' => 8
        ],
        [
            'category' => 'Reports',
            'title' => 'Generating IPCR Report',
            'content' => '
                <h3>Individual Performance Commitment and Review</h3>
                <ol>
                    <li>Go to your <strong>Dashboard</strong></li>
                    <li>Click <strong>Generate IPCR</strong></li>
                    <li>Select rating period</li>
                    <li>Review the generated form</li>
                    <li>Download as PDF</li>
                </ol>
                <p>The IPCR form includes:</p>
                <ul>
                    <li>Performance ratings by criterion</li>
                    <li>Task accomplishments</li>
                    <li>Overall score and adjectival rating</li>
                    <li>Signature sections</li>
                </ul>
            ',
            'order' => 9
        ],
        [
            'category' => 'Reports',
            'title' => 'Generating OPCR Summary',
            'content' => '
                <h3>Office Performance Commitment and Review</h3>
                <p>For Administrators and Deans:</p>
                <ol>
                    <li>Go to <strong>Reports > OPCR Summary</strong></li>
                    <li>Select department</li>
                    <li>Select rating period</li>
                    <li>Generate report</li>
                </ol>
                <p>The OPCR summary includes:</p>
                <ul>
                    <li>Department performance metrics</li>
                    <li>Faculty count and participation</li>
                    <li>Overall averages</li>
                    <li>Comparative analysis</li>
                </ul>
            ',
            'order' => 10
        ],
        [
            'category' => 'Troubleshooting',
            'title' => 'Common Issues',
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
            'order' => 11
        ]
    ];
    
    $db = getDB();
    foreach($help_items as $item) {
        $stmt = $db->prepare("
            INSERT INTO help_docs (category, title, content, `order`, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->bind_param('sssi', $item['category'], $item['title'], $item['content'], $item['order']);
        $stmt->execute();
        $stmt->close();
    }
}
