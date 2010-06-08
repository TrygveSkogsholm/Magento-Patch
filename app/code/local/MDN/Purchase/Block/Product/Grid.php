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
class MDN_Purchase_Block_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('ProductsGrid');
        $this->_parentTemplate = $this->getTemplate();
        //$this->setTemplate('Shipping/List.phtml');	
        $this->setEmptyText(Mage::helper('customer')->__('No Items Found'));
        $this->setSaveParametersInSession(true);
    }

    /**
     * Charge la collection
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {	
    	//Recupere les paramétrages par défaut
    	$DefaultManageStock = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');
    	if ($DefaultManageStock == '')
    		$DefaultManageStock = 1;
		$DefaultNotifyStockQty = Mage::getStoreConfig('cataloginventory/item_options/notify_stock_qty');
    	if ($DefaultNotifyStockQty == '')
    		$DefaultNotifyStockQty = 0;
    		
    	//Charge la collection
        $collection = Mage::getModel('Catalog/Product')
        	->getCollection()
        	->addAttributeToSelect('name')
        	->addAttributeToSelect('ordered_qty')
        	->addAttributeToSelect('price')
        	->addAttributeToSelect('cost')
            ->addAttributeToSelect('status')
            ->addAttributeToSelect('visibility')
            ->joinField('stock_qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->joinField('notify_stock_qty',
                'cataloginventory/stock_item',
                'notify_stock_qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->joinField('use_config_notify_stock_qty',
                'cataloginventory/stock_item',
                'use_config_notify_stock_qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->addExpressionAttributeToSelect('real_notify_stock_qty',
                'if(`_table_stock_qty`.`use_config_notify_stock_qty` = 0, `_table_stock_qty`.`notify_stock_qty`, '.$DefaultNotifyStockQty.')',
                 array())
            ->addExpressionAttributeToSelect('qty_needed',
                '-(`_table_stock_qty`.`qty` - {{ordered_qty}} - if(`_table_stock_qty`.`use_config_notify_stock_qty` = 0, `_table_stock_qty`.`notify_stock_qty`, '.$DefaultNotifyStockQty.'))',
                 array('ordered_qty'))
            ->addExpressionAttributeToSelect('margin',
                'round(({{price}} - {{cost}}) / {{price}} * 100, 2)',
                 array('price', 'cost'));
        	;
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
    	
    	$this->addColumn('organiser', array(
            'header'=> Mage::helper('Organizer')->__('Organizer'),
       		'renderer'  => 'MDN_Organizer_Block_Widget_Column_Renderer_Comments',
            'align' => 'center',
            'entity' => 'product',
            'filter' => false,
            'sort' => false
        ));
                               
        $this->addColumn('Sku', array(
            'header'=> Mage::helper('purchase')->__('Sku'),
            'index' => 'sku'
        ));
        
        $this->addColumn('name', array(
            'header'=> Mage::helper('purchase')->__('Name'),
            'index' => 'name',
        ));
        
        $this->addColumn('buy_price', array(
            'header'=> Mage::helper('purchase')->__('Buy Price'),
            'index' => 'cost',
            'type'	=> 'price',
            'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
        ));
        
        $this->addColumn('sell_price', array(
            'header'=> Mage::helper('purchase')->__('Sell Price'),
            'index' => 'price',
            'type'	=> 'price',
            'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
            'align'	=> 'center'
        ));
        
		$this->addColumn('margin', array(
            'header'=> Mage::helper('purchase')->__('Margin %'),
            'index' => 'margin',
            'type'	=> 'number',
            'align'	=> 'center'
        ));

        $this->addColumn('stock', array(
            'header'=> Mage::helper('purchase')->__('Stock'),
            'index' => 'stock_qty',
            'type'	=> 'number',
            'align'	=> 'center'
        ));

        $this->addColumn('stock_mini', array(
            'header'=> Mage::helper('purchase')->__('Stock Mini'),
            'index' => 'real_notify_stock_qty',
            'type'	=> 'number',
            'align'	=> 'center'
        ));
        
        $this->addColumn('ordered_qty', array(
            'header'=> Mage::helper('purchase')->__('Qty Ordered'),
            'index' => 'ordered_qty',
            'type'	=> 'number',
            'align'	=> 'center'
        ));
        
        $this->addColumn('qty_needed', array(
            'header'=> Mage::helper('purchase')->__('Qty Needed'),
            'renderer'  => 'MDN_Purchase_Block_Product_Widget_Column_Renderer_QtyNeeded',
            'index' => 'qty_needed',
            'type'	=> 'number',
            'align'	=> 'center'
        ));

        /*
        $this->addColumn('waiting_for_supply_qty', array(
            'header'=> Mage::helper('sales')->__('Waiting<br>For<br>Supply Qty'),
            'index' => 'waiting_for_supply_qty',
            'type'	=> 'number',
            'align'	=> 'center'
        ));

		$this->addColumn('delta', array(
            'header'=> Mage::helper('sales')->__('Delta'),
            'index' => 'delta',
            'type'	=> 'number',
            'align'	=> 'center'
        ));
        */

        $this->addColumn('status', array(
            'header'    => Mage::helper('catalog')->__('Status'),
            'width'     => '80',
            'index'     => 'status',
            'type'  => 'options',
            'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
        ));
        $this->addColumn('visibility', array(
            'header'    => Mage::helper('catalog')->__('Visibility'),
            'width'     => '80',
            'index'     => 'visibility',
            'type'  => 'options',
            'options' => Mage::getModel('catalog/product_visibility')->getOptionArray()
        ));
        
        $this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
        
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
    	return $this->getUrl('Purchase/Products/Edit', array())."product_id/".$row->getId();
    }
    
}
