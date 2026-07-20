<?php include 'db_connect.php' ?>
<?php
$designation_qry = $conn->query("SELECT * FROM designation_list ORDER BY id ASC");
$designations = [];
while($row = $designation_qry->fetch_assoc()) $designations[] = $row;

function getAllocation($conn, $position_id, $designation_id, $category, $sub_category) {
    $designation_id = ($designation_id === '' || $designation_id === null) ? 3 : intval($designation_id);
    $sql = "SELECT percentage FROM percentage_allocation 
            WHERE position_id = $position_id AND designation_id = $designation_id AND category = '$category'";
    $sql .= $sub_category ? " AND sub_category = '$sub_category'" : " AND (sub_category IS NULL OR sub_category = '')";
    $sql .= " LIMIT 1";
    $qry = $conn->query($sql);
    return ($qry && $qry->num_rows > 0) ? floatval($qry->fetch_assoc()['percentage']) : 0;
}

$rank_groups = [
    ['name' => 'Instructor I-III',           'ids' => [1, 2, 3]],
    ['name' => 'Assistant Professor I-IV',    'ids' => [4, 5, 6, 7]],
    ['name' => 'Associate Professor I-V',    'ids' => [8, 9, 10, 11, 12]],
    ['name' => 'Professor I-VI',             'ids' => [13, 14, 15, 16, 17, 18], 'prof_dev' => true],
    ['name' => 'Contract of Service',         'ids' => [19], 'no_research_ext' => true],
];

$sel_desig = isset($_GET['d']) ? intval($_GET['d']) : 3;
$sel_rank  = isset($_GET['r']) ? intval($_GET['r']) : 0;
if($sel_rank < 0 || $sel_rank >= count($rank_groups)) $sel_rank = 0;
$group = $rank_groups[$sel_rank];
$d_name = 'Faculty';
foreach($designations as $d) { if($d['id'] == $sel_desig) { $d_name = $d['designation']; break; } }
?>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0"><i class="fa fa-percent"></i> Percentage Allocation</h5>
            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                <label class="mb-0 font-weight-bold small">Designation:</label>
                <select id="desig_select" class="form-control form-control-sm" style="width: 200px;">
                    <?php foreach($designations as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo $sel_desig == $d['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d['designation']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <label class="mb-0 font-weight-bold small">Rank:</label>
                <select id="rank_select" class="form-control form-control-sm" style="width: 180px;">
                    <?php foreach($rank_groups as $i => $rg): ?>
                    <option value="<?php echo $i; ?>" <?php echo $sel_rank == $i ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rg['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-sm btn-warning save-group" data-group="0">
                    <i class="fa fa-save"></i> Save
                </button>
            </div>
        </div>
        <div class="card-body p-2">
            <form id="allocationForm">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge <?php echo isset($group['no_research_ext']) && $group['no_research_ext'] ? 'badge-warning' : 'badge-info'; ?> mr-2">
                        <?php echo $group['name']; ?>
                    </span>
                    <span class="text-muted small">
                        Instructions Total (Subtotal + Research + Extension + Prof. Dev.) &le; 100% &middot; Grand Total (Strategic + Core + Support) = 100%
                    </span>
                </div>

                <table class="table table-sm table-bordered mb-1" id="table_0" style="font-size: 0.85rem;">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:55%;">Function</th>
                            <th class="text-center" style="width:45%;"><?php echo htmlspecialchars($d_name); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($sel_desig != 3): // Show Strategic for all non-Faculty designations ?>
                        <tr>
                            <td><strong>Strategic Functions</strong></td>
                            <td class="text-center">
                                <select class="form-control form-control-sm alloc-select w-50 mx-auto strategic-select" data-positions='<?php echo json_encode($group['ids']); ?>' data-designation="<?php echo $sel_desig; ?>" data-category="strategic" data-group="0">
                                    <option value="0">0%</option>
                                    <?php for($i = 5; $i <= 90; $i+=5): ?><option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], $sel_desig, 'strategic', null) == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option><?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td><strong>Core Functions</strong> <em class="text-muted small">— Total (must be 100%)</em></td>
                            <td class="text-center">
                                <select class="form-control form-control-sm alloc-select w-50 mx-auto core-total-select" data-positions='<?php echo json_encode($group['ids']); ?>' data-designation="<?php echo $sel_desig; ?>" data-category="core" data-sub="total" data-group="0">
                                    <option value="0">0%</option>
                                    <?php for($i = 5; $i <= 100; $i+=5): ?><option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'total') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option><?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 1.5rem;"><strong>Instructions</strong></td>
                            <td class="text-center text-muted small">
                                TER + Instruction = 100% &middot; + Research + Extension &le; 100%
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 2.5rem;">TER</td>
                            <td class="text-center">
                                <select class="form-control form-control-sm alloc-select w-50 mx-auto instr-sub-select" data-positions='<?php echo json_encode($group['ids']); ?>' data-designation="<?php echo $sel_desig; ?>" data-category="core" data-sub="ter" data-group="0">
                                    <option value="0">0%</option>
                                    <?php for($i = 5; $i <= 100; $i+=5): ?><option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'ter') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option><?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 2.5rem;">Instruction</td>
                            <td class="text-center">
                                <select class="form-control form-control-sm alloc-select w-50 mx-auto instr-sub-select" data-positions='<?php echo json_encode($group['ids']); ?>' data-designation="<?php echo $sel_desig; ?>" data-category="core" data-sub="instruction" data-group="0">
                                    <option value="0">0%</option>
                                    <?php for($i = 5; $i <= 100; $i+=5): ?><option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'instruction') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option><?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <tr class="instr-subtotal-row">
                            <td style="padding-left: 2.5rem;"><strong>Instructions Subtotal</strong></td>
                            <?php $instr_sub = getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'ter') + getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'instruction'); ?>
                            <td class="text-center">
                                <span class="badge badge-lg <?php echo $instr_sub == 100 ? 'badge-success' : ($instr_sub > 100 ? 'badge-danger' : 'badge-warning'); ?>"><?php echo $instr_sub; ?>%</span>
                            </td>
                        </tr>
                        <?php if(!isset($group['no_research_ext']) || !$group['no_research_ext']): ?>
                        <tr>
                            <td style="padding-left: 2.5rem;">Research</td>
                            <td class="text-center">
                                <select class="form-control form-control-sm alloc-select w-50 mx-auto core-sub-select" data-positions='<?php echo json_encode($group['ids']); ?>' data-designation="<?php echo $sel_desig; ?>" data-category="core" data-sub="research" data-group="0">
                                    <option value="0">0%</option>
                                    <?php for($i = 5; $i <= 100; $i+=5): ?><option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'research') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option><?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 2.5rem;">Extension</td>
                            <td class="text-center">
                                <select class="form-control form-control-sm alloc-select w-50 mx-auto core-sub-select" data-positions='<?php echo json_encode($group['ids']); ?>' data-designation="<?php echo $sel_desig; ?>" data-category="core" data-sub="extension" data-group="0">
                                    <option value="0">0%</option>
                                    <?php for($i = 5; $i <= 100; $i+=5): ?><option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'extension') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option><?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if(isset($group['prof_dev']) && $group['prof_dev']): ?>
                        <tr>
                            <td style="padding-left: 2.5rem;">Professional Development</td>
                            <td class="text-center">
                                <select class="form-control form-control-sm alloc-select w-50 mx-auto core-sub-select" data-positions='<?php echo json_encode($group['ids']); ?>' data-designation="<?php echo $sel_desig; ?>" data-category="core" data-sub="prof_dev" data-group="0">
                                    <option value="0">0%</option>
                                    <?php for($i = 5; $i <= 100; $i+=5): ?><option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'prof_dev') == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option><?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr class="core-subtotal-row">
                            <td style="padding-left: 1.5rem;"><strong>Core Subtotal</strong></td>
                            <?php
                            $r_val = (isset($group['no_research_ext']) && $group['no_research_ext']) ? 0 : getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'research');
                            $e_val = (isset($group['no_research_ext']) && $group['no_research_ext']) ? 0 : getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'extension');
                            $pd_val = (isset($group['prof_dev']) && $group['prof_dev']) ? getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'prof_dev') : 0;
                            $instr_total = $instr_sub + $r_val + $e_val + $pd_val;
                            ?>
                            <td class="text-center">
                                <span class="badge badge-lg <?php echo $instr_total == 100 ? 'badge-success' : ($instr_total > 100 ? 'badge-danger' : 'badge-warning'); ?>"><?php echo $instr_total; ?>%</span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Support Functions</strong></td>
                            <td class="text-center">
                                <select class="form-control form-control-sm alloc-select w-50 mx-auto support-select" data-positions='<?php echo json_encode($group['ids']); ?>' data-designation="<?php echo $sel_desig; ?>" data-category="support" data-group="0">
                                    <option value="0">0%</option>
                                    <?php for($i = 5; $i <= 100; $i+=5): ?><option value="<?php echo $i; ?>" <?php echo getAllocation($conn, $group['ids'][0], $sel_desig, 'support', null) == $i ? 'selected' : '' ?>><?php echo $i; ?>%</option><?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <tr class="table-primary font-weight-bold total-row">
                            <td>GRAND TOTAL</td>
                            <?php
                            $sv = ($sel_desig != 3) ? getAllocation($conn, $group['ids'][0], $sel_desig, 'strategic', null) : 0;
                            $cv = getAllocation($conn, $group['ids'][0], $sel_desig, 'core', 'total');
                            $sup = getAllocation($conn, $group['ids'][0], $sel_desig, 'support', null);
                            $gt = $sv + $cv + $sup;
                            ?>
                            <td class="text-center">
                                <span class="badge badge-lg <?php echo $gt == 100 ? 'badge-success' : ($gt > 100 ? 'badge-danger' : 'badge-warning'); ?>"><?php echo $gt; ?>%</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#desig_select').change(function(){
        window.location.href = 'index.php?page=percentage_allocation&d=' + $(this).val() + '&r=' + $('#rank_select').val();
    });
    $('#rank_select').change(function(){
        window.location.href = 'index.php?page=percentage_allocation&d=' + $('#desig_select').val() + '&r=' + $(this).val();
    });

    $('.alloc-select').change(function(){
        updateTotals(0);
        $('.save-group').removeClass('btn-warning').addClass('btn-danger');
    });

    function updateTotals(groupIdx) {
        var $table = $('#table_' + groupIdx);
        var instrSubtotal = 0;
        $table.find('.instr-sub-select').each(function(){ instrSubtotal += parseFloat($(this).val()) || 0; });
        var researchExt = 0;
        $table.find('.core-sub-select').each(function(){ researchExt += parseFloat($(this).val()) || 0; });
        var instrTotal = instrSubtotal + researchExt;

        $table.find('.instr-subtotal-row td:eq(1) span').text(instrSubtotal + '%')
            .removeClass('badge-success badge-warning badge-danger')
            .addClass(instrSubtotal == 100 ? 'badge-success' : (instrSubtotal > 100 ? 'badge-danger' : 'badge-warning'));

        $table.find('.core-subtotal-row td:eq(1) span').text(instrTotal + '%')
            .removeClass('badge-success badge-warning badge-danger')
            .addClass(instrTotal == 100 ? 'badge-success' : (instrTotal > 100 ? 'badge-danger' : 'badge-warning'));

        var strategic = 0, coreTotal = 0, support = 0;
        $table.find('.strategic-select').each(function(){ strategic += parseFloat($(this).val()) || 0; });
        $table.find('.core-total-select').each(function(){ coreTotal += parseFloat($(this).val()) || 0; });
        $table.find('.support-select').each(function(){ support += parseFloat($(this).val()) || 0; });
        var grandTotal = strategic + coreTotal + support;
        $table.find('.total-row td:eq(1) span').text(grandTotal + '%')
            .removeClass('badge-success badge-warning badge-danger')
            .addClass(grandTotal == 100 ? 'badge-success' : (grandTotal > 100 ? 'badge-danger' : 'badge-warning'));
    }

    $('.save-group').click(function(){
        var $btn = $(this);
        var $table = $('#table_0');
        var instrTotal = 0;
        $table.find('.instr-sub-select').each(function(){ instrTotal += parseFloat($(this).val()) || 0; });
        $table.find('.core-sub-select').each(function(){ instrTotal += parseFloat($(this).val()) || 0; });
        if(instrTotal > 100) { showToast("Cannot save! Instructions Total exceeds 100% (" + instrTotal + "%).", "danger"); return; }

        var strategic = 0, coreTotal = 0, support = 0;
        $table.find('.strategic-select').each(function(){ strategic += parseFloat($(this).val()) || 0; });
        $table.find('.core-total-select').each(function(){ coreTotal += parseFloat($(this).val()) || 0; });
        $table.find('.support-select').each(function(){ support += parseFloat($(this).val()) || 0; });
        var grandTotal = strategic + coreTotal + support;
        if(grandTotal != 100) { showToast("Cannot save! Grand total must be 100% (currently " + grandTotal + "%).", "danger"); return; }

        var $selects = $table.find('.alloc-select');
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        var saved = 0, toSave = $selects.length;

        $selects.each(function(){
            var $select = $(this);
            var positions = $select.data('positions');
            var designation = $select.data('designation');
            var category = $select.data('category');
            var subCategory = $select.data('sub') || '';
            var value = $select.val();
            positions.forEach(function(posId) {
                $.ajax({
                    url: 'ajax.php?action=save_percentage_allocation_quick', method: 'POST',
                    data: { position_id: posId, designation_id: designation, category: category, sub_category: subCategory, percentage: value },
                    success: function() {
                        saved++;
                        if(saved >= toSave) {
                            $btn.html('<i class="fa fa-check"></i> Saved!').removeClass('btn-danger').addClass('btn-success');
                            setTimeout(function(){ $btn.html('<i class="fa fa-save"></i> Save').removeClass('btn-success').addClass('btn-warning'); }, 2000);
                        }
                    }
                });
            });
        });
        showToast("Changes saved successfully!", "success");
    });

    function showToast(msg, type) {
        var cls = type == 'success' ? 'bg-success' : 'bg-danger';
        var icon = type == 'danger' ? 'exclamation-triangle' : 'check';
        $('body').prepend('<div class="alert-toast ' + cls + ' text-white p-2 position-fixed" style="top:20px;right:20px;z-index:9999;border-radius:5px;box-shadow:0 2px 10px rgba(0,0,0,0.3);"><i class="fa fa-' + icon + ' mr-2"></i>' + msg + '</div>');
        setTimeout(function(){ $('.alert-toast').fadeOut(function(){ $(this).remove(); }); }, 4000);
    }
});
</script>