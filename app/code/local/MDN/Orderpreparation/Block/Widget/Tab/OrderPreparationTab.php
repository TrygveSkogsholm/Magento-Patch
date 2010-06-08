<?php

/**
 * tab pour la préparation des commandes
 *
 */
class MDN_Orderpreparation_Block_Widget_Tab_OrderPreparationTab extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('order_preparation_tabs');
        //$this->setDestElementId('main-container');
        $this->setDestElementId('order_preparation_tabs');
        $this->setTitle(Mage::helper('customer')->__('Order Preparation'));
        $this->setTemplate('widget/tabshoriz.phtml');
    }

    protected function _beforeToHtml()
    {

        $this->addTab('fullstockorders', array(
            'label'     => Mage::helper('customer')->__('Full stock Orders').' ('.mage::getModel('Orderpreparation/ordertoprepare')->getFullStockOrdersFromCache()->getSize().')',
            'content'   => $this->getLayout()->createBlock('Orderpreparation/FullStockOrders')->setTemplate('Orderpreparation/FullStockOrders.phtml')->toHtml()
        ));

        $this->addTab('stocklessorders', array(
            'label'     => Mage::helper('customer')->__('Stockless Orders').' ('.mage::getModel('Orderpreparation/ordertoprepare')->getStockLessOrdersFromCache()->getSize().')',
            'content'   => $this->getLayout()->createBlock('Orderpreparation/StocklessOrders')->setTemplate('Orderpreparation/StocklessOrders.phtml')->toHtml()
        ));

        $this->addTab('ignoredorders', array(
            'label'     => Mage::helper('customer')->__('Ignored Orders').' ('.mage::getModel('Orderpreparation/ordertoprepare')->getIgnoredOrdersFromCache()->getSize().')',
            'content'   => $this->getLayout()->createBlock('Orderpreparation/IgnoredOrders')->setTemplate('Orderpreparation/IgnoredOrders.phtml')->toHtml(),
            'active'    => true
        ));
        
        $this->addTab('selectedorders', array(
            'label'     => Mage::helper('customer')->__('Selected Orders').' ('.mage::getModel('Orderpreparation/ordertoprepare')->getSelectedOrders()->getSize().')',
            'content'   => $this->getLayout()->createBlock('Orderpreparation/SelectedOrders')->setTemplate('Orderpreparation/SelectedOrders.phtml')->toHtml(),
            'active'    => true
        ));
            
        return parent::_beforeToHtml();
    }
    
    protected function _toHtml()
    {
    	$retour = parent::_toHtml();
    	$button = '<div align="right"><button onclick="document.location.href=\''.$this->getUrl('OrderPreparation/OrderPreparation/RefreshList').'\'" class="scalable save" type="button"><span>'.$this->__('Force Refresh').'</span></button></div>';
        return $button.$retour;
    }

}
