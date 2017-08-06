<?php

class Wpopwechat_Payment_NotifyController extends Mage_Core_Controller_Front_Action
{
    /**
     * Instantiate notify model and pass notify request to it
     */
    public function indexAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $data = $this->getRequest()->getPost();
        if(!isset($data['hash']) ||!isset($data['trade_order_id'])){
            return;
        }
        
        if(isset($data['plugins'])&&$data['plugins']!='magento-wechat'){
            return;
        }
        
        $helper =Mage::helper('wpopwechat');
        $hashkey          = $helper->getConfigData('app_secret');
        $app_id           = $helper->getConfigData('app_id');
        $hash             = $helper->generate_xh_hash($data,$hashkey);
        if($data['hash']!=$hash){
            return;
        }
        
        $order_id = $data['trade_order_id'];
        $transaction_id = isset($data['transacton_id'])?$data['transacton_id']:'';
        
        try{
            $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
            if (! $order || ! $order->getId() || ! $order instanceof Mage_Sales_Model_Order) {
                throw new Exception('unknow order');
            }
            
            if (!in_array($order->getState(), array(
                Mage_Sales_Model_Order::STATE_NEW,
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
            
            ))) {
                $params = array(
                    'action'=>'success',
                    'appid'=>$app_id
                );
                
                $params['hash']=$helper->generate_xh_hash($params, $hashkey);
                ob_clean();
                print json_encode($params);
                exit;
            }
             
            $payment = $order->getPayment();
            if( $payment->getMethod()!='wpopwechat'){
                throw new Exception('unknow order payment method');
            }
            
            $payment->setTransactionId($transaction_id)
            ->registerCaptureNotification($order->getGrandTotal(), true);
            $order->save();
            
            // notify customer
            $invoice = $payment->getCreatedInvoice();
            if ($invoice && ! $order->getEmailSent()) {
                $order->sendNewOrderEmail()
                ->addStatusHistoryComment(Mage::helper('wpopwechat')->__('Notified customer about invoice #%s.', $invoice->getIncrementId()))
                ->setIsCustomerNotified(true)
                ->save();
            }
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($order->getQuoteId());
            $session->getQuote()->setIsActive(false)->save();
            
        }catch(Exception $e){
            //looger
            $helper->log( $e->getMessage());
            $params = array(
                'action'=>'fail',
                'appid'=>$app_id,
                'errcode'=>$e->getCode(),
                'errmsg'=>$e->getMessage()
            );
        
            $params['hash']=$helper->generate_xh_hash($params, $hashkey);
            ob_clean();
            print json_encode($params);
            exit;
        }
        
        $params = array(
            'action'=>'success',
            'appid'=>$app_id
        );
        
        $params['hash']=$helper->generate_xh_hash($params, $hashkey);
        ob_clean();
        print json_encode($params);
        exit;
    }
}
