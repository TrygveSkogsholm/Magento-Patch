<?php

/*
* retourne les �l�ments � envoyer pour une commande s�lectionn�e pour la pr�paration de commandes
*/
class MDN_Orderpreparation_Block_Adminhtml_Widget_Grid_Column_Renderer_ContentToShip
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
    	$retour = "";
    	
    	//Verifie si le shipment a �t� cr��
    	$order = $row;
    	$OrderToPrepare = mage::getModel('Orderpreparation/ordertoprepare')->load($order->getId(), 'order_id');
    	
    	//recupere les items � envoyer
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