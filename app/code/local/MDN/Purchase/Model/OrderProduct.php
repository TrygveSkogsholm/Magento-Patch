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
class MDN_Purchase_Model_OrderProduct  extends Mage_Core_Model_Abstract
{
	private $_currency = null;
		
	/*****************************************************************************************************************************
	* ***************************************************************************************************************************
	* Constructeur
	*
	*/
	public function _construct()
	{
		parent::_construct();
		$this->_init('Purchase/OrderProduct');
	}
	    
    /**
     * Retourne le total pour une ligne
     *
     */
    public function getRowTotal()
    {
    	return round(($this->getpop_price_ht() + $this->getpop_eco_tax()) * $this->getpop_qty(), 2);
    }
    	   	    
    /**
     * Retourne le total pour une ligne
     *
     */
    public function getRowTotal_base()
    {
    	return round(($this->getpop_price_ht_base() + $this->getpop_eco_tax_base()) * $this->getpop_qty(), 2);
    }
     
    /**
     * Retourne le total pour une ligne avec les taxes
     *
     */
    public function getRowTotalWithTaxes()
    {
    	$tax_rate = $this->getpop_tax_rate();
    	$value = (($this->getpop_price_ht() + $this->getpop_eco_tax()) * $this->getpop_qty()) * (1 + $tax_rate / 100);
    	$value = round($value, 2);
    	return $value;
    }
    
    /**
     * Retourne le cout réel du produit avec les frais d'approche
     *
     */
    public function getUnitPriceWithExtendedCosts_base()
    {
    	
    	$retour = round($this->getpop_price_ht_base() + $this->getpop_eco_tax_base() + $this->getpop_extended_costs_base(), 2);
    	return $retour;
    }
    	    
    /**
     * Retourne le cout réel du produit avec les frais d'approche
     *
     */
    public function getUnitPriceWithExtendedCosts()
    {
    	$tax_rate = $this->getpop_tax_rate();
    	$retour = round(($this->getpop_price_ht()*(1 + $tax_rate / 100)) + ($this->getpop_extended_costs()/$this->getpop_qty()), 2);
    	return $retour;
    }
    	
	/**
	 * Retourne l'objet currency en euro
	 *
	 */
	public function getEuroCurrency()
	{
		return mage::getModel('directory/currency')->load('EUR');
	}
	
		
	/**
	 * Retourne l'objet currency lié à la commande
	 *
	 */
	public function getCurrency()
	{
		if ($this->_currency == null)
		{
			if ($this->getpo_currency() != '')
				$this->_currency = mage::getModel('directory/currency')->load($this->getpo_currency());
			else 
			{
				$this->_currency = mage::getModel('directory/currency')->load('EUR');
			}
		}		
		return $this->_currency;
	}
	
	/**
	 * Met a jour la qte recu pour ce produit
	 *
	 */
	public function updateDeliveredQty()
	{
		$collection = mage::getModel('Purchase/StockMovement')
			->getCollection()
			->addFieldToFilter('sm_po_num', $this->getpop_order_num())
			->addFieldToFilter('sm_product_id', $this->getpop_product_id());
			
		$sum = 0;
		foreach($collection as $item)
		{
			$sum += $item->getsm_qty();
		}
		$this->setpop_supplied_qty($sum)->save();
	}
}