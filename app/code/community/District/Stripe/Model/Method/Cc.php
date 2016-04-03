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
class District_Stripe_Model_Method_Cc extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'stripe_cc';
    protected $_formBlockType = 'stripe/form_cc';
    protected $_infoBlockType = 'stripe/info_cc';

    protected $_isGateway = true;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = false; //No void through Stripe, use cancel instead
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false; //Would require multiple tokens
    protected $_isInitializeNeeded = false;
    protected $_canFetchTransactionInfo = false; //Removes "get payment update" button for orders under review
    protected $_canReviewPayment = true;
    protected $_canCreateBillingAgreement = false;
    protected $_canManageRecurringProfiles = true;
    protected $_canSaveCc = false;
    protected $_isAvailable = true;

    //Charge data
    private $_chargeData = array();

    /**
     * District_Stripe_Model_Method_Cc constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Capture payment
     *
     * @param Varien_Object $payment
     * @param $amount
     * @return $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
        //Call parent capture function
        parent::capture($payment, $amount);

        //Check amount is okay
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('payment')->__('Invalid amount for authorization.'));
        }

        //Get last transaction for this payment
        $lastTransaction = $payment->getTransaction($payment->getLastTransId());

        //Create the charge
        if ($lastTransaction && $lastTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) { //If previously authorized
            $charge = $this->_retrieveCharge($payment->getCcTransId());

            try {
                $charge->capture();
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('stripe')->__('Could not capture payment.'));
            }

        } else { //Auth and capture
            $charge = $this->_createCharge($payment, $amount, true);

            //Create the payment in Magento
            $this->_createOrderPayment($payment, $charge, $amount);
        }

        //Create the transaction
        $this->_createTransaction($payment, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, $charge->id, false, true);

        return $this;
    }

    /**
     * Authorize payment method
     *
     * @param Varien_Object $payment
     * @param $amount
     * @return $this
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
        $this->_createOrderPayment($payment, $charge, $amount);

        //Create the transaction
        //Append -auth to txn id, since transaction table needs unique txn id, and capture id is the same
        $this->_createTransaction($payment, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, $charge->id . '-auth');

        return $this;
    }

    /**
     * Refund
     *
     * @param Varien_Object $payment
     * @param $amount
     * @return $this
     */
    public function refund(Varien_Object $payment, $amount)
    {
        //Get transaction id
        $transactionId = $payment->getCcTransId();

        //Create the refund
        $refund = $this->_createRefund($transactionId, $amount, $payment);

        //Create transaction
        $this->_createTransaction($payment, Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND, $refund->id, true, true);

        return $this;
    }

    /**
     * Validate payment method
     *
     * @return $this
     */
    public function validate()
    {
        //Call parent validate
        parent::validate();

        //Are we validating a new card or saved card?
        if (isset($_POST['stripeSavedCard']) && !empty($_POST['stripeSavedCard'])) { //Saved card

            //Get customer
            $customer = Mage::helper('stripe')->retrieveCustomer();

            //Get card info
            $card = $this->_retrieveCard($customer, $_POST['stripeSavedCard']);

            //Set charge data
            $this->_chargeData['customer'] = $customer->id;
            $this->_chargeData['source'] = $card->id;

            //Add card details to quote
            $this->_addCardToQuote($card);

        } elseif (isset($_POST['stripeToken']) && !empty($_POST['stripeToken'])) { //New card

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
     * Accept a payment that is under review
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);

        return true;
    }

    /**
     * Attempt to deny a payment that is under review
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        parent::denyPayment($payment);

        $this->refund($payment, $payment->getBaseAmountAuthorized());

        return true;
    }

    /**
     * Set order payment information
     *
     * @param Varien_Object $payment
     * @param $charge
     * @param $amount
     */
    protected function _createOrderPayment(Varien_Object $payment, $charge, $amount)
    {
        //Set amount
        $payment->setAmount($amount);

        //Add payment cc information
        $payment->setcc_exp_month($charge->source->exp_month);
        $payment->setcc_exp_year($charge->source->exp_year);
        $payment->setcc_last4($charge->source->last4);
        $payment->setcc_owner($charge->source->name);
        $payment->setcc_type($charge->source->brand);
        $payment->setcc_trans_id($charge->id);

        //Add payment fraud information
        $payment->setcc_avs_status($charge->source->address_line1_check . '/' . $charge->source->address_zip_check);
        $payment->setcc_cid_status($charge->source->cvc_check); //CID = CVC

        //Did we detect fraud on this order?
        if (Mage::helper('stripe')->getDeclinedOrdersCount($payment->getOrder()->getIncrementId(), true) > 0) {
            $payment->setIsFraudDetected(true);
        }

        //Add any additional information
        $payment->setadditional_information(array(
            'funding' => $charge->source->funding,
            'country' => $charge->source->country
        ));
    }

    /**
     * Creates payment transaction
     *
     * @param Varien_Object $payment
     * @param $requestType
     * @param $transactionId
     * @param bool $close
     * @param bool $closeParent
     */
    protected function _createTransaction(Varien_Object $payment, $requestType, $transactionId, $close = false, $closeParent = false)
    {
        //Set attributes
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed($close);
        $payment->setShouldCloseParentTransaction($closeParent);

        //Add the transaction
        $payment->addTransaction($requestType, null, true);

        //Skip transaction creation
        $payment->setSkipTransactionCreation(true);
    }

    /**
     * Add card details to quote
     *
     * @param $card
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
     * Retrieve Stripe Token
     *
     * @param $tokenString
     * @return bool|\Stripe\Token
     */
    protected function _retrieveToken($tokenString)
    {
        Mage::helper('stripe')->setApiKey();

        try {
            return \Stripe\Token::retrieve(trim($tokenString));
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('stripe')->__('Invalid token. Please try again.'));
        }

        return false;
    }

    /**
     * Create charge
     *
     * @param Varien_Object $payment
     * @param $amount
     * @param bool $capture
     * @return \Stripe\Charge
     */
    protected function _createCharge(Varien_Object $payment, $amount, $capture = true)
    {
        //Set API key
        Mage::helper('stripe')->setApiKey();

        //Save card?
        if (isset($_POST['stripeSaveCard']) && ($_POST['stripeSavedCard'] === '' || !isset($_POST['stripeSavedCard']) )) {

            if (isset($_POST['isStripeCustomer'])) { //Stripe customer

                //Get the customer
                $customer = Mage::helper('stripe')->retrieveCustomer();

                //Save the card
                if ($card = $this->_saveCard($customer)) {
                    //Set charge data
                    $this->_chargeData['customer'] = $customer->id;
                    $this->_chargeData['source'] = $card->id;
                }

            } else {

                //Create customer
                $customer = Mage::helper('stripe')->createCustomer($this->_chargeData['source']);

                //Set charge data
                $this->_chargeData['customer'] = $customer->id;
                $this->_chargeData['source'] = $customer->default_source;

            }
        }

        //Set charge data
        $this->_chargeData['amount'] = Mage::helper('stripe')->calculateCurrencyAmount($amount, $payment->getOrder()->getBaseCurrencyCode());
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

            //Additional info
            $additionalInfo = array();

            //Set error type
            if (isset($error['type'])) {
                $additionalInfo['type'] = $error['type'];
            }

            //Set error code
            if (isset($error['code'])) {
                $additionalInfo['code'] = $error['code'];

                //Append _fraudulent to code if decline code is set
                if (isset($error['decline_code']) && $error['decline_code'] === 'fraudulent') {
                    $additionalInfo['code'] .= '_fraudulent';
                }
            }

            //Set charge token
            if (isset($error['charge'])) {
                $additionalInfo['token'] = $error['charge'];
            }

            //Set message
            if (isset($error['message'])) {
                $additionalInfo['message'] = $error['message'];
            }

            //Save error in additional info column
            if (!empty($additionalInfo)) {
                Mage::getSingleton('checkout/session')->getQuote()->getPayment()->setAdditionalInformation($additionalInfo);
            }

            //Throw payment error
            throw new Mage_Payment_Model_Info_Exception(Mage::helper('stripe')->__($error['message']));

        } catch (Exception $e) {

            //Throw payment error
            throw new Mage_Payment_Model_Info_Exception(Mage::helper('stripe')->__($error['message']));

        }

        return $charge;
    }

    /**
     * Retrieve charge
     *
     * @param $transactionId
     * @return bool|\Stripe\Charge
     */
    protected function _retrieveCharge($transactionId)
    {
        Mage::helper('stripe')->setApiKey();

        try {
            return \Stripe\Charge::retrieve($transactionId);
        } catch (Exception $e) {
            $message = $e->getMessage();
            Mage::throwException($message);
        }

        return false;
    }

    /**
     * Create refund
     *
     * @param $transactionId
     * @param $amount
     * @param $payment
     * @return bool|\Stripe\Refund
     */
    protected function _createRefund($transactionId, $amount, $payment)
    {
        Mage::helper('stripe')->setApiKey();

        try {
            return \Stripe\Refund::create(array(
                'charge' => $transactionId,
                'amount' => Mage::helper('stripe')->calculateCurrencyAmount($amount, $payment->getOrder()->getBaseCurrencyCode()),
            ));
        } catch (Exception $e) {
            $message = $e->getMessage();
            Mage::throwException($message);
        }

        return false;
    }

    /**
     * Save card
     *
     * @param $customer
     * @return bool
     */
    protected function _saveCard($customer)
    {
        Mage::helper('stripe')->setApiKey();

        try {

            //Save the card
            return $customer->sources->create(array(
                'source' => $this->_chargeData['source']
            ));

        } catch (Exception $e) {

            //Fail gracefully, don't stop transaction
            Mage::log(Mage::helper('stripe')->__('Could not save card'));

        }

        return false;
    }

    /**
     * Retrieve card
     *
     * @param $customer
     * @param $card
     * @return bool
     */
    protected function _retrieveCard($customer, $card)
    {
        Mage::helper('stripe')->setApiKey();

        try {

            //Get card info
            return $customer->sources->retrieve($card);

        } catch (Exception $e) {

            Mage::throwException(Mage::helper('stripe')->__('Could not retrieve card. Please try again.'));

        }

        return false;
    }
}
