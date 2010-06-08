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
class MDN_Purchase_Block_Product_Edit_Tabs_AssociatedSuppliers extends Mage_Adminhtml_Block_Template
{
	private $_product_id = null;
	private $_currency = null;
	
	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		parent::__construct();
				
	}
	
	/**
	 * Définit le produit concerné
	 *
	 * @param unknown_type $value
	 */
	public function setProductId($value)
	{
		$this->_product_id = $value;
		return $this;
	}
			
	/**
	 * Retourne le produit concerné
	 *
	 * @param unknown_type $value
	 */
	public function getProductId()
	{
		return $this->_product_id;
	}
	
	/**
	 * Retourne les fournisseurs associés à un produit
	 *
	 */
	public function getSuppliers()
	{
		$collection = mage::GetModel('Purchase/ProductSupplier')
						->getCollection()
			            ->join('Purchase/Supplier',
					           'sup_id=pps_supplier_num')
						->addFieldToFilter('pps_product_id', $this->getProductId())
						->setOrder('pps_last_order_date', 'desc')
						;
		return $collection;
	}

	
	/**
	 * Retourne la liste des Fournisseurs non liés au produit sous la forme d'un combo
	 *
	 */
	public function getNonLinkedSuppliersAsCombo($name='supplier')
	{
		$collection = mage::GetModel('Purchase/ProductSupplier')
				->getCollection()
				->addFieldToFilter('pps_product_id', $this->_product_id)
				;
		$t_ids = array();
		$t_ids[] = -1;
		foreach ($collection as $item)
		{
			$t_ids[] = $item->getpps_supplier_num();
		}
		
		//Recupere la liste
		$collection = mage::GetModel('Purchase/Supplier')
						->getCollection()
						->addFieldToFilter('sup_id', array('nin' => $t_ids));
		
		//transforme en combo
		$retour = '<select id="'.$name.'" name="'.$name.'">';
		foreach($collection as $item)
		{
			$retour .= '<option value="'.$item->getId().'">'.$item->getsup_name().'</option>';
		}
		$retour .= '</select>';
		
		//retour
		return $retour;
	}
	
			
	/**
	 * Retourne la liste des positionnement de prix sous la forme d'un combo
	 *
	 */
	public function getPricePositionAsCombo($name='price_position')
	{
		
		//transforme en combo
		$retour = '<select id="'.$name.'" name="'.$name.'">';
		$retour .= '<option value="unknown">'.$this->__('Unknown').'</option>';
		$retour .= '<option value="excellent">'.$this->__('Excellent').'</option>';
		$retour .= '<option value="good">'.$this->__('Good').'</option>';
		$retour .= '<option value="average">'.$this->__('Average').'</option>';
		$retour .= '<option value="bad">'.$this->__('Bad').'</option>';
		$retour .= '</select>';
		
		//retour
		return $retour;
	}

			
	/**
	 * Retourne l'objet currency lié à la commande
	 *
	 */
	public function getDefaultCurrency()
	{
		if ($this->_currency == null)
		{
			$this->_currency = mage::getModel('directory/currency')->load(Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE));
		}
		return $this->_currency;
	}
	
}