<?php

/**
 * Customer edit block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MDN_Purchase_Block_Product_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	private $_product = null;
	
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'product';
        $this->_blockGroup = 'Purchase';

        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('purchase')->__('Save'));
        $this->_removeButton('delete');
        
        if (Mage::helper('catalogInventory')->isQty($this->getProduct()->getTypeId()))
		{
	        $this->_addButton(
	            'update_stocks',
	            array(
	                'label'     => Mage::helper('purchase')->__('Force Stocks Update'),
	                'onclick'   => "window.location.href='".$this->getUrl('Purchase/Products/UpdateStock', array('product_id' => $this->getProduct()->getId()))."'",
	                'level'     => -1
	            )
	        );
		}
		
        $this->_addButton(
            'view_product',
            array(
                'label'     => Mage::helper('purchase')->__('View product'),
                'onclick'   => "window.location.href='".$this->getUrl('adminhtml/catalog_product/edit', array('id' => $this->getProduct()->getId()))."'",
                'level'     => -1
            )
        );
	        
    }
   
    public function getHeaderText()
    {
        return $this->getProduct()->getName();
    }
    
    /**
	 * Retourne le produit concerné
	 *
	 * @param unknown_type $value
	 */
	public function getProduct()
	{
		if ($this->_product == null)
		{
			$this->_product = mage::getModel('catalog/product')->load($this->getRequest()->getParam('product_id'));
		}
		return $this->_product;
	}
	
	public function getSaveUrl()
    {
        return $this->getUrl('Purchase/Products/Save');
    }
    
   	public function GetBackUrl()
	{
		return $this->getUrl('Purchase/Products/List', array());
	}

}
