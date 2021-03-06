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
class MDN_Purchase_Block_Product_Edit_Tabs_AssociatedManufacturers extends Mage_Adminhtml_Block_Template
{
	private $_product_id = null;
		
	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		parent::__construct();
				
	}
	
	/**
	 * D�finit le produit concern�
	 *
	 * @param unknown_type $value
	 */
	public function setProductId($value)
	{
		$this->_product_id = $value;
		return $this;
	}
			
	/**
	 * Retourne le produit concern�
	 *
	 * @param unknown_type $value
	 */
	public function getProductId()
	{
		return $this->_product_id;
	}
	
	/**
	 * Retourne la liste des manufacturer associ� � un produit
	 *
	 * @return unknown
	 */
	public function getManufacturers()
	{
		$collection = mage::GetModel('Purchase/ProductManufacturer')
						->getCollection()
			            ->join('Purchase/Manufacturer',
					           'man_id=ppm_manufacturer_num')
						->addFieldToFilter('ppm_product_id', $this->_product_id)
						;
		return $collection;
	}
			
	/**
	 * Retourne la liste des Fabricants non li�s au produit sous la forme d'un combo
	 *
	 */
	public function getNonLinkedManufacturersAsCombo($name='manufacturer')
	{
		//recupere la liste des manufacturers li�s
		$collection = mage::GetModel('Purchase/ProductManufacturer')
				->getCollection()
				->addFieldToFilter('ppm_product_id', $this->_product_id)
				;
		$t_ids = array();
		$t_ids[] = -1;
		foreach ($collection as $item)
		{
			$t_ids[] = $item->getppm_manufacturer_num();
		}
						
		//Recupere la liste
		$collection = mage::GetModel('Purchase/Manufacturer')
						->getCollection()
						->addFieldToFilter('man_id', array('nin' => $t_ids));
		
		//transforme en combo
		$retour = '<select id="'.$name.'" name="'.$name.'">';
		foreach($collection as $item)
		{
			$retour .= '<option value="'.$item->getId().'">'.$item->getman_name().'</option>';
		}
		$retour .= '</select>';
		
		//retour
		return $retour;
	}
}