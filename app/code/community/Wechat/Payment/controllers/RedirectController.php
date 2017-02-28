<?php

class Wechat_Payment_RedirectController extends Mage_Core_Controller_Front_Action {

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
    	$order = Mage::helper('wechat')->getOrder();
    	if(!in_array($order->getState(), array(
    	    Mage_Sales_Model_Order::STATE_NEW,
    	    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
    	     
    	))){
    	    $this->_redirectUrl(Mage::getUrl('wechat/redirect/success', array('transaction_id' => $order->getRealOrderId())));
    	    return;
    	}
    	if(!($order&&$order instanceof Mage_Sales_Model_Order)){
    	   throw new Exception('unknow order');
    	}
    	
    	$payment =$order->getPayment();
    	if( $payment->getMethod()!='wechat'){
    	    throw new Exception('unknow order payment method');
    	}
    	
    	$protocol = (! empty ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] !== 'off' || $_SERVER ['SERVER_PORT'] == 443) ? "https://" : "http://";
    	$website=$protocol.$_SERVER['HTTP_HOST'];
    	
    	$total_amount     = round($order->getGrandTotal(),2);
    	$helper =Mage::helper('wechat');
    	
    	$data=array(
    	    'version'   => '1.1',//api version
    	    'lang'       => Mage::getStoreConfig('general/locale/code'),
    	    'is_app'    =>  $helper->isWebApp()?'Y':'N',
    	    'plugins'   => 'magento-wechat',
    	    'appid'     => $helper->getConfigData('app_id'),
    	    'trade_order_id'=> $order->getRealOrderId(),
    	    'payment'   => 'wechat',
    	    'total_fee' => $total_amount,
    	    'title'     => $helper->get_order_title($order),
    	    'description'=> $helper->get_order_desc($order),
    	    'time'      => time(),
    	    'notify_url'=> Mage::getUrl('wechat/notify'),
    	    'return_url'=> Mage::getUrl('customer/account'),
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
    	
    	    if($result['errcode']!=0){
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
        	    	<title><?php print $helper->__('Redirect to wechat ...')?></title>
        	    </head>
        	    <body>
                	<?php 
            	    print $helper->__('Redirect to wechat ...');
            	    ?>
    	    		<script type="text/javascript">location.href="<?php print $result['url'];?>";</script>
        	 </body>
        	</html>
        	<?php 
    	    
    	} catch (Exception $e) {
    	    ?>
    	 <!DOCTYPE html>
    	    <html>
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
}
?>