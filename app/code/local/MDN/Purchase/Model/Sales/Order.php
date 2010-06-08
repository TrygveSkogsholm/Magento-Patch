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
* surcharge la classe order
*/
class MDN_Purchase_Model_Sales_Order extends Mage_Sales_Model_Order
{
    
    /**
     * Met a jour les qte commandées des produits lorsque la commande est passée
     *
     */
    public function UpdateProductsOrdererQty()
    {
    	
	    //stock la valeur eco taxe de chaque élément de la commande
        foreach($this->getAllItems() as $item)
        {
        	//recupere le produit correspondant
        	$product = mage::getModel('catalog/product')->load($item->getproduct_id());
        	
        	//met a jour les qte
        	$model = mage::getModel('Purchase/Productstock');
        	$model->UpdateOrderedQty($product);
        	mage::helper('purchase/ProductReservation')->UpdateReservedQty($product);
        }   	
	        
    }
    
   
    /**
     * Retourne la marge pour la commande
     *
     */
    public function getMargin()
    {
    	$retour = 0;
    	foreach ($this->getAllVisibleItems() as $item)
    	{
    		$retour += $item->getMargin();
    	}
    	return $retour;
    }
    
    /**
     * Retourne la marge en %
     *
     */
    public function getMarginPercent()
    {
    	if ($this->getsubtotal() > 0)
	    	return ($this->getMargin()) / $this->getsubtotal() * 100;
	    else 
	    	return 0;
    }

	//Permet de savoir si tous les produits sont en stock    
    public function IsFullStock()
    {
    	foreach ($this->getItemsCollection() as $item)
    	{
    		$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getproduct_id());
    		if ($stockItem)
    		{
				if ($stockItem->getManageStock())
				{
		    		//si produit pas réservé
		    		$remaining_qty = $item->getRemainToShipQty();
		    		if (($item->getreserved_qty() < $remaining_qty) && ($remaining_qty > 0))
		    		{
		    			return false;
		    		}
				}
    		}
    	}
		return true;
    }
    
        
    /**
     * Define if all items are shipped
     *
     */
    public function IsCompletelyShipped()
    {
    	//recupere la liste des produits de la commande
    	foreach ($this->getItemsCollection() as $item)
    	{
    		if ($item->getRemainToShipQty() > 0)
    			return false;
    	}
    	
    	return true;
    }
    
        
    /**
     * Return true if order is considered
     */
    public function IsValidForPurchaseProcess()
    {
    	return mage::helper('purchase/ConsideredOrders')->orderIsConsidered($this);
    }
    
    /**
     * Override cancel method to dispatch order
     *
     * @return unknown
     */
    public function cancel()
    {
    	parent::cancel();
    	
    	//dispatch order in order preparation tab
		mage::helper('BackgroundTask')->AddTask('Dispatch order #'.$this->getId(), 
								'Orderpreparation',
								'dispatchOrder',
								$this->getId()
								);	
		return $this;
    }
    
    /**
     * Return planning for order
     *
     */
    public function getPlanning()
    {    	
    	$planning = mage::getModel('Purchase/SalesOrderPlanning')->load($this->getId(), 'psop_order_id');
    	//if object does not exists, create it
    	if (!$planning->getId())
    	{
    		$planning = mage::helper('purchase/Planning')->createPlanning($this);   		
    		$planning->save();
    	}
    	
    	return $planning;
    
    }
    
    /**
     * Return true if all products are reserved
     *
     */
    public function allProductsAreReserved()
    {
    	foreach ($this->getItemsCollection() as $item)
    	{
    		$product = mage::getModel('catalog/product')->load($item->getproduct_id());
    		$manageStock = true;
    		if ($product->getId())
				$manageStock = $product->getStockItem()->getManageStock();    
    		if ($manageStock)
    		{
	    		$remaining_qty = $item->getRemainToShipQty() - $item->getreserved_qty();
	    		if ($remaining_qty > 0)
		    		return false;
    		}
    	}
    	
    	return true;
    }
    
    /**
     * Dispatch order or reset planning when criterias value change
     *
     */
    protected function _beforeSave()
    {
    	parent::_beforeSave();
    	
    	$paymentValidatedChange = ($this->getpayment_validated() != $this->getOrigData('payment_validated'));
    	$statusChange = ($this->getstate() != $this->getOrigData('state'));
    	$totalDueChange = ($this->gettotal_due() != $this->getOrigData('total_due'));
    	
    	//update planning and dispatch order
    	if ($paymentValidatedChange || $statusChange || $totalDueChange)
    	{
    		//do not update planning & dispatch order if order is being inserted (those operation are planed by the cron)
    		if ($this->getId())
    		{
				mage::helper('BackgroundTask')->AddTask('Dispatch order #'.$this->getId(), 
										'Orderpreparation',
										'dispatchOrder',
										$this->getId()
										);	
										
				//add a task to update order planning
				mage::helper('BackgroundTask')->AddTask('Update planning for order '.$this->getId(), 
								'purchase/Planning',
								'updatePlanning',
								$this->getId()
								);	  
    		}
    	}
    }
}

?>