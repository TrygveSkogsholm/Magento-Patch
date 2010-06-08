<?php

class MDN_Purchase_Block_Catalog_Product_View_Type_Configurable extends Mage_Catalog_Block_Product_View_Type_Configurable
{
	
	/**
	 * Overwrite getjsonconfig to add stock status information for each sub product (to display dynamic stock status)
	 *
	 * @return unknown
	 */
	public function getJsonConfig()
    {
        $attributes = array();
        $options = array();
        $subProductsAvailability = array();
        $store = Mage::app()->getStore();
        foreach ($this->getAllowProducts() as $product) {
            $productId  = $product->getId();
			$product = mage::getModel('catalog/product')->load($productId);
            
            //add sub product availability information
            $subProductInfo = array();
            $subProductInfo['id'] = $productId;
            $subProductInfo['in_stock'] = $this->isInStock($product);
            $subProductInfo['stock'] = $product->getStockItem()->getQty();
            $subProductInfo['ordered_qty'] = $product->getordered_qty();
			$subProductInfo['supply_date'] = $this->getSupplyDate($product);         
			$subProductInfo['supply_delay'] = $product->getdefault_supply_delay();    
			$subProductInfo['availability_timestamp'] = $this->getTimestamp($product);    
			$subProductInfo['description'] = $this->getDescription($product);    
			$subProductInfo['availability'] = $this->getAvailability($product);    
            $subProductsAvailability[] = $subProductInfo;
            
            foreach ($this->getAllowAttributes() as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());
                if (!isset($options[$productAttribute->getId()])) {
                    $options[$productAttribute->getId()] = array();
                }

                if (!isset($options[$productAttribute->getId()][$attributeValue])) {
                    $options[$productAttribute->getId()][$attributeValue] = array();
                }
                $options[$productAttribute->getId()][$attributeValue][] = $productId;
            }
        }

        $this->_resPrices = array(
            $this->_preparePrice($this->getProduct()->getFinalPrice())
        );

        foreach ($this->getAllowAttributes() as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            $attributeId = $productAttribute->getId();
            $info = array(
               'id'        => $productAttribute->getId(),
               'code'      => $productAttribute->getAttributeCode(),
               'label'     => $attribute->getLabel(),
               'options'   => array()
            );

            $optionPrices = array();
            $prices = $attribute->getPrices();
            if (is_array($prices)) {
                foreach ($prices as $value) {
                    if(!$this->_validateAttributeValue($attributeId, $value, $options)) {
                        continue;
                    }

                    $info['options'][] = array(
                        'id'    => $value['value_index'],
                        'label' => $value['label'],
                        'price' => $this->_preparePrice($value['pricing_value'], $value['is_percent']),
                        'products'   => isset($options[$attributeId][$value['value_index']]) ? $options[$attributeId][$value['value_index']] : array(),
                    );
                    $optionPrices[] = $this->_preparePrice($value['pricing_value'], $value['is_percent']);
                    //$this->_registerAdditionalJsPrice($value['pricing_value'], $value['is_percent']);
                }
            }
            /**
             * Prepare formated values for options choose
             */
            foreach ($optionPrices as $optionPrice) {
                foreach ($optionPrices as $additional) {
                    $this->_preparePrice(abs($additional-$optionPrice));
                }
            }
            if($this->_validateAttributeInfo($info)) {
               $attributes[$attributeId] = $info;
            }
        }
        /*echo '<pre>';
        print_r($this->_prices);
        echo '</pre>';die();*/

        $_request = Mage::getSingleton('tax/calculation')->getRateRequest(false, false, false);
        $_request->setProductClassId($this->getProduct()->getTaxClassId());
        $defaultTax = Mage::getSingleton('tax/calculation')->getRate($_request);

        $_request = Mage::getSingleton('tax/calculation')->getRateRequest();
        $_request->setProductClassId($this->getProduct()->getTaxClassId());
        $currentTax = Mage::getSingleton('tax/calculation')->getRate($_request);

        $taxConfig = array(
            'includeTax'        => Mage::helper('tax')->priceIncludesTax(),
            'showIncludeTax'    => Mage::helper('tax')->displayPriceIncludingTax(),
            'showBothPrices'    => Mage::helper('tax')->displayBothPrices(),
            'defaultTax'        => $defaultTax,
            'currentTax'        => $currentTax,
            'inclTaxTitle'      => Mage::helper('catalog')->__('Incl. Tax'),
        );

        $config = array(
            'attributes'        => $attributes,
            'template'          => str_replace('%s', '#{price}', $store->getCurrentCurrency()->getOutputFormat()),
//            'prices'          => $this->_prices,
            'basePrice'         => $this->_registerJsPrice($this->_convertPrice($this->getProduct()->getFinalPrice())),
            'oldPrice'          => $this->_registerJsPrice($this->_convertPrice($this->getProduct()->getPrice())),
            'productId'         => $this->getProduct()->getId(),
            'chooseText'        => Mage::helper('catalog')->__('Choose option...'),
            'taxConfig'         => $taxConfig,
            'subProductsAvailability'		=> $subProductsAvailability
        );

        return Zend_Json::encode($config);
    }	
    
    	
	/**
	 * Retourne vrai si un produit est dispo
	 *
	 */
	public function isInStock($product)
	{
		try 
		{
			//echo '<br>-->'.$product->getId().' stock='.$Stock.' ordered='.$OrderedQty;
			$Stock = $product->getStockItem()->getQty();
			$OrderedQty = $product->getordered_qty();
			
			if (($Stock - $OrderedQty ) > 0)
				return true;
			else 
				return false;			
		}
		catch (Exception $ex)
		{
			mage::log('Error inside isInStock method : '.$ex->getMessage());
			return false;
		}
	}
	
		
	/**
	 * Retourne la prochaine date d'appro (ou null si inexistante)
	 *
	 */
	public function getSupplyDate($product)
	{
		try 
		{
			$date = strtotime($product->getsupply_date());
			if ($date > time())
			{
				$date = $this->formatDate($product->getsupply_date(), 'long');
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
	 * Return timestamp matching to date from which product must be available
	 */
	public function getTimestamp($product)
	{
		$retour = null;
		
		if (!$this->isInStock($product))
		{
			$supplyDate = $this->getSupplyDate($product);
			$supplyDelay = $product->getdefault_supply_delay();
			
			if ($supplyDate != null)
				$retour = strtotime($supplyDate);
			else 
				$retour = time() + $supplyDelay * 3600 * 24;
		}
		else 
			$retour = time();
		
		return $retour;
	}
	
	/**
	 * Show availability description
	 *
	 * @param unknown_type $product
	 */
	public function getDescription($product)
	{
		$retour = '';
		
		if (!$this->isInStock($product))
		{
			if ($this->getSupplyDate($product) != null)
				$retour = $this->__('Supply planed on ').$this->getSupplyDate($product);
			else 
			{
				$storeId = mage::app()->getStore()->getCode();
				$retour = mage::helper('purchase/ProductAvailability')->getLabel($storeId, $product->getdefault_supply_delay());
			}
		}
		
		return $retour;
	}
	
		
	/**
	 * Retourne la dispo de maniere textuelle
	 *
	 */
	public function getAvailability($product)
	{
		
		if ($this->isInStock($product))
			return $this->__('In stock');
		else 
			return $this->__('Out of stock');
	}
}