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
class MDN_Purchase_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function UpdateProductStock($ProductId)
	{
		Mage::GetModel('Purchase/StockMovement')->ComputeProductStock($ProductId);
		
	}
	
	public function UpdateProductsDeliveryDate($orderId)
	{
		$order = mage::getModel('Purchase/Order')->load($orderId);
		$order->UpdateProductsDeliveryDate();
	}
	
	public function updateSupplyNeedsForProduct($productId)
	{
		$model = mage::getModel('Purchase/SupplyNeeds');
		$model->refreshSupplyNeedsForProduct($productId);
	}
	
	public function updateProductDeliveryDate($productId)
	{
		$model = mage::getModel('catalog/product');
		$model->updateProductDeliveryDate($productId);
	}
	
	/**
	 * Set stocks updated to 1 for canceled orders
	 *
	 */
	public function cleanCanceledOrders()
	{
		$collection = mage::getModel('sales/order')
						->getCollection()
						->addFieldToFilter('stocks_updated', 0)
						->addAttributeToFilter('status', 'canceled');
						
		foreach ($collection as $order)
		{
			$order->setstocks_updated(1)->save();
		}
	}
	
}

?>