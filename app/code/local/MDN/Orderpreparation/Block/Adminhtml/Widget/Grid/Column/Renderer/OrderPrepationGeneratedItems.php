<?php

/*
* retourne les �l�ments � envoyer pour une commande s�lectionn�e pour la pr�paration de commandes
*/
class MDN_Orderpreparation_Block_Adminhtml_Widget_Grid_Column_Renderer_OrderPrepationGeneratedItems
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
    	//recupere les infos
    	$retour = $this->_getValue($row);
    	return $retour;
    }
    
}