<!-- <script src="https://www.paypalobjects.com/api/checkout.js"></script> -->
<?php
$total = 0;
$qry = $conn->query("SELECT c.*,p.title,i.price,p.id as pid from `cart` c inner join `inventory` i on i.id=c.inventory_id inner join products p on p.id = i.product_id where c.client_id = " . $_settings->userdata('id'));
while ($row = $qry->fetch_assoc()) :
    $total += $row['price'] * $row['quantity'];
endwhile;

?>
<section class="py-5">
    <div class="container">
        <div class="card rounded-0">
            <div class="card-body"></div>
            <h3 class="text-center"><b>MUA HÀNG</b></h3>
            <hr class="border-dark">
            <form action="#" id="place_order" method="POST">
                <input type="hidden" name="amount" value="<?php echo $total ?>">
                <input type="hidden" name="payment_method" value="cod">
                <input type="hidden" name="paid" value="0">
                <div class="row row-col-1 justify-content-center">
                    <div class="col-6">
                        <div class="form-group col mb-0">
                            <label for="" class="control-label">Phương thức giao hàng</label>
                        </div>
                        <div class="form-group d-flex pl-2">
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input custom-control-input-primary custom-control-input-outline" type="radio" id="customRadio5" name="order_type" value="1" checked="">
                                <label for="customRadio5" class="custom-control-label">Giao hàng tận nơi</label>
                            </div>
                            <div class="custom-control custom-radio ml-3">
                                <input class="custom-control-input custom-control-input-primary" type="radio" id="customRadio4" name="order_type" value="2">
                                <label for="customRadio4" class="custom-control-label">Đến cửa hàng</label>
                            </div>
                        </div>
                        <div class="form-group col address-holder">
                            <label for="" class="control-label">Địa chỉ giao hàng</label>
                            <textarea id="" cols="30" rows="3" name="delivery_address" class="form-control" style="resize:none"><?php echo $_settings->userdata('default_delivery_address') ?></textarea>
                        </div>
                        <div class="col">
                            <span>
                                <h4><b>Tổng:</b> <?php echo number_format($total) ?></h4>
                            </span>
                        </div>
                        <hr>
                        <div class="col my-3">
                            <h4 class="text-muted">Phương thức thanh toán</h4>
                            <div class="d-flex w-100 justify-content-between">
                                <button class="btn btn-flat btn-dark">Thanh toán khi nhận hàng</button>
                                <button id="momo-payment" onclick="payWithMomo()" type="button">Momo</button>
                                <button id="momo-payment" onclick="payWithVNPay()" type="button">VNPay</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>


<script>
    function payment_online() {
        $('[name="payment_method"]').val("Thanh toán Online")
        $('[name="paid"]').val(1)
        callAPI()
    }

    function payWithMomo(e) {
        start_loader()

        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=pay_with_momo',
            method: 'POST',
            data: {
                amount: '<?php echo $total; ?>',
            },
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("an error occured", "error")
                end_loader();
            },

            success: function(resp) {
                if (resp.errorCode) {
                    alert_toast(resp.localMessage, "error")
                    end_loader();
                } else {
                    var payUrl = resp.payUrl;

                    location.href = payUrl;
                }
            }
        })
    }

    function payWithVNPay() {
        start_loader()

        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=pay_with_vnpay',
            method: 'POST',
            data: {
                amount: '<?php echo $total; ?>',
            },
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("an error occured", "error")
                end_loader();
            },

            success: function(resp) {
                if (resp.errorCode) {
                    alert_toast(resp.localMessage, "error")
                    end_loader();
                } else {
                    var payUrl = resp;

                    location.href = payUrl;
                }
            }

        }).then(function(resp) {
            console.log(resp)
            end_loader();
        })
    }

    function callAPI() {
        $.ajax({
            url: 'classes/Master.php?f=place_order',
            method: 'POST',
            data: $('#place_order').serializeArray(),
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("an error occured", "error")
                end_loader();
            },
            success: function(resp) {
                if (!!resp.status && resp.status == 'success') {
                    alert_toast("Order Successfully placed.", "success")
                    setTimeout(function() {
                        location.replace('./')
                    }, 2000)
                } else {
                    console.log(resp)
                    alert_toast("an error occured", "error")
                    end_loader();
                }
            }
        })
    }

    $(document).ready(function() {
        $('[name="order_type"]').change(function() {
            if ($(this).val() == 2) {
                $('.address-holder').hide('slow')
            } else {
                $('.address-holder').show('slow')
            }
        })
        $('#place_order').submit(function(e) {
            e.preventDefault()
            start_loader();
            callAPI();

        })
    })
</script>

<?php

if (!empty($_GET)) {
    if (isset($_GET['vnp_OrderInfo'])) {
        $config = file_get_contents('./payment/vnpay/config.json');
        $array = json_decode($config, true);

        $vnp_SecureHash = $_GET['vnp_SecureHash'];
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $array['vnp_HashSecret']);
        if ($secureHash == $vnp_SecureHash) {
            if ($_GET['vnp_ResponseCode'] == '00') {
                echo "<script>
                    alert_toast('Thanh toán thành công');
                    payment_online();
                </script>";

                return;
            } else {
                echo "<script>
                    alert_toast('Thanh toán thất bại');
                </script>";
                return;
            }
        } else {
            echo "<script>
                alert_toast(\"This transaction could be hacked, please check your signature and returned signature\", \"error\");
                </script>";

            return;
        }
    } else {
        $partnerCode = isset($_GET["partnerCode"]) ? $_GET["partnerCode"] : "";
        $accessKey = isset($_GET["accessKey"]) ? $_GET["accessKey"] : "";
        $orderId = isset($_GET["orderId"]) ? $_GET["orderId"] : "";
        $localMessage = isset($_GET["localMessage"]) ? $_GET["localMessage"] : "";
        $message = isset($_GET["message"]) ? $_GET["message"] : "";
        $transId = isset($_GET["transId"]) ? $_GET["transId"] : "";
        $orderInfo = isset($_GET["orderInfo"]) ? $_GET["orderInfo"] : "";
        $amount = isset($_GET["amount"]) ? $_GET["amount"] : "";
        $errorCode = isset($_GET["errorCode"]) ? $_GET["errorCode"] : "";
        $responseTime = isset($_GET["responseTime"]) ? $_GET["responseTime"] : "";
        $requestId = isset($_GET["requestId"]) ? $_GET["requestId"] : "";
        $extraData = isset($_GET["extraData"]) ? $_GET["extraData"] : "";
        $payType = isset($_GET["payType"]) ? $_GET["payType"] : "";
        $orderType = isset($_GET["orderType"]) ? $_GET["orderType"] : "";
        $extraData = isset($_GET["extraData"]) ? $_GET["extraData"] : "";
        $m2signature = isset($_GET["signature"]) ? $_GET["signature"] : "";

        if (
            $partnerCode && $accessKey && $orderId && $localMessage && $message && $transId && $orderInfo && $amount && ($errorCode || $errorCode == '0') && $responseTime && $requestId && $extraData && $payType && $orderType && $extraData && $m2signature
        ) {
            $rawHash = "partnerCode=" . $partnerCode . "&accessKey=" . $accessKey . "&requestId=" . $requestId . "&amount=" . $amount . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
                "&orderType=" . $orderType . "&transId=" . $transId . "&message=" . $message . "&localMessage=" . $localMessage . "&responseTime=" . $responseTime . "&errorCode=" . $errorCode .
                "&payType=" . $payType . "&extraData=" . $extraData;


            $config = file_get_contents('./payment/momo/config.json');
            $array = json_decode($config, true);

            $secretKey = $array['secretKey'];

            $partnerSignature = hash_hmac("sha256", $rawHash, $secretKey);

            if ($m2signature == $partnerSignature) {
                if ($errorCode == '0') {
                    echo "<script>
                    alert_toast('Thanh toán thành công');
                    payment_online();
                </script>";

                    return;
                } else {
                    echo "<script>
                    alert_toast('Thanh toán thất bại');
                </script>";

                    return;
                }
            } else {
                echo "<script>
                alert_toast(\"This transaction could be hacked, please check your signature and returned signature\", \"error\");
                </script>";

                return;
            }
        }

        return;
    }
}

?>