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

class District_Stripe_Helper_Data extends Mage_Core_Helper_Abstract
{
    const DASHBOARD_PAYMENTS_URL = 'https://dashboard.stripe.com/payments/';

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
            Mage::throwException($this->__('Cannot set Stripe API key'));
        }
    }

    /**
    * Retrieve a customer from Stripe
    *
    * @param   string $token
    * @return  Stripe_Customer $customer
    */
    public function retrieveCustomer()
    {
        $this->setApiKey();

        //Get the customer token from magento
        if($token = Mage::helper('stripe')->getCustomer()->getToken())
        {
            try {
                $customer = \Stripe\Customer::retrieve(Mage::helper('core')->decrypt($token));
            } catch(Exception $e) {
                //Fail gracefully
                Mage::log($this->__('Could not retrieve customer'));
            }

            return $customer;
        }

        return false;
    }

    /**
    * Get a customer from District Stripe Database
    *
    * @param   none
    * @return  District_Stripe_Model_Customer
    */
    public function getCustomer()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $model = Mage::getModel('stripe/customer');

        return $model->load($customer->getId(), 'customer_id');
    }

    /**
    * Get payments dashboard URL
    *
    * @param   none
    * @return  string DASHBOARD_PAYMENTS_URL
    */
    public function getPaymentsDashboardUrl()
    {
        return self::DASHBOARD_PAYMENTS_URL;
    }

    /**
    * Calculates amount based on currency
    * https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
    *
    * @param float $amount
    * @param string $currencyCode
    * @return float $amount
    */
    public function calculateCurrencyAmount($amount, $currencyCode)
    {
        $zeroDecimalCurrencies = array(
            'BIF', //Burundian Franc
            'CLP', //Chilean Peso
            'DJF', //Djiboutian Franc
            'GNF', //Guinean Franc
            'JPY', //Japanese Yen
            'KMF', //Comorian Franc
            'KRW', //South Korean Won
            'MGA', //Malagasy Ariary
            'PYG', //Paraguayan Guaraní
            'RWF', //Rwandan Franc
            'VND', //Vietnamese Đồng
            'VUV', //Vanuatu Vatu
            'XAF', //Central African Cfa Franc
            'XOF', //West African Cfa Franc
            'XPF', //Cfp Franc
        );

        if(in_array($currencyCode, $zeroDecimalCurrencies)) {
            $amount = round($amount, 0);
        } else {
            $amount = $amount * 100;
        }

        return $amount;
    }

    /**
    * Create a customer
    *
    * @param   string $token
    * @return  Stripe_Customer $stripeCustomer
    */
    public function createCustomer($token)
    {
        //Set API Key
        $this->setApiKey();

        //Create the customer
        try {

            //Get customer object
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            //Create customer in Stripe
            $stripeCustomer = \Stripe\Customer::create(array(
                'source' => $token,
                'email' => $customer->getEmail()
            ));

            //Create stripe customer in magento
            $model = Mage::getModel('stripe/customer');
            $model->setCustomerId($customer->getId());
            $model->setToken(Mage::helper('core')->encrypt($stripeCustomer->id));
            $model->save();

        } catch (Exception $e) {
            //Fail gracefully
            Mage::log($this->__('Could not create customer'));
        }

        return $stripeCustomer;
    }

    /**
    * Get Declined Orders Count
    *
    * @param   string $orderId
    * @return  District_Stripe_Model_Mysql4_Order_Failed_Collection
    */
    public function getDeclinedOrdersCount($orderId)
    {
        //Count failed orders
        return Mage::getModel('stripe/order_failed')
            ->getCollection()
            ->addFieldToFilter('order_id', array('eq' => $orderId))
            ->addFieldToFilter('code', array('like' => '%card_declined%'))
            ->getSize();
    }

    /**
    * Delete a stored card
    *
    * @param   string $cardId
    * @return  Stripe_Object
    */
    public function deleteCard($cardId)
    {
        if($customer = $this->retrieveCustomer())
        {
            return $customer->sources->retrieve($cardId)->delete();
        }
    }

    public function retrieveCard($cardId)
    {
        if($customer = $this->retrieveCustomer())
        {
            return $customer->sources->retrieve($cardId);
        }
    }

}
