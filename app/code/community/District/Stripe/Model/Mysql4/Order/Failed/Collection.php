<?php

class District_Stripe_Model_Mysql4_Order_Failed_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
  
  protected function _construct()
  {
    $this->_init('stripe/order_failed');
  }
    
}