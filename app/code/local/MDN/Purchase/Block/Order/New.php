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
class MDN_Purchase_Block_Order_New extends Mage_Adminhtml_Block_Widget_Form
{
		
	/**
	 * Constructeur: on charge le devis
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		
	}
	
	/**
	 * Retourne l'url pour retourner a la liste des commandes
	 */
	public function GetBackUrl()
	{
		return $this->getUrl('Purchase/Orders/List', array());
	}

	/**
	 * Retourne la liste des fournisseur ssous la forme d'un combo
	 */
	public function getSuppliersAsCombo($name='supplier')
	{
		$retour = '<select  id="'.$name.'" name="'.$name.'">';

		//charge la liste des pays
		$collection = Mage::getModel('Purchase/Supplier')
			->getCollection()
			->setOrder('sup_name', 'asc');
		foreach ($collection as $item)
		{
			$retour .= '<option value="'.$item->getsup_id().'">'.$item->getsup_name().'</option>';
		}
		
		$retour .= '</select>';
		return $retour;
	}
}
