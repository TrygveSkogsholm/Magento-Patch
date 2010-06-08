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
/*
* retourne les éléments à envoyer pour une commande sélectionnée pour la préparation de commandes
*/
class MDN_Purchase_Block_Widget_Column_Renderer_OrderPlanning
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
    	//get planning
    	$planning = $row->getPlanning();
    	
    	//if no planning found, try to load it from order id
    	if ($planning == null)
    	{
    		$orderId = $row->getopp_order_id();
    		$planning = mage::getModel('Purchase/SalesOrderPlanning')->load($orderId , 'psop_order_id');
    	}
    	
    	if ($planning)
    	{
    		$html = '<div class="nowrap" style="text-align: left;">';
    		if ($planning->getFullstockDate() != '')
		    	$html .= mage::helper('purchase')->__('Prepare').' : <font color="'.$this->getColorForDate($planning->getFullstockDate()).'">'.mage::helper('core')->formatDate($planning->getFullstockDate(), 'short').'</font>';	
    		if ($planning->getShippingDate() != '')
		    	$html .= '<br>'.mage::helper('purchase')->__('Ship').' : <font color="'.$this->getColorForDate($planning->getShippingDate()).'">'.mage::helper('core')->formatDate($planning->getShippingDate(), 'short').'</font>';	
    		if ($planning->getDeliveryDate() != '')
		    	$html .= '<br>'.mage::helper('purchase')->__('Delivery').' <font color="'.$this->getColorForDate($planning->getDeliveryDate()).'">: '.mage::helper('core')->formatDate($planning->getDeliveryDate(), 'short').'</font>';	
	    	$html .= '</div>';
    	}
    	else 
    		$html = $this->__('No planning');
		return $html;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $date
     */
    public function getColorForDate($date)
    {
    	$retour = '';
    	$timestamp = strtotime($date);
    	$now = time();
    	
    	if ($timestamp < $now)
    		$retour = '#ff0000';
    	else 
    	{
    		$diff = $timestamp - $now;
    		if ($diff > 3600 * 24 * 2)
    			$retour = '#00FF00';
    		else 
    			$retour = 'orange';
    	}
    	
    	return $retour;
    }
    
}