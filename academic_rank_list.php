<?php include 'db_connect.php' ?>
<div class="col-lg-12">
    <div class="card card-outline card-info">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-graduation-cap"></i> Academic Rank List</h5>
            <div class="card-tools">
                <a class="btn btn-sm btn-default btn-flat border-primary new_rank" href="javascript:void(0)"><i class="fa fa-plus"></i> Add New Rank</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 60px;">#</th>
                            <th>Position</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $qry = $conn->query("SELECT * FROM position_list ORDER BY id ASC");
                        while($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <th class="text-center font-weight-bold"><?php echo $i++ ?></th>
                            <td><strong><?php echo htmlspecialchars($row['position']) ?></strong></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="javascript:void(0)" data-id='<?php echo $row['id'] ?>' class="btn btn-sm btn-info manage_rank">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger delete_rank" data-id="<?php echo $row['id'] ?>">
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

    $('.new_rank').click(function(){
        uni_modal("New Academic Rank","manage_academic_rank.php")
    })
    $('.manage_rank').click(function(){
        uni_modal("Manage Academic Rank","manage_academic_rank.php?id="+$(this).attr('data-id'))
    })
    $('.delete_rank').click(function(){
        _conf("Are you sure to delete this Position?","delete_academic_rank",[$(this).attr('data-id')])
    })
})

function delete_academic_rank($id){
    start_load()
    $.ajax({
        url:'ajax.php?action=delete_academic_rank',
        method:'POST',
        data:{id:$id},
        success:function(resp){
            if(resp==1){
                alert_toast("Data successfully deleted",'success')
                setTimeout(function(){
                    location.reload()
                },1500)
            } else {
                alert_toast("Cannot delete: Position is in use",'danger')
                end_load()
            }
        }
    })
}
</script>

<style>
.card-header { background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%); color: white; }
</style>
