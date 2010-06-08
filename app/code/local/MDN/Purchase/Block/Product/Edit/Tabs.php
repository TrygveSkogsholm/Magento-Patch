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
class MDN_Purchase_Block_Product_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	private $_product = null;
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('purchase_product_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle('');
    }

    protected function _beforeToHtml()
    {
        $product = $this->getProduct();

        $this->addTab('tab_info', array(
            'label'     => Mage::helper('purchase')->__('Summary'),
            'content'   => $this->getLayout()->createBlock('Purchase/Product_Edit_Tabs_Summary')->toHtml(),
        ));

        if (Mage::helper('catalogInventory')->isQty($product->getTypeId()))
        {
			$this->addTab('tab_orders', array(
	            'label'     => Mage::helper('purchase')->__('Purchase Orders'),
	            'content'   => $this->getLayout()->createBlock('Purchase/Product_Edit_Tabs_AssociatedOrdersGrid')->setProductId($product->getId())->toHtml(),
	        ));
        
        
			$this->addTab('tab_suppliers', array(
	            'label'     => Mage::helper('purchase')->__('Suppliers'),
	            'content'   => $this->getLayout()
	            					->createBlock('Purchase/Product_Edit_Tabs_AssociatedSuppliers')
	            					->setTemplate('Purchase/Product/Edit/Tab/AssociatedSuppliers.phtml')
	            					->setProductId($product->getId())
	            					->toHtml(),
	        ));
        }

		$this->addTab('tab_manufacturers', array(
            'label'     => Mage::helper('purchase')->__('Manufacturers'),
            'content'   => $this->getLayout()
            					->createBlock('Purchase/Product_Edit_Tabs_AssociatedManufacturers')
            					->setTemplate('Purchase/Product/Edit/Tab/AssociatedManufacturers.phtml')
            					->setProductId($product->getId())
            					->toHtml(),
        ));

        if (Mage::helper('catalogInventory')->isQty($product->getTypeId()))
        {
			$this->addTab('tab_stock_movements', array(
	            'label'     => Mage::helper('purchase')->__('Stock Movements'),
	            'content'   => $this->getLayout()
	            					->createBlock('Purchase/Product_Edit_Tabs_StockMovementGrid')
	            					->setTemplate('Purchase/Product/Edit/Tab/StockMovement.phtml')
	            					->setProductId($product->getId())
	            					->toHtml(),
	        ));
        }
                
        $this->addTab('pending_orders', array(
            'label'     => Mage::helper('purchase')->__('Pending Customer Orders'),
            'content'   => $this->getLayout()
            					->createBlock('Purchase/Product_Edit_Tabs_PendingCustomerOrders')
            					->setTemplate('Purchase/Product/Edit/Tab/PendingCustomerOrders.phtml')
            					->setProductId($product->getId())
            					->toHtml(),
        ));

        if (Mage::helper('catalogInventory')->isQty($product->getTypeId()))
        {       
	        $this->addTab('stock_graph', array(
	            'label'     => Mage::helper('purchase')->__('Graph'),
	            'content'   => $this->getLayout()
	            					->createBlock('Purchase/Product_Edit_Tabs_Graph')
	            					->setTemplate('Purchase/Product/Edit/Tab/Graph.phtml')
	            					->setProductId($product->getId())
	            					->toHtml(),
	            					
	        ));
        }
        
    	$TaskCount = 0;
    	$gridBlock = $this->getLayout()
    				->createBlock('Organizer/Task_Grid')
    				->setEntityType('product')
    				->setEntityId($this->getProduct()->getId())
    				->setShowTarget(false)
    				->setShowEntity(false)
    				->setTemplate('Organizer/Task/List.phtml');
    				
		$content = $gridBlock->toHtml();
		
		$TaskCount = $gridBlock->getCollection()->getSize();
        $this->addTab('product_organizer', array(
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
    public function getProduct()
    {
		if ($this->_product == null)
		{
			$this->_product = mage::getModel('catalog/product')->load($this->getRequest()->getParam('product_id'));
		}
		return $this->_product;
    }

}
