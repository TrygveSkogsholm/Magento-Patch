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
class MDN_Purchase_Block_Product_Edit_Tabs_AssociatedOrdersGrid extends Mage_Adminhtml_Block_Widget_Grid
{
	private $_productId = null;
		
	/**
	 * Définition du numéro de produit
	 *
	 * @param unknown_type $ProductId
	 */
	public function setProductId($ProductId)
	{
		$this->_productId = $ProductId;
		return $this;
	}
	public function getProductId()
	{
		return $this->_productId;
	}
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('AssociatedOrdersGrid');
        $this->_parentTemplate = $this->getTemplate();
        //$this->setTemplate('Shipping/List.phtml');	
        $this->setEmptyText($this->__('No items'));
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('product_associated_orders');
        $this->setDefaultSort('po_order_id', 'DESC');
    }

    /**
     * Charge la collection
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {		            
		//charge les mouvements de stock
		$collection = mage::getModel('Purchase/OrderProduct')
			->getCollection()
			->addFieldToFilter('pop_product_id', $this->_productId)
			->join('Purchase/Order','po_num=pop_order_num')
			->join('Purchase/Supplier','po_sup_num=sup_id');
                 
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
                               
        $this->addColumn('po_order_id', array(
            'header'=> Mage::helper('purchase')->__('Order'),
            'align' => 'center',
            'index'	=> 'po_order_id'
        ));
        
        $this->addColumn('po_date', array(
            'header'=> Mage::helper('purchase')->__('Date'),
            'index' => 'po_date',
            'type'	=> 'date'
        ));
                
        $this->addColumn('sup_name', array(
            'header'=> Mage::helper('purchase')->__('Supplier'),
            'index' => 'sup_name'
        ));
                      
        $this->addColumn('pop_qty', array(
            'header'=> Mage::helper('purchase')->__('Qty'),
            'index' => 'pop_qty',
            'type'	=> 'number'
        ));
                      
        $this->addColumn('pop_supplied_qty', array(
            'header'=> Mage::helper('purchase')->__('Delivered Qty'),
            'index' => 'pop_supplied_qty',
            'type'	=> 'number'
        ));
                              
        $this->addColumn('pop_price_ht_base', array(
            'header'=> Mage::helper('purchase')->__('Unit Price'),
            'index' => 'pop_price_ht_base',
            'type'	=> 'price',
            'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
        ));
                              
        $this->addColumn('pop_extended_costs_base', array(
            'header'=> Mage::helper('purchase')->__('Unit Price + Cost'),
            'index' => 'pop_extended_costs_base',
            'align'	=> 'right',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_ProductUnitPricePlusCost',
            'filterable' => false,
            'sortable' => false
        ));
                                      
                       
        $this->addColumn('Paid', array(
            'header'=> Mage::helper('purchase')->__('Paid'),
            'index' => 'po_paid',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center'
        ));
        
        $this->addColumn('po_status', array(
            'header'=> Mage::helper('purchase')->__('Status'),
            'index' => 'po_status',
            'type' => 'options',
            'options' => mage::getModel('Purchase/Order')->getStatuses(),
            'align'	=> 'right'
        ));

        
        return parent::_prepareColumns();
    }

     public function getGridUrl()
    {
        return $this->getUrl('*/*/AssociatedOrdersGrid', array('_current'=>true, 'product_id' => $this->_productId));
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
    	return $this->getUrl('Purchase/Orders/Edit', array('po_num' => $row->getpo_num()));
    }

}
