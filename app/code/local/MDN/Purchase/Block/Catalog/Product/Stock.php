<?php

/**
 * Block pour afficher sur le front les infos de disponibilité du produit
 *
 */
class MDN_Purchase_Block_Catalog_Product_Stock extends Mage_Core_Block_Template
{
	private $_product = null;
		
	
	/**
	 * Retourne le produit courant
	 */
	public function getProduct()
	{
		//Charge le produit si il ne l'est pas
		if ($this->_product == null)
		{
			$this->_product = mage::registry('current_product');
		}
		return $this->_product;
	}
	
	/**
	 * Retourne la dispo de maniere textuelle
	 *
	 */
	public function getStockAvailability()
	{
		
		if ($this->isInStock())
			return $this->__('In stock');
		else 
			return $this->__('Out of stock');
	}
	
	/**
	 * Retourne vrai si un produit est dispo
	 *
	 */
	public function isInStock()
	{
		try 
		{
			$Stock = $this->getProduct()->getStockItem()->getQty();
			$OrderedQty = $this->getProduct()->getordered_qty();
			
			if (($Stock - $OrderedQty ) > 0)
				return true;
			else 
				return false;			
		}
		catch (Exception $ex)
		{
			return false;
		}
	}
	
	/**
	 * Retourne la prochaine date d'appro (ou null si inexistante)
	 *
	 */
	public function getSupplyDate()
	{
		try 
		{
			$date = strtotime($this->getProduct()->getsupply_date());
			if ($date > time())
			{
				$date = $this->formatDate($this->getProduct()->getsupply_date(), 'long');
				return $date;
			}
			else 
				return null;			
		}
		catch (Exception $ex)
		{
			return null;
		}
	}
	
	/**
	 * Retourne le délai moyen d'appro
	 *
	 */
	public function getAverageSupplyDelay()
	{
		return $this->getProduct()->getdefault_supply_delay();
	}
	
	/**
	 * Return availability message
	 *
	 */
	public function getAvailabilityMessage()
	{
		$storeId = mage::app()->getStore()->getCode();
		return mage::helper('purchase/ProductAvailability')->getLabel($storeId, $this->getAverageSupplyDelay());
	}
    
}
