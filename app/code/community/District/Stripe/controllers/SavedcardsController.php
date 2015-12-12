<?php
class District_Stripe_SavedcardsController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function deleteAction()
    {
        //Get id of card
        $id = $this->getRequest()->getParam('id');

        if($id) {
            if(Mage::helper('stripe')->deleteCard($id)) {
                Mage::getSingleton('core/session')->addSuccess($this->__('Card was sucessfully deleted.'));
            } else {
                Mage::getSingleton('core/session')->addError($this->__('Card could not be deleted.'));
            }
        } else {
            Mage::getSingleton('core/session')->addError($this->__('Card could not be deleted.'));
        }

        $this->_redirect('*/*/');

    }
}
