<?php include 'db_connect.php' ?>
<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-building"></i> Department List</h5>
            <div class="card-tools d-flex align-items-center" style="gap:8px;">
                <select id="college_filter" class="form-control form-control-sm" style="width:auto;">
                    <option value="all">All Colleges / Offices</option>
                    <?php
                    $colleges_qry = $conn->query("SELECT * FROM college_office_list ORDER BY name ASC");
                    while($co = $colleges_qry->fetch_assoc()):
                    ?>
                    <option value="<?= $co['id'] ?>"><?= htmlspecialchars($co['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <a class="btn btn-sm btn-default btn-flat border-primary new_department" href="javascript:void(0)"><i class="fa fa-plus"></i> Add New</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Department</th>
                            <th>College / Office</th>
                            <th>Description</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $qry = $conn->query("SELECT d.*, c.name as college_name, c.code as college_code FROM department_list d LEFT JOIN college_office_list c ON d.college_office_id = c.id ORDER BY d.department asc");
                        while($row = $qry->fetch_assoc()):
                        ?>
                        <tr data-college="<?php echo $row['college_office_id'] ?? '' ?>">
                            <th class="text-center font-weight-bold"><?php echo $i++ ?></th>
                            <td><strong><?php echo htmlspecialchars($row['department']) ?></strong></td>
                            <td><?php echo $row['college_name'] ? htmlspecialchars($row['college_name']) . ($row['college_code'] ? ' <span class="badge badge-info">'.htmlspecialchars($row['college_code']).'</span>' : '') : '<span class="text-muted">—</span>' ?></td>
                            <td><?php echo htmlspecialchars($row['description']) ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="javascript:void(0)" data-id='<?php echo $row['id'] ?>' class="btn btn-sm btn-info manage_department">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger delete_department" data-id="<?php echo $row['id'] ?>">
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

    $('.new_department').click(function(){
        uni_modal("New Department","manage_department.php")
    })
    $('.manage_department').click(function(){
        uni_modal("Manage Department","manage_department.php?id="+$(this).attr('data-id'))
    })
    $('.delete_department').click(function(){
        _conf("Are you sure to delete this Department?","delete_department",[$(this).attr('data-id')])
    })

    // College/Office filter
    $('#college_filter').on('change', function(){
        var val = $(this).val();
        $('#list tbody tr').each(function(){
            var co = $(this).data('college') !== undefined ? $(this).data('college').toString() : '';
            if(val === 'all' || co === val){
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
})

function delete_department($id){
    start_load()
    $.ajax({
        url:'ajax.php?action=delete_department',
        method:'POST',
        data:{id:$id},
        success:function(resp){
            if(resp==1){
                alert_toast("Data successfully deleted",'success')
                setTimeout(function(){
                    location.reload()
                },1500)
            }
        }
    })
}
</script>

<style>
</style>
