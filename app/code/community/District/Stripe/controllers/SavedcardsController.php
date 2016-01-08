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
        if($id = $this->getRequest()->getParam('id')) {
            if(Mage::helper('stripe')->deleteCard($id)) {
                Mage::getSingleton('core/session')->addSuccess($this->__('Card was sucessfully deleted.'));
            } else {
                Mage::getSingleton('core/session')->addError($this->__('Card could not be deleted.'));
            }
        } else {
            Mage::getSingleton('core/session')->addError($this->__('Card could not be deleted.'));
        }
    }

    public function editAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        //Get id of card
        if($id = $this->getRequest()->getParam('id')) {
            if($card = Mage::helper('stripe')->retrieveCard($id)) {

                $this->loadLayout();
                $this->getlayout()->getBlock('district_stripe_savedcards_edit')->assign('card', $card);
                $this->renderLayout();

            } else {
                Mage::getSingleton('core/session')->addError($this->__('Could not retrieve card from Stripe.'));
            }
        } else {
            Mage::getSingleton('core/session')->addError($this->__('Card does not exist.'));
        }
    }

    public function saveAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        //Get id of card
        if($id = $this->getRequest()->getParam('id')) {
            if($card = Mage::helper('stripe')->retrieveCard($id)) {

                $card->address_line1 = $this->getRequest()->getPost('address_line1');
                $card->address_zip = $this->getRequest()->getPost('address_zip');
                $card->save();

                Mage::getSingleton('core/session')->addSuccess($this->__('Card was sucessfully saved.'));



            } else {
                Mage::getSingleton('core/session')->addError($this->__('Could not retrieve card from Stripe.'));
            }
        } else {
            Mage::getSingleton('core/session')->addError($this->__('Card does not exist.'));
        }

         $this->_redirect('*/*/');
    }
}
