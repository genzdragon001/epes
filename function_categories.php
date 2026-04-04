<?php include 'db_connect.php' ?>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h5 class="card-title"><i class="fa fa-tasks"></i> Function Categories Management</h5>
            <div class="card-tools">
                <button class="btn btn-sm btn-primary btn-flat border-primary" id="new_category">
                    <i class="fa fa-plus"></i> Add Function Category
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php
            $categories = $conn->query("SELECT * FROM function_categories ORDER BY category, id");
            $cats = [];
            while($row = $categories->fetch_assoc()) {
                $cats[$row['category']][] = $row;
            }
            ?>
            
            <div class="row">
                <?php if(isset($cats['strategic'])): ?>
                <div class="col-md-4">
                    <div class="card card-outline card-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title">Strategic Functions</h5>
                            <span class="badge badge-light float-right"><?php echo array_sum(array_column($cats['strategic'], 'percentage')); ?>%</span>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach($cats['strategic'] as $c): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($c['name']); ?></span>
                                    <span class="badge badge-primary"><?php echo $c['percentage']; ?>%</span>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-info edit_cat" data-id="<?php echo $c['id'] ?>"><i class="fa fa-edit"></i></button>
                                        <button type="button" class="btn btn-danger delete_cat" data-id="<?php echo $c['id'] ?>"><i class="fa fa-trash"></i></button>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($cats['core'])): ?>
                <div class="col-md-4">
                    <div class="card card-outline card-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title">Core Functions</h5>
                            <span class="badge badge-dark float-right"><?php echo array_sum(array_column($cats['core'], 'percentage')); ?>%</span>
                        </div>
                        <div class="card-body">
                            <?php 
                            $sub_cats = ['instructions' => 'Instructions', 'research' => 'Research', 'extension' => 'Extension'];
                            foreach($sub_cats as $sub => $label): 
                                $items = array_filter($cats['core'], function($c) use ($sub) { return $c['sub_category'] == $sub; });
                            ?>
                            <div class="mb-3">
                                <h6><?php echo $label; ?> <small>(<?php echo array_sum(array_column($items, 'percentage')); ?>%)</small></h6>
                                <ul class="list-group list-group-sm">
                                    <?php foreach($items as $c): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-1">
                                        <small><?php echo htmlspecialchars($c['name']); ?></small>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info btn-sm edit_cat" data-id="<?php echo $c['id'] ?>"><i class="fa fa-edit"></i></button>
                                            <button type="button" class="btn btn-danger btn-sm delete_cat" data-id="<?php echo $c['id'] ?>"><i class="fa fa-trash"></i></button>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($cats['support'])): ?>
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title">Support Functions</h5>
                            <span class="badge badge-light float-right"><?php echo array_sum(array_column($cats['support'], 'percentage')); ?>%</span>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach($cats['support'] as $c): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($c['name']); ?></span>
                                    <span class="badge badge-primary"><?php echo $c['percentage']; ?>%</span>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-info edit_cat" data-id="<?php echo $c['id'] ?>"><i class="fa fa-edit"></i></button>
                                        <button type="button" class="btn btn-danger delete_cat" data-id="<?php echo $c['id'] ?>"><i class="fa fa-trash"></i></button>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fa fa-info-circle"></i> <strong>Note:</strong> Total percentage should equal 100%. Core functions are divided into Instructions, Research, and Extension.
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-tasks"></i> <span id="modal_title">Add Function Category</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="manage-category">
                <div class="modal-body">
                    <input type="hidden" name="id" id="cat_id">
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" name="category" id="cat_category" required>
                            <option value="">-- Select Category --</option>
                            <option value="strategic">Strategic Functions</option>
                            <option value="core">Core Functions</option>
                            <option value="support">Support Functions</option>
                        </select>
                    </div>
                    <div class="form-group" id="sub_category_group" style="display:none;">
                        <label>Sub-Category (for Core only)</label>
                        <select class="form-control" name="sub_category" id="cat_sub_category">
                            <option value="">-- Select Sub-Category --</option>
                            <option value="instructions">Instructions</option>
                            <option value="research">Research</option>
                            <option value="extension">Extension</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" id="cat_name" required>
                    </div>
                    <div class="form-group">
                        <label>Percentage (%)</label>
                        <input type="number" class="form-control" name="percentage" id="cat_percentage" step="0.01" min="0" max="100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#cat_category').change(function(){
        if($(this).val() == 'core') {
            $('#sub_category_group').show();
        } else {
            $('#sub_category_group').hide();
            $('#cat_sub_category').val('');
        }
    });
    
    $('#new_category').click(function(){
        $('#cat_id').val('');
        $('#cat_category').val('');
        $('#cat_sub_category').val('');
        $('#cat_name').val('');
        $('#cat_percentage').val('');
        $('#sub_category_group').hide();
        $('#modal_title').text('Add Function Category');
        $('#categoryModal').modal('show');
    });
    
    $('.edit_cat').click(function(){
        var id = $(this).data('id');
        $.ajax({
            url: 'ajax.php?action=get_function_category',
            method: 'POST',
            data: { id: id },
            success: function(resp) {
                var data = JSON.parse(resp);
                $('#cat_id').val(data.id);
                $('#cat_category').val(data.category).trigger('change');
                $('#cat_sub_category').val(data.sub_category || '');
                $('#cat_name').val(data.name);
                $('#cat_percentage').val(data.percentage);
                $('#modal_title').text('Edit Function Category');
                $('#categoryModal').modal('show');
            }
        });
    });
    
    $('.delete_cat').click(function(){
        if(confirm('Are you sure you want to delete this function category?')) {
            var id = $(this).data('id');
            $.ajax({
                url: 'ajax.php?action=delete_function_category',
                method: 'POST',
                data: { id: id },
                success: function(resp) {
                    if(resp == 1) {
                        alert_toast("Category deleted successfully", "success");
                        setTimeout(function(){ location.reload(); }, 1000);
                    }
                }
            });
        }
    });
    
    $('#manage-category').submit(function(e){
        e.preventDefault();
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_function_category',
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if(resp == 1) {
                    alert_toast("Category saved successfully", "success");
                    setTimeout(function(){ location.reload(); }, 1000);
                }
                end_load();
            }
        });
    });
});
</script>
