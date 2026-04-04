<?php include 'db_connect.php' ?>
<?php
$rating_period = isset($_SESSION['rating_period']) ? $_SESSION['rating_period'] : date('Y');

function getAdjectivalRating($score) {
    if (!is_numeric($score) || $score <= 0) return "NO RATING";
    $score = round($score, 2);
    if ($score >= 4.75) return "OUTSTANDING";
    if ($score >= 3.61) return "VERY SATISFACTORY";
    if ($score >= 2.61) return "SATISFACTORY";
    if ($score >= 1.61) return "UNSATISFACTORY";
    if ($score <= 1.60) return "POOR";
    return "NO RATING";
}

$periods_result = $conn->query("SELECT DISTINCT rating_period FROM renewal_recommendations UNION SELECT '" . date('Y') . "' ORDER BY rating_period DESC");

$qry = $conn->query("
    SELECT rr.*, e.firstname, e.middlename, e.lastname, d.department,
           ev.firstname as eval_firstname, ev.lastname as eval_lastname
    FROM renewal_recommendations rr
    LEFT JOIN employee_list e ON rr.faculty_id = e.id
    LEFT JOIN department_list d ON e.department_id = d.id
    LEFT JOIN evaluator_list ev ON rr.evaluator_id = ev.id
    ORDER BY rr.created_at DESC
");
?>
<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-clipboard-check"></i> COS Faculty Recommendations Summary</h5>
            <div class="card-tools">
                <select id="rating_period_filter" class="form-control form-control-sm" style="width: 150px;">
                    <option value="">All Periods</option>
                    <?php while($p = $periods_result->fetch_assoc()): ?>
                    <option value="<?php echo $p['rating_period'] ?>" <?php echo ($rating_period == $p['rating_period']) ? 'selected' : '' ?>><?php echo $p['rating_period'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="card-body">
            <?php if($qry && $qry->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="rec-list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Faculty Name</th>
                            <th>Department</th>
                            <th class="text-center">Period</th>
                            <th class="text-center">Overall Score</th>
                            <th class="text-center">Adjectival Rating</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Evaluator</th>
                            <th class="text-center">Date Created</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($row = $qry->fetch_assoc()):
                            $faculty_name = ucwords($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']);
                            $evaluator_name = ucwords($row['eval_firstname'] . ' ' . $row['eval_lastname']);
                            $adjectival = getAdjectivalRating($row['overall_score']);
                            $created_date = date('M d, Y', strtotime($row['created_at']));
                            
                            $status_class = $row['recommendation_status'] == 'Recommended' ? 'badge-success' : ($row['recommendation_status'] == 'Not Recommended' ? 'badge-danger' : 'badge-secondary');
                        ?>
                        <tr>
                            <td class="text-center font-weight-bold"><?php echo $i++ ?></td>
                            <td><strong><?php echo htmlspecialchars($faculty_name) ?></strong></td>
                            <td><?php echo htmlspecialchars($row['department'] ?? 'N/A') ?></td>
                            <td class="text-center"><?php echo $row['rating_period'] ?></td>
                            <td class="text-center"><strong><?php echo number_format($row['overall_score'], 2) ?></strong></td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-<?php 
                                    echo $adjectival == 'OUTSTANDING' ? 'success' : 
                                        ($adjectival == 'VERY SATISFACTORY' ? 'primary' : 
                                        ($adjectival == 'SATISFACTORY' ? 'info' : 
                                        ($adjectival == 'UNSATISFACTORY' ? 'warning' : 'danger')));
                                ?>"><?php echo $adjectival ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-pill <?php echo $status_class ?>"><?php echo $row['recommendation_status'] ?></span>
                            </td>
                            <td class="text-center"><?php echo htmlspecialchars($evaluator_name) ?></td>
                            <td class="text-center"><small><?php echo $created_date ?></small></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info view-rec" data-id="<?php echo $row['id'] ?>">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary edit-rec" data-id="<?php echo $row['id'] ?>">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-rec" data-id="<?php echo $row['id'] ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fa fa-clipboard-list fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No recommendations found</h5>
                <p class="text-muted">Recommendations will appear here once evaluators generate them.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa fa-file-alt"></i> Recommendation Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="view-content"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-edit"></i> Edit Recommendation</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="edit-form">
                <div class="modal-body" id="edit-content"></div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#rec-list').DataTable({
        "dom": 'Bfrtip',
        "buttons": ['copy', 'csv', 'excel', 'pdf', 'print'],
        "ordering": true,
        "order": [[8, 'desc']]
    });

    $(document).on('click', '.view-rec', function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'ajax.php?action=get_rec_details',
            method: 'POST',
            data: { id: id },
            success: function(resp) {
                var data = JSON.parse(resp);
                var content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Faculty Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Name:</strong></td><td>${data.faculty_name}</td></tr>
                                <tr><td><strong>Department:</strong></td><td>${data.department}</td></tr>
                                <tr><td><strong>Rating Period:</strong></td><td>${data.rating_period}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Performance Scores</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Overall Score:</strong></td><td>${parseFloat(data.overall_score).toFixed(2)}</td></tr>
                                <tr><td><strong>Instruction Average:</strong></td><td>${data.instruction_ave || 'N/A'}</td></tr>
                                <tr><td><strong>Support Average:</strong></td><td>${data.support_ave || 'N/A'}</td></tr>
                                <tr><td><strong>Total Tasks:</strong></td><td>${data.total_tasks}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6 class="text-primary">System Generated Statement</h6>
                            <div class="p-3 bg-light rounded" style="white-space: pre-wrap;">${data.system_generated_reason}</div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <small class="text-muted">
                                <strong>Evaluator:</strong> ${data.evaluator_name} | 
                                <strong>Created:</strong> ${data.created_at}
                            </small>
                        </div>
                    </div>
                `;
                $('#view-content').html(content);
                $('#viewModal').modal('show');
            }
        });
    });

    $(document).on('click', '.edit-rec', function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'ajax.php?action=get_rec_details',
            method: 'POST',
            data: { id: id },
            success: function(resp) {
                var data = JSON.parse(resp);
                var content = `
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="form-group">
                        <label><strong>Overall Score:</strong></label>
                        <input type="number" name="overall_score" class="form-control" step="0.01" min="0" max="5" value="${data.overall_score}" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Instruction Average:</strong></label>
                        <input type="number" name="instruction_ave" class="form-control" step="0.01" min="0" max="5" value="${data.instruction_ave || ''}">
                    </div>
                    <div class="form-group">
                        <label><strong>Support Average:</strong></label>
                        <input type="number" name="support_ave" class="form-control" step="0.01" min="0" max="5" value="${data.support_ave || ''}">
                    </div>
                    <div class="form-group">
                        <label><strong>Recommendation Status:</strong></label>
                        <select name="recommendation_status" class="form-control">
                            <option value="Pending" ${data.recommendation_status == 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Recommended" ${data.recommendation_status == 'Recommended' ? 'selected' : ''}>Recommended</option>
                            <option value="Not Recommended" ${data.recommendation_status == 'Not Recommended' ? 'selected' : ''}>Not Recommended</option>
                            <option value="For Review" ${data.recommendation_status == 'For Review' ? 'selected' : ''}>For Review</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>System Generated Reason:</strong></label>
                        <textarea name="system_reason" class="form-control" rows="4">${data.system_generated_reason}</textarea>
                    </div>
                `;
                $('#edit-content').html(content);
                $('#editModal').modal('show');
            }
        });
    });

    $('#edit-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax.php?action=update_rec',
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if(resp == 1) {
                    alert('Recommendation updated successfully!');
                    location.reload();
                } else {
                    alert('Failed to update: ' + resp);
                }
            }
        });
    });

    $(document).on('click', '.delete-rec', function() {
        if (!confirm('Are you sure you want to delete this recommendation?')) return;
        var id = $(this).data('id');
        $.ajax({
            url: 'ajax.php?action=delete_rec',
            method: 'POST',
            data: { id: id },
            success: function(resp) {
                if(resp == 1) {
                    alert('Recommendation deleted successfully!');
                    location.reload();
                } else {
                    alert('Failed to delete: ' + resp);
                }
            }
        });
    });
});
</script>

<style>
.card-header { background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%); color: white; }
</style>
