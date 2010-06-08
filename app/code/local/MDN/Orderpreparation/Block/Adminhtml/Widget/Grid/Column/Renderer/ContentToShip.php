<?php

/*
* retourne les éléments à envoyer pour une commande sélectionnée pour la préparation de commandes
*/
class MDN_Orderpreparation_Block_Adminhtml_Widget_Grid_Column_Renderer_ContentToShip
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
    	$retour = "";
    	
    	//Verifie si le shipment a été créé
    	$order = $row;
    	$OrderToPrepare = mage::getModel('Orderpreparation/ordertoprepare')->load($order->getId(), 'order_id');
    	
    	//recupere les items à envoyer
    	$order = $row;
    	$collection = mage::getModel('Orderpreparation/ordertoprepare')->GetItemsToShip($order->getId());
    	foreach ($collection as $item)
    	{
    		$product = mage::getModel('catalog/product')->load($item->getproduct_id());
			$retour .= $item->getqty();
	    	$retour .= 'x '.$product->getname().'<br>';

    	}
    	
    	return $retour;
    }
    
}