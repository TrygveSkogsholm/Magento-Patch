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
class MDN_Purchase_Block_SupplyNeeds_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	private $_mode = null;
	private $_orderId = null;
	
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('SupplyNeedsGrid');
        $this->_parentTemplate = $this->getTemplate();
        $this->setVarNameFilter('supply_needs');
        //$this->setTemplate('Shipping/List.phtml');	
        $this->setEmptyText(Mage::helper('customer')->__('No Items Found'));
        
        $this->setDefaultSort('sn_deadline', 'desc');
        $this->setSaveParametersInSession(true);
    }
    
    public function setMode($mode, $orderId)
    {
    	$this->_mode = $mode;
    	$this->_orderId = $orderId;
    }

    /**
     * 
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {		            
        $collection = Mage::getModel('Purchase/SupplyNeeds')
        	->getCollection();
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
            
        $this->addColumn('sn_priority', array(
            'header'=> Mage::helper('purchase')->__('Priority'),
            'index' => 'sn_priority',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_SupplyNeedPriority',
            'align'	=> 'center',
            'filter'    => false,
        ));
    	    
        $this->addColumn('sn_manufacturer_id', array(
            'header'=> Mage::helper('purchase')->__('Manufacturer'),
            'index' => 'sn_manufacturer_id',
            'type' => 'options',
            'options' => $this->getManufacturersAsArray(),
        ));
           
        $this->addColumn('sn_product_sku', array(
            'header'=> Mage::helper('purchase')->__('Sku'),
            'index' => 'sn_product_sku'
        ));
               
        $this->addColumn('sn_product_name', array(
            'header'=> Mage::helper('purchase')->__('Product'),
            'index' => 'sn_product_name'
        ));
                                    
        $this->addColumn('sn_status', array(
            'header'=> Mage::helper('purchase')->__('Status'),
            'index' => 'sn_status',
            'align' => 'center',
            'type' => 'options',
            'options' => array(
                'supply_order' => Mage::helper('purchase')->__('Supply for orders'),
                'supply_min_qty' => Mage::helper('purchase')->__('Supply for mini qty'),
                'manual_supply_need' => Mage::helper('purchase')->__('manual_supply_need'),
                'OK' => Mage::helper('purchase')->__('Ok')
            ),
        ));
                  
        $this->addColumn('sn_details', array(
            'header'=> Mage::helper('purchase')->__('Details'),
            'index' => 'sn_details',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_SupplyNeedsDetails',
            'align'	=> 'center',
            'filter'    => false,
            'sortable'  => false
        ));
                                      
        $this->addColumn('sn_needed_qty', array(
            'header'=> Mage::helper('purchase')->__('Qty'),
            'index' => 'sn_needed_qty',
            'align'	=> 'center'
        ));
        
        $this->addColumn('sn_deadline', array(
            'header'=> Mage::helper('purchase')->__('Dead Line'),
            'index' => 'sn_deadline',
            'renderer' => 'MDN_Purchase_Block_Widget_Column_Renderer_DateMaxInvisible',
            'align'	=> 'center'
        ));
        
        $this->addColumn('sn_purchase_deadline', array(
            'header'=> Mage::helper('purchase')->__('Dead Line<br>for Purchase'),
            'index' => 'sn_purchase_deadline',
            'renderer' => 'MDN_Purchase_Block_Widget_Column_Renderer_DateMaxInvisible',
            'align'	=> 'center'
        ));
        
                          
        $this->addColumn('sn_suppliers_name', array(
            'header'=> Mage::helper('purchase')->__('Suppliers'),
            'index' => 'sn_suppliers_ids',    
            'filter'    => 'Purchase/Widget_Column_Filter_SupplyNeedsSuppliers',
            'renderer' => 'MDN_Purchase_Block_Widget_Column_Renderer_SupplyNeedsSuppliers'       
        ));
        
        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('sn_product_id');
        $this->getMassactionBlock()->setFormFieldName('supply_needs_product_ids');

        
        switch ($this->_mode) {
        	case null:
        		        $this->getMassactionBlock()->addItem('create_order', array(
				             'label'=> Mage::helper('sales')->__('Create Purchase Order'),
				             'url'  => $this->getUrl('*/*/CreatePurchaseOrder'),
				             'additional' => array(
					                    'supplier' => array(
				                         'name' => 'supplier',
				                         'type' => 'select',
				                         'class' => 'required-entry',
				                         'label' => Mage::helper('catalog')->__('Supplier'),
				                         'values' => $this->getSuppliersAsArray()
				                     )
				             )
				        ));
        		break;
        
        	case 'import':
        		        $this->getMassactionBlock()->addItem('create_order', array(
				             'label'=> Mage::helper('sales')->__('Import in Purchase Order'),
				             'url'  => "javascript: addProducts();"
				             ));
        		break;
        }

        return $this;
    }
    
    public function getGridUrl()
    {
        //return $this->getUrl('*/*/GridAjax', array('_current'=>true));
    }

    public function getGridParentHtml()
    {
        $templateName = Mage::getDesign()->getTemplateFilename($this->_parentTemplate, array('_relative'=>true));
        return $this->fetchView($templateName);
    }
    

    /**
     * Définir l'url pour chaque ligne
     * permet d'accéder à l'écran "d'édition" d'une commande
     */
    public function getRowUrl($row)
    {
    	if ($this->_mode != 'import')
	    	return $this->getUrl('Purchase/Products/Edit', array('product_id' => $row->getsn_product_id()));
    }
    
    /**
     * Url pour ajouter un Custom Shipping
     *
     */
    public function getNewUrl()
    {
		//return $this->getUrl('Purchase/Orders/New', array());
    }

    /**
     * Return suppliers list as array
     *
     */
    public function getSuppliersAsArray()
    {
		$retour = array();

		//charge la liste des pays
		$collection = Mage::getModel('Purchase/Supplier')
			->getCollection()
			->setOrder('sup_name', 'asc');
		foreach ($collection as $item)
		{
			$retour[$item->getsup_id()] = $item->getsup_name();
		}
		return $retour;
    }
    
    /**
     * Return manufacturers list as array
     *
     */
    public function getManufacturersAsArray()
    {
		$retour = array();
		
		//recupere la liste des manufacturers
		$product = Mage::getModel('catalog/product');
		$attributes = Mage::getResourceModel('eav/entity_attribute_collection')
		    ->setEntityTypeFilter($product->getResource()->getTypeId())
	    	->addFieldToFilter('attribute_code', 'manufacturer') // This can be changed to any attribute code
	    	->load(false);
 		$attribute = $attributes->getFirstItem()->setEntity($product->getResource());
		$manufacturers = $attribute->getSource()->getAllOptions(false);
		
		//ajoute au menu
		foreach ($manufacturers as $manufacturer)
		{
			$retour[$manufacturer['value']] = $manufacturer['label'];
		}
		
		return $retour;
    }
}
