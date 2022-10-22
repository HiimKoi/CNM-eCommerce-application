<?php if(isset($_GET['view'])): 
require_once('../../config.php');
endif;?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php 
if(!isset($_GET['id'])){
    $_settings->set_flashdata('error','No order ID Provided.');
    redirect('admin/?page=orders');
}
$order = $conn->query("SELECT o.*,concat(c.firstname,' ',c.lastname) as client FROM `orders` o inner join clients c on c.id = o.client_id where o.id = '{$_GET['id']}' ");
if($order->num_rows > 0){
    foreach($order->fetch_assoc() as $k => $v){
        $$k = $v;
    }
}else{
    $_settings->set_flashdata('error','Order ID provided is Unknown');
    redirect('admin/?page=orders');
}
?>
<div class="card card-outline card-primary">
    <div class="card-body">
        <div class="conitaner-fluid">
            <p><b>Tên khách hàng: <?php echo $client ?></b></p>
            <?php if($order_type == 1): ?>
            <p><b>Địa chỉ giao hàng: <?php echo $delivery_address ?></b></p>
            <?php endif; ?>
            <table class="table-striped table table-bordered">
                <colgroup>
                    <col width="15%">
                    <col width="35%">
                    <col width="25%">
                    <col width="25%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Số lượng</th>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $olist = $conn->query("SELECT o.*,p.title,p.author FROM order_list o inner join products p on o.product_id = p.id where o.order_id = '{$id}' ");
                        while($row = $olist->fetch_assoc()):
                        foreach($row as $k => $v){
                            $row[$k] = trim(stripslashes($v));
                        }
                    ?>
                    <tr>
                        <td><?php echo $row['quantity'] ?></td>
                        <td>
                            <p class="m-0"><?php echo $row['title']?></p>
                            <p class="m-0"><small>Nguyên liệu: <?php echo $row['author']?></small></p>
                           
                        </td>
                        <td class="text-right"><?php echo number_format($row['price']) ?></td>
                        <td class="text-right"><?php echo number_format($row['price'] * $row['quantity']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan='3'  class="text-right">Tổng đơn hàng</th>
                        <th class="text-right"><?php echo number_format($amount) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="row">
            <div class="col-6">
                <p>Phương thức thanh toán: <?php echo $payment_method ?></p>
                <p>Tình trạng thanh toán: <?php echo $paid == 0 ? '<span class="badge badge-light text-dark">Chưa thanh toán</span>' : '<span class="badge badge-success">Đã thanh toán</span>' ?></p>
                <p>Phương thức giao hàng: <?php echo $order_type == 1 ? '<span class="badge badge-light text-dark">Giao hàng tận nơi</span>' : '<span class="badge badge-light text-dark">Đến cửa hàng</span>' ?></p>
            </div>
            <div class="col-6 row row-cols-2">
                <div class="col-3">Tình trạng đơn hàng:</div>
                <div class="col-9">
                <?php 
                    switch($status){
                        case '0':
                            echo '<span class="badge badge-light text-dark">Chờ xác nhận</span>';
	                    break;
                        case '1':
                            echo '<span class="badge badge-primary">Đang đóng gói</span>';
	                    break;
                        case '2':
                            echo '<span class="badge badge-warning">Đơn hàng đã xuất kho</span>';
	                    break;
                        case '3':
                            echo '<span class="badge badge-success">Đã giao hàng</span>';
	                    break;
                        case '5':
                            echo '<span class="badge badge-success">Đã nhận</span>';
	                    break;
                        default:
                            echo '<span class="badge badge-danger">Đã hủy</span>';
	                    break;
                    }
                ?>
                </div>
                <?php if(!isset($_GET['view'])): ?>
                <div class="col-3"></div>
                <div class="col">
                    <button type="button" id="update_status" class="btn btn-sm btn-flat btn-primary">Update Status</button>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>
<?php if(isset($_GET['view'])): ?>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>
<style>
    #uni_modal>.modal-dialog>.modal-content>.modal-footer{
        display:none;
    }
    #uni_modal .modal-body{
        padding:0;
    }
</style>
<?php endif; ?>
<script>
    $(function(){
        $('#update_status').click(function(){
            uni_modal("Update Status", "./orders/update_status.php?oid=<?php echo $id ?>&status=<?php echo $status ?>")
        })
    })
</script>