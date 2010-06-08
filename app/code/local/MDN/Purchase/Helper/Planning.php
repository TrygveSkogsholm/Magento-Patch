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
class MDN_Purchase_Helper_Planning extends Mage_Core_Helper_Abstract
{
	/**
	 * Helper to create planning for an order
	 *
	 * @param unknown_type $order
	 */
	public function createPlanning($order)
	{
		//delete planning if exists (as we create it :))
		$planning = mage::getModel('Purchase/SalesOrderPlanning')->load($order->getId(), 'psop_order_id');
		if ($planning->getId())
			$planning->delete();
		
		//create & init information
		$planning = mage::getModel('Purchase/SalesOrderPlanning');
		$planning->setpsop_order_id($order->getId());
		$planning->setConsiderationInformation($order);
		$planning->setFullStockInformation($order);
		$planning->setShippingInformation($order);
		$planning->setDeliveryInformation($order);
		return $planning;
	}
	
		
	/**
	 * Return estimated delivery date for a quote
	 * return planning object
	 * @param unknown_type $quote
	 */
	public function getEstimationForQuote($quote)
	{
		//calculate planning
		$planning = mage::getModel('Purchase/SalesOrderPlanning');
		$planning->setConsiderationInformation($quote, true);
		$planning->setFullStockInformation($quote, true);
		$planning->setShippingInformation($quote, true);
		$planning->setDeliveryInformation($quote, true);

		$planning->setpsop_anounced_date($planning->getpsop_delivery_date());
		$planning->setpsop_anounced_date_max($planning->getpsop_delivery_date_max());
		
		return $planning;
	}

	/**
	 * Update planning (method to use when information for the order changes (product reservation, payment, expedition ...)
	 *
	 * @param unknown_type $orderId
	 */
	public function updatePlanning($orderId)
	{
		mage::log('##Start update planning for order #'.$orderId);
		$order = mage::getModel('sales/order')->load($orderId);
		if ($order->getId())
		{
			$planning = $order->getPlanning();
	
			if ($planning->getConsiderationDate() == null)
			{
				mage::log('Set condideration date');
				$planning->setConsiderationInformation($order);			
			}
				
			$planning->setFullStockInformation($order);
			$planning->setShippingInformation($order);
			$planning->setDeliveryInformation($order);
			$planning->save();
		}
		else 
			mage::log('Unable to load order #'.$orderId);
		mage::log('##End update planning for order #'.$orderId);
	}
	
}