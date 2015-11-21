<?php

class District_Stripe_Helper_Data extends Mage_Core_Helper_Abstract
{
  /**
   * Sets the API key for interfacing with Stripe API
   *
   * @param   none
   * @return  none
   */
  public function setApiKey()
  {
    try {
      \Stripe\Stripe::setApiKey(Mage::getStoreConfig('payment/stripe/api_secret_key'));
    } catch (Exception $e) {
      Mage::throwException('Stripe: Could not set API key');
    }
  }
  
  public function isCustomer()
  {
    //Get logged in customer
    $customer = Mage::getSingleton('customer/session')->getCustomer();
    $stripeCustomerModel = Mage::getModel('stripe/customer');
    $stripeCustomer = $stripeCustomerModel->load($customer->getId(), 'customer_id');
    
    if(!$stripeCustomer->getId()) {
      return false;
    }
    
    return true;
  }
  
  /**
   * Retrieve a customer from Stripe
   *
   * @param   token
   * @return  none
   */
  public function retrieveCustomer($token)
  {
    $this->setApiKey();
    
    try {
      $customer = \Stripe\Customer::retrieve(Mage::helper('core')->decrypt($token));
    } catch(Exception $e) {
      Mage::throwException('Stripe: Could not retrieve customer');
    }
    
    return $customer;
  }
  
  /**
   * Get a customer from Database
   *
   * @param   token
   * @return  none
   */
  public function getCustomer()
  {
    $customer = Mage::getSingleton('customer/session')->getCustomer();
    $model = Mage::getModel('stripe/customer');
    
    return $model->load($customer->getId(), 'customer_id');
  }
  
}