<?php

class Wechat_Payment_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        
        $this->setTemplate('wechat/form.phtml');
        $this->setMethodTitle('');
    }

    public function getMethodLabelAfterHtml()
    {
        if (! $this->hasData('_method_label_html')) {
            $code = $this->getMethod()->getCode();
            $labelBlock = Mage::app()->getLayout()->createBlock('core/template', null, array(
                'template' => 'wechat/payment/payment_method_label.phtml',
                'payment_method_icon' => $this->getSkinUrl("images/wechat/logo.png"),
                'payment_method_label' => Mage::helper('wechat')->getConfigData('title'),
                'payment_method_class' => $code
            ));
            
            $this->setData('_method_label_html', $labelBlock->toHtml());
        }
        return $this->getData('_method_label_html');
    }
}
