<?php

class MDN_Purchase_Block_Widget_Column_Renderer_AvailableQty
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
		$orderedQty = $row->getordered_qty();
		
		$stock = $row->getstock();
		if ($stock == '')
			$stock = $row->getqty();
		if ($stock == '')
		{
			if ($row->getStockItem())
				$stock = $row->getStockItem()->getQty();
		}
		
    	$retour = ((int)$stock) - ((int)$orderedQty); 
    	if ($retour <= 0)
    		$retour = "0";
				
		return $retour;
    }
    
}