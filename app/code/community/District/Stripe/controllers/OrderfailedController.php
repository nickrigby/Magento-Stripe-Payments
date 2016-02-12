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

class District_Stripe_OrderfailedController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    /**
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('report/district/orderfailed')
            ->_title($this->__('District Commerce'))->_title($this->__('Failed Stripe Orders'))
            ->_addBreadcrumb($this->__('District Commerce'), $this->__('District Commerce'))
            ->_addBreadcrumb($this->__('Failed Stripe Orders'), $this->__('Failed Stripe Orders'));
        return $this;
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('district/orderfailed');
    }
}
