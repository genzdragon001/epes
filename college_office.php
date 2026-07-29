<?php include 'db_connect.php' ?>
<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-university"></i> Colleges / Offices</h5>
            <div class="card-tools">
                <a class="btn btn-block btn-sm btn-default btn-flat border-primary new_college_office" href="javascript:void(0)"><i class="fa fa-plus"></i> Add New</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th class="text-center" style="width:80px;">Status</th>
                            <th class="text-center" style="width:100px;">Faculty Count</th>
                            <th class="text-center" style="width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $qry = $conn->query("SELECT c.*,
                                (SELECT COUNT(*) FROM employee_list e WHERE e.college_office_id = c.id) as direct_count,
                                (SELECT COUNT(*) FROM employee_list e JOIN department_list d ON e.department_id = d.id WHERE d.college_office_id = c.id) as dept_count
                            FROM college_office_list c ORDER BY c.name ASC");
                        while($row = $qry->fetch_assoc()):
                            $total_fac = intval($row['direct_count']) + intval($row['dept_count']);
                        ?>
                        <tr>
                            <th class="text-center font-weight-bold"><?php echo $i++ ?></th>
                            <td><strong><?php echo htmlspecialchars($row['name']) ?></strong></td>
                            <td><?php echo htmlspecialchars($row['code'] ?? '') ?></td>
                            <td class="text-center">
                                <?php if ($row['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info" title="<?= $row['direct_count'] ?> direct, <?= $row['dept_count'] ?> via department"><?= $total_fac ?></span>
                                <?php if ($row['direct_count'] > 0 && $row['dept_count'] > 0): ?>
                                <small class="text-muted d-block" style="font-size:0.65rem;"><?= $row['direct_count'] ?> direct + <?= $row['dept_count'] ?> dept</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="javascript:void(0)" data-id="<?php echo $row['id'] ?>" class="btn btn-sm btn-info manage_college_office">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger delete_college_office" data-id="<?php echo $row['id'] ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
        "order": [[1, 'asc']]
    });

    $('.new_college_office').click(function(){
        uni_modal("New College/Office", "manage_college_office.php")
    })
    $('.manage_college_office').click(function(){
        uni_modal("Manage College/Office", "manage_college_office.php?id="+$(this).attr('data-id'))
    })
    $('.delete_college_office').click(function(){
        _conf("Are you sure to delete this College/Office?", "delete_college_office", [$(this).attr('data-id')])
    })
})

function delete_college_office($id){
    start_load()
    $.ajax({
        url:'ajax.php?action=delete_college_office',
        method:'POST',
        data:{id:$id},
        success:function(resp){
            if(resp==1){
                alert_toast("Data successfully deleted",'success')
                setTimeout(function(){ location.reload() },1500)
            } else {
                alert_toast("Failed to delete — faculty may be assigned to this College/Office.",'danger')
                end_load()
            }
        }
    })
}
</script>