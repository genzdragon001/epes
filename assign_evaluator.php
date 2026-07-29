<?php
require_once 'db_connect.php';
if (!isset($_SESSION['login_type']) || intval($_SESSION['login_type']) !== 2) {
    echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied. Only administrators can assign evaluators.</div></div>';
    exit;
}
?>
<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-user-secret"></i> Assign Evaluator by Designation</h5>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-primary" id="apply_all"><i class="fa fa-wand-magic-sparkles"></i> Apply All Designations</button>
            </div>
        </div>
        <div class="card-body">
            <!-- Toolbar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px;">
                <div class="d-flex flex-wrap" style="gap:10px;">
                    <input type="text" id="desig_search" class="form-control form-control-sm" placeholder="Search designation…" style="max-width:220px;">
                    <select id="desig_filter" class="form-control form-control-sm" style="max-width:240px;">
                        <option value="all">All designations</option>
                        <option value="assigned">With evaluator set</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                </div>
                <button type="button" class="btn btn-sm btn-default border-secondary" id="reset_btn"><i class="fa fa-rotate-left"></i> Reset to saved</button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width:40px;">#</th>
                            <th>Designation</th>
                            <th class="text-center" style="width:120px;">Faculty Count</th>
                            <th style="width:340px;">Default Evaluator</th>
                            <th class="text-center" style="width:180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        // Evaluators available to assign (everyone in evaluator_list), grouped by their designation
                        $evals = $conn->query("SELECT ev.id, CONCAT(ev.firstname,' ',ev.lastname) AS name, ev.designation_id
                                                FROM evaluator_list ev ORDER BY name ASC");
                        $eval_by_desig = [];
                        while ($e = $evals->fetch_assoc()) {
                            $eval_by_desig[intval($e['designation_id'])][intval($e['id'])] = $e['name'];
                        }
                        // For each employee designation, which evaluator designation is allowed to evaluate them?
                        // Faculty (3) -> evaluated by a Department Head (2)
                        // Department Head (2) -> evaluated by a Dean (1)
                        // Everything else -> any evaluator
                        $allowed_evaluator_desig = function($emp_desig) {
                            if ($emp_desig == 3) return 2;   // Faculty -> Department Head
                            if ($emp_desig == 2) return 1;   // Department Head -> Dean
                            return null;                     // no restriction
                        };
                        // Faculty count per designation
                        $cnt_q = $conn->query("SELECT designation_id, COUNT(*) AS c FROM employee_list GROUP BY designation_id");
                        $cnt_arr = [];
                        while ($cr = $cnt_q->fetch_assoc()) {
                            $cnt_arr[intval($cr['designation_id'])] = intval($cr['c']);
                        }
                        // Current default evaluator per designation = the evaluator_id most common among that designation's employees
                        $def_q = $conn->query("SELECT designation_id, evaluator_id, COUNT(*) c
                                                FROM employee_list
                                                WHERE evaluator_id IS NOT NULL AND evaluator_id <> 0 AND evaluator_id <> ''
                                                GROUP BY designation_id, evaluator_id");
                        $def_arr = [];
                        // keep the highest-count evaluator per designation
                        $def_best = [];
                        while ($dr = $def_q->fetch_assoc()) {
                            $did = intval($dr['designation_id']);
                            $eid = intval($dr['evaluator_id']);
                            if (!isset($def_best[$did]) || $dr['c'] > $def_best[$did]['c']) {
                                $def_best[$did] = ['evaluator_id' => $eid, 'c' => intval($dr['c'])];
                            }
                        }
                        foreach ($def_best as $did => $v) {
                            $def_arr[$did] = $v['evaluator_id'];
                        }

                        $qry = $conn->query("SELECT * FROM designation_list ORDER BY designation ASC");
                        while ($row = $qry->fetch_assoc()):
                            $did = intval($row['id']);
                            $count = $cnt_arr[$did] ?? 0;
                            $current = $def_arr[$did] ?? 0;
                            // Which evaluator designation is allowed to evaluate this employee designation?
                            $allow_desig = $allowed_evaluator_desig($did); // int or null
                            if ($allow_desig === null) {
                                // No restriction: offer every evaluator across all designations
                                $eval_options = [];
                                foreach ($eval_by_desig as $grp) { $eval_options += $grp; }
                            } else {
                                $eval_options = $eval_by_desig[$allow_desig] ?? [];
                            }
                            // Allowed-evaluator hint label
                            $allow_label = '';
                            if ($allow_desig !== null) {
                                $all_desig = $conn->query("SELECT designation FROM designation_list WHERE id = $allow_desig LIMIT 1")->fetch_assoc();
                                $allow_label = $all_desig ? $all_desig['designation'] : '';
                            }
                        ?>
                        <tr data-desig="<?php echo htmlspecialchars($row['designation']) ?>" data-eval="<?php echo $current ?>">
                            <td class="text-center font-weight-bold"><?php echo $i++ ?></td>
                            <td><strong><?php echo htmlspecialchars($row['designation']) ?></strong></td>
                            <td class="text-center"><span class="badge badge-secondary"><?php echo $count ?></span></td>
                            <td>
                                <select class="form-control form-control-sm eval-select" data-desig="<?php echo $did ?>">
                                    <option value="0" <?php echo $current == 0 ? 'selected' : '' ?>>— Select evaluator —</option>
                                    <?php foreach ($eval_options as $eid => $ename): ?>
                                    <option value="<?php echo $eid ?>" <?php echo $current == $eid ? 'selected' : '' ?>><?php echo htmlspecialchars($ename) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($allow_label !== ''): ?>
                                <small class="text-muted d-block mt-1">Evaluator must be a <?php echo htmlspecialchars($allow_label) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($count > 0): ?>
                                <button type="button" class="btn btn-sm btn-success apply-one" data-desig="<?php echo $did ?>">
                                    <i class="fa fa-check"></i> Apply to <?php echo $count ?> faculty
                                </button>
                                <?php else: ?>
                                <span class="text-muted small"><i class="fa fa-circle-check"></i> No faculty</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                <small class="text-muted"><i class="fa fa-circle-info"></i> "Apply" sets each matching faculty member's evaluator to the chosen default, overwriting any existing individual assignment for that designation.</small>
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

    function applyDesignation(desigId, btn) {
        var $sel = $('.eval-select[data-desig="'+desigId+'"]');
        var evaluatorId = $sel.val();
        if (evaluatorId == 0 || evaluatorId === "") {
            alert_toast("Please choose an evaluator first.", 'warning');
            return;
        }
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_assign_evaluator',
            method: 'POST',
            data: {designation_id: desigId, evaluator_id: evaluatorId},
            success: function(resp){
                if (resp == 1) {
                    alert_toast("Evaluator applied successfully.", 'success');
                    // reflect saved state on the row
                    var $tr = $sel.closest('tr');
                    $tr.attr('data-eval', evaluatorId);
                } else {
                    alert_toast("Failed to apply evaluator.", 'danger');
                }
                end_load();
            },
            error: function(){
                alert_toast("An error occurred.", 'danger');
                end_load();
            }
        });
    }

    $(document).on('click', '.apply-one', function(){
        var desigId = $(this).data('desig');
        applyDesignation(desigId, this);
    });

    $('#apply_all').click(function(){
        var rows = [];
        var incomplete = false;
        $('.eval-select').each(function(){
            var did = $(this).data('desig');
            var eid = $(this).val();
            var count = parseInt($(this).closest('tr').find('.badge').text()) || 0;
            if (count > 0 && (eid == 0 || eid === "")) {
                incomplete = true;
            }
            if (count > 0 && eid != 0 && eid !== "") {
                rows.push({designation_id: did, evaluator_id: eid});
            }
        });
        if (rows.length === 0) {
            alert_toast("No designations with a selected evaluator to apply.", 'warning');
            return;
        }
        if (incomplete && !confirm("Some designations with faculty have no evaluator selected — they will be skipped. Continue?")) {
            return;
        }
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_assign_evaluator',
            method: 'POST',
            data: {bulk: 1, assignments: JSON.stringify(rows)},
            success: function(resp){
                if (resp == 1) {
                    alert_toast("All designations applied successfully.", 'success');
                } else {
                    alert_toast("Failed to apply assignments.", 'danger');
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

    // Client-side search + filter (lightweight, DataTable searching disabled)
    function applyFilter(){
        var q = $('#desig_search').val().toLowerCase();
        var f = $('#desig_filter').val();
        $('#list tbody tr').each(function(){
            var name = $(this).data('desig').toString().toLowerCase();
            var evalv = $(this).data('eval');
            var show = name.indexOf(q) > -1;
            if (f === 'assigned') show = show && (evalv != 0 && evalv !== '');
            else if (f === 'unassigned') show = show && (evalv == 0 || evalv === '');
            $(this).toggle(show);
        });
    }
    $('#desig_search').on('keyup', applyFilter);
    $('#desig_filter').on('change', applyFilter);
});
</script>
