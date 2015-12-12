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

class District_Stripe_OrderfailController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('report/district/orderfail')
            ->_title($this->__('District Commerce'))->_title($this->__('Failed orders'))
            ->_addBreadcrumb($this->__('District'), $this->__('District'))
            ->_addBreadcrumb($this->__('Failed orders'), $this->__('Failed orders'));
        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('district/orderfail');
    }
}
