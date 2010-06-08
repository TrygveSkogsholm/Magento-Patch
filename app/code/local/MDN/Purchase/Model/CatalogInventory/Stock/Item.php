<?php

/**
 * Surchage la classe pour gérer les dispo de produits par rapport aux qte backorder et autorisations de backorder
 *
 */
class MDN_Purchase_Model_CatalogInventory_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item
{
   /**
     * Checking quote item quantity
     *
     * @param mixed $qty quantity of this item (item qty x parent item qty)
     * @param mixed $summaryQty quantity of this product in whole shopping cart which should be checked for stock availability
     * @param mixed $origQty original qty of item (not multiplied on parent item qty)
     * @return Varien_Object
     */
    public function checkQuoteItemQty($qty, $summaryQty, $origQty = 0)
    {
    	//Définit la qte utilisable pour les verifi
    	$Product = mage::getModel('catalog/product')->load($this->getProductId());
    	$RealStockQty = $this->getQty() - $Product->getordered_qty();
    	
        $result = new Varien_Object();
        $result->setHasError(false);

        if (!is_numeric($qty)) {
            $qty = Mage::app()->getLocale()->getNumber($qty);
        }

        /**
         * Check quantity type
         */
        $result->setItemIsQtyDecimal($this->getIsQtyDecimal());

        if (!$this->getIsQtyDecimal()) {
            $result->setHasQtyOptionUpdate(true);
            $qty = intval($qty);

            /**
              * Adding stock data to quote item
              */
            $result->setItemQty($qty);

            if (!is_numeric($qty)) {
                $qty = Mage::app()->getLocale()->getNumber($qty);
            }
            $origQty = intval($origQty);
            $result->setOrigQty($origQty);
        }

        if ($this->getMinSaleQty() && ($qty) < $this->getMinSaleQty()) {
            $result->setHasError(true)
                ->setMessage(Mage::helper('cataloginventory')->__('The minimum quantity allowed for purchase is %s.', $this->getMinSaleQty() * 1))
                ->setQuoteMessage(Mage::helper('cataloginventory')->__('Some of the products cannot be ordered in the requested quantity'))
                ->setQuoteMessageIndex('qty');
            return $result;
        }

        if ($this->getMaxSaleQty() && ($qty) > $this->getMaxSaleQty()) {
            $result->setHasError(true)
                ->setMessage(Mage::helper('cataloginventory')->__('The maximum quantity allowed for purchase is %s.', $this->getMaxSaleQty() * 1))
                ->setQuoteMessage(Mage::helper('cataloginventory')->__('Some of the products can not be ordered in requested quantity'))
                ->setQuoteMessageIndex('qty');
            return $result;
        }

        if (!$this->getManageStock()) {
            return $result;
        }

        if (!$this->getIsInStock()) {
            $result->setHasError(true)
                ->setMessage(Mage::helper('cataloginventory')->__('This product is currently out of stock.'))
                ->setQuoteMessage(Mage::helper('cataloginventory')->__('Some of the products are currently out of stock'))
                ->setQuoteMessageIndex('stock');
            $result->setItemUseOldQty(true);
            return $result;
        }

        if (!$this->checkQty($summaryQty)) {
            $message = Mage::helper('cataloginventory')->__('The requested quantity for "%s" is not available.', $this->getProductName());
            $result->setHasError(true)
                ->setMessage($message)
                ->setQuoteMessage($message)
                ->setQuoteMessageIndex('qty');
            return $result;
        }
        else {
            if (($RealStockQty - $summaryQty) < 0) {
                if ($this->getProductName()) {
                    $backorderQty = ($RealStockQty > 0) ? ($summaryQty - $RealStockQty) * 1 : $qty * 1;
                    if ($backorderQty>$qty) {
                        $backorderQty = $qty;
                    }
                    $result->setItemBackorders($backorderQty);
                    if ($this->getBackorders() == Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY) {
                        $result->setMessage(Mage::helper('cataloginventory')->__('This product is not available in the requested quantity. %d of the items will be backordered.',
                            $backorderQty,
                            $this->getProductName())
                            )
                        ;
                    }
                }
            }
            // no return intentionally
        }

        return $result;
    }
    
    /**
     * Check quantity
     *
     * @param   decimal $qty
     * @exception Mage_Core_Exception
     * @return  bool
     */
    public function checkQty($qty)
    {
    	//Définit la qte utilisable pour les verifi
    	$Product = mage::getModel('catalog/product')->load($this->getProductId());
    	$RealStockQty = $this->getQty() - $Product->getordered_qty();
    	
        if ($RealStockQty - $qty < 0) {
            switch ($this->getBackorders()) {
                case Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY:
                case Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY:
                    break;
                default:
                    /*if ($this->getProduct()) {
                        Mage::throwException(
                            Mage::helper('cataloginventory')->__('The requested quantity for "%s" is not available.', $this->getProduct()->getName())
                        );
                    }
                    else {
                        Mage::throwException(Mage::helper('cataloginventory')->__('The requested quantity is not available.'));
                    }*/
                    return false;
                    break;
            }
        }
        return true;
    }
    
    /**
	 * when saving, update supply needs for product
	 *
	 */
    protected function _afterSave()
    {
	    	parent::_afterSave();
	    	$productId = $this->getProductId();
	    	
	    	//check if stock changed. If so, add a stock movement to 
	    	if ($this->getqty() != $this->getOrigData('qty'))
	    	{
				//get product stock from stock movement to check if it is different
				$stockMovementResult = mage::getModel('Purchase/Productstock')->ComputeProductStock($productId);
				if ($this->getqty() != $stockMovementResult)	
				{
					//add stock movement
					$diff = $this->getqty() - $stockMovementResult;
					$model = mage::getModel('Purchase/StockMovement');
					//Cree le movement
					$model
						->setsm_product_id($productId)
						->setsm_qty($diff)
						->setsm_coef($model->GetTypeCoef('adjustment'))
						->setsm_description('')
						->setsm_type('adjustment')
						->setsm_date(date('Y-m-d'))
						->save();
				}
	    	}
	    	
	    	//check if we have to update supply needs
	    	$updateSupplyNeeds = false;
	    	if ($this->getqty() != $this->getOrigData('qty'))
	    		$updateSupplyNeeds = true;
	    	if ($this->getmin_qty() != $this->getOrigData('min_qty'))
	    		$updateSupplyNeeds = true;
	    	if ($this->getuse_config_min_qty() != $this->getOrigData('use_config_min_qty'))
	    		$updateSupplyNeeds = true;
	    	if ($this->getmanage_stock() != $this->getOrigData('manage_stock'))
	    		$updateSupplyNeeds = true;
	    	if ($this->getuse_config_manage_stock() != $this->getOrigData('use_config_manage_stock'))
	    		$updateSupplyNeeds = true;
	    	if ($this->getnotify_stock_qty() != $this->getOrigData('notify_stock_qty'))
	    		$updateSupplyNeeds = true;
	    	if ($this->getuse_config_notify_stock_qty() != $this->getOrigData('use_config_notify_stock_qty'))
	    		$updateSupplyNeeds = true;
	    			
	    	if ($updateSupplyNeeds)
	    	{
				
		    	Mage::dispatchEvent('purchase_update_supply_needs_for_product', array('product_id'=>$productId, 'from' => 'stockitem aftersave'));
	    	}
    }	
}