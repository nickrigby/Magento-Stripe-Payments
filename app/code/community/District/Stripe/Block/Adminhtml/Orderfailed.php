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

class District_Stripe_Block_Adminhtml_Orderfailed extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * District_Stripe_Block_Adminhtml_Orderfailed constructor.
     */
    public function __construct()
    {
        $this->_blockGroup = 'stripe';
        $this->_controller = 'adminhtml_orderfailed';
        $this->_headerText = $this->__('Failed Stripe Orders');
        parent::__construct();
        $this->_removeButton('add');
    }
}
