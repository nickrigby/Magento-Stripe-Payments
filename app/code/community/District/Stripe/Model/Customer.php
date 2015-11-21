<?php

class District_Stripe_Model_Customer extends Mage_Core_Model_Abstract {
  
  protected function _construct()
  {
    $this->_init('stripe/customer');
  }

}