<?php
require_once 'db_connect.php';
if (!isset($_SESSION['login_type']) || intval($_SESSION['login_type']) !== 2) {
    echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied. Only administrators can assign evaluators.</div></div>';
    exit;
}

// Load existing designation-to-designation mapping
$map_qry = $conn->query("SELECT employee_designation_id, evaluator_designation_id FROM evaluator_designation_map");
$current_map = [];
if ($map_qry) {
    while ($m = $map_qry->fetch_assoc()) {
        $current_map[intval($m['employee_designation_id'])] = intval($m['evaluator_designation_id']);
    }
}

// All designations
$desigs = $conn->query("SELECT * FROM designation_list ORDER BY designation ASC");
$all_desigs = [];
while ($d = $desigs->fetch_assoc()) {
    $all_desigs[intval($d['id'])] = trim($d['designation']);
}

// Faculty count per designation
$cnt_q = $conn->query("SELECT designation_id, COUNT(*) AS c FROM employee_list GROUP BY designation_id");
$cnt_arr = [];
while ($cr = $cnt_q->fetch_assoc()) {
    $cnt_arr[intval($cr['designation_id'])] = intval($cr['c']);
}

// Evaluator count per designation — include evaluators whose evaluator_list
// designation_id is 0 but whose employee_list designation matches (fallback).
$eval_cnt_q = $conn->query("
    SELECT COALESCE(NULLIF(el.designation_id, 0), em.designation_id, el.designation_id) AS designation_id, COUNT(*) AS c
    FROM evaluator_list el
    LEFT JOIN employee_list em ON el.email = em.email
    GROUP BY COALESCE(NULLIF(el.designation_id, 0), em.designation_id, el.designation_id)
");
$eval_cnt_arr = [];
while ($ecr = $eval_cnt_q->fetch_assoc()) {
    $eval_cnt_arr[intval($ecr['designation_id'])] = intval($ecr['c']);
}
?>
<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-user-secret"></i> Assign Evaluator by Designation</h5>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-primary" id="apply_all"><i class="fa fa-wand-magic-sparkles"></i> Apply All</button>
            </div>
        </div>
        <div class="card-body">
            <!-- Toolbar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
                <input type="text" id="desig_search" class="form-control form-control-sm" placeholder="Search designation…" style="max-width:240px;">
                <button type="button" class="btn btn-sm btn-default border-secondary" id="reset_btn"><i class="fa fa-rotate-left"></i> Reset to saved</button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width:40px;">#</th>
                            <th>Employee Designation</th>
                            <th class="text-center" style="width:100px;">Faculty Count</th>
                            <th class="text-center" style="width:60px;">Eval Count</th>
                            <th style="width:340px;">Evaluated By (Designation)</th>
                            <th class="text-center" style="width:160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($all_desigs as $did => $dname):
                            $count = $cnt_arr[$did] ?? 0;
                            $current_eval_desig = $current_map[$did] ?? 0;
                        ?>
                        <tr data-desig="<?= htmlspecialchars($dname) ?>" data-evaldesig="<?= $current_eval_desig ?>">
                            <td class="text-center font-weight-bold"><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($dname) ?></strong></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= $count ?></span></td>
                            <td class="text-center">
                                <?php $ec = $eval_cnt_arr[$did] ?? 0; ?>
                                <span class="badge badge-<?= $ec > 0 ? 'success' : 'warning' ?>"><?= $ec ?> evaluators</span>
                            </td>
                            <td>
                                <select class="form-control form-control-sm eval-desig-select" data-empdesig="<?= $did ?>">
                                    <option value="0">— Select evaluator designation —</option>
                                    <?php foreach ($all_desigs as $edid => $ename):
                                        if ($edid == $did) continue; // can't evaluate yourself
                                    ?>
                                    <option value="<?= $edid ?>" <?= $current_eval_desig == $edid ? 'selected' : '' ?>><?= htmlspecialchars($ename) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-center">
                                <?php if ($count > 0): ?>
                                <button type="button" class="btn btn-sm btn-success apply-one" data-empdesig="<?= $did ?>">
                                    <i class="fa fa-check"></i> Apply
                                </button>
                                <?php else: ?>
                                <span class="text-muted small"><i class="fa fa-circle-check"></i> No faculty</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                <small class="text-muted"><i class="fa fa-circle-info"></i> "Apply" assigns evaluators of the selected evaluator-designation to all faculty of this employee-designation. If multiple evaluators exist for the evaluator-designation, they are distributed round-robin.</small>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#list').DataTable({
        "dom": 'Bfrtip',
        "buttons": ['copy', 'csv', 'excel', 'pdf', 'print'],
        "ordering": true,
        "order": [[1, 'asc']],
        "paging": false,
        "searching": false
    });

    function applyDesignation(empDesigId, evalDesigId, btn) {
        if (evalDesigId == 0 || evalDesigId === "") {
            alert_toast("Please choose an evaluator designation first.", 'warning');
            return false;
        }
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_assign_evaluator',
            method: 'POST',
            data: {
                employee_designation_id: empDesigId,
                evaluator_designation_id: evalDesigId
            },
            success: function(resp){
                try {
                    var r = typeof resp === 'string' ? JSON.parse(resp) : resp;
                    if (r.status === 'success') {
                        alert_toast(r.message || "Evaluator applied successfully.", 'success');
                        var $tr = $('.eval-desig-select[data-empdesig="'+empDesigId+'"]').closest('tr');
                        $tr.attr('data-evaldesig', evalDesigId);
                    } else {
                        alert_toast(r.message || "Failed to apply evaluator.", 'danger');
                    }
                } catch(e) {
                    if (resp == 1) {
                        alert_toast("Evaluator applied successfully.", 'success');
                    } else {
                        alert_toast("Failed to apply evaluator.", 'danger');
                    }
                }
                end_load();
            },
            error: function(){
                alert_toast("An error occurred.", 'danger');
                end_load();
            }
        });
        return true;
    }

    $(document).on('click', '.apply-one', function(){
        var empDesigId = $(this).data('empdesig');
        var evalDesigId = $('.eval-desig-select[data-empdesig="'+empDesigId+'"]').val();
        applyDesignation(empDesigId, evalDesigId, this);
    });

    $('#apply_all').click(function(){
        var rows = [];
        var incomplete = false;
        $('.eval-desig-select').each(function(){
            var empDesig = $(this).data('empdesig');
            var evalDesig = $(this).val();
            var count = parseInt($(this).closest('tr').find('.badge-secondary').text()) || 0;
            if (count > 0 && (evalDesig == 0 || evalDesig === "")) {
                incomplete = true;
            }
            if (count > 0 && evalDesig != 0 && evalDesig !== "") {
                rows.push({employee_designation_id: empDesig, evaluator_designation_id: evalDesig});
            }
        });
        if (rows.length === 0) {
            alert_toast("No designations with a mapping to apply.", 'warning');
            return;
        }
        if (incomplete && !confirm("Some designations with faculty have no evaluator designation selected — they will be skipped. Continue?")) {
            return;
        }
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_assign_evaluator',
            method: 'POST',
            data: {bulk: 1, assignments: JSON.stringify(rows)},
            success: function(resp){
                try {
                    var r = typeof resp === 'string' ? JSON.parse(resp) : resp;
                    if (r.status === 'success') {
                        alert_toast(r.message || "All designations applied successfully.", 'success');
                    } else {
                        alert_toast(r.message || "Failed to apply assignments.", 'danger');
                    }
                } catch(e) {
                    if (resp == 1) {
                        alert_toast("All designations applied successfully.", 'success');
                    } else {
                        alert_toast("Failed to apply assignments.", 'danger');
                    }
                }
                end_load();
            },
            error: function(){
                alert_toast("An error occurred.", 'danger');
                end_load();
            }
        });
    });

    $('#reset_btn').click(function(){
        location.reload();
    });

    // Client-side search
    function applyFilter(){
        var q = $('#desig_search').val().toLowerCase();
        $('#list tbody tr').each(function(){
            var name = $(this).data('desig').toString().toLowerCase();
            $(this).toggle(name.indexOf(q) > -1);
        });
    }
    $('#desig_search').on('keyup', applyFilter);
});
</script>