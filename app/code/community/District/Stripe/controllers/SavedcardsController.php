<?php
class District_Stripe_SavedcardsController extends Mage_Core_Controller_Front_Action
{
    /**
     * Authenticate user
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

    /**
     * View cards
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Delete card
     */
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

        $this->_redirect('*/*/');
    }

    /**
     * Edit card
     */
    public function editAction()
    {
        //Get id of card
        if($id = $this->getRequest()->getParam('id')) {

            //Retrieve the card from Stripe
            if($card = Mage::helper('stripe')->retrieveCard($id)) {

                //Load the layout
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

    /**
     * Save card
     */
    public function saveAction()
    {
        //Get id of card
        if($id = $this->getRequest()->getParam('id')) {

            //Retrieve the card from Stripe
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
