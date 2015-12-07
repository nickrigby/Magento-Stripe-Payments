<?php

class District_Stripe_Model_Observer_Orderfail extends Varien_Event_Observer {

    /**
     * This an observer function for the event 'controller_front_init_before'.
     * It prepends our autoloader, so we can load the extra libraries.
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