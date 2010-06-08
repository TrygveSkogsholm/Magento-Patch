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
class MDN_Purchase_Model_Catalog_Product extends Mage_Catalog_Model_Product
{
		
	/**
	 * Retourne la qté disponible du produit, cad celle du produit - la qté réservée
	 *
	 */
	public function GetAvailableQty()
	{
		$reservedQty = $this->getreserved_qty();
		$stock = $this->getStockItem()->getQty();
		return ($stock - $reservedQty);
	}
	
	/**
	 * Retourne la liste des commandes qui contiennent ce produit et qui n'ont pas été livrée
	 *
	 */
	public function GetPendingOrders($asArray = true)
	{	
		//define orders ids to collect
		$OrdersId = array();
		$collection = mage::getModel('sales/order_item')
			->getCollection()
			->addFieldToFilter('product_id', $this->getId())
			->addFieldToFilter('order_id', array('in' => mage::helper('purchase/ConsideredOrders')->getConsideredOrderIds()));
		foreach ($collection as $OrderItem)
		{
			if ($OrderItem->getqty_ordered() - $OrderItem->getRealShippedQty() - $OrderItem->getqty_canceled() - $OrderItem->getqty_refunded() > 0)
				$OrdersId[] = $OrderItem->getorder_id();
		}
		
		//collect orders
		$collection = mage::getModel('sales/order')
			->getCollection()
			->addFieldToFilter('entity_id', array('in'=>$OrdersId))
			->addAttributeToSelect('status')
			->addAttributeToSelect('state')
			->addAttributeToSelect('total_paid')
			->addAttributeToSelect('grand_total')
			->addAttributeToSelect('payment_validated')
			->addAttributeToSelect('customer_firstname')
			->addAttributeToSelect('customer_lastname')
			->addExpressionAttributeToSelect('billing_name',
	            'CONCAT({{customer_firstname}}, " ", {{customer_lastname}}, " ")',
	            array('customer_firstname', 'customer_lastname'))
	        ->setOrder('entity_id', 'asc')
			;
		
		//return datas
		if ($asArray)
		{
			$orders = array();
			foreach ($collection as $order)
				$orders[] = $order;
			return $orders;
		}
		else 
			return $collection;
	}

		
	/**
	 * Méthode vérifiant si on peut réserver une qty X du produit
	 *
	 * @param unknown_type $qty
	 */
	public function CanReserveQty($qty)
	{
		$retour = false;
		
		//si le produit gere les stocks
		if ($this->getStockItem()->getManageStock())
		{
			//si il y a assez de stock
			$stock = $this->getStockItem()->getQty();
			$reservedQty = $this->getreserved_qty();
			if (($stock - $reservedQty) >= $qty)
				$retour = true;
		}
		
		return $retour;
	}

	
	/**
	 * Retourne le taux de taxe achat pour le produit
	 *
	 */
	public function getPurchaseTaxRate()
	{
		//Définit le tax id
		$TaxId = $this->getpurchase_tax_rate();
		if (($TaxId == 0) || ($TaxId == ''))
			$TaxId = mage::getStoreConfig('purchase/purchase_order/products_default_tax_rate');

		//recupere et retourne la valeur
		return mage::getModel('Purchase/TaxRates')->load($TaxId)->getptr_value();
	}
	
	/**
	 * when saving, update supply needs for product (if concerned)
	 *
	 */
    protected function _afterSave()
    {
	    	parent::_afterSave();
	    	
	    	//define in supply_needs may change
	    	$UpdateSupplyNeeds = false;
	    	if ($this->getexclude_from_supply_needs() != $this->getOrigData('exclude_from_supply_needs'))
	    		$UpdateSupplyNeeds = true;
	    	if ($this->getordered_qty() != $this->getOrigData('ordered_qty'))
	    		$UpdateSupplyNeeds = true;
	    	if ($this->getmanual_supply_need_qty() != $this->getOrigData('manual_supply_need_qty'))
	    		$UpdateSupplyNeeds = true;
	    	if ($this->getmanual_supply_need_comments() != $this->getOrigData('manual_supply_need_comments'))
	    		$UpdateSupplyNeeds = true;
 	    	if ($this->getmanual_supply_need_date() != $this->getOrigData('manual_supply_need_date'))
	    		$UpdateSupplyNeeds = true;   	
	    		
	    	//update supply needs
	    	if ($UpdateSupplyNeeds)
	    	{
				$productId = $this->getId();
		    	Mage::dispatchEvent('purchase_update_supply_needs_for_product', array('product_id'=>$productId, 'from' => 'product aftersave'));
	    	}
	    	
	    	//check if we have to update planning for pending orders
	    	$defaultSupplyDelayChanged = ($this->getdefault_supply_delay() != $this->getOrigData('default_supply_delay'));
	    	$supplyDateChanged = ($this->getsupply_date() != $this->getOrigData('supply_date'));
			if ($defaultSupplyDelayChanged || $supplyDateChanged)
				$this->updatePlanningForPendingOrders();
    }
    
    /**
     * Update delivery date for product
     *
     * @param unknown_type $productId
     */
    public function updateProductDeliveryDate($productId)
    {
    	$deliveryDate = null;
    	mage::log('Updating product delivery date for product #'.$productId);
    	
    	//collect PO for product (po status = waiting for delivery and order contains product
    	$collection = mage::getModel('Purchase/Order')
    					->getCollection()
    					->join('Purchase/OrderProduct', 'po_num=pop_order_num')
    					->addFieldToFilter('po_status', MDN_Purchase_Model_Order::STATUS_WAITING_FOR_DELIVERY)
    					->addFieldToFilter('pop_product_id', $productId);
    					
    					
    	//browse colleciton to set date
    	foreach ($collection as $item)
    	{
    		mage::log('--> check order #'.$item->getId().' with supply date = '.$item->getpo_supply_date());
    		if ($item->getpop_qty() > $item->getpop_supplied_qty())
    		{
    			if (($deliveryDate == null) || ($deliveryDate > $item->getpo_supply_date()))
    				$deliveryDate = $item->getpo_supply_date();
    		}
    	}
    	
    	//update product
    	mage::log('--> save date : '.$deliveryDate);
		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
    	$product = mage::getModel('catalog/product')->load($productId);
    	$product->setsupply_date($deliveryDate)->save();
    }
    
    /**
     * Update planning for pending orders
     *
     */
    public function updatePlanningForPendingOrders()
    {
		//collect orders
		$orders = $this->GetPendingOrders();
		foreach($orders as $order)
		{
			//add a task to update order planning
			mage::helper('BackgroundTask')->AddTask('Update planning for order '.$order->getId(), 
							'purchase/Planning',
							'updatePlanning',
							$order->getId()
							);	    
					
		}
    }
}

?>