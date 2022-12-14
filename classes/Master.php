<?php
require_once('../config.php');

require './../libs/mailer/PHPMailer/src/Exception.php';
require './../libs/mailer/PHPMailer/src/PHPMailer.php';
require './../libs/mailer/PHPMailer/src/SMTP.php';
require './../libs/phpqrcode/qrlib.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



class Master extends DBConnection
{
	private $settings;
	public function __construct()
	{
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct()
	{
		parent::__destruct();
	}
	function capture_err()
	{
		if (!$this->conn->error)
			return false;
		else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_category()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'description'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if (isset($_POST['description'])) {
			if (!empty($data)) $data .= ",";
			$data .= " `description`='" . addslashes(htmlentities($description)) . "' ";
		}
		$check = $this->conn->query("SELECT * FROM `categories` where `category` = '{$category}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Category already exist.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `categories` set {$data} ";
			$save = $this->conn->query($sql);
		} else {
			$sql = "UPDATE `categories` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if ($save) {
			$resp['status'] = 'Thành công';
			if (empty($id))
				$this->settings->set_flashdata('Thành công', "Danh mục mới đã được lưu thành công.");
			else
				$this->settings->set_flashdata('Thành công', "Đã cập nhật danh mục thành công.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_category()
	{
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `categories` where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('Thành công', "Danh mục đã được xóa thành công.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_sub_category()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'description'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if (isset($_POST['description'])) {
			if (!empty($data)) $data .= ",";
			$data .= " `description`='" . addslashes(htmlentities($description)) . "' ";
		}
		$check = $this->conn->query("SELECT * FROM `sub_categories` where `sub_category` = '{$sub_category}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Sub Category already exist.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `sub_categories` set {$data} ";
			$save = $this->conn->query($sql);
		} else {
			$sql = "UPDATE `sub_categories` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if ($save) {
			$resp['status'] = 'success';
			if (empty($id))
				$this->settings->set_flashdata('success', "New Sub Category successfully saved.");
			else
				$this->settings->set_flashdata('success', "Sub Category successfully updated.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_sub_category()
	{
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `sub_categories` where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "Sub Category successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_product()
	{
		foreach ($_POST as $k => $v) {
			$_POST[$k] = addslashes($v);
		}
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'description'))) {
				if (!empty($data)) $data .= ",";
				$v = addslashes($v);
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if (isset($_POST['description'])) {
			if (!empty($data)) $data .= ",";
			$data .= " `description`='" . addslashes(htmlentities($description)) . "' ";
		}
		$check = $this->conn->query("SELECT * FROM `products` where `title` = '{$title}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Book already exist.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `products` set {$data} ";
			$save = $this->conn->query($sql);
			$id = $this->conn->insert_id;
		} else {
			$sql = "UPDATE `products` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if ($save) {
			$upload_path = "uploads/product_" . $id;
			if (!is_dir(base_app . $upload_path))
				mkdir(base_app . $upload_path);
			if (isset($_FILES['img']) && count($_FILES['img']['tmp_name']) > 0) {
				foreach ($_FILES['img']['tmp_name'] as $k => $v) {
					if (!empty($_FILES['img']['tmp_name'][$k])) {
						move_uploaded_file($_FILES['img']['tmp_name'][$k], base_app . $upload_path . '/' . $_FILES['img']['name'][$k]);
					}
				}
			}
			$resp['status'] = 'Thành công';
			if (empty($id))
				$this->settings->set_flashdata('Thành công', "Đã lưu sản phẩm");
			else
				$this->settings->set_flashdata('Thành công', "Đã cập nhật sản phẩm");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_product()
	{
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `products` where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('Thành công', "Đã xóa sản phẩm thành công");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function delete_img()
	{
		extract($_POST);
		if (is_file($path)) {
			if (unlink($path)) {
				$resp['status'] = 'success';
			} else {
				$resp['status'] = 'failed';
				$resp['error'] = 'failed to delete ' . $path;
			}
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = 'Unkown ' . $path . ' path';
		}
		return json_encode($resp);
	}
	function save_inventory()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'description'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `inventory` where `product_id` = '{$product_id}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Inventory already exist.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `inventory` set {$data} ";
			$save = $this->conn->query($sql);
		} else {
			$sql = "UPDATE `inventory` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if ($save) {
			$resp['status'] = 'success';
			if (empty($id))
				$this->settings->set_flashdata('success', "New Inventory successfully saved.");
			else
				$this->settings->set_flashdata('success', "Inventory successfully updated.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_inventory()
	{
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `inventory` where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "Invenory successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function register()
	{
		extract($_POST);
		$data = "";
		$_POST['password'] = md5($_POST['password']);
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `clients` where `email` = '{$email}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `clients` set {$data} ";
			$save = $this->conn->query($sql);
			$id = $this->conn->insert_id;
		} else {
			$sql = "UPDATE `clients` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if ($save) {
			$resp['status'] = 'success';
			if (empty($id))
				$this->settings->set_flashdata('Thành công', "Tài khoản đã được tạo thành công.");
			else
				$this->settings->set_flashdata('Thành công', "Tài khoản đã cập nhật thành công.");
			foreach ($_POST as $k => $v) {
				$this->settings->set_userdata($k, $v);
			}
			$this->settings->set_userdata('id', $id);
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}

	function add_to_cart()
	{
		extract($_POST);
		$data = " client_id = '" . $this->settings->userdata('id') . "' ";
		$_POST['price'] = str_replace(",", "", $_POST['price']);
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `cart` where `inventory_id` = '{$inventory_id}' and client_id = " . $this->settings->userdata('id'))->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$sql = "UPDATE `cart` set quantity = quantity + {$quantity} where `inventory_id` = '{$inventory_id}' and client_id = " . $this->settings->userdata('id');
		} else {
			$sql = "INSERT INTO `cart` set {$data} ";
		}

		$save = $this->conn->query($sql);
		if ($this->capture_err())
			return $this->capture_err();
		if ($save) {
			$resp['status'] = 'success';
			$resp['cart_count'] = $this->conn->query("SELECT SUM(quantity) as items from `cart` where client_id =" . $this->settings->userdata('id'))->fetch_assoc()['items'];
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function update_cart_qty()
	{
		extract($_POST);

		$save = $this->conn->query("UPDATE `cart` set quantity = '{$quantity}' where id = '{$id}'");
		if ($this->capture_err())
			return $this->capture_err();
		if ($save) {
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function empty_cart()
	{
		$delete = $this->conn->query("DELETE FROM `cart` where client_id = " . $this->settings->userdata('id'));
		if ($this->capture_err())
			return $this->capture_err();
		if ($delete) {
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_cart()
	{
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `cart` where id = '{$id}'");
		if ($this->capture_err())
			return $this->capture_err();
		if ($delete) {
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_order()
	{
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `orders` where id = '{$id}'");
		$delete2 = $this->conn->query("DELETE FROM `order_list` where order_id = '{$id}'");
		$delete3 = $this->conn->query("DELETE FROM `sales` where order_id = '{$id}'");
		if ($this->capture_err())
			return $this->capture_err();
		if ($delete) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('Thành công', "Order successfully deleted");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function place_order()
	{
		extract($_POST);
		$client_id = $this->settings->userdata('id');

		$data = " client_id = '{$client_id}' ";
		$data .= " ,payment_method = '{$payment_method}' ";
		$data .= " ,order_type = '{$order_type}' ";
		$data .= " ,amount = '{$amount}' ";
		$data .= " ,paid = '{$paid}' ";
		$data .= " ,delivery_address = '{$delivery_address}' ";
		$order_sql = "INSERT INTO `orders` set $data";
		$save_order = $this->conn->query($order_sql);
		if ($this->capture_err())
			return $this->capture_err();
		if ($save_order) {
			$order_id = $this->conn->insert_id;
			$data = '';
			$cart = $this->conn->query("SELECT c.*,p.title,i.price,p.id as pid from `cart` c inner join `inventory` i on i.id=c.inventory_id inner join products p on p.id = i.product_id where c.client_id ='{$client_id}' ");
			while ($row = $cart->fetch_assoc()) :
				if (!empty($data)) $data .= ", ";
				$total = $row['price'] * $row['quantity'];
				$data .= "('{$order_id}','{$row['pid']}','{$row['quantity']}','{$row['price']}', $total)";
			endwhile;
			$list_sql = "INSERT INTO `order_list` (order_id,product_id,quantity,price,total) VALUES {$data} ";
			$save_olist = $this->conn->query($list_sql);
			if ($this->capture_err())
				return $this->capture_err();
			if ($save_olist) {
				$empty_cart = $this->conn->query("DELETE FROM `cart` where client_id = '{$client_id}'");
				$data = " order_id = '{$order_id}'";
				$data .= " ,total_amount = '{$amount}'";
				$save_sales = $this->conn->query("INSERT INTO `sales` set $data");
				if ($this->capture_err())
					return $this->capture_err();
				$resp['status'] = 'success';
			} else {
				$resp['status'] = 'failed';
				$resp['err_sql'] = $save_olist;
			}
		} else {
			$resp['status'] = 'failed';
			$resp['err_sql'] = $save_order;
		}

		$path = base_app . "assets/img/qr/";

		$filename = uniqid() . ".png";
		$file = $path . $filename;

		$text = "http://localhost/perfume_shop/admin/orders/view_order.php?view=user&id=19";

		$ecc = 'L';
		$pixel_Size = 10;
		$frame_size = 10;
		QRcode::png($text, $file, $ecc, $pixel_Size, $frame_size);


		$this->sendMail("levanlong220700@gmail.com", $filename);


		return json_encode($resp);
	}
	function update_order_status()
	{
		extract($_POST);
		$update = $this->conn->query("UPDATE `orders` set `status` = '$status' where id = '{$id}' ");
		if ($update) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata("Thành công", " Order status successfully updated.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function pay_order()
	{
		extract($_POST);
		$update = $this->conn->query("UPDATE `orders` set `paid` = '1' where id = '{$id}' ");
		if ($update) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata("Thành công", " Order payment status successfully updated.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function update_account()
	{
		extract($_POST);
		$data = "";
		if (!empty($password)) {
			$_POST['password'] = md5($password);
			if (md5($cpassword) != $this->settings->userdata('password')) {
				$resp['status'] = 'failed';
				$resp['msg'] = "Current Password is Incorrect";
				return json_encode($resp);
				exit;
			}
		}
		$check = $this->conn->query("SELECT * FROM `clients`  where `email`='{$email}' and `id` != $id ")->num_rows;
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		foreach ($_POST as $k => $v) {
			if ($k == 'cpassword' || ($k == 'password' && empty($v)))
				continue;
			if (!empty($data)) $data .= ",";
			$data .= " `{$k}`='{$v}' ";
		}
		$save = $this->conn->query("UPDATE `clients` set $data where id = $id ");
		if ($save) {
			foreach ($_POST as $k => $v) {
				if ($k != 'cpassword')
					$this->settings->set_userdata($k, $v);
			}

			$this->settings->set_userdata('id', $this->conn->insert_id);
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	function payWithMomo()
	{
		header('Content-type: text/html; charset=utf-8');
		$config = file_get_contents('./../payment/momo/config.json');

		$array = json_decode($config, true);

		include './../payment/helper.php';

		$endpoint =
			"https://test-payment.momo.vn/gw_payment/transactionProcessor";

		$partnerCode = $array["partnerCode"];
		$accessKey = $array["accessKey"];
		$secretKey = $array["secretKey"];
		$orderInfo = "Thanh toán qua MoMo";
		$orderId = time() . "";
		$requestId = time() . "";
		$returnUrl = $array['baseUrl']  . "?p=checkout";
		$notifyurl =
			$array['baseUrl'] . "?p=checkout";
		// Lưu ý: link notifyUrl không phải là dạng localhost
		$extraData = "merchantName=Perfume Shop";
		$requestType = "captureMoMoWallet";
		$amount = $_POST['amount'];

		$amount < 1000 ? $amount = '1000' : $amount = $amount;

		$rawHash = "partnerCode=" . $partnerCode . "&accessKey=" . $accessKey . "&requestId=" . $requestId . "&amount=" . $amount . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&returnUrl=" . $returnUrl . "&notifyUrl=" . $notifyurl . "&extraData=" . $extraData;
		$signature = hash_hmac("sha256", $rawHash, $secretKey);


		$data = array(
			'partnerCode' => $partnerCode,
			'accessKey' => $accessKey,
			'requestId' => $requestId,
			'amount' => $amount,
			'orderId' => $orderId,
			'orderInfo' => $orderInfo,
			'returnUrl' => $returnUrl,
			'notifyUrl' => $notifyurl,
			'extraData' => $extraData,
			'requestType' => $requestType,
			'signature' => $signature
		);

		$result = execPostRequest($endpoint, json_encode($data));

		return $result;
	}

	function payWithVNPay()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');

		$config = file_get_contents('./../payment/vnpay/config.json');

		$array = json_decode($config, true);

		include './../payment/helper.php';

		$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";

		$vnp_TmnCode = $array['vnp_TmnCode']; //Mã website tại VNPAY 
		$vnp_HashSecret = $array['vnp_HashSecret']; //Chuỗi bí mật

		$vnp_Returnurl = $array['baseUrl'] . "?p=checkout";

		$vnp_TxnRef = time();
		$vnp_OrderInfo = "Thanh toán qua VNPay";
		$vnp_OrderType = 'billpayment';
		$vnp_Amount = $_POST['amount'] < 1000 ? 50000 * 100 : $_POST['amount'] * 100;
		$vnp_Locale = 'vn';
		$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

		$inputData = array(
			"vnp_Version" => "2.0.0",
			"vnp_TmnCode" => $vnp_TmnCode,
			"vnp_Amount" => $vnp_Amount,
			"vnp_Command" => "pay",
			"vnp_CreateDate" => date('YmdHis'),
			"vnp_CurrCode" => "VND",
			"vnp_IpAddr" => $vnp_IpAddr,
			"vnp_Locale" => $vnp_Locale,
			"vnp_OrderInfo" => $vnp_OrderInfo,
			"vnp_OrderType" => $vnp_OrderType,
			"vnp_ReturnUrl" => $vnp_Returnurl,
			"vnp_TxnRef" => $vnp_TxnRef,
		);

		if (isset($vnp_BankCode) && $vnp_BankCode != "") {
			$inputData['vnp_BankCode'] = $vnp_BankCode;
		}

		ksort($inputData);
		$query = "";

		$i = 0;
		$hashdata = "";

		foreach ($inputData as $key => $value) {
			if ($i == 1) {
				$hashdata .= '&' . $key . "=" . $value;
			} else {
				$hashdata .= $key . "=" . $value;
				$i = 1;
			}
			$query .= urlencode($key) . "=" . urlencode($value) . '&';
		}

		$vnp_Url = $vnp_Url . "?" . $query;
		if (isset($vnp_HashSecret)) {
			$vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //  
			$vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
		}

		return json_encode($vnp_Url);
	}

	function sendMail($to, $image_path)
	{
		$send_mail = new PHPMailer(true);

		try {

			$path = base_app . "assets/img/qr/";

			// read file json config
			$email_config = json_decode(file_get_contents('./../libs/mailer/config.json'), true);
			//Server settings
			$send_mail->isSMTP(); // gửi mail SMTP
			$send_mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
			$send_mail->SMTPAuth = true; // Enable SMTP authentication
			$send_mail->Username = $email_config['mFrom']; // SMTP username
			$send_mail->Password = $email_config['mPass']; // SMTP password
			$send_mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
			$send_mail->Port = $email_config['port']; // TCP port to connect to
			//Recipients
			$send_mail->setFrom('sales@perfume-shop.com', $email_config['mFrom']);
			$send_mail->addAddress($to); // Add a recipient
			// Content
			$send_mail->isHTML(true);   // Set email format to HTML
			$send_mail->addEmbeddedImage($path . $image_path, 'qr');
			$send_mail->Subject = '[Perfume Shop - No Reply] - Notification Order';
			$send_mail->Body = '<p><strong>Ch&agrave;o bạn,</strong></p>
								<p>Cảm ơn bạn đ&atilde; đặt mua sản phẩm của ch&uacute;ng t&ocirc;i.</p>
								<p>Bạn c&oacute; thể theo d&otilde;i th&ocirc;ng tin đơn h&agrave;ng của m&igrave;nh tại đ&acirc;y.</p>
								<p><img src="cid:qr"/></p>
								<p><br></p>
								<p><span style="font-size: 12px;">Đ&acirc;y l&agrave; mail tự động. Vui l&ograve;ng kh&ocirc;ng trả lời lại email n&agrave;y.</span></p>';

			$send_mail->send();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_category':
		echo $Master->save_category();
		break;
	case 'delete_category':
		echo $Master->delete_category();
		break;
	case 'save_sub_category':
		echo $Master->save_sub_category();
		break;
	case 'delete_sub_category':
		echo $Master->delete_sub_category();
		break;
	case 'save_product':
		echo $Master->save_product();
		break;
	case 'delete_product':
		echo $Master->delete_product();
		break;

	case 'save_inventory':
		echo $Master->save_inventory();
		break;
	case 'delete_inventory':
		echo $Master->delete_inventory();
		break;
	case 'register':
		echo $Master->register();
		break;
	case 'add_to_cart':
		echo $Master->add_to_cart();
		break;
	case 'update_cart_qty':
		echo $Master->update_cart_qty();
		break;
	case 'delete_cart':
		echo $Master->delete_cart();
		break;
	case 'empty_cart':
		echo $Master->empty_cart();
		break;
	case 'delete_img':
		echo $Master->delete_img();
		break;
	case 'place_order':
		echo $Master->place_order();
		break;
	case 'update_order_status':
		echo $Master->update_order_status();
		break;
	case 'pay_order':
		echo $Master->pay_order();
		break;
	case 'update_account':
		echo $Master->update_account();
		break;
	case 'delete_order':
		echo $Master->delete_order();
		break;

	case 'pay_with_momo':
		echo $Master->payWithMomo();
		break;

	case 'pay_with_vnpay':
		echo $Master->payWithVNPay();
		break;
	default:
		// echo $sysset->index();
		break;
}
