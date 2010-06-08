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
class MDN_Purchase_Block_StockMovement_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('StockMovementGrid');
        $this->_parentTemplate = $this->getTemplate();
        //$this->setTemplate('Shipping/List.phtml');	
        $this->setEmptyText(Mage::helper('customer')->__('No Items Found'));
        $this->setSaveParametersInSession(true);

        $this->setDefaultSort('sm_id');
		$this->setDefaultDir('desc');
		
    }

    /**
     * Charge la collection
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {	  		
    	//Charge la collection
        $collection = Mage::getModel('Purchase/StockMovement')
        	->getCollection()
            ->join('Purchase/CatalogProduct','sm_product_id=`Purchase/CatalogProduct`.entity_id')
            ->join('Purchase/CatalogProductVarchar','sm_product_id=`Purchase/CatalogProductVarchar`.entity_id and store_id=0 and attribute_id = '.mage::getModel('Purchase/Constant')->GetProductNameAttributeId());
        	
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
    	$this->addColumn('sm_id', array(
            'header'=> Mage::helper('sales')->__('Id'),
            'index' => 'sm_id',
            'filter' => false
        ));
    	
        $this->addColumn('sm_date', array(
            'header'=> Mage::helper('sales')->__('Date'),
            'index' => 'sm_date',
            'type' => 'date',
            'align' => 'center'
        ));
        
        $this->addColumn('sku', array(
            'header'=> Mage::helper('sales')->__('Sku'),
            'index' => 'sku'
        ));
    	                               
        $this->addColumn('value', array(
            'header'=> Mage::helper('sales')->__('Name'),
            'index' => 'value'
        ));

        
        $this->addColumn('type', array(
            'header'=> Mage::helper('sales')->__('Type'),
            'index' => 'sm_type',
            'align' => 'center',
            'renderer'  => 'MDN_Purchase_Block_Product_Widget_Column_Renderer_StockMovementType'
        ));
        
        $this->addColumn('Qty', array(
            'header'=> Mage::helper('sales')->__('Qty'),
            'index' => 'sm_qty',
            'align' => 'center',
            'renderer'  => 'MDN_Purchase_Block_Product_Widget_Column_Renderer_StockMovementQty'
        ));
        
        $this->addColumn('Description', array(
            'header'=> Mage::helper('sales')->__('Description'),
            'index' => 'sm_description'
        ));
        
        return parent::_prepareColumns();
    }

     public function getGridUrl()
    {
        return ''; //$this->getUrl('*/*/wishlist', array('_current'=>true));
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
    	return $this->getUrl('Purchase/Products/Edit', array('product_id' => $row->getentity_id()));
    }
    
}
