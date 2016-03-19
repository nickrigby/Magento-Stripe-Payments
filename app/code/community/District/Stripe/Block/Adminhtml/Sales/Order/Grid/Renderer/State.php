<?php
/**
 * District Commerce
 *
 * @category    District
 * @package     Stripe
 * @author      District Commerce <support@districtcommerce.com>
 * @copyright   Copyright (c) 2015 District Commerce (http://districtcommerce.com)
 *
 */

class District_Stripe_Block_Adminhtml_Sales_Order_Grid_Renderer_State extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $result = '';
        $order = Mage::getModel('sales/order')->load($row->getId());
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance()->getCode();

        if($method === 'stripe_cc') {

            //Get CVC result
            $cvc_result = $payment->getCcCidStatus();

            //Get AVS result (stored in two parts: line1/zip)
            $avs_status = explode('/', $payment->getCcAvsStatus());

            //Get declined orders
            $declined_orders = Mage::helper('stripe')->getDeclinedOrdersCount($order->getIncrementId());

            //Show the status icon
            if($cvc_result !== 'pass' || $avs_status[1] !== 'pass' || $declined_orders > 0) {
                $result = '<i class="icon-warning"></i>';
            } else {
                $result = '<i class="icon-pass"></i>';
            }

        }

        return $result;
    }

}
