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
    $charge = $this->_createCharge($payment, $amount, true);
    
    //Set payment information
    $this->_setPaymentInfo($payment, $charge);
    
    //Add the transaction
    $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, true, '');
    
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
    //Call parent authorize function
    parent::authorize($payment, $amount);
    
    //Check amount is okay
    if ($amount <= 0) {
      Mage::throwException(Mage::helper('payment')->__('Invalid amount for authorization.'));
    }

    //Create the charge (authorization only)
    $charge = $this->_createCharge($payment, $amount, false);
    
    //Set payment information
    $this->_setPaymentInfo($payment, $charge);
    
    //Add the transaction
    $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, true, '');
    
    //Skip transaction creation
    $payment->setSkipTransactionCreation(true);

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
      $this->_setApiKey();
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
  private function _setPaymentInfo($payment, $charge)
  {
    //Transaction id
    $payment->setTransactionId($charge->id);
    
    //Add payment cc information
    $payment->setcc_trans_id($charge->id); //Transaction id
    $payment->setcc_exp_month($charge->source->exp_month);
    $payment->setcc_exp_year($charge->source->exp_year);
    $payment->setcc_last4($charge->source->last4);
    $payment->setcc_owner($charge->source->name);
    $payment->setcc_type($charge->source->brand);

    //Add payment fraud information
    $payment->setcc_avs_status($charge->source->address_line1_check . '/' . $charge->source->address_zip_check);
    $payment->setcc_cid_status($charge->source->cvc_check); //CID = CVC

    //Add any additional information
    $payment->setadditional_information(array(
      'funding' => $charge->source->funding,
      'country' => $charge->source->country
    ));
  }
  
  /**
   * Sets the API key for interfacing with Stripe API
   *
   * @param   none
   * @return  none
   */
  private function _setApiKey()
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
  private function _getToken($tokenString)
  {
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
  private function _createCharge($payment, $amount, $capture = true)
  {
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

}