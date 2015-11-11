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

/**
 * Source model for available payment actions
 */
class District_Stripe_Model_System_Config_Source_Payment_Cctype extends Mage_Payment_Model_Source_Cctype
{
    protected $_allowedTypes = array('AE','DI','DC','JCB','MC','VI');
}
