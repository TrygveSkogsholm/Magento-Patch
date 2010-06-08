<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2009 Maison du Logiciel (http://www.maisondulogiciel.com)
 * @author : Olivier ZIMMERMANN
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_Purchase_Block_Order_Edit_Tabs_ProductSelection extends Mage_Adminhtml_Block_Widget_Grid
{
	private $_order = null;
	
	/**
	 * Définit l'order
	 *
	 */
	public function setOrderId($value)
	{
		$this->_order = mage::getModel('Purchase/Order')->load($value);
		return $this;
	}
	
	/**
	 * Retourne la commande
	 *
	 */
	public function getOrder()
	{
		return $this->_order;
	}
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('ProductSelection');
		//$this->_parentTemplate = $this->getTemplate();
        //$this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        //$this->setVarNameFilter('product_selection');
        //$this->setTemplate('Shipping/List.phtml');	
        $this->setEmptyText('Aucun elt');
    }

    /**
     * Charge la collection des devis
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {		            
		$allowProductTypes = array();
		$allowProductTypes[] = 'simple';
		$allowProductTypes[] = 'virtual';

		$alreadyAddedProducts = array();
		foreach ($this->getOrder()->getProducts() as $item)
		{
			$alreadyAddedProducts[] = $item->getpop_product_id();
		}
		
		$collection = Mage::getResourceModel('catalog/product_collection')
        	->addFieldToFilter('type_id', $allowProductTypes)
        	->addAttributeToSelect('name')
			->addAttributeToSelect('ordered_qty')
        	->addAttributeToSelect('reserved_qty')
        	->addAttributeToSelect('manufacturer')
        	->joinField('stock',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');

		if (count($alreadyAddedProducts) > 0)
        	$collection->addFieldToFilter('entity_id', array('nin' => $alreadyAddedProducts));
        	
        $this->setCollection($collection);
        
        return parent::_prepareCollection();
    }
    
   /**
     * Défini les colonnes du grid
     *
     * @return unknown
     */
    protected function _prepareColumns()
    {

	    $this->addColumn('in_products', array(
            'header_css_class' => 'a-center',
            'type'      => 'checkbox',
            'name'      => 'in_products',
            'values'    => $this->getSelectedProducts(),
            'align'     => 'center',
            'index'     => 'entity_id'
        ));
            
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('catalog')->__('ID'),
            'sortable'  => true,
            'width'     => '60px',
            'index'     => 'entity_id'
        ));
        
        $this->addColumn('Sku', array(
            'header'=> Mage::helper('purchase')->__('Sku'),
            'index' => 'sku',
        ));
                   
        $this->addColumn('Name', array(
            'header'=> Mage::helper('purchase')->__('Name'),
            'index' => 'name'
        ));
                   
        $this->addColumn('ordered_qty', array(
            'header'=> Mage::helper('purchase')->__('Available Qty'),
            'renderer' => 'MDN_Purchase_Block_Widget_Column_Renderer_AvailableQty',
            'filter'	=> false,
            'sortable'	=> false,
            'index'	=> 'ordered_qty'
        ));
		
        $this->addColumn('Suppliers', array(
            'header'=> Mage::helper('purchase')->__('Suppliers'),
            'renderer' => 'MDN_Purchase_Block_Widget_Column_Renderer_ProductSuppliers',
			'filter' => 'Purchase/Widget_Column_Filter_ProductSupplier',
			'index' => 'entity_id'
        ));
		
        $this->addColumn('qty', array(
            'header'    => Mage::helper('purchase')->__('Qty'),
            'name'      => 'qty',
            'type'      => 'number',
            'index'     => 'qty',
            'width'     => '70',
            'editable'  => true,
            'edit_only' => false
        ));
                     
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getData('grid_url') ? $this->getData('grid_url') : $this->getUrl('*/*/ProductSelectionGrid', array('_current'=>true, 'po_num' => $this->getOrder()->getId()));
    }

    public function getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('products', null);
        if (!is_array($products)) {
            $products = array();
        }
        return $products;
    }
}
