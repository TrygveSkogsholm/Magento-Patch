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
class MDN_Purchase_Helper_ConsideredOrders extends Mage_Core_Helper_Abstract
{
	/**
	 * Return sql direct condition 
	 *
	 */
	public function getSqlConditions()
	{
		$retour = '';
		
		//Status conditions
		$t_status = explode(',', mage::getStoreConfig('purchase/considered_orders/managed_order_status'));
		$isFirst = true;
		if (count($t_status) > 0)
		{
			$retour .= "(";
			foreach($t_status as $status)
			{
				if (!$isFirst)
					$retour .= " or ";
				$retour .= "".mage::getModel('Purchase/Constant')->getTablePrefix()."sales_order_varchar.value = '".$status."'";	
				$isFirst = false;
			}
			$retour .= ")";
		}
		
		//Total due condition
		if (mage::getStoreConfig('purchase/considered_orders/require_totaldue_equal_zero') == 1)
		{
			if ($retour != '')
				$retour .= ' and ';				
			$retour .= '('.mage::getModel('Purchase/Constant')->getTablePrefix().'sales_order.grand_total - '.mage::getModel('Purchase/Constant')->getTablePrefix().'sales_order.total_paid)  <= 0 ';
		}
		
		//Payment validated condition
		if (mage::getStoreConfig('purchase/considered_orders/require_payment_validated_flag') == 1)
		{
			if ($retour != '')
				$retour .= ' and ';
			$retour .= ' '.mage::getModel('Purchase/Constant')->getTablePrefix().'sales_order_int.value = 1 ';
		}
		
		if ($retour == '')
			$retour = '(1=1)';
					
		return $retour;
	}
	
	/**
	 * Add conditions to an order collection
	 *
	 * @param unknown_type $collection
	 * @return unknown
	 */
	public function addConditionToCollection($collection)
	{
		
		//Status conditions
		$t_status = explode(',', mage::getStoreConfig('purchase/considered_orders/managed_order_status'));
		if (count($t_status) > 0)
			$collection->addAttributeToFilter('status', array('in' => $t_status));
		
		//Total due condition
		if (mage::getStoreConfig('purchase/considered_orders/require_totaldue_equal_zero') == 1)
			$collection->addAttributeToFilter('total_paid', array('gt' => 0));	
		
		//Payment validated condition
		if (mage::getStoreConfig('purchase/considered_orders/require_payment_validated_flag') == 1)
			$collection->addAttributeToFilter('payment_validated', 1);		
		
		return $collection;
	}
	
	/**
	 * Return an array with considered orders Ids
	 *
	 */
	public function getConsideredOrderIds()
	{
		//set collection
		$collection = mage::getModel('sales/order')->getCollection();
		$collection = $this->addConditionToCollection($collection);

		$retour = array();
		foreach ($collection as $order)
			$retour[] = $order->getId();
		
		return $retour;
	}
	
	/**
	 * Check if an order is considered
	 *
	 * @param unknown_type $order
	 */
	public function orderIsConsidered($order)
	{
    	$retour = true;
    
    	//status	
		$AllowedStates = mage::getStoreConfig('purchase/considered_orders/managed_order_status');
		$pos = strpos($AllowedStates, $order->getState());
		if ($pos === false)
			return false;
	
		//total due
		if (mage::getStoreConfig('purchase/considered_orders/require_totaldue_equal_zero') == 1)
		{
			if ($order->getTotalDue() > 0)
				return false;
		}
		
		//payment validated
		if (mage::getStoreConfig('purchase/considered_orders/require_payment_validated_flag') == 1)
		{
			if ($order->getpayment_validated() == 0)
				return false;
		}
		
    	return $retour;
	}
}