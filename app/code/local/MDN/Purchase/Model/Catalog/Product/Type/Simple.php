<?php

class MDN_Purchase_Model_Catalog_Product_Type_Simple extends Mage_Catalog_Model_Product_Type_Simple
{

    /**
     * Check is product available for sale
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function isSalable($product = null)
    {
        $salable = $this->getProduct($product)->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        
		//Gere par rapport a la nouvelle gestion de stock
		if ($salable)
		{
			try 
			{
				$Product = $this->getProduct($product);
				//Charge le stock item correspondant au produit (à recoder)
				$StockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($Product->getId());
				//Si gere les stock
				if ($StockItem->getManageStock() == 1)
				{
					
			    	$RealStockQty = $StockItem->getQty() - $Product->getordered_qty();
			    	//Si qte réelle négative et backorder interdites
			    	if (($RealStockQty <=0 ) && ($StockItem->getBackorders() == Mage_CatalogInventory_Model_Stock::BACKORDERS_NO))
			    		$salable = false;
				}				
			}
			catch (Exception $ex)
			{
				//rien
			}
		}
		        
        if ($salable && $this->getProduct($product)->hasData('is_salable')) {
            $salable = $this->getProduct($product)->getData('is_salable');
        }
        elseif ($salable && $this->isComposite()) {
            $salable = null;
        }

        return $salable;
    }
    
}