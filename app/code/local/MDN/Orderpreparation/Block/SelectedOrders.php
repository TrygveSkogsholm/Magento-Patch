<?php
/**
 * Tableau contenant la liste des commandes préparables
 *
 */
class MDN_OrderPreparation_Block_SelectedOrders extends Mage_Adminhtml_Block_Widget_Grid
{
	protected $_parentTemplate = '';
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('SelectedOrdersGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('increment_id');
        $this->setDefaultDir('DESC');
        $this->_parentTemplate = $this->getTemplate();
        //$this->setTemplate('Orderpreparation/SelectedOrders.phtml');
        $this->setEmptyText(Mage::helper('customer')->__('No Items Found'));
		$this->setDefaultLimit(50);
        //$this->setSaveParametersInSession(true);
        //$this->setVarNameFilter('orderpreparation_selected');
    }

    /**
     * Charge la collection des devis
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {
    	//recupere la liste des commandes (et attributs) dont l'état est 'holded' ou 'pending' ou 'processing'
    	$collection = Mage::getSingleton('Orderpreparation/ordertoprepare')->getSelectedOrders();
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
            'header'=> Mage::helper('sales')->__('Order #'),
            'type'  => 'number',
            'index' => 'increment_id'
        ));
        
		//Organizer
        $this->addColumn('organizer', array(
            'header'=> Mage::helper('Organizer')->__('Organizer'),
       		'renderer'  => 'MDN_Organizer_Block_Widget_Column_Renderer_Comments',
            'align' => 'center',
            'entity' => 'order',
            'filter' => false,
            'sort' => false
        ));
        
        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name'
        ));
        	
        $this->addColumn('content', array(
            'header' => Mage::helper('sales')->__('Content to Ship'),
            'renderer'  => 'MDN_Orderpreparation_Block_Adminhtml_Widget_Grid_Column_Renderer_ContentToShip',
            'index' => 'content',
            'sortable'	=> false,
            'filter'    => false
        ));         
        
        $this->addColumn('shipping_description', array(
            'header' => Mage::helper('sales')->__('Shipping Description'),
            'index' => 'shipping_description'
        ));
                
        $this->addColumn('created_items', array(
            'header' => Mage::helper('sales')->__('Created items'),
            'index' => 'shipping_description',
            'renderer'  => 'MDN_Orderpreparation_Block_Widget_Grid_Column_Renderer_CreatedItems',
            'sortable'	=> false,
            'filter'    => false
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
            'mode' => 'selected',
            'renderer'  => 'MDN_Orderpreparation_Block_Widget_Grid_Column_Renderer_Actions',
            'align' => 'center',
            'filter'    => false,
            'sortable'  => false
        ));
        
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/SelectedOrderGrid', array('_current'=>true));
    }

    public function getGridParentHtml()
    {
        $templateName = Mage::getDesign()->getTemplateFilename($this->_parentTemplate, array('_relative'=>true));
        return $this->fetchView($templateName);
    }
    
    /**
     * Retourne l'id du client courant
     *
     */
    public function getCurrentCustomerId()
    {
    	return Mage::registry('current_customer')->getId();
    }
    
    /**
     * Return comments for all orders
     *
     */
    public function getAllComments()
    {
		$retour = '';
    	$collection = Mage::getSingleton('Orderpreparation/ordertoprepare')->getSelectedOrders();
    	foreach($collection as $item)
    	{
    		$comments = mage::helper('Organizer')->getEntityCommentsSummary('order', $item->getId(), true);
    		if ($comments != '')
				$retour .= '<a href="'.$this->getUrl('adminhtml/sales_order/view', array('order_id' => $item->getId())).'"><b>Order #'.$item->getincrement_id().'</b></a> : '.$comments;    		
    	}
    	return $retour;    	
    }

}