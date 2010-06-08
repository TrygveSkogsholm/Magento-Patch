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
class MDN_Purchase_Helper_ProductReservation extends Mage_Core_Helper_Abstract
{
	
	/**
	 * reserve product for orders
	 *
	 * @param unknown_type $productId
	 */
	public function reserveProductForOrders($productId)
	{
		//define if we must reserve product for orders (positive stock movement) or release(negative stock movement)
		$product = mage::getModel('catalog/product')->load($productId);
		$reservedQty = $product->getreserved_qty();
		$stock = $product->getStockItem()->getQty();
		
		//if stock allow to reserve products in other orders
		if ($stock > $reservedQty)
		{
			//collect orders with no reservation
			$orders = $product->GetPendingOrders(false);
			foreach ($orders as $order)
			{
				$this->reserveProductForOrder($order->getId(), $product->getId());
			}
		}
		else 
		{
			//collect all pending orders (sort by date) and unreserve products
			mage::log('Product #'.$productId.' stock is <= reserved qty : no reservation possible');
		}
	}
	
	/**
	 * Retourne la qte reservée
	 *
	 * @param unknown_type $ProductId
	 */
	public function GetReservedQty($ProductId)
	{
		$retour = 0;

		//collect pending orders ids
		$product = mage::getModel('catalog/product')->load($ProductId);
		$orders = $product->GetPendingOrders(false);
		$pendingOrdersIds = array();
		foreach ($orders as $order)
		{
			$pendingOrdersIds[] = $order->getId();
		}
		
		//parse order items to calculte ordered qty
		$orderItems = mage::getModel('sales/order_item')
							->getCollection()
							->addFieldToFilter('order_id', array('in' => $pendingOrdersIds))
							->addFieldToFilter('product_id', $ProductId);
		foreach ($orderItems as $orderItem)
		{
			$retour += $orderItem->getreserved_qty();
		}

		return $retour;
	}
		
	/**
	 * Met a jour la qté réservée
	 *
	 * @param unknown_type $qty
	 */
	public function UpdateReservedQty($product)
	{

        //recupere la qté
        $reservedQty = $this->GetReservedQty($product->getId());

		//check if record exists
        $sql = 'select count(*) from '.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity_int where entity_id = '.$product->getId().' and attribute_id = '.mage::getModel('Purchase/Constant')->GetProductReservedQtyAttributeId();
        $res = mage::getResourceModel('sales/order_item_collection')->getConnection()->fetchOne($sql);
        if ($res == 0)
        {
			//insert record
			$sql = 'insert into '.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity_int (value, entity_id, attribute_id) values ('.$reservedQty.', '.$product->getId().', '.mage::getModel('Purchase/Constant')->GetProductReservedQtyAttributeId().')';
			$res = mage::getResourceModel('sales/order_item_collection')->getConnection()->query($sql);
        }
        else 
        {
			//met a jour pour le produit
			$sql = 'update '.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity_int set value = '.$reservedQty.' where entity_id = '.$product->getId().' and attribute_id = '.mage::getModel('Purchase/Constant')->GetProductReservedQtyAttributeId();
			mage::getResourceModel('sales/order_item_collection')->getConnection()->query($sql);
        }

		return ;
	}
	
	/**
	 * Release (unreserve) product for an order
	 *
	 * @param unknown_type $orderId
	 * @param unknown_type $productId
	 */
	public function releaseProductForOrder($orderId, $productId)
	{
		$product = mage::getModel('catalog/product')->load($productId);
		$order = mage::getModel('sales/order')->load($orderId);
		if ($order->getId())
		{
			foreach($order->getAllItems() as $item)
			{
				if ($item->getproduct_id() == $productId)
					$item->setreserved_qty(0)->save();
			}
		}
		
		//update reserved qty for product
		$this->UpdateReservedQty($product);
		
		//dispatch order in order preparation tabs
		mage::helper('BackgroundTask')->AddTask('Dispatch order #'.$order->getId(), 
								'Orderpreparation',
								'dispatchOrder',
								$order->getId()
								);	
	}
	
	/**
	 * Reserve product for order
	 *
	 * @param unknown_type $orderId
	 * @param unknown_type $productId
	 */
	public function reserveProductForOrder($orderId, $productId)
	{
		$product = mage::getModel('catalog/product')->load($productId);
		$order = mage::getModel('sales/order')->load($orderId);
		$qtyMax = $product->GetAvailableQty();
		if ($order->getId())
		{
			foreach($order->getAllItems() as $item)
			{
				if ($item->getproduct_id() == $productId)
				{
					//init vars
					$qtyToShip = $item->getRemainToShipQty();
					$qtyReserved = $item->getreserved_qty();
					$qtyRemainingToReserve = $qtyToShip - $qtyReserved;
					
					//if product is not enterely reserved
					if ($qtyRemainingToReserve > 0)
					{
						$qtyToReserveInAddition = $qtyRemainingToReserve;
						if ($qtyToReserveInAddition > $qtyMax)
							$qtyToReserveInAddition = $qtyMax;
						if ($qtyToReserveInAddition > 0)
						{
							$item->setreserved_qty($item->getreserved_qty() + $qtyToReserveInAddition)->save();
						}
						$qtyMax -= $qtyToReserveInAddition;
					}
				}
			}
		}
		
		//update reserved qty for product
		$this->UpdateReservedQty($product);
		
		//dispatch order in order preparation tabs
		mage::helper('BackgroundTask')->AddTask('Dispatch order #'.$order->getId(), 
								'Orderpreparation',
								'dispatchOrder',
								$order->getId()
								);	
	}
}