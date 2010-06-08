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
class MDN_Orderpreparation_Block_CarrierTemplate_ImportTracking extends Mage_Adminhtml_Block_Widget_Form
{
	public function getCarrierTemplateAsCombo($name)
	{
		$retour = '<select name="'.$name.'" id="'.$name.'">';
		$retour .= '<option value=""></option>';
		$collection = mage::getModel('Orderpreparation/CarrierTemplate')->getCollection();
		foreach($collection as $item)
		{
			$retour .= '<option value="'.$item->getct_id().'">'.$item->getct_name().'</option>';			
		}
		$retour .= '</select>';
		return $retour;
	}
	
	public function getTemplateList()
	{
		return mage::getModel('Orderpreparation/CarrierTemplate')->getCollection();
	}
}