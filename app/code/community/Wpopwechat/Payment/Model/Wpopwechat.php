<?php

class Wpopwechat_Payment_Model_Wpopwechat extends Mage_Payment_Model_Method_Abstract {
    protected $_code          = 'wpopwechat';
    protected $_formBlockType = 'wpopwechat/form';
     //protected $_infoBlockType = 'wpopwechat/info';
    protected $_order;

    protected $_isGateway               = false;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canRefund               = false;

    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('wpopwechat/redirect', array('_secure' => true));
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)->setLastTransId($this->getTransactionId());
    
        return $this;
    }
    
    public function getRepayUrl($order){
        return Mage::getUrl('wpopwechat/redirect', array('_secure' => true,'orderId'=>$order->getRealOrderId()));
    }
}
