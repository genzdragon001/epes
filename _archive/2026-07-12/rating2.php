<?php
include 'db_connect.php';

class PerformanceEvaluation {
    private $conn;
    private $faculty_id;
    private $employee_position;
    private $tasks = [];
    private $config = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->faculty_id = $_SESSION['login_id'] ?? 0;
        $this->initializeConfiguration();
        $this->loadTasks();
    }
    
    private function initializeConfiguration() {
        $this->employee_position = $this->fetchEmployeePosition();
        $this->config = $this->getConfigurationByPosition();
    }
    
    private function fetchEmployeePosition() {
        $qry = $this->conn->query("
            SELECT position_id 
            FROM employee_list 
            WHERE id = '{$this->faculty_id}' 
            ORDER BY UNIX_TIMESTAMP(date_created) ASC 
            LIMIT 1
        ");
        return $qry->fetch_assoc()['position_id'] ?? null;
    }
    
    private function getConfigurationByPosition() {
        switch ($this->employee_position) {
            case '15': // COS Faculty
                return [
                    'mfo' => "1,0",
                    'instruction_percentage' => "A. INSTRUCTION",
                    'support_percentage' => "Submission of Statutory Requirements",
                    'number_of_instruction' => 7,
                    'number_of_support' => 10,
                    'instruction_rowspan' => 10,
                    'support_rowspan' => 5,
                    'support_index' => 7,
                    'inst_percent' => "100%",
                    'permanent_faculty' => false,
                    'isDesignated' => false
                ];
                
            default: // Permanent Faculty
                return [
                    'mfo' => "1,2,3,4,0",
                    'permanent_faculty' => true,
                    'instruction_percentage' => "A. INSTRUCTION (60%)",
                    'research_percentage' => "RESEARCH (20%)",
                    'extension_percentage' => "EXTENSION (20%)",
                    'support_percentage' => "Submission of Statutory Requirements",
                    'number_of_instruction' => 8,
                    'number_of_research' => 11,
                    'number_of_extension' => 14,
                    'number_of_support' => 20,
                    'isDesignated' => false,
                    'instruction_rowspan' => 11,
                    'research_rowspan' => 4,
                    'extension_rowspan' => 4,
                    'support_rowspan' => 6,
                    'support_index' => 14,
                    'inst_percent' => "60%",
                    'res_percent' => "20%",
                    'ext_percent' => "20%"
                ];
        }
    }
    
    private function loadTasks() {
        $qry = $this->conn->query("
            SELECT 
                tp.id AS progress_id,
                tp.task_id,
                tp.faculty_id,
                tp.progress,
                tp.date_created,
                t.targets_measures,
                t.success_indicators,
                r.efficiency AS rating_efficiency,
                r.timeliness AS rating_timeliness,
                r.quality AS rating_quality,
                t.efficiency AS task_efficiency,
                t.timeliness AS task_timeliness,
                t.quality AS task_quality,
                t.average AS task_average,
                t.mfo
            FROM task_progress tp
            INNER JOIN task_list t ON tp.task_id = t.id
            LEFT JOIN ratings r 
                ON tp.task_id = r.task_id 
               AND tp.faculty_id = r.employee_id
            WHERE tp.faculty_id = {$this->faculty_id} 
              AND tp.rating_period = '{$_SESSION['rating_period']}'
              AND t.mfo IN ({$this->config['mfo']})
            ORDER BY t.id ASC
        ");
        
        while ($row = $qry->fetch_assoc()) {
            $this->tasks[] = [
                'id' => $row['task_id'] ?? null,
                'success_indicators' => $row['success_indicators'] ?? '',
                'targets_measures' => $row['targets_measures'] ?? '',
                'average' => $this->calculateTaskAverage($row),
            ];
        }
    }
    
    private function calculateTaskAverage($task) {
        $criteria = [
            'quality' => ($task['task_quality'] == 'Applicable') ? $task['rating_quality'] : null,
            'efficiency' => ($task['task_efficiency'] == 'Applicable') ? $task['rating_efficiency'] : null,
            'timeliness' => ($task['task_timeliness'] == 'Applicable') ? $task['rating_timeliness'] : null,
        ];
        
        $sum = 0;
        $divisor = 0;
        
        foreach ($criteria as $value) {
            if (!is_null($value)) {
                $sum += $value;
                $divisor++;
            }
        }
        
        return ($task['task_average'] == 'Applicable' && $divisor > 0)
            ? number_format($sum / $divisor, 2)
            : 0;
    }
    
    public function calculateAverages() {
        $averages = [
            'instruction' => $this->calculateInstructionAverage(),
            'support' => $this->calculateSupportAverage()
        ];
        
        if ($this->config['permanent_faculty']) {
            $averages['research'] = $this->calculateResearchAverage();
            $averages['extension'] = $this->calculateExtensionAverage();
        }
        
        return $averages;
    }
    
    private function calculateInstructionAverage() {
        if (empty($this->tasks)) return "0.00";
        
        $a1 = $this->tasks[0]['average'] ?? 0;
        $a2_instruction = 0;
        $instruction_count = 0;
        
        for ($i = 1; $i < min($this->config['number_of_instruction'], count($this->tasks)); $i++) {
            $a2_instruction += $this->tasks[$i]['average'];
            $instruction_count++;
        }
        
        $a2_average = $instruction_count > 0 ? $a2_instruction / $instruction_count : 0;
        return number_format(($a1 + $a2_average) / 2, 2);
    }
    
    private function calculateResearchAverage() {
        return $this->calculateSectionAverage(
            $this->config['number_of_instruction'],
            $this->config['number_of_research']
        );
    }
    
    private function calculateExtensionAverage() {
        return $this->calculateSectionAverage(
            $this->config['number_of_research'],
            $this->config['number_of_extension']
        );
    }
    
    private function calculateSupportAverage() {
        return $this->calculateSectionAverage(
            $this->config['support_index'],
            $this->config['number_of_support']
        );
    }
    
    private function calculateSectionAverage($start_index, $end_index) {
        $sum = 0;
        $count = 0;
        
        for ($i = $start_index; $i < min($end_index, count($this->tasks)); $i++) {
            $sum += $this->tasks[$i]['average'];
            $count++;
        }
        
        return $count > 0 ? number_format($sum / $count, 2) : "0.00";
    }
    
    public function getConfig($key) {
        return $this->config[$key] ?? null;
    }
    
    public function getTasks() {
        return $this->tasks;
    }
    
    public function getComment() {
        $qry = $this->conn->query("
            SELECT comment_text FROM comments 
            WHERE employee_id = '{$this->faculty_id}' 
            ORDER BY id DESC LIMIT 1
        ");
        
        if ($qry && $qry->num_rows > 0) {
            $row = $qry->fetch_assoc();
            return htmlspecialchars($row['comment_text']);
        }
        
        return "<i>No comment yet.</i>";
    }
    
    // Public method to render the entire evaluation
    public function render() {
        $averages = $this->calculateAverages();
        
        // Calculate weighted scores
        $core_function = $this->config['permanent_faculty'] 
            ? ($averages['instruction'] + $averages['research'] + $averages['extension']) / 3 
            : $averages['instruction'];

        $core_weighted = $core_function * 0.9;
        $instruction_weighted = $averages['instruction'] * ($this->config['permanent_faculty'] ? 0.6 : 1.0);
        $support_weighted = $averages['support'] * 0.1;
        $total_rating = $support_weighted + $core_weighted;
        
        ob_start();
        ?>
        <div class="col-lg-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h5 class="card-title"><b>Performance Evaluation</b></h5>
                </div>

                <div class="card-body">
                    <!-- Performance Table -->
                    <table class="table table-bordered table-sm" id="list">
                        <thead class="bg-warning text-center">
                            <tr>
                                <th width="20%">MAJOR FINAL OUTPUT</th>
                                <th width="40%">SUCCESS INDICATORS (TARGETS + MEASURES)</th>
                                <th>AVE.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?= $this->renderPerformanceTable($averages) ?>
                        </tbody>
                    </table>

                   <!-- Top Section: Overall Rating Table and Rating Equivalent Table Side by Side -->
                 
                    <div class="row">
                        <!-- LEFT SIDE: Overall Rating Table (2/3 width) -->
                        <div class="col-md-8">
                            <?= $this->renderOverallRatingTable($averages, $core_function, $core_weighted, $total_rating) ?>
                        </div>

                        <!-- RIGHT SIDE: Rating Equivalent + Final Rating (1/3 width) -->
                        <div class="col-md-4">
                                <!-- Rating Equivalent Table -->
                                <div class="d-flex justify-content-center mb-3">
                                    <?= $this->renderRatingEquivalentTable() ?>
                                </div>

                                <!-- Final Rating Table (directly below, same width) -->
                                <div class="d-flex justify-content-center">
                                    <?= $this->renderFinalRatingTable($total_rating) ?>
                                </div>
                        </div>
                </div>



                    <!-- Comments Section -->
                    <?= $this->renderCommentsSection() ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function renderPerformanceTable($averages) {
        $tasks = $this->tasks;
        ob_start();
        ?>
        <tr><td colspan="3"><b>CORE FUNCTION (90%)</b></td></tr>

        <!-- Instruction Section -->
        <tr>
            <td rowspan="<?= $this->config['instruction_rowspan'] ?>">MFO 1. Higher Education<br>MFO 2. Advanced Education</td>
            <td colspan="2"><b><?= $this->config['instruction_percentage'] ?></b></td>
        </tr>

        <tr><td colspan="2"><b>A1. Teaching Effectiveness (50% of Instruction)</b></td></tr>

        <?php if (!empty($tasks[0])): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($tasks[0]['success_indicators']) ?><br>
                    <small><i><?= htmlspecialchars($tasks[0]['targets_measures']) ?></i></small>
                </td>
                <td class="text-center"><?= $tasks[0]['average'] ?></td>
            </tr>
        <?php endif; ?>

        <tr><td colspan="2"><b>A2. INSTRUCTION (50% of Instruction)</b></td></tr>

        <?php for ($i = 1; $i < min($this->config['number_of_instruction'], count($tasks)); $i++): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($tasks[$i]['success_indicators']) ?><br>
                    <small><i><?= htmlspecialchars($tasks[$i]['targets_measures']) ?></i></small>
                </td>
                <td class="text-center"><?= $tasks[$i]['average'] ?></td>
            </tr>
        <?php endfor; ?>

        <tr>
            <td></td>
            <td style="text-align: right;"><b><i>Instruction (Average)</i></b></td>
            <td class="text-center"><b><?= $averages['instruction'] ?></b></td>
        </tr>

        <?php if ($this->config['permanent_faculty']): ?>
            <!-- Research Section -->
            <tr>
                <td rowspan="<?= $this->config['research_rowspan'] ?>">MFO 3. Research and Development</td>
                <td colspan="2"><b><?= $this->config['research_percentage'] ?></b></td>
            </tr>

            <?php for ($i = $this->config['number_of_instruction']; $i < min($this->config['number_of_research'], count($tasks)); $i++): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($tasks[$i]['success_indicators']) ?><br>
                        <small><i><?= htmlspecialchars($tasks[$i]['targets_measures']) ?></i></small>
                    </td>
                    <td class="text-center"><?= $tasks[$i]['average'] ?></td>
                </tr>
            <?php endfor; ?>

            <tr>
                <td></td>
                <td style="text-align: right;"><b><i>Research (Average)</i></b></td>
                <td class="text-center"><b><?= $averages['research'] ?></b></td>
            </tr>

            <!-- Extension Section -->
            <tr>
                <td rowspan="<?= $this->config['extension_rowspan'] ?>">MFO 4. Extension Services and Community Outreach</td>
                <td colspan="2"><b><?= $this->config['extension_percentage'] ?></b></td>
            </tr>

            <?php for ($i = $this->config['number_of_research']; $i < min($this->config['number_of_extension'], count($tasks)); $i++): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($tasks[$i]['success_indicators']) ?><br>
                        <small><i><?= htmlspecialchars($tasks[$i]['targets_measures']) ?></i></small>
                    </td>
                    <td class="text-center"><?= $tasks[$i]['average'] ?></td>
                </tr>
            <?php endfor; ?>

            <tr>
                <td></td>
                <td style="text-align: right;"><b><i>Extension Services (Average)</i></b></td>
                <td class="text-center"><b><?= $averages['extension'] ?></b></td>
            </tr>
        <?php endif; ?>

        <!-- Support Function -->
        <tr><td colspan="3"><b>SUPPORT FUNCTION (10%)</b></td></tr>
        <tr><td rowspan="<?= $this->config['support_rowspan'] ?>"></td></tr>
        <tr><td colspan="2"><b><?= $this->config['support_percentage'] ?></b></td></tr>

        <?php for ($i = $this->config['support_index']; $i < min($this->config['number_of_support'], count($tasks)); $i++): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($tasks[$i]['success_indicators']) ?><br>
                    <small><i><?= htmlspecialchars($tasks[$i]['targets_measures']) ?></i></small>
                </td>
                <td class="text-center"><?= $tasks[$i]['average'] ?></td>
            </tr>
        <?php endfor; ?>

        <tr>
            <td></td>
            <td style="text-align: right;"><b><i>Support Function (Average)</i></b></td>
            <td class="text-center"><b><?= $averages['support'] ?></b></td>
        </tr>
        <?php
        return ob_get_clean();
    }
    
    private function renderOverallRatingTable($averages, $core_function, $core_weighted, $total_rating) {
        ob_start();
        ?>
        <table class="table table-bordered text-center align-middle" style="width:100%; border:1px solid #000; border-collapse:collapse;">
            <thead>
                <tr style="background-color:#f8b195; color:black;">
                    <th colspan="5" style="text-align:center; font-weight:bold;">OVER-ALL RATING</th>
                </tr>
                <tr style="background-color:#fff;">
                    <th style="width:25%;">&nbsp;</th>
                    <th style="width:20%;">Percentage based on weight of functions (%)</th>
                    <th style="width:20%;">Average of Actual Rating</th>
                    <th style="width:20%;">Portion of Rating in a given percentage</th>
                    <th style="width:15%;">Adjectival Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php if($this->config['isDesignated']): ?>
                    <tr>
                        <td style="font-weight:bold; text-align:left;">Strategic Functions</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                <?php endif; ?>

                <tr style="font-weight:bold;">
                    <td style="text-align:left;">Core Functions</td>
                    <td>90%</td>
                    <td><?= number_format($core_function, 2) ?></td>
                    <td><?= number_format($core_weighted, 2) ?></td>
                    <td><?= getAdjectivalRating($core_function) ?></td>
                </tr>

                <tr>
                    <td style="padding-left:40px;">Instruction</td>
                    <td><?= $this->config['inst_percent'] ?></td>
                    <td><?= number_format($averages['instruction'], 2) ?></td>
                    <td><?= number_format($averages['instruction'] * ($this->config['permanent_faculty'] ? 0.6 : 1.0), 2) ?></td>
                    <td><?= getAdjectivalRating($averages['instruction']) ?></td>
                </tr>

                <?php if($this->config['permanent_faculty']): ?>
                    <tr>
                        <td style="padding-left:40px;">Research</td>
                        <td><?= $this->config['res_percent'] ?></td>
                        <td><?= number_format($averages['research'], 2) ?></td>
                        <td><?= number_format($averages['research'] * 0.2, 2) ?></td>
                        <td><?= getAdjectivalRating($averages['research']) ?></td>
                    </tr>

                    <tr>
                        <td style="padding-left:40px;">Extension</td>
                        <td><?= $this->config['ext_percent'] ?></td>
                        <td><?= number_format($averages['extension'], 2) ?></td>
                        <td><?= number_format($averages['extension'] * 0.2, 2) ?></td>
                        <td><?= getAdjectivalRating($averages['extension']) ?></td>
                    </tr>
                <?php endif; ?>

                <tr style="font-weight:bold;">
                    <td style="text-align:left;">Support Functions</td>
                    <td>10%</td>
                    <td><?= number_format($averages['support'], 2) ?></td>
                    <td><?= number_format($averages['support'] * 0.1, 2) ?></td>
                    <td><?= getAdjectivalRating($averages['support']) ?></td>
                </tr>

                <tr style="background-color:#fff3cd; font-weight:bold;">
                    <td colspan="3" style="text-align:right;">TOTAL</td>
                    <td><?= number_format($total_rating, 2) ?></td>
                    <td><?= getAdjectivalRating($total_rating) ?></td>
                </tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
    
    private function renderRatingEquivalentTable() {
        ob_start();
        ?>
        <table class="table table-bordered text-center mt-3" style="width:100%; border:1px solid #000; border-collapse:collapse;">
            <thead>
                <tr style="background-color:#f0f0f0;">
                    <th colspan="2">RATING EQUIVALENT</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>4.75 to 5.00</td><td>OUTSTANDING</td></tr>
                <tr><td>3.61 to 4.74</td><td>VERY SATISFACTORY</td></tr>
                <tr><td>2.61 to 3.30</td><td>SATISFACTORY</td></tr>
                <tr><td>1.61 to 2.60</td><td>UNSATISFACTORY</td></tr>
                <tr><td>1.60 and below</td><td>POOR</td></tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
    
    private function renderFinalRatingTable($total_rating) {
        ob_start();
        ?>
        <table class="table table-bordered text-center" style="width:100%; border:1px solid #000; border-collapse:collapse;">
            <thead style="background-color:#9edff0; font-weight:bold;">
                <tr>
                    <th>FINAL RATING</th>
                    <th>ADJECTIVAL RATING</th>
                </tr>
            </thead>
            <tbody>
                <tr style="font-weight: bold;">
                    <td style="height:40px;"><?= number_format($total_rating, 2) ?></td>
                    <td><?= getAdjectivalRating($total_rating) ?></td>
                </tr>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
    
    private function renderCommentsSection() {
        ob_start();
        ?>
        <table style="width:100%; border-collapse:collapse; margin-top:20px;">
            <tr>
                <td style="border:1px solid black; height:150px; vertical-align:top; padding:10px;">
                    <b>Rater's comments and recommendations for development purposes or rewards/promotion:</b>
                    <br><br>
                    <div style="min-height:100px;">
                        <?= $this->getComment() ?>
                    </div>
                </td>
            </tr>
        </table>
        <?php
        return ob_get_clean();
    }
}

// Utility Functions (outside the class)
function getAdjectivalRating($score) {
    if (!is_numeric($score) || $score <= 0) {
        return "NO RATING";
    }

    $score = round($score, 2);

    if ($score >= 4.75 && $score <= 5.00) return "OUTSTANDING";
    if ($score >= 3.61 && $score <= 4.74) return "VERY SATISFACTORY";
    if ($score >= 2.61 && $score <= 3.60) return "SATISFACTORY";
    if ($score >= 1.61 && $score <= 2.60) return "UNSATISFACTORY";
    if ($score <= 1.60) return "POOR";
    
    return "NO RATING";
}

function safeAverage($total, $count) {
    return ($count > 0) ? number_format($total / $count, 2) : "0.00";
}

// Initialize and render the evaluation
$evaluation = new PerformanceEvaluation($conn);
echo $evaluation->render();
?>