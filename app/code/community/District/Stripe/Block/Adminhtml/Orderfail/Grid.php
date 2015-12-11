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

class District_Stripe_Block_Adminhtml_Orderfail_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
    parent::__construct();
    
    $this->setDefaultSort('id');
    $this->setId('district_stripe_orderfail_grid');
    $this->setDefaultDir('desc');
    $this->setSaveParametersInSession(true);
  }
  
  protected function _getCollectionClass()
  {
    return 'stripe/order_failed_collection';
  }
  
  protected function _prepareCollection()
  {
    $collection = Mage::getResourceModel($this->_getCollectionClass());
    $this->setCollection($collection);
    
    return parent::_prepareCollection();
  }
  
  protected function _prepareColumns()
  {
    $this->addColumn('id', array(
      'header'=> $this->__('ID'),
      'align' =>'right',
      'width' => '50px',
      'index' => 'id',
    ));
    
    $this->addColumn('order_id', array(
      'header'=> $this->__('Order ID'),
      'index' => 'order_id',
    ));
    
    $this->addColumn('cc_type', array(
      'header'=> $this->__('Card Type'),
      'index' => 'cc_type',
    ));
    
    $this->addColumn('cc_last4', array(
      'header'=> $this->__('Last 4'),
      'index' => 'cc_last4',
    ));
    
    $this->addColumn('amount', array(
      'header'=> $this->__('Amount'),
      'index' => 'amount',
    ));
    
    $this->addColumn('reason', array(
      'header'=> $this->__('Reason'),
      'index' => 'reason',
    ));
    
    return parent::_prepareColumns();
  }
  
}