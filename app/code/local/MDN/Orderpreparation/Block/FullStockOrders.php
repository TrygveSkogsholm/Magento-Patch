<?php

/**
 * Tableau contenant la liste des commandes préparables
 *
 */
class MDN_Orderpreparation_Block_FullStockOrders extends Mage_Adminhtml_Block_Widget_Grid
{
	protected $_parentTemplate = '';
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('FullStockOrdersGrid');
        $this->_parentTemplate = $this->getTemplate();
        //$this->setTemplate('Orderpreparation/FullStockOrders.phtml');
        $this->setEmptyText(Mage::helper('customer')->__('No Items Found'));
        $this->setDefaultLimit(50);
        $this->setUseAjax(true);
        $this->setVarNameFilter('orderpreparation_fullstock');
    }

    /**
     * Charge la collection des devis
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {   	
    	$collection = mage::getModel('Orderpreparation/ordertoprepare')->getFullStockOrdersFromCache();
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

     
        $this->addColumn('real_order_id', array(
            'header'=> Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type'  => 'number',
            'index' => 'opp_order_increment_id',
        ));
        
		//Organizer
        $this->addColumn('organizer', array(
            'header'=> Mage::helper('Organizer')->__('Organizer'),
       		'renderer'  => 'MDN_Organizer_Block_Widget_Column_Renderer_Comments',
            'align' => 'center',
            'entity' => 'order',
            'filter' => false,
            'sort' => false,
            'entity_id_field' => 'opp_order_id'
        ));
        
        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'opp_shipto_name',
        ));
        	
        $this->addColumn('content', array(
            'header' => Mage::helper('sales')->__('Remain to Ship'),
            'index' => 'opp_remain_to_ship',
            'renderer'  => 'MDN_Orderpreparation_Block_Adminhtml_Widget_Grid_Column_Renderer_Content',
            'sortable'  => false,
        ));
                     
        $this->addColumn('summary', array(
            'header' => Mage::helper('sales')->__('Summary'),
            'align' => 'left',
            'index' => 'opp_details',
            'renderer'  => 'MDN_Orderpreparation_Block_Adminhtml_Widget_Grid_Column_Renderer_OrderPrepationGeneratedItems',
            'sortable'  => false
        ));
        
        $this->addColumn('shipping_method', array(
            'header' => Mage::helper('sales')->__('Shipping Method'),
            'index' => 'opp_shipping_method'
        ));
                
        $this->addColumn('payment_validated', array(
            'header' => Mage::helper('sales')->__('Payment validated'),
            'index' => 'opp_payment_validated',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center'
        ));
        
        $this->addColumn('planning', array(
            'header' => Mage::helper('purchase')->__('Planning'),
            'index' => 'planning',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_OrderPlanning',
            'align'	=> 'center',
            'filter'    => false,
            'sortable'  => false
        ));
        
        $this->addColumn('actions', array(
            'header' => Mage::helper('purchase')->__('Actions'),
            'mode' => 'fullstock',
            'renderer'  => 'MDN_Orderpreparation_Block_Widget_Grid_Column_Renderer_Actions',
            'align' => 'center',
            'filter'    => false,
            'sortable'  => false
        ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/FullStockOrderGrid', array('_current'=>true));
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
    	return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getopp_order_id()));
    }
    
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('opp_order_id');
        $this->getMassactionBlock()->setFormFieldName('full_stock_orders_order_ids');

        $this->getMassactionBlock()->addItem('add_selection', array(
             'label'=> Mage::helper('sales')->__('Add to Selection'),
             'url'  => $this->getUrl('*/*/massAddToSelection'),
        ));

        return $this;
    }
    
    /**
     * Retourne les commentaires pour toues les commandes sélectionnées
     *
     */
    public function getAllComments()
    {
    	$retour = '';
    	$collection = Mage::getSingleton('Orderpreparation/ordertoprepare')->getFullStockOrdersFromCache();
    	foreach($collection as $item)
    	{
    		$comments = mage::helper('Organizer')->getEntityCommentsSummary('order', $item->getopp_order_id(), true);
    		if ($comments != '')
				$retour .= '<a href="'.$this->getUrl('adminhtml/sales_order/view', array('order_id' => $item->getopp_order_id())).'"><b>Order #'.$item->getopp_order_increment_id().'</b></a> : '.$comments;    		
    	}
    	return $retour;
    }
    
}