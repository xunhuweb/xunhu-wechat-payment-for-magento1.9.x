<?php

class Wpopwechat_Payment_RedirectController extends Mage_Core_Controller_Front_Action {

//     protected function _expireAjax() {
//         if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
//             $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
//             exit;
//         }
//     }
    
//     protected function _getCheckout()
//     {
//         return Mage::getSingleton('checkout/session');
//     }

    public function indexAction() {
        $orderId = $this->getRequest()->get('orderId');
        if ($orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        } else {
            $order = Mage::helper('wpopwechat')->getOrder();
        }
        $order_id =$order->getRealOrderId();
    	if(!in_array($order->getState(), array(
    	    Mage_Sales_Model_Order::STATE_NEW,
    	    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
    	     
    	))){
    	    $this->_redirectUrl(Mage::getUrl('wpopwechat/redirect/success', array('transaction_id' => $order->getRealOrderId())));
    	    return;
    	}
    	if(!($order&&$order instanceof Mage_Sales_Model_Order)){
    	   throw new Exception('unknow order');
    	}
    	
    	$payment =$order->getPayment();
    	if( $payment->getMethod()!='wpopwechat'){
    	    throw new Exception('unknow order payment method');
    	}
    	
    	$protocol = (! empty ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] !== 'off' || $_SERVER ['SERVER_PORT'] == 443) ? "https://" : "http://";
    	$website=$protocol.$_SERVER['HTTP_HOST'];
    	
    	$total_amount     = round($order->getGrandTotal(),2);
    	$helper =Mage::helper('wpopwechat');
    	
    	$data=array(
    	    'version'   => '1.1',//api version
    	    'lang'       => Mage::getStoreConfig('general/locale/code'),
    	    'is_app'    =>  $helper->is_wpopwechat_app()?'Y':'N',
    	    'plugins'   => 'magento-wechat',
    	    'appid'     => $helper->getConfigData('app_id'),
    	    'trade_order_id'=> $order->getRealOrderId(),
    	    'payment'   => 'wechat',
    	    'total_fee' => $total_amount,
    	    'title'     => $helper->get_order_title($order),
    	    'description'=> $helper->get_order_desc($order),
    	    'time'      => time(),
    	    'notify_url'=> Mage::getUrl('wpopwechat/notify'),
    	    'return_url'=> Mage::getUrl('wpopwechat/redirect/success', array('orderId' => $order_id)),
    	    'callback_url'=>Mage::getUrl('checkout/cart'),
    	    'nonce_str' => str_shuffle(time())
    	);
    	
    	$hashkey          = $helper->getConfigData('app_secret');
    	$data['hash']     = $helper->generate_xh_hash($data,$hashkey);
    	$url              = $helper->getConfigData('transaction_url').'/payment/do.html';
    	
    	try {
    	    $response     = $helper->http_post($url, json_encode($data));
    	    $result       = $response?json_decode($response,true):null;
    	    if(!$result){
    	        throw new Exception($helper->__('Internal server error'),500);
    	    }
    	     
    	    $hash         = $helper->generate_xh_hash($result,$hashkey);
    	    if(!isset( $result['hash'])|| $hash!=$result['hash']){
    	        throw new Exception($helper->__('Invalid sign!'),40029);
    	    }
    	   
    	    if("{$result['errcode']}"!=0){
    	        throw new Exception($result['errmsg'],$result['errcode']);
    	    }
    	    
    	    $session = Mage::getSingleton('checkout/session');
    	    $session->setQuoteId($order->getRealOrderId());
    	    $session->getQuote()
    	    ->setIsActive(false)
    	    ->save();
    	    
    	    ?>
        	 <!DOCTYPE html>
        	    <html>
        	    <head>
        	    	<title><?php print $helper->__('Redirect to wpopwechat ...')?></title>
        	    </head>
        	    <body>
                	<?php 
            	    print $helper->__('Redirect to wpopwechat ...');
            	    ?>
    	    		<script type="text/javascript">location.href="<?php print $result['url'];?>";</script>
        	 </body>
        	</html>
        	<?php 
    	    
    	} catch (Exception $e) {
    	    ?>
    	    <html>
    	    <meta charset="utf-8" />
        	<title><?php print $helper->__('System error!')?></title>
        	<meta http-equiv="X-UA-Compatible" content="IE=edge">
        	<meta content="width=device-width, initial-scale=1.0" name="viewport" />
    	    
    	    <head>
    	    	<title><?php print $helper->__('Ops!Something is wrong.')?></title>
    	    </head>
    	    <body>
            <?php 
    	       echo "errcode:{$e->getCode()},errmsg:{$e->getMessage()}";
    	   ?>
    	   </body>
    	   </html>
    	   <?php
    	}
    	
    	exit;
    }
    
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    public function successAction() {
        $orderId = $this->getRequest()->get('orderId');
        if ($orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if($order){
                 
                $session = Mage::getSingleton('checkout/session');
                 
                $session->setLastSuccessQuoteId($order->getQuoteId())
                ->setLastQuoteId($order->getQuoteId())
                ->addSuccess(Mage::helper('wpopwechat')->__('Your Payment was Successful!'))
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId());
                $session->getQuote()->setIsActive(false)->save();
            }
        }
    
        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }
}
?>