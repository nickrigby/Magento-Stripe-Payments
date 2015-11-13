<?php

class District_Stripe_Model_Stripe extends Mage_Payment_Model_Method_Abstract {
  
  protected $_code = 'stripe';
  protected $_formBlockType = 'stripe/form_cc';
  protected $_infoBlockType = 'stripe/info_cc';
  
  /**
   * Payment Method features
   * @var bool
   */
  protected $_isGateway                   = false;
  protected $_canOrder                    = true;
  protected $_canAuthorize                = true;
  protected $_canCapture                  = true;
  protected $_canRefund                   = true;
  protected $_canRefundInvoicePartial     = true;
  protected $_canVoid                     = true;
  protected $_canUseInternal              = true;
  protected $_canUseCheckout              = true;
  protected $_canUseForMultishipping      = true;
  protected $_isInitializeNeeded          = false;
  protected $_canFetchTransactionInfo     = true;
  protected $_canReviewPayment            = true;
  protected $_canCreateBillingAgreement   = false;
  protected $_canManageRecurringProfiles  = true;
  protected $_canSaveCc                   = false;
  protected $_isAvailable                 = true;
  
  //Stripe specific
  private $_apiSecretKey;
  private $_token;
  
  public function __construct()
  {
    parent::__construct();
    
    $this->_apiSecretKey = $this->getConfigData('api_secret_key');
  }
  
  /**
   * Capture the payment
   *
   * @param Varien_Object $payment
   * @param float $amount
   *
   * @return Mage_Payment_Model_Abstract
   */
  public function capture(Varien_Object $payment, $amount)
  { 
    //Call parent capture function
    parent::capture($payment, $amount);
    
    //Check amount is okay
    if ($amount <= 0) {
      Mage::throwException(Mage::helper('payment')->__('Invalid amount for authorization.'));
    }

    //Create the charge (authorization and capture)
    if($payment->getLastTransId()) {
      $charge = $this->_retrieveCharge($payment->getLastTransId());
    } else {
      $charge = $this->_createCharge($payment, $amount, true);
    }
    
    //Create the payment in Magento
    $this->_createPayment($payment, $charge, $amount, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
    
    //Skip transaction creation
    $payment->setSkipTransactionCreation(true);

    return $this;
  }
  
  /**
   * Authorize payment abstract method
   *
   * @param Varien_Object $payment
   * @param float $amount
   *
   * @return Mage_Payment_Model_Abstract
   */
  public function authorize(Varien_Object $payment, $amount)
  {
     Mage::log('auth');
    
    //Call parent authorize function
    parent::authorize($payment, $amount);
    
    //Check amount is okay
    if ($amount <= 0) {
      Mage::throwException(Mage::helper('payment')->__('Invalid amount for authorization.'));
    }
    
    //Create the charge (authorization only)
    $charge = $this->_createCharge($payment, $amount, false);
    
    //Create the payment in Magento
    $this->_createPayment($payment, $charge, $amount, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
    
    //Skip transaction creation
    $payment->setSkipTransactionCreation(true);
    
    return $this;
  }
  
  public function processInvoice($invoice, $payment)
  {
    parent::processInvoice($invoice, $payment);
    
    Mage::log('processInvoice');
    
    return $this;
    
  }
  
  /**
   * Validate payment method
   *
   * @param   Mage_Payment_Model_Info $info
   * @return  Mage_Payment_Model_Abstract
   */
  public function validate()
  {
    //Call parent validate
    parent::validate();
    
    //Get the token from the form
    $tokenString = trim($_POST['stripeToken']);
    
    //If the token isn't empty
    if(!empty($tokenString)) {
      $this->_getToken($tokenString);
    } else {
      Mage::throwException(Mage::helper('payment')->__('Token is empty.'));
    }
    
    return $this;
  }
  
  /**
   * Sets the payment information
   *
   * @param   none
   * @return  none
   */
  protected function _createPayment(Varien_Object $payment, $charge, $amount, $requestType)
  {
    //Transaction id
    $payment->setTransactionId($charge->id);
    $payment->setIsTransactionClosed(0);
    $payment->setAmount($amount);
    
    //Add payment cc information
    $payment->setcc_exp_month($charge->source->exp_month);
    $payment->setcc_exp_year($charge->source->exp_year);
    $payment->setcc_last4($charge->source->last4);
    $payment->setcc_owner($charge->source->name);
    $payment->setcc_type($charge->source->brand);
    $payment->setcc_trans_id($charge->source->id);

    //Add payment fraud information
    $payment->setcc_avs_status($charge->source->address_line1_check . '/' . $charge->source->address_zip_check);
    $payment->setcc_cid_status($charge->source->cvc_check); //CID = CVC

    //Add any additional information
    $payment->setadditional_information(array(
      'funding' => $charge->source->funding,
      'country' => $charge->source->country
    ));
    
    //Add the transaction
    $payment->addTransaction($requestType, null, true);
  }
  
  /**
   * Sets the API key for interfacing with Stripe API
   *
   * @param   none
   * @return  none
   */
  protected function _setApiKey()
  {
    try {
      \Stripe\Stripe::setApiKey($this->_apiSecretKey);
    } catch (Exception $e) {
      Mage::throwException('Stripe: Could not set API key');
    }
  }
  
  /**
   * Get the token from Stripe based on passed in tokenString
   *
   * @param   stripe token string
   * @return  none
   */
  protected function _getToken($tokenString)
  {
    $this->_setApiKey();
    
    try {
      $this->_token = \Stripe\Token::retrieve($tokenString);
    } catch (Exception $e) {
      Mage::throwException('Stripe: Invalid token');
    }
  }
  
  /**
   * Create a charge
   *
   * @param   stripe token string
   * @return  none
   */
  protected function _createCharge(Varien_Object $payment, $amount, $capture = true)
  {
    $this->_setApiKey();
    
    try {
      $charge = \Stripe\Charge::create(array(
        'amount' => $amount * 100,
        'currency' => $payment->getOrder()->getBaseCurrencyCode(),
        'source' => $this->_token,
        'capture' => $capture,
        'description' => sprintf('Payment for order #%s on %s', $payment->getOrder()->getIncrementId(), $payment->getOrder()->getStore()->getFrontendName())
      ));
    } catch (\Stripe\Error\InvalidRequest $e) {
      Mage::throwException($e);
    } catch (\Stripe\Error\Card $e) {
      Mage::throwException($e);
    }
    
    return $charge;
  }
  
  protected function _retrieveCharge($transactionId)
  {
    $this->_setApiKey();
    
    try {
      $charge = \Stripe\Charge::retrieve($transactionId);
    } catch (\Stripe\Error\InvalidRequest $e) {
      Mage::throwException($e);
    } catch (\Stripe\Error\Card $e) {
      Mage::throwException($e);
    }
    
    return $charge;
  }

}