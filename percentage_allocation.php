<?php include 'db_connect.php' ?>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-percent"></i> Faculty Percentage Allocation by Academic Rank</h5>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-success" id="save_all">
                    <i class="fa fa-save"></i> Save All Changes
                </button>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted">Set percentage allocations. Core Functions sub-items (Instructions, Research, Extension) must total to 100%. Click "Save All Changes" when done.</p>
            
            <?php
            function getAllocation($conn, $position_id, $designation_id, $category, $sub_category) {
                $designation_id = ($designation_id === '' || $designation_id === null) ? 3 : intval($designation_id);
                $sql = "SELECT percentage FROM percentage_allocation 
                        WHERE position_id = $position_id 
                        AND designation_id = $designation_id
                        AND category = '$category'";
                if($sub_category) {
                    $sql .= " AND sub_category = '$sub_category'";
                } else {
                    $sql .= " AND (sub_category IS NULL OR sub_category = '')";
                }
                $sql .= " LIMIT 1";
                $qry = $conn->query($sql);
                if($qry && $qry->num_rows > 0) {
                    return floatval($qry->fetch_assoc()['percentage']);
                }
                return 0;
            }
            
            $rank_groups = [
                ['name' => 'Instructor I - Assistant Professor IV', 'ids' => [1, 2, 3, 4, 5, 6, 7], 'strategic_enabled' => true],
                ['name' => 'Associate Professor I-V', 'ids' => [8, 9, 10, 11, 12], 'strategic_enabled' => true],
                ['name' => 'Professor I-VI', 'ids' => [13, 14, 15, 16, 17, 18], 'strategic_enabled' => true],
                ['name' => 'Contract of Service', 'ids' => [19], 'strategic_enabled' => false, 'no_designation' => true],
            ];
            ?>
            
            <form id="allocationForm">
                <?php foreach($rank_groups as $group_idx => $group): ?>
                <div class="card card-outline card-info mb-4">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?php echo $group['name']; ?>
                                <?php if(isset($group['no_designation']) && $group['no_designation']): ?>
                                <span class="badge badge-warning ml-2">No Designation</span>
                                <?php endif; ?>
                            </h5>
                            <button type="button" class="btn btn-sm btn-warning save-group" data-group="<?php echo $group_idx; ?>">
                                <i class="fa fa-save"></i> Save <?php echo $group['name']; ?>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0" id="table_<?php echo $group_idx; ?>">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 25%;">Function</th>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <th class="text-center" style="width: 37.5%;">Without Designation</th>
                                    <th class="text-center" style="width: 37.5%;">With Designation</th>
                                    <?php else: ?>
                                    <th class="text-center" style="width: 75%;">Percentage</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($group['strategic_enabled']): ?>
                                <tr>
                                    <td><strong>Strategic Functions</strong></td>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto strategic-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="strategic"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 90; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'strategic', null) == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto strategic-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="2"
                                                data-category="strategic"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 90; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 2, 'strategic', null) == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-50 mx-auto strategic-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="strategic"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 90; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'strategic', null) == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endif; ?>
                                
                                <tr class="table-secondary">
                                    <td colspan="3"><strong>Core Functions</strong></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;<em>Total Core (must be 100%)</em></td>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <td class="text-center bg-light">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto core-total-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="core"
                                                data-sub="total"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'core', 'total') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center bg-light">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto core-total-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="2"
                                                data-category="core"
                                                data-sub="total"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 2, 'core', 'total') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center bg-light">
                                        <select class="form-control form-control-sm alloc-select w-50 mx-auto core-total-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="core"
                                                data-sub="total"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'core', 'total') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                
                                <tr class="table-light">
                                    <td colspan="3"><strong>&nbsp;&nbsp;&nbsp;&nbsp;Instructions</strong> <em>(TER + Instruction = Instructions Total)</em></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Instructions (Total)</strong> <small>(computed)</small></td>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <td class="text-center bg-light">
                                        <span class="badge badge-lg instructions-total-display" data-designation="3">0%</span>
                                    </td>
                                    <td class="text-center bg-light">
                                        <span class="badge badge-lg instructions-total-display" data-designation="2">0%</span>
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center bg-light">
                                        <span class="badge badge-lg instructions-total-display" data-designation="3">0%</span>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;TER</td>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto instr-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="core"
                                                data-sub="ter"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'core', 'ter') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto instr-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="2"
                                                data-category="core"
                                                data-sub="ter"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 2, 'core', 'ter') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-50 mx-auto instr-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="core"
                                                data-sub="ter"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'core', 'ter') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Instruction</td>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto instr-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="core"
                                                data-sub="instruction"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'core', 'instruction') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto instr-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="2"
                                                data-category="core"
                                                data-sub="instruction"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 2, 'core', 'instruction') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-50 mx-auto instr-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="core"
                                                data-sub="instruction"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'core', 'instruction') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <tr class="instr-subtotal-row">
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Instructions Subtotal</strong></td>
                                    <?php
                                    $instr_sub_no = getAllocation($conn, $group['ids'][0], 3, 'core', 'ter') + getAllocation($conn, $group['ids'][0], 3, 'core', 'instruction');
                                    $instr_sub_yes = getAllocation($conn, $group['ids'][0], 2, 'core', 'ter') + getAllocation($conn, $group['ids'][0], 2, 'core', 'instruction');
                                    ?>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <td class="text-center">
                                        <span class="badge badge-lg <?php echo $instr_sub_no == 100 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $instr_sub_no; ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-lg <?php echo $instr_sub_yes == 100 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $instr_sub_yes; ?>%
                                        </span>
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center">
                                        <span class="badge badge-lg <?php echo $instr_sub_no == 100 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $instr_sub_no; ?>%
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                <tr>
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Research</td>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto core-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="core"
                                                data-sub="research"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'core', 'research') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto core-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="2"
                                                data-category="core"
                                                data-sub="research"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 2, 'core', 'research') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Extension</td>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto core-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="core"
                                                data-sub="extension"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'core', 'extension') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto core-sub-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="2"
                                                data-category="core"
                                                data-sub="extension"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 2, 'core', 'extension') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr class="core-subtotal-row">
                                    <td>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Core Subtotal</strong></td>
                                    <?php
                                    $core_sub_no = getAllocation($conn, $group['ids'][0], 3, 'core', 'instructions') + (isset($group['no_designation']) && $group['no_designation'] ? 0 : getAllocation($conn, $group['ids'][0], 3, 'core', 'research') + getAllocation($conn, $group['ids'][0], 3, 'core', 'extension'));
                                    $core_sub_yes = getAllocation($conn, $group['ids'][0], 2, 'core', 'instructions') + (isset($group['no_designation']) && $group['no_designation'] ? 0 : getAllocation($conn, $group['ids'][0], 2, 'core', 'research') + getAllocation($conn, $group['ids'][0], 2, 'core', 'extension'));
                                    ?>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <td class="text-center">
                                        <span class="badge badge-lg <?php echo $core_sub_no == 100 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $core_sub_no; ?>% (of Core)
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-lg <?php echo $core_sub_yes == 100 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $core_sub_yes; ?>% (of Core)
                                        </span>
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center">
                                        <span class="badge badge-lg <?php echo $core_sub_no == 100 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $core_sub_no; ?>% (of Core)
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                
                                <tr class="table-secondary">
                                    <td colspan="3"><strong>Support Functions</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Support Functions</strong></td>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto support-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="support"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'support', null) == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-75 mx-auto support-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="2"
                                                data-category="support"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 2, 'support', null) == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm alloc-select w-50 mx-auto support-select" 
                                                data-positions='<?php echo json_encode($group['ids']); ?>' 
                                                data-designation="3"
                                                data-category="support"
                                                data-group="<?php echo $group_idx; ?>">
                                            <option value="0">0%</option>
                                            <?php for($i = 5; $i <= 100; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], 3, 'support', null) == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                
                                <tr class="table-primary font-weight-bold total-row">
                                    <td>GRAND TOTAL</td>
                                    <?php
                                    $no_strategic = $group['strategic_enabled'] ? getAllocation($conn, $group['ids'][0], 3, 'strategic', null) : 0;
                                    $core_total_no = getAllocation($conn, $group['ids'][0], 3, 'core', 'total');
                                    $support_total_no = getAllocation($conn, $group['ids'][0], 3, 'support', null);
                                    $grand_total_no = $no_strategic + $core_total_no + $support_total_no;
                                    
                                    $yes_strategic = $group['strategic_enabled'] ? getAllocation($conn, $group['ids'][0], 2, 'strategic', null) : 0;
                                    $core_total_yes = getAllocation($conn, $group['ids'][0], 2, 'core', 'total');
                                    $support_total_yes = getAllocation($conn, $group['ids'][0], 2, 'support', null);
                                    $grand_total_yes = $yes_strategic + $core_total_yes + $support_total_yes;
                                    ?>
                                    <?php if(!isset($group['no_designation']) || !$group['no_designation']): ?>
                                    <td class="text-center">
                                        <span class="badge badge-lg <?php echo $grand_total_no == 100 ? 'badge-success' : ($grand_total_no > 100 ? 'badge-danger' : 'badge-warning'); ?>">
                                            <?php echo $grand_total_no; ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-lg <?php echo $grand_total_yes == 100 ? 'badge-success' : ($grand_total_yes > 100 ? 'badge-danger' : 'badge-warning'); ?>">
                                            <?php echo $grand_total_yes; ?>%
                                        </span>
                                    </td>
                                    <?php else: ?>
                                    <td class="text-center">
                                        <span class="badge badge-lg <?php echo $grand_total_no == 100 ? 'badge-success' : ($grand_total_no > 100 ? 'badge-danger' : 'badge-warning'); ?>">
                                            <?php echo $grand_total_no; ?>%
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> <strong>Legend:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Instructions:</strong> TER + Instruction must equal 100%</li>
                        <li><strong>Core Sub-items:</strong> Instructions + Research + Extension must equal 100%</li>
                        <li><strong>Grand Total:</strong> Strategic + Core + Support = 100%</li>
                        <li><strong>Strategic Functions:</strong> N/A for "Without Designation" and "Instructor I-III"</li>
                    </ul>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('.alloc-select').change(function(){
        var $select = $(this);
        var groupIdx = $select.data('group');
        updateTotals(groupIdx);
        $select.closest('.card').find('.save-group').removeClass('btn-warning').addClass('btn-danger');
    });
    
    function updateTotals(groupIdx) {
        var $table = $('#table_' + groupIdx);
        var hasTwoColumns = $table.find('thead th').length === 3;
        
        // Calculate Instructions Subtotal (TER + Instruction)
        var instrSubNo = 0, instrSubYes = 0;
        $table.find('.instr-sub-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) instrSubNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) instrSubYes += parseFloat($sel.val()) || 0;
        });
        
        // Update Instructions Total display (TER + Instruction)
        var instrTotalNo = 0, instrTotalYes = 0;
        $table.find('.instr-sub-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) instrTotalNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) instrTotalYes += parseFloat($sel.val()) || 0;
        });
        $table.find('.instructions-total-display[data-designation="3"]').text(instrTotalNo + '%').removeClass('badge-success badge-warning').addClass(instrTotalNo == 100 ? 'badge-success' : 'badge-warning');
        if(hasTwoColumns) {
            $table.find('.instructions-total-display[data-designation="2"]').text(instrTotalYes + '%').removeClass('badge-success badge-warning').addClass(instrTotalYes == 100 ? 'badge-success' : 'badge-warning');
        }
        
        // Update Instructions Subtotal badge
        var $instrRow = $table.find('.instr-subtotal-row');
        if(hasTwoColumns) {
            $instrRow.find('td:eq(1) span').text(instrSubNo + '%').removeClass('badge-success badge-warning').addClass(instrSubNo == 100 ? 'badge-success' : 'badge-warning');
            $instrRow.find('td:eq(2) span').text(instrSubYes + '%').removeClass('badge-success badge-warning').addClass(instrSubYes == 100 ? 'badge-success' : 'badge-warning');
        } else {
            $instrRow.find('td:eq(1) span').text(instrSubNo + '%').removeClass('badge-success badge-warning').addClass(instrSubNo == 100 ? 'badge-success' : 'badge-warning');
        }
        
        // Calculate Core Subtotal (TER + Instruction + Research + Extension)
        var coreSubNo = 0, coreSubYes = 0;
        $table.find('.instr-sub-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) coreSubNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) coreSubYes += parseFloat($sel.val()) || 0;
        });
        $table.find('.core-sub-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) coreSubNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) coreSubYes += parseFloat($sel.val()) || 0;
        });
        
        // Update Core Subtotal
        var $coreRow = $table.find('.core-subtotal-row');
        if(hasTwoColumns) {
            $coreRow.find('td:eq(1) span').text(coreSubNo + '% (of Core)').removeClass('badge-success badge-warning').addClass(coreSubNo == 100 ? 'badge-success' : 'badge-warning');
            $coreRow.find('td:eq(2) span').text(coreSubYes + '% (of Core)').removeClass('badge-success badge-warning').addClass(coreSubYes == 100 ? 'badge-success' : 'badge-warning');
        } else {
            $coreRow.find('td:eq(1) span').text(coreSubNo + '% (of Core)').removeClass('badge-success badge-warning').addClass(coreSubNo == 100 ? 'badge-success' : 'badge-warning');
        }
        
        // Calculate Grand Total = Strategic + Core Total + Support Total
        var strategicNo = 0, strategicYes = 0;
        $table.find('.strategic-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) strategicNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) strategicYes += parseFloat($sel.val()) || 0;
        });
        
        var coreTotalNo = 0, coreTotalYes = 0;
        $table.find('.core-total-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) coreTotalNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) coreTotalYes += parseFloat($sel.val()) || 0;
        });
        
        var supportNo = 0, supportYes = 0;
        $table.find('.support-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) supportNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) supportYes += parseFloat($sel.val()) || 0;
        });
        
        var grandTotalNo = strategicNo + coreTotalNo + supportNo;
        var grandTotalYes = strategicYes + coreTotalYes + supportYes;
        
        // Update Grand Total
        var $totalRow = $table.find('.total-row');
        if(hasTwoColumns) {
            $totalRow.find('td:eq(1) span').text(grandTotalNo + '%').removeClass('badge-success badge-warning badge-danger').addClass(grandTotalNo == 100 ? 'badge-success' : (grandTotalNo > 100 ? 'badge-danger' : 'badge-warning'));
            $totalRow.find('td:eq(2) span').text(grandTotalYes + '%').removeClass('badge-success badge-warning badge-danger').addClass(grandTotalYes == 100 ? 'badge-success' : (grandTotalYes > 100 ? 'badge-danger' : 'badge-warning'));
        } else {
            $totalRow.find('td:eq(1) span').text(grandTotalNo + '%').removeClass('badge-success badge-warning badge-danger').addClass(grandTotalNo == 100 ? 'badge-success' : (grandTotalNo > 100 ? 'badge-danger' : 'badge-warning'));
        }
    }
    
    $('.save-group').click(function(){
        var $btn = $(this);
        var $card = $btn.closest('.card');
        var groupIdx = $card.find('.alloc-select').first().data('group');
        var $table = $('#table_' + groupIdx);
        var hasTwoColumns = $table.find('thead th').length === 3;
        
        // Calculate Instructions subtotal (TER + Instruction)
        var instrSubNo = 0, instrSubYes = 0;
        $table.find('.instr-sub-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) instrSubNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) instrSubYes += parseFloat($sel.val()) || 0;
        });
        
        // Removed: TER + Instruction must total 100% restriction
        // This allows flexible allocation where Instructions Total can be any value
        // as long as overall Core sub-items still total 100%.
        
        // Calculate core subtotal (TER + Instruction + Research + Extension)
        var coreSubNo = 0, coreSubYes = 0;
        $table.find('.instr-sub-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) coreSubNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) coreSubYes += parseFloat($sel.val()) || 0;
        });
        $table.find('.core-sub-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) coreSubNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) coreSubYes += parseFloat($sel.val()) || 0;
        });
        
        if(coreSubNo != 100 || (hasTwoColumns && coreSubYes != 100)) {
            showToast("Cannot save! Core sub-items must total 100%.", "danger");
            return;
        }
        
        // Calculate grand total for validation
        var strategicNo = 0, strategicYes = 0;
        $table.find('.strategic-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) strategicNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) strategicYes += parseFloat($sel.val()) || 0;
        });
        
        var coreTotalNo = 0, coreTotalYes = 0;
        $table.find('.core-total-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) coreTotalNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) coreTotalYes += parseFloat($sel.val()) || 0;
        });
        
        var supportNo = 0, supportYes = 0;
        $table.find('.support-select').each(function(){
            var $sel = $(this);
            var colIdx = $sel.closest('td').index();
            if(colIdx === 1) supportNo += parseFloat($sel.val()) || 0;
            if(hasTwoColumns && colIdx === 2) supportYes += parseFloat($sel.val()) || 0;
        });
        
        var grandTotalNo = strategicNo + coreTotalNo + supportNo;
        var grandTotalYes = strategicYes + coreTotalYes + supportYes;
        
        if(grandTotalNo != 100) {
            showToast("Cannot save! Grand total (Without Designation) must be 100%.", "danger");
            return;
        }
        if(hasTwoColumns && grandTotalYes != 100) {
            showToast("Cannot save! Grand total (With Designation) must be 100%.", "danger");
            return;
        }
        
        var $selects = $card.find('.alloc-select');
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        var saved = 0;
        var toSave = $selects.length;
        
        $selects.each(function(){
            var $select = $(this);
            var positions = $select.data('positions');
            var designation = $select.data('designation');
            var category = $select.data('category');
            var subCategory = $select.data('sub') || '';
            var value = $select.val();
            
            positions.forEach(function(posId) {
                $.ajax({
                    url: 'ajax.php?action=save_percentage_allocation_quick',
                    method: 'POST',
                    data: {
                        position_id: posId,
                        designation_id: designation,
                        category: category,
                        sub_category: subCategory,
                        percentage: value
                    },
                    success: function(resp) {
                        saved++;
                        if(saved >= toSave) {
                            $btn.html('<i class="fa fa-check"></i> Saved!').removeClass('btn-danger').addClass('btn-success');
                            setTimeout(function(){
                                $btn.html('<i class="fa fa-save"></i> Save <?php echo $group['name']; ?>').removeClass('btn-success').addClass('btn-warning');
                            }, 2000);
                        }
                    }
                });
            });
        });
        
        showToast("Changes saved successfully!", "success");
    });
    
    $('#save_all').click(function(){
        var $btn = $(this);
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving All...');
        
        $('.save-group').each(function(){
            $(this).click();
        });
        
        setTimeout(function(){
            $btn.html('<i class="fa fa-check"></i> All Saved!');
            setTimeout(function(){
                $btn.html('<i class="fa fa-save"></i> Save All Changes');
            }, 2000);
        }, 1000);
    });
    
    function showToast(msg, type) {
        var cls = type == 'success' ? 'bg-success' : (type == 'danger' ? 'bg-danger' : 'bg-info');
        $('body').prepend('<div class="alert-toast ' + cls + ' text-white p-2 position-fixed" style="top:20px;right:20px;z-index:9999;border-radius:5px;box-shadow:0 2px 10px rgba(0,0,0,0.3);"><i class="fa fa-' + (type == 'danger' ? 'exclamation-triangle' : 'check') + ' mr-2"></i>' + msg + '</div>');
        setTimeout(function(){ $('.alert-toast').fadeOut(function(){ $(this).remove(); }); }, 4000);
    }
});
</script>
