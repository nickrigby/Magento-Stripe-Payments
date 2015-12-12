<?php

class District_Stripe_Block_Customer_Savedcards extends Mage_Core_Block_Template
{
    public function getCards()
    {
        //If customer exists in our Stripe table
        if($cust = Mage::helper('stripe')->retrieveCustomer()) {

            //Get stored cards
            $cards = $cust->sources->data;

            //If there are stored cards, set saved cards flag
            if(sizeof($cards) > 0) {
                return $cards;
            }
        }

        return false;
    }
}
