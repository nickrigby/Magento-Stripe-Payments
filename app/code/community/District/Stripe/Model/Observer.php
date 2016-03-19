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

class District_Stripe_Model_Observer
{
    /**
     * @param $observer
     * @return mixed
     */
    public function addStatusToSalesOrderGrid($observer)
    {
        $block = $observer->getEvent()->getBlock();

        if($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {

            $block->addColumnAfter('district_stripe_status', array(
                'header' => Mage::helper('stripe')->__('Stripe Security'),
                'index' => 'district_stripe_status',
                'align' => 'center',
                'width' => '80px',
                'filter' => false,
                'renderer' => 'stripe/adminhtml_sales_order_grid_renderer_state',
                'sortable' => false,
            ), 'real_order_id');
        }

        return $observer;
    }

}
