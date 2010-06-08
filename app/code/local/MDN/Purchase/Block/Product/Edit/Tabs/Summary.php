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
class MDN_Purchase_Block_Product_Edit_Tabs_Summary extends Mage_Adminhtml_Block_Widget_Form
{
	
	private $_product = null;
	private $_supplyNeeds = null;
	
	private $_maxDelay = 90;
	
	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->_blockGroup = 'Purchase';
        $this->_objectId = 'id';
        $this->_controller = 'product';
        
        
		parent::__construct();

	    $this->setTemplate('Purchase/Product/Edit/Tab/Summary.phtml');

		//charge le produit
        $product_id = Mage::app()->getRequest()->getParam('product_id', false);	
        $model = Mage::getModel('catalog/product');
		$this->_product = $model->load($product_id);
		
	}
	
	public function getHeaderText()
    {
          return Mage::helper('purchase')->__('Summary');
    }
	
	/**
	 * Retourne l'url pour retourner a la liste des manufacturers
	 */
	public function GetBackUrl()
	{
		return $this->getUrl('Purchase/Products/List', array());
	}

	
	/**
	 * Retourne l'objet
	 *
	 * @return unknown
	 */
	public function getProduct()
	{
		return $this->_product;
	}
	
	/**
	 * Retourne les stats sur les besoins d'appro pour le produit
	 *
	 */
	public function getSupplyNeeds()
	{
		if ($this->_supplyNeeds == null)
		{
			$this->_supplyNeeds = mage::getModel('Purchase/SupplyNeeds')->getSupplyNeedsForProduct($this->getProduct());
		}
		return $this->_supplyNeeds;
	}
	
	/**
	 * Retourne un combo pour choisir le délai d'appro d'un produit
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 * @return unknown
	 */
	public function getSupplyDelayCombo($name, $value)
	{
		$html = '<select  id="'.$name.'" name="'.$name.'">';		
		for ($i=0;$i<=$this->_maxDelay;$i++)
		{
			$selected = '';
			if ($i == $value)
				$selected = ' selected ';
			$html .= '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
		}
		$html .= '</select>';
		return $html;
	}
	
	/**
	 * Retourne un combo pour définir le taux de tva pour les achats
	 *
	 */
	public function getDefaultPurchaseTaxRateCombo()
	{
		$html = '<select  id="purchase_tax_rate" name="purchase_tax_rate">';
		$value = $this->getProduct()->getpurchase_tax_rate();
		
		//Ajoute le premier élément (default)	
		$html .= '<option value="">'.$this->__('[Default]').'</option>';
		
		//Ajoute les autres taux
		$collection = mage::getModel('Purchase/TaxRates')->getCollection();
		foreach($collection as $item)
		{
			$selected = '';
			if ($item->getId() == $value)
				$selected = ' selected ';
			$html .= '<option value="'.$item->getId().'" '.$selected.'>'.$item->getptr_name().'</option>';
		}
		
		$html .= '</select>';
		return $html;
	}
	
	/**
	 * Return tax rate to use in pricer
	 *
	 */
	public function getPricerTaxRate()
	{
		return Mage::getStoreConfig('purchase/purchase_product/pricer_default_tax_rate');
	}

}
