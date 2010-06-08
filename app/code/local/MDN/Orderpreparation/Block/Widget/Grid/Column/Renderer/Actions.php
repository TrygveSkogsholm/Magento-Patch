<?php

/*
* retourne le contenu d'une commande
*/
class MDN_Orderpreparation_Block_Widget_Grid_Column_Renderer_Actions
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
    	$mode = $this->getColumn()->getmode();
    	$retour = '';

    	
    	
    	switch ($mode) {
    		case 'selected':
    			$retour = '<a href="'.$this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getorder_id())).'">'.$this->__('View order').'</a>';
		    	$retour .= '<br><a href="'.$this->getUrl('OrderPreparation/OrderPreparation/RemoveFromSelection', array('order_id' => $row->getorder_id())).'">'.$this->__('Remove').'</a>';    			
    			break;
    		case 'fullstock':
    			$retour = '<a href="'.$this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getopp_order_id())).'">'.$this->__('View order').'</a>';
		    	$retour .= '<br><a href="'.$this->getUrl('OrderPreparation/OrderPreparation/AddToSelection', array('order_id' => $row->getopp_order_id())).'">'.$this->__('Select').'</a>';    			
    			break;
    		case 'stockless':
    			$retour = '<a href="'.$this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getopp_order_id())).'">'.$this->__('View order').'</a>';
		    	$retour .= '<br><a href="'.$this->getUrl('OrderPreparation/OrderPreparation/AddToSelection', array('order_id' => $row->getopp_order_id())).'">'.$this->__('Select').'</a>';    			
    			break;
    		case 'ignored':
    			$retour = '<a href="'.$this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getopp_order_id())).'">'.$this->__('View order').'</a>';
		    	$retour .= '<br><a href="'.$this->getUrl('OrderPreparation/OrderPreparation/AddToSelection', array('order_id' => $row->getopp_order_id())).'">'.$this->__('Select').'</a>';    			
    			break;    			
    	}
    	
        return $retour;
    }
}