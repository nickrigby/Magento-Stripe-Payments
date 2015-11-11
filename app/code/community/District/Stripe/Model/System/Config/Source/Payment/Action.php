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
class District_Stripe_Model_System_Config_Source_Payment_Action
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
      return array(
        array('value' => 0, 'label' => 'Authorize'),
        array('value' => 1, 'label' => 'Authorize and Capture')
      );
    }
}
