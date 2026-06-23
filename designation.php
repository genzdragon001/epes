<?php include 'db_connect.php' ?>
<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-id-badge"></i> Designation List</h5>
            <div class="card-tools">
                <a class="btn btn-block btn-sm btn-default btn-flat border-primary new_designation" href="javascript:void(0)"><i class="fa fa-plus"></i> Add New</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">#</th>
                            <th>Designation</th>
                            <th>Description</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $qry = $conn->query("SELECT * FROM designation_list order by designation asc ");
                        while($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <th class="text-center font-weight-bold"><?php echo $i++ ?></th>
                            <td><strong><?php echo htmlspecialchars($row['designation']) ?></strong></td>
                            <td><?php echo htmlspecialchars($row['description']) ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="javascript:void(0)" data-id='<?php echo $row['id'] ?>' class="btn btn-sm btn-info manage_designation">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger delete_designation" data-id="<?php echo $row['id'] ?>">
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

    $('.new_designation').click(function(){
        uni_modal("New Designation","manage_designation.php")
    })
    $('.manage_designation').click(function(){
        uni_modal("Manage Designation","manage_designation.php?id="+$(this).attr('data-id'))
    })
    $('.delete_designation').click(function(){
        _conf("Are you sure to delete this Designation?","delete_designation",[$(this).attr('data-id')])
    })
})

function delete_designation($id){
    start_load()
    $.ajax({
        url:'ajax.php?action=delete_designation',
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
