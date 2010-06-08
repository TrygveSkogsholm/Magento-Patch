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
class MDN_Purchase_Block_Widget_Column_Renderer_SupplyNeedsDetails
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
		$retour = '';
		$retour .= '<a href="#" class="lien-popup">';
		$retour .= '<img src="'.$this->getSkinUrl('images/note_msg_icon.gif').'">';
		if ($row->getsn_is_critical())
			$retour .= '<img src="'.$this->getSkinUrl('images/purchase/warning.png').'">';
		$retour .= '<span>'.nl2br($row->getsn_details()).'</span>';
		$retour .= '</a>';		
		return $retour;
    }
    
}