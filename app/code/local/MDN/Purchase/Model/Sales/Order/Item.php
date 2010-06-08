<?php

/**
 * Order Item Model
 *
 * @category    Mage
 * @package     Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MDN_Purchase_Model_Sales_Order_Item extends Mage_Sales_Model_Order_Item
{

    /**
     * Retourne la marge pour cette ligne commande
     *
     */
    public function GetMargin()
    {
	    //Calcul la marge
	    $retour = 0;
   		$retour = ($this->getPrice() * $this->getqty_ordered()) - ($this->getData(mage::helper('purchase/MagentoVersionCompatibility')->getSalesOrderItemCostColumnName()) * $this->getqty_ordered());
    		
    	return $retour;
    }
    
    /**
     * Retourne la marge en %
     *
     */
    public function GetMarginPercent()
    {
    	if ($this->getPrice() > 0)
		    return ($this->getPrice() - $this->getData(mage::helper('purchase/MagentoVersionCompatibility')->getSalesOrderItemCostColumnName())) / $this->getPrice() * 100;
		else 
			return 0;
    }
    
    /**
	 * when saving, update supply needs for product (if concerned)
	 *
	 */
    protected function _afterSave()
    {
	    parent::_afterSave();
	    
	    //If reserved qty change, update order planning
    	$reservedQtyChanged = ($this->getreserved_qty() != $this->getOrigData('reserved_qty'));
    	$qtyToShip = $this->getqty_ordered() - $this->getRealShippedQty() - $this->getqty_canceled() - $this->getqty_refunded();
		if (($reservedQtyChanged) && ($qtyToShip > 0))
		{
			//add a task to update order planning
			$orderId = $this->getorder_id();
			mage::helper('BackgroundTask')->AddTask('Update planning for order '.$orderId, 
							'purchase/Planning',
							'updatePlanning',
							$orderId
							);							
		}
	    
	    return $this;
    }
    
    /**
     * return real qty shipped (multiply with parent item)
     *
     */
    public function getRealShippedQty()
    {
    	$qty = 0;
    	
    	//if no parent
    	if ($this->getparent_item_id() == null)
    	{
	    	$qty = $this->getqty_shipped();		
    	}
    	else 
    	{
			//if has parent
			$parentItem = mage::getModel('sales/order_item')->load($this->getparent_item_id());
			if ($parentItem->isShipSeparately())
			{
				$qty = $this->getqty_shipped();		
			}
			else 
			{
				$qty = $parentItem->getqty_shipped() * ($this->getqty_ordered() / $parentItem->getqty_ordered());
			}
    	}
    	
    	return $qty;
    }
    
    /**
     * Return qty remaining to ship
     *
     */
    public function getRemainToShipQty()
    {
    	$retour = 0;
    	
    	//if no parent
    	if ($this->getparent_item_id() == null)
    	{
    		switch($this->getproduct_type())
    		{
    			case null:
    			case 'simple':
    			case 'configurable':
		    		$retour = $this->getqty_ordered() - $this->getqty_shipped() - $this->getqty_refunded() - $this->getqty_canceled();
    				break;			
    			case 'bundle':
		    		if ($this->isShipSeparately())
		    			$retour = 0;
		    		else 
						$retour = $this->getqty_ordered() - $this->getqty_shipped() - $this->getqty_refunded() - $this->getqty_canceled();		    			
    				break;
    		}
    	}
		else 
		{
			//if has parent
			$parentItem = mage::getModel('sales/order_item')->load($this->getparent_item_id());
			if ($parentItem->isShipSeparately())
			{
				$retour = $this->getqty_ordered() - $this->getqty_shipped() - $this->getqty_refunded() - $this->getqty_canceled();				
			}
			else 
			{
				$retour = $parentItem->getqty_ordered() - $parentItem->getqty_shipped() - $parentItem->getqty_refunded() - $parentItem->getqty_canceled();		    												
				$retour *= ($this->getqty_ordered() / $parentItem->getqty_ordered());
			}
		}
    	
    	if ($retour < 0)
    		$retour = 0;

    	return $retour;
    }

}