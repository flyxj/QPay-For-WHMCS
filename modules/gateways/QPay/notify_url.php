<?php 
include_once("./log_.php");
include_once("./lib/QPayPubHelper.php");
$gatewayModule = "QPay";
$gatewayParams = getGatewayVariables($gatewayModule);
if( !$gatewayParams["type"] ) 
{
    exit( "Module Not Activated" );
}

$notify = new Notify_pub();
$xml = $GLOBALS["HTTP_RAW_POST_DATA"];
if( !$xml ) 
{
    $xml = file_get_contents("php://input");
}

$notify->saveData($xml);
if( $notify->checkSign() == false ) 
{
    $notify->setReturnParameter("return_code", "FAIL");
    $notify->setReturnParameter("return_msg", "签名失败");
}
else
{
    $notify->setReturnParameter("return_code", "SUCCESS");
}

$returnXml = $notify->returnXml();
echo $returnXml;
$log_ = new Log_();
$log_name = "./notify_url.log";
//$log_->log_result($log_name, "【接收到的notify通知】:\n" . $xml . json_decode($notify->data) . "\n");
if( $notify->checkSign() == true ) 
{
    if( $notify->data["trade_state"] == "FAIL" ) 
    {
        //$log_->log_result($log_name, "【通信出错】:\n" . $xml . "\n");
    }
    else
    {
        if( $notify->data["trade_state"] == "FAIL" ) 
        {
            //$log_->log_result($log_name, "【业务出错】:\n" . $xml . "\n");
        }
        else
        {
            //$log_->log_result($log_name, "【支付成功】:\n" . $xml . "\n");
            $success = $notify->data["trade_state"];
            $invoiceId = $notify->data["out_trade_no"];
            $transactionId = $notify->data["transaction_id"];
            $paymentAmount = $notify->data["total_fee"] / 100;
            $paymentFee = 0;
            $transactionStatus = ($success ? "SUCCESS" : "FAIL");
            $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams["name"]);
            checkCbTransID($transactionId);
            //logTransaction($gatewayParams["name"], json_decode($notify->data), $transactionStatus);
            $paymentSuccess = false;
            if( $success ) 
            {
                addInvoicePayment($invoiceId, $transactionId, $paymentAmount, $paymentFee, $gatewayModule);
                $paymentSuccess = true;
            }

            callback3DSecureRedirect($invoiceId, $paymentSuccess);
        }

    }

}
?>