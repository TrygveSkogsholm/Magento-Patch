<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * admin product edit tabs
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MDN_Purchase_Block_Order_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	private $_purchaseOrder = null;
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('purchase_order_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle('');
    }

    protected function _beforeToHtml()
    {
        $product = $this->getProduct();

        $this->addTab('tab_info', array(
            'label'     => Mage::helper('purchase')->__('Summary'),
            'content'   => $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_Info')->toHtml(),
        ));

        $this->addTab('tab_products', array(
            'label'     => Mage::helper('purchase')->__('Products'),
            'content'   => $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_Products')->toHtml(),
        ));
        
        if ($this->getPurchaseOrder()->getpo_status() != MDN_Purchase_Model_Order::STATUS_COMPLETE)
        {
	        $this->addTab('tab_add_products', array(
	            'label'     => Mage::helper('purchase')->__('Add Products'),
	            'url'   => $this->getUrl('*/*/ProductSelectionGrid', array('_current'=>true, 'po_num' => $this->getPurchaseOrder()->getId())),
	            'class' => 'ajax',
	        ));        
        }
        
        $this->addTab('tab_ship_to', array(
            'label'     => Mage::helper('purchase')->__('Ship to Address'),
            'content'   => $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_ShippingAddress')->toHtml(),
        ));	
        
        $this->addTab('tab_deliveries', array(
            'label'     => Mage::helper('purchase')->__('Deliveries'),
            'content'   => $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_Deliveries')->toHtml(),
        ));	
        
        if ($this->getPurchaseOrder()->getpo_status() != MDN_Purchase_Model_Order::STATUS_COMPLETE)
        {
	        $this->addTab('tab_send_to_supplier', array(
	            'label'     => Mage::helper('purchase')->__('Send to supplier'),
	            'content'   => $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_SendToSupplier')->setOrderId($this->getPurchaseOrder()->getId())->toHtml(),
	        ));
        }
        
    	$this->addTab('tab_accounting', array(
            'label'     => Mage::helper('purchase')->__('Accounting'),
            'content'   => $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_accounting')->setOrderId($this->getPurchaseOrder()->getId())->toHtml(),
        ));

        	
    	$TaskCount = 0;
    	$gridBlock = $this->getLayout()
    				->createBlock('Organizer/Task_Grid')
    				->setEntityType('purchase_order')
    				->setEntityId($this->getPurchaseOrder()->getId())
    				->setShowTarget(false)
    				->setShowEntity(false)
    				->setTemplate('Organizer/Task/List.phtml');
    				
		$content = $gridBlock->toHtml();
		
		$TaskCount = $gridBlock->getCollection()->getSize();
        $this->addTab('purchase_order_organizer', array(
            'label'     => Mage::helper('Organizer')->__('Organizer').' ('.$TaskCount.')',
            'title'     => Mage::helper('Organizer')->__('Organizer').' ('.$TaskCount.')',
            'content'   => $content,
        ));
        
        //set active tab
        $defaultTab = $this->getRequest()->getParam('tab');
        if ($defaultTab == null)
        	$defaultTab = 'tab_info';
        $this->setActiveTab($defaultTab);

        return parent::_beforeToHtml();
    }

    /**
     * Retrive product object from object if not from registry
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getPurchaseOrder()
    {
		if ($this->_purchaseOrder == null)
		{
			$this->_purchaseOrder = mage::getModel('Purchase/Order')->load($this->getRequest()->getParam('po_num'));
		}
		return $this->_purchaseOrder;
    }

}
