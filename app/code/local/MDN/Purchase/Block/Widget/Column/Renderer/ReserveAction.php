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
class MDN_Purchase_Block_Widget_Column_Renderer_ReserveAction
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
		$html = '';

    	//retrieve information
    	$productId =  $this->getColumn()->getproduct_id();
    	$collection = mage::getModel('sales/order_item')
    						->getCollection()
    						->addFieldToFilter('order_id', $row->getId())
    						->addFieldToFilter('product_id', $productId);
    	$orderItemRow = null;
    	foreach ($collection as $item)
    	{
    		$orderItemRow = $item;
    	}
		
    	if ($orderItemRow != null)
    	{
	    	$remainingQty = $orderItemRow->getRemainToShipQty();
	    	
	    	$reserveUrl = Mage::helper('adminhtml')->getUrl('Purchase/ProductReservation/Reserve', array('product_id' => $productId, 'order_id' => $row->getId()));
	    	$releaseUrl = Mage::helper('adminhtml')->getUrl('Purchase/ProductReservation/Release', array('product_id' => $productId, 'order_id' => $row->getId()));
	    	
			if (($orderItemRow->getreserved_qty() == 0) && ($remainingQty > 0))
				$html .= '<a href="'.$reserveUrl.'">'.mage::helper('purchase')->__('Reserve').'</a><br>';	
			if ($orderItemRow->getreserved_qty() > 0)
				$html .= '<a href="'.$releaseUrl.'">'.mage::helper('purchase')->__('Release').'</a>';	
    	}
		return $html;
    }
    
}