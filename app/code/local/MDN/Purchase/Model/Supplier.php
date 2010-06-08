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
class MDN_Purchase_Model_Supplier  extends Mage_Core_Model_Abstract
{
	
	/*****************************************************************************************************************************
	* ***************************************************************************************************************************
	* Constructeur
	*
	*/
	public function _construct()
	{
		parent::_construct();
		$this->_init('Purchase/Supplier');
	}
	
	/**
	 * Retourne l'adresse du fournisseur au format texte
	 *
	 */
	public function getAddressAsText($ShowAll = true)
	{
		$retour = $this->getsup_name()." \n ";
		$retour .= $this->getsup_address1()." \n ";
		$retour .= $this->getsup_address2()." \n ";
		$retour .= $this->getsup_zipcode().' '.$this->getsup_city()." \n ";
		if ($this->getsup_country() != '')
			$retour .= Mage::getModel('directory/country')->loadByCode($this->getsup_country())->getName()." \n ";
		if ($ShowAll)
		{
			$retour .= 'Fax : '.$this->getsup_fax()." \n ";
			$retour .= 'Email : '.$this->getsup_mail();
		}
		return $retour;
	}
	
	/**
	 * Retourne la référence pour un produit chez ce fournisseur
	 *
	 * @param unknown_type $ProductId
	 */
	public function getProductReference($ProductId)
	{
		$retour = '';
		
		$collection = mage::getModel('Purchase/ProductSupplier')
			->getCollection()
			->addFieldToFilter('pps_product_id', $ProductId)
			->addFieldToFilter('pps_supplier_num', $this->getId());
			
		//si ya des résultats
		if (sizeof($collection) > 0)
		{
			foreach ($collection as $item)
			{
				$retour = $item->getpps_reference();
			}
		}
		
		return $retour;
	}
}