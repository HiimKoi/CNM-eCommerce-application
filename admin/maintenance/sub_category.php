<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Danh sách hãng nước hoa</h3>
		<div class="card-tools">
			<a href="?page=maintenance/manage_sub_category" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Tạo</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-bordered table-stripped">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="15%">
					<col width="20%">
					<col width="25%">
					<col width="10%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Ngày tạo</th>
						<th>Loại</th>
						<th>Hãng</th>
						<th>Chi tiết</th>
						<th>Trạng thái</th>
						<th>Tùy chọn</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
						$qry = $conn->query("SELECT s.*,c.category from `sub_categories` s inner join `categories` c on c.id = s.parent_id order by unix_timestamp(s.date_created) desc ");
						while($row = $qry->fetch_assoc()):
                            $row['description'] = strip_tags(stripslashes(html_entity_decode($row['description'])));
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
							<td><?php echo $row['category'] ?></td>
							<td><?php echo $row['sub_category'] ?></td>
							<td ><p class="truncate-1 m-0"><?php echo $row['description'] ?></p></td>
							<td class="text-center">
                                <?php if($row['status'] == 1): ?>
                                    <span class="badge badge-success">Sẵn sàng</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Không sẵn sàng</span>
                                <?php endif; ?>
                            </td>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
								 	Tùy chọn
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
				                    <a class="dropdown-item" href="?page=maintenance/manage_sub_category&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Chỉnh sửa</a>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Xóa</a>
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
		$('.delete_data').click(function(){
			_conf("Bạn có chắc chắn xóa hãng sản phẩm này vĩnh viễn không?","delete_category",[$(this).attr('data-id')])
		})
		$('table').dataTable({
                language: {
                    lengthMenu: "Hiển thị _MENU_ dòng mỗi trang",
                    search: "Tìm kiếm:",
                    info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                }
            });
	})
	function delete_category($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_sub_category",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
</script>