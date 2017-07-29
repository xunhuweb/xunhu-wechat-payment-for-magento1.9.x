<?php

class Wpopwechat_Payment_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        
        $this->setTemplate('wpopwechat/form.phtml');
        $this->setMethodTitle('');
    }

    public function getMethodLabelAfterHtml()
    {
        if (! $this->hasData('_method_label_html')) {
            $code = $this->getMethod()->getCode();
            $labelBlock = Mage::app()->getLayout()->createBlock('core/template', null, array(
                'template' => 'wpopwechat/payment/payment_method_label.phtml',
                'payment_method_icon' => $this->getSkinUrl("images/wpopwechat/logo.png"),
                'payment_method_label' => Mage::helper('wpopwechat')->getConfigData('title'),
                'payment_method_class' => $code
            ));
            
            $this->setData('_method_label_html', $labelBlock->toHtml());
        }
        return $this->getData('_method_label_html');
    }
}
