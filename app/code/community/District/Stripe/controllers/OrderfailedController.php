<?php
/**
 * District Commerce
 *
 * @category    District
 * @package     Stripe
 * @author      District Commerce <support@districtcommerce.com>
 * @copyright   Copyright (c) 2016 District Commerce (http://districtcommerce.com)
 * @license     http://store.districtcommerce.com/license
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

    /**
     *
     */
    public function massDeleteAction()
    {
        $failedOrderIds = $this->getRequest()->getParam('failed_order_ids');

        if(!is_array($failedOrderIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select records to delete.'));
        } else {
            try {
                $model = Mage::getModel('stripe/order_failed');
                foreach ($failedOrderIds as $failedOrderId) {
                    $model->load($failedOrderId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('Total of %d record(s) were deleted.', count($failedOrderIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }
}
