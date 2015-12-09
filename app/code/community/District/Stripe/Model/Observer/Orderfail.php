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

class District_Stripe_Model_Observer_Orderfail extends Varien_Event_Observer {

    /**
     * This an observer function for the event 'controller_front_init_before'.
     *
     * @param Varien_Event_Observer $event
     */
    public function save(Varien_Event_Observer $observer) {
      
      //Get quote object
      $quote = $observer->getEvent()->getQuote();
      
      //Get payment
      $payment = $quote->getPayment();
      
      //Get failed order model
      $model = Mage::getModel('stripe/order_failed');
      
      //Get additional info (contains error)
      $info = $payment->getAdditionalInformation();
      
      //Save failed order
      $model->setOrderId($quote->getReservedOrderId());
      $model->setCcType($payment->getCcType());
      $model->setCcLast4($payment->getCcLast4());
      $model->setAmount($quote->getBaseGrandTotal());
      $model->setReason($info['error']);
      $model->save();
    }

}