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
  protected $_canRefundInvoicePartial     = false;
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
  private $_token;
  private $_useSavedCard = false;
  
  public function __construct()
  {
    parent::__construct();
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

    //Create the charge
    if($payment->getLastTransId()) { //If previously authorized
      $charge = $this->_retrieveCharge($payment->getLastTransId());
    } else { //Auth and capture
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
  
  /**
   * Refund
   *
   * @param Varien_Object $payment
   * @param float $amount
   */
  public function refund(Varien_Object $payment, $amount)
  {
    //Get transaction id
    $transactionId = $payment->getLastTransId();
    
    //Create the refund
    $refund = $this->_createRefund($transactionId, $amount);
    
    //Close payment
    $this->_closePayment($payment, $refund, Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
    
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
    
    //New card or saved card being used?
    if(isset($_POST['stripeToken']) && !empty($_POST['stripeToken'])) { //New card

      //Token was set with stripe.js, so retrieve it
      $token = $this->_retrieveToken($_POST['stripeToken']);

      //Save the token
      $this->_token = $token->id;

    } else if(isset($_POST['stripeSavedCard']) && !empty($_POST['stripeSavedCard'])) { //Saved card

      //Token is a card token, which was saved to the customer previously
      $this->_token = $_POST['stripeSavedCard'];

      //Set flag so we can set customer on charge call later
      $this->_useSavedCard = true;

    } else { //Error

      //Token was not set, throw an error
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
   * Closes a payment
   *
   * @param   none
   * @return  none
   */
  protected function _closePayment(Varien_Object $payment, $refund, $requestType)
  {
    $payment->setTransactionId($refund->id);
    $payment->setIsTransactionClosed(true);
    $payment->setShouldCloseParentTransaction(true);
    
    //Add the transaction
    $payment->addTransaction($requestType, null, true);
  }
  
  /**
   * Get the token from Stripe based on passed in tokenString
   *
   * @param   stripe token string
   * @return  none
   */
  protected function _retrieveToken($tokenString)
  {
    Mage::helper('stripe')->setApiKey();
    
    try {
      $token = \Stripe\Token::retrieve(trim($tokenString));
    } catch (Exception $e) {
      Mage::throwException('Stripe: Invalid token');
    }
    
    return $token;
  }
  
  /**
   * Create a charge
   *
   * @param   stripe token string
   * @return  none
   */
  protected function _createCharge(Varien_Object $payment, $amount, $capture = true)
  {
    //Set API key
    Mage::helper('stripe')->setApiKey();
    
    //Save card, if checkbox is checked
    if(isset($_POST['stripeSaveCard'])) {
      $this->_saveCard();
    }
    
    //Set data
    $chargeData = array(
      'amount' => $amount * 100,
      'currency' => $payment->getOrder()->getBaseCurrencyCode(),
      'source' => $this->_token,
      'capture' => $capture,
      'description' => sprintf('Payment for order #%s on %s', $payment->getOrder()->getIncrementId(), $payment->getOrder()->getStore()->getFrontendName())
    );
    
    //If a saved card is being used, set the customer token
    if($this->_useSavedCard) {
      $customerToken = Mage::helper('stripe')->getCustomer()->getToken();
      $chargeData['customer'] = Mage::helper('core')->decrypt($customerToken);
    }
    
    //Create the charge
    try {
      $charge = \Stripe\Charge::create($chargeData);    
    } catch (\Stripe\Error\InvalidRequest $e) {
      Mage::throwException($e);
    } catch (\Stripe\Error\Card $e) {
      Mage::throwException($e);
    }
    
    return $charge;
  }
  
  /**
   * Retrieve a charge
   *
   * @param   transaction id
   * @return  none
   */
  protected function _retrieveCharge($transactionId)
  {
    Mage::helper('stripe')->setApiKey();
    
    try {
      $charge = \Stripe\Charge::retrieve($transactionId);
    } catch (\Stripe\Error\InvalidRequest $e) {
      Mage::throwException($e);
    } catch (\Stripe\Error\Card $e) {
      Mage::throwException($e);
    }
    
    return $charge;
  }
  
  /**
   * Create a refund
   *
   * @param   transaction id
   * @param   amount
   * @return  none
   */
  protected function _createRefund($transactionId, $amount)
  {
    Mage::helper('stripe')->setApiKey();
    
    try {
      $refund = \Stripe\Refund::create(array(
        'charge' => $transactionId,
        'amount' => $amount * 100
      ));
    } catch (\Stripe\Error\InvalidRequest $e) {
      Mage::throwException($e);
    } catch (\Stripe\Error\Card $e) {
      Mage::throwException($e);
    }
    
    return $refund;
  }
  
  /**
   * Save a card
   *
   * @param   none
   * @return  none
   */
  protected function _saveCard()
  {
    //Before we can save card, is this an existing stripe customer?
    if(!Mage::helper('stripe')->isCustomer()) { //No
      
      //Create the customer in Stripe
      $customer = $this->_createCustomer();
      
      //Set the flag to use a saved card (since we just saved it)
      $this->_useSavedCard = true;
      
      //Token is set to card token
      $this->_token = $customer->default_source;
      
    } else { //Yes
      
      //Get the customer token from magento
      $customerToken = Mage::helper('stripe')->getCustomer()->getToken();
      
      //Get the customer
      $customer = Mage::helper('stripe')->retrieveCustomer($customerToken);
      
      //Save the card
      try {
        $customer->sources->create(array(
          'source' => $this->_token
        ));
      } catch (Exception $e) {
        //Silently fail, don't stop transaction
        Mage::log('Stripe: Could not save card');
      }
    }
  }
  
  /**
   * Create a customer
   *
   * @param   none
   * @return  none
   */
  protected function _createCustomer()
  {
    //Set API Key
    Mage::helper('stripe')->setApiKey();
    
    //Create the customer
    try {
      
      //Get customer object
      $customer = Mage::getSingleton('customer/session')->getCustomer();
      
      //Create customer in Stripe
      $stripeCustomer = \Stripe\Customer::create(array(
        'source' => $this->_token,
        'email' => $customer->getEmail()
      ));
      
      //Create stripe customer in magento
      $model = Mage::getModel('stripe/customer');
      $model->setCustomerId($customer->getId());
      $model->setToken(Mage::helper('core')->encrypt($stripeCustomer->id));
      $model->save();
      
    } catch (Exception $e) {
      //Silently fail, don't stop transaction
      Mage::log('Stripe: Could not create customer');
    }
    
    return $stripeCustomer;
  }

}