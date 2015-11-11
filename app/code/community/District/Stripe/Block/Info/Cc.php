<?php
/**
 * District Commerce
 *
 * @category    District
 * @package     Stripe
 * @author      District Commerce <support@districtcommerce.com>
 * @copyright   District Commerce (http://districtcommerce.com)
 * 
 */

class District_Stripe_Block_Info_Cc extends Mage_Payment_Block_Info_Cc
{
    protected function _construct()
    {
      Mage::log('Block2');
        parent::_construct();
        $this->setTemplate('district/stripe/info/cc.phtml');
    }

}