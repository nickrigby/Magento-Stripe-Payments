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

class District_Stripe_Model_Method_Cc extends Mage_Payment_Model_Method_Abstract {

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

    //Charge data
    private $_chargeData = array();

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
    * @param   none
    *
    * @return  Mage_Payment_Model_Abstract
    */
    public function validate()
    {
        //Call parent validate
        parent::validate();

        //Are we validating a new card or saved card?
        if(isset($_POST['stripeSavedCard']) && !empty($_POST['stripeSavedCard'])) { //Saved card

            //Get customer
            $customer = Mage::helper('stripe')->retrieveCustomer();

            //Get card info
            $card = $this->_retrieveCard($customer, $_POST['stripeSavedCard']);

            //Set charge data
            $this->_chargeData['customer'] = $customer->id;
            $this->_chargeData['source'] = $card->id;

            //Add card details to quote
            $this->_addCardToQuote($card);

        } else if(isset($_POST['stripeToken']) && !empty($_POST['stripeToken'])) { //New card

            //Token was set with stripe.js, so retrieve it
            $token = $this->_retrieveToken($_POST['stripeToken']);

            //Set charge data
            $this->_chargeData['source'] = $token->id;

            //Add card details to quote
            $this->_addCardToQuote($token->card);

        } else { //Error

            //Token was not set, throw an error
            Mage::throwException(Mage::helper('stripe')->__('There was an error validating your card. Please try again.'));

        }

        return $this;
    }

    /**
    * Sets the payment information
    *
    * @param   Varien_Object $payment
    * @param   Stripe_Charge $charge
    * @param   float $amount
    * @param   Mage_Sales_Model_Order_Payment_Transaction $requestType
    *
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
    * Add card details to quote
    *
    * @param   Stripe_Object $card
    *
    * @return  none
    */
    protected function _addCardToQuote($card)
    {
        Mage::getSingleton('checkout/session')->getQuote()->getPayment()->addData(array(
            'cc_exp_year' => $card->exp_year,
            'cc_exp_month' => $card->exp_month,
            'cc_last4' => $card->last4,
            'cc_type' => $card->brand,
        ));
    }

    /**
    * Closes a payment
    *
    * @param   Varien_Object $payment
    * @param   Stripe_Refund $refund
    * @param   Mage_Sales_Model_Order_Payment_Transaction $requestType
    *
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
    * @param   string $tokenString
    *
    * @return  Stripe_Token $token
    */
    protected function _retrieveToken($tokenString)
    {
        Mage::helper('stripe')->setApiKey();

        try {
            $token = \Stripe\Token::retrieve(trim($tokenString));
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('stripe')->__('Invalid token. Please try again.'));
        }

        return $token;
    }

    /**
    * Create a charge
    *
    * @param   Varien_Object $payment
    * @param   float $amount
    * @param   boolean $capture
    *
    * @return  Stripe_Charge $charge
    */
    protected function _createCharge(Varien_Object $payment, $amount, $capture = true)
    {
        //Set API key
        Mage::helper('stripe')->setApiKey();

        //Save card?
        if(isset($_POST['stripeSaveCard'])) {

            if(isset($_POST['isStripeCustomer'])) { //Stripe customer

                //Get the customer
                $customer = Mage::helper('stripe')->retrieveCustomer();

                //Save the card
                $card = $this->_saveCard($customer);

                //Set charge data
                $this->_chargeData['customer'] = $customer->id;
                $this->_chargeData['source'] = $card->id;

            } else {

                //Create customer
                $customer = Mage::helper('stripe')->createCustomer($this->_chargeData['source']);

                //Set charge data
                $this->_chargeData['customer'] = $customer->id;
                $this->_chargeData['source'] = $customer->default_source;

            }
        }

        //Set charge data
        $this->_chargeData['amount'] = $amount * 100;
        $this->_chargeData['currency'] = $payment->getOrder()->getBaseCurrencyCode();
        $this->_chargeData['capture'] = $capture;
        $this->_chargeData['description'] = sprintf('Payment for order #%s on %s', $payment->getOrder()->getIncrementId(), $payment->getOrder()->getStore()->getFrontendName());

        //Create the charge
        try {

            $charge = \Stripe\Charge::create($this->_chargeData);

        } catch (\Stripe\Error\Card $e) {

            //Get messages
            $jsonBody = $e->getJsonBody();
            $error = $jsonBody['error'];

            //Save error in additional info column
            Mage::getSingleton('checkout/session')->getQuote()->getPayment()->setAdditionalInformation(array(
                'error' => $error['message'],
                'type'  => $error['type'],
                'code'  => $error['code'],
                'token' => $error['charge'],
            ));

            //Throw the error
            Mage::throwException(Mage::helper('stripe')->__($error['message']));

        } catch (Exception $e) {

            //Throw the error
            Mage::throwException(Mage::helper('stripe')->__($e->getMessage()));

        }

        return $charge;
    }

    /**
    * Retrieve a charge
    *
    * @param   string $transactionId
    *
    * @return  Stripe_Charge $charge
    */
    protected function _retrieveCharge($transactionId)
    {
        Mage::helper('stripe')->setApiKey();

        try {
            $charge = \Stripe\Charge::retrieve($transactionId);
        } catch (Exception $e) {
            $message = $e->getMessage();
            Mage::throwException($message);
        }

        return $charge;
    }

    /**
    * Create a refund
    *
    * @param   string $transactionId
    * @param   float amount
    *
    * @return  Stripe_Refund $refund
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
    * @param   Stripe_Customer $customer
    *
    * @return  Stripe_Card $card
    */
    protected function _saveCard($customer)
    {
        try {

            //Save the card
            $card = $customer->sources->create(array(
                'source' => $this->_chargeData['source']
            ));

        } catch (Exception $e) {

            //Fail gracefully, don't stop transaction
            Mage::log(Mage::helper('stripe')->__('Could not save card'));

        }

        return $card;
    }

    /**
    * Retrieve a card
    *
    * @param   Stripe_Customer $customer
    * @param   string $card
    *
    * @return  Stripe_Card $card
    */
    protected function _retrieveCard($customer, $card)
    {
        try {

            //Get card info
            $card = $customer->sources->retrieve($card);

        } catch (Exception $e) {

            Mage::throwException(Mage::helper('stripe')->__('Could not retrieve card. Please try again.'));

        }

        return $card;
    }
}
