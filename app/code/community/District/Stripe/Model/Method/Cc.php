<?php

class District_Stripe_Model_Stripe extends Mage_Payment_Model_Method_Cc {
  
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
  protected $_canCapturePartial           = true;
  protected $_canCaptureOnce              = false;
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
  
  private $apiKey;
  private $token;
  
  public function __construct() {
    
    parent::__construct();
    
    $this->apiKey = $this->getConfigData('api_secret_key');
  }
  
  /**
   * Capture the payment
   *
   * @param Varien_Object $payment
   * @param float $amount
   *
   * @return Mage_Payment_Model_Abstract
   */
  public function capture(Varien_Object $payment, $amount) {
    
    //Call parent capture function
    parent::capture();
    
    //Shortcut to order var
    /*$order = $payment->getOrder();
  
    //Process the card
    try {
      
      //Charge the card
      $charge = \Stripe\Charge::create(array(
        'amount' => $amount,
        'currency' => $order->getBaseCurrencyCode(),
        'capture' => $this->getConfigPaymentAction(), //false is preauth, true captures immediately
        'description' => sprintf('Payment for order #%s on %s', $order->getIncrementId(), $order->getStore()->getFrontendName())
      ));
      
      //Add payment cc information
      $payment->setcc_trans_id = $charge->id; //Transaction id
      $payment->setcc_exp_month = $charge->source->exp_month;
      $payment->setcc_exp_year = $charge->source->exp_year;
      $payment->setcc_last4 = $charge->source->last4;
      $payment->setcc_owner = $charge->source->name;
      $payment->setcc_type = $charge->source->brand;
      
      //Add payment fraud information
      $payment->setcc_avs_status = $charge->source->address_line1_check . '/' . $charge->source->address_zip_check;
      $payment->setcc_cid_status = $charge->source->cvc_check; //CID = CVC
      
      //Add any additional information
      $payment->setadditional_information(array(
        'funding' => $charge->source->funding,
        'country' => $charge->source->country
      ));
      
      //Set additional data depending on payment action
      if(!$this->getConfigPaymentAction()) {
        $payment
          ->setIsTransactionPending(true)
          ->setIsTransactionClosed(false);
      }
      
      //Add the transaction
      $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, true, $message);
      
    } catch (Exception $e) {
      
      $this->debugData($e->getMessage());
      Mage::throwException('Stripe: Charge failed');
      
    }*/
    
    Mage::log('Stripe: Capture');
  
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
        /*if (!$this->canAuthorize()) {
            Mage::throwException(Mage::helper('payment')->__('Authorize action is not available.'));
        }
        return $this;*/
      
      //Call parent capture function
      parent::authorize();
      
      Mage::log('Stripe: Authorize');
      
      return $this;
    }
  
  public function order(Varien_Object $payment, $amount)
    {
        parent::authorize();
      
      Mage::log('Stripe: Order');
      
      return $this;
    }
  
  /**
   * Sets the API key for interfacing with Stripe API
   *
   * @param   none
   * @return  none
   */
  private function setApiKey() {
    try {
      \Stripe\Stripe::setApiKey($this->apiKey);
    } catch (Exception $e) {
      Mage::throwException('Stripe: Could not set API key');
    }
  }
  
  /**
   * Validate payment method
   *
   * @param   Mage_Payment_Model_Info $info
   * @return  Mage_Payment_Model_Abstract
   */
  public function validate() {
    
    Mage::log('Stripe: Validate');
    
    //Call parent validate
    parent::validate();
    
    //Get the token from the form
    $tokenString = trim($_POST['stripeToken']);
    
    //If the token isn't empty
    if(!empty($tokenString)) {
      
      $this->setApiKey();
      
      try {
        $this->$token = \Stripe\Token::retrieve($tokenString);
      } catch (Exception $e) {
        Mage::throwException('Stripe: Invalid token');
      }
    }
    
    return $this;
    
  }

}