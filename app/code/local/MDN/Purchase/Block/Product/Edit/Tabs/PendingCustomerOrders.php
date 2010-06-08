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
class MDN_Purchase_Block_Product_Edit_Tabs_PendingCustomerOrders extends Mage_Adminhtml_Block_Widget_Grid
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
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('PendingCustomerOrdersGrid');
        $this->_parentTemplate = $this->getTemplate();
        //$this->setTemplate('Shipping/List.phtml');	
        $this->setEmptyText('Aucun elt');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('pending_customer_orders');
        $this->setDefaultSort('increment_id', 'DESC');
    }

    /**
     * Charge la collection
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {		            
		//charge les mouvements de stock
		$product = mage::getModel('catalog/product')->load($this->getProductId());
		$collection = $product->GetPendingOrders(false);

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
                               
        $this->addColumn('increment_id', array(
            'header'=> Mage::helper('purchase')->__('Id'),
            'index' => 'increment_id'
        ));
        
        $this->addColumn('created_at', array(
            'header' => Mage::helper('purchase')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));
        
        $this->addColumn('billing_name', array(
            'header' => Mage::helper('purchase')->__('Bill to Name'),
            'index' => 'billing_name',
        ));
        
        $this->addColumn('grand_total', array(
            'header' => Mage::helper('purchase')->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type'  => 'currency',
            'currency' => 'order_currency_code',
        ));
            
        $this->addColumn('ordered_qty', array(
            'header' => Mage::helper('purchase')->__('Ordered<br>Qty'),
            'index' => 'ordered_qty',
            'product_id' => $this->getProductId(),
            'field_name'	=> 'ordered_qty',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_OrderItemQty',
            'align'	=> 'center',
            'filter'    => false,
            'sortable'  => false
        ));
   
        $this->addColumn('shipped_qty', array(
            'header' => Mage::helper('purchase')->__('Shipped<br>Qty'),
            'index' => 'shipped_qty',
            'product_id' => $this->getProductId(),
            'field_name'	=> 'shipped_qty',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_OrderItemQty',
            'align'	=> 'center',
            'filter'    => false,
            'sortable'  => false
        ));
                                       
        $this->addColumn('remaining_qty', array(
            'header' => Mage::helper('purchase')->__('Qty to ship'),
            'index' => 'remaining_qty',
            'product_id' => $this->getProductId(),
            'field_name'	=> 'remaining_qty',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_OrderItemQty',
            'align'	=> 'center',
            'filter'    => false,
            'sortable'  => false
        ));
                         
        $this->addColumn('reserved_qty', array(
            'header' => Mage::helper('purchase')->__('Reserved<br>Qty'),
            'index' => 'reserved_qty',
            'product_id' => $this->getProductId(),
            'field_name'	=> 'reserved_qty',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_OrderItemQty',
            'align'	=> 'center',
            'filter'    => false,
            'sortable'  => false
        ));
        
        $this->addColumn('status', array(
            'header' => Mage::helper('purchase')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));
                                 
        $this->addColumn('planning', array(
            'header' => Mage::helper('purchase')->__('Planning'),
            'index' => 'planning',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_OrderPlanning',
            'align'	=> 'center',
            'filter'    => false,
            'sortable'  => false
        ));
        
        $this->addColumn('reserve_actions', array(
            'header' => Mage::helper('purchase')->__('Actions'),
            'index' => 'planning',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_ReserveAction',
            'align'	=> 'center',
            'product_id' => $this->getProductId(),
            'filter'    => false,
            'sortable'  => false
        ));
        
        return parent::_prepareColumns();
    }

     public function getGridUrl()
    {
        //return $this->getUrl('*/*/StockMovementGrid', array('_current'=>true, 'product_id' => $this->_productId));
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
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
        }
        return false;
    }
		
		
	/**
	 * Retourne l'id du produit courant
	 *
	 * @return unknown
	 */
	public function getProductId()
	{
		return $this->_productId;
	}
}
