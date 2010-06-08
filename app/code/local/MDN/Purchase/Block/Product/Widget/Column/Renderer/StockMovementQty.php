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
class MDN_Purchase_Block_Product_Widget_Column_Renderer_StockMovementQty
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
    	$retour = $row->getsm_qty() * $row->getsm_coef();
    	
    	if ($retour > 0)
    		$retour = '<font color="green">'.$retour.'</font>';
		else 
    		$retour = '<font color="red">'.$retour.'</font>';
    	
    	//retourne
        return $retour;
    }
}