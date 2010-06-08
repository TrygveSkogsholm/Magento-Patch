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
class MDN_Purchase_Model_SalesOrderPlanning  extends Mage_Core_Model_Abstract
{
	
	/**
	 * Constructor
	 *
	 */
	public function _construct()
	{
		parent::_construct();
		$this->_init('Purchase/SalesOrderPlanning');
	}
		
	/**
	 * return consideration date
	 *
	 */
	public function getConsiderationDate()
	{
		$retour = $this->getpsop_consideration_date();
		if ($this->getpsop_consideration_date_force() != '')
			$retour = $this->getpsop_consideration_date_force();		
		return $retour;
	}

	/**
	 * return fullstock date
	 *
	 */
	public function getFullstockDate()
	{
		$retour = $this->getpsop_fullstock_date();
		if ($this->getpsop_fullstock_date_force() != '')
			$retour = $this->getpsop_fullstock_date_force();		
		return $retour;
	}

	/**
	 * return shipping date
	 *
	 */
	public function getShippingDate()
	{
		$retour = $this->getpsop_shipping_date();
		if ($this->getpsop_shipping_date_force() != '')
			$retour = $this->getpsop_shipping_date_force();		
		return $retour;
	}

	/**
	 * return shipping date
	 *
	 */
	public function getDeliveryDate()
	{
		$retour = $this->getpsop_delivery_date();
		if ($this->getpsop_delivery_date_force() != '')
			$retour = $this->getpsop_delivery_date_force();		
		return $retour;
	}
	
	/*****************************************************************************************************************************
	******************************************************************************************************************************
	****************** Fill Sections *****************************************************************************************************
	******************************************************************************************************************************
	******************************************************************************************************************************/
	
	/**
	 * Define consideration information depending of order and parameters
	 *
	 */
	public function setConsiderationInformation($order, $quoteMode = false)
	{
		
		$considerationDateTimeStamp = null;
		$considerationComments = '';
		if (!$quoteMode)
			$orderRealDatetime = $order->getCreatedAt();
		else 
			$orderRealDatetime = date('Y-m-d H:i:s');
		mage::log('Order real datatime = '.$orderRealDatetime);
			
		if (!$quoteMode)
		{
			
			//init consider date when order placed
			if (Mage::getStoreConfig('planning/consider/consider_order_when_placed') == 1)
			{
				$considerationDateTimeStamp = strtotime($orderRealDatetime);			
				$considerationComments = mage::helper('purchase')->__('Order placed on '.$orderRealDatetime).'<br>';
				
				//if order placed after specifi hour, add one day
				$maxHour = Mage::getStoreConfig('planning/consider/consider_order_tomorow_if_placed_after');
				if (date('G', $considerationDateTimeStamp) > $maxHour)
				{
					$considerationDateTimeStamp += 3600 * 24;
					$considerationComments .= 'add 1 day as order placed after '.$maxHour.'h<br>';
				}
			}
	
			//init order information when order invoiced
			if (Mage::getStoreConfig('planning/consider/consider_order_when_invoiced') == 1)
			{
				$invoiceDate = $this->getOrderInvoicedDate($order);
				
				if ($invoiceDate != null)
				{
					$considerationDateTimeStamp = strtotime($invoiceDate);			
					$considerationComments = 'Order invoiced on '. $invoiceDate.'<br>';
				}
				else 
					$considerationComments = 'Order not invoiced<br>';
			}
			
			//init order information when payment_validated
			if (Mage::getStoreConfig('planning/consider/consider_order_on_paypment_validated') == 1)
			{
				if ($order->getpayment_validated() == 1)
				{
					$considerationDateTimeStamp = time();			
					$considerationComments = mage::helper('purchase')->__('Payment validated on '.date('Y-m-d')).'<br>';
					
					//if order placed after specifi hour, add one day
					$maxHour = Mage::getStoreConfig('planning/consider/consider_order_tomorow_if_placed_after');
					if (date('G', $considerationDateTimeStamp) > $maxHour)
					{
						$considerationDateTimeStamp += 3600 * 24;
						$considerationComments .= 'add 1 day as order placed after '.$maxHour.'h<br>';
					}
				}
			}
		}
		else //if quote mode 
		{		
			$considerationDateTimeStamp = strtotime($orderRealDatetime);		
			$considerationComments .= 'Quote for today<br>';
			
			//add days depending of payment method
			$method = $order->getPayment()->getMethod();	
			if ($method != null)
			{
				$delay = $this->getPaymentDelay($method);
				if ($delay > 0)
				{
					$considerationDateTimeStamp += 3600 * 24 * $delay;
					$considerationComments .= 'Add '.$delay.' days for payment method ('.$method.') -> '.date('Y-m-d', $considerationDateTimeStamp).'<br>';
				}
			}
			else 
				$considerationComments .= 'No payment method<br>';
		}
		
		//add days to avoid holy day
		if (Mage::getStoreConfig('planning/consider/include_holy_days') == 0)
		{
			$daysToAdd = $this->DaysUntilNotHolyDay($considerationDateTimeStamp);
			if ($daysToAdd > 0)
			{
				$considerationDateTimeStamp += 3600 * 24 * $daysToAdd;
				$considerationComments .= 'add '.$daysToAdd.' days to avoid holy day<br>';
			}
		}
		
		//set consideration informaiton
		if ($considerationDateTimeStamp != null)
		{
			$this->setpsop_consideration_date(date('Y-m-d', $considerationDateTimeStamp));
			$this->setpsop_consideration_comments($considerationComments);
		}
		else 
		{
			mage::log('Consideration date is null');
			$this->setpsop_consideration_date(null);
			$this->setpsop_consideration_comments($considerationComments);			
		}

		return $this;
	}
	
	/**
	 * Set full stock information
	 *
	 * @param unknown_type $order
	 */
	public function setFullStockInformation($order, $quoteMode = false)
	{
		$considerationDate = $this->getConsiderationDate();
		if ($considerationDate == null)
		{
			$this->setpsop_fullstock_date(null);
			$this->setpsop_fullstock_date_max(null);
			$this->setpsop_fullstock_comments('');
			return $this;
		}
		
		//init vars
		$beginingDate = strtotime($considerationDate);
		$allProductReserved = true;
		$worstSupplyDate = $beginingDate;
		$ProductInformation = '';
		$ParentProductsWhichOverridePlanning = array();
		
		//browse products
		foreach($order->getItemsCollection() as $item)
		{
			$productId = $item->getproduct_id();
			if (!$quoteMode)
			{
				$remaining_qty = $item->getRemainToShipQty();
				$reservedQty = $item->getreserved_qty();
				$missingQty = $remaining_qty - $reservedQty;
			}
			else 
			{	
				$remaining_qty = $item->getQty();
				$parentItem = $item->getParentItem();
				if ($parentItem)
					$remaining_qty = $item->getQty() * $parentItem->getQty();
				$reservedQty = 0;
				$missingQty = $remaining_qty;
			}
			
			if ($remaining_qty > 0)
			{
				if ($reservedQty >= $remaining_qty)
					$ProductInformation .= $item->getName().' reserved ('.$reservedQty.')<br>';
				else
				{
					//remains product to reserve
					$product = mage::getModel('catalog/product')->load($productId);
					$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);

			
					//Check if product overrides subproducts qty
					if ($product->getoverride_subproducts_planning() == 1)
					{
						$ProductInformation .= $item->getName().' overrides childs ('.$product->getdefault_supply_delay().' days)<br>';
						$ParentProductsWhichOverridePlanning[$productId] = $product->getdefault_supply_delay();
					}
						
					//if product manage stock
					if ($stockItem->getManageStock())
					{
						$allProductReserved = false;
						
						//if we can reserve qty
						if ($product->CanReserveQty($remaining_qty))
							$ProductInformation .= $item->getName().' '.$remaining_qty.'x : in stock<br>';
						else 
						{
							$ProductInformation .= $item->getName().' : '.$missingQty.' missing';
							
							//If product has parent and if this poarent overrides planning
							$parentItem = $item->getParentItem();
							$OverridePlanning = null;
							if ($parentItem != null)
							{
								$parentProductId = $parentItem->getproduct_id();
								if (isset($ParentProductsWhichOverridePlanning[$parentProductId]))
								{
									$OverridePlanning =  $beginingDate + $ParentProductsWhichOverridePlanning[$parentProductId] * 3600 * 24;
									$ProductInformation .= ' (planning overrided) <br>';
								}
							}
							
							if ($OverridePlanning == null)
							{
								//calculate product supply date
								$ProductSupplyDate = strtotime($product->getsupply_date());
								if (($ProductSupplyDate < $beginingDate) || ($ProductSupplyDate < time()))
								{
									$ProductSupplyDate = $beginingDate + $product->getdefault_supply_delay() * 3600 * 24;
									$ProductInformation .= ' (supply delay is '.$product->getdefault_supply_delay().' days = '.date('Y-m-d', $ProductSupplyDate).')<br>';
								}
								else 
									$ProductInformation .= ' (PO delivery planed to '.date('Y-m-d', $ProductSupplyDate).')<br>';
								//update worst supply date for order
								if ($ProductSupplyDate > $worstSupplyDate)
									$worstSupplyDate = $ProductSupplyDate;
							}
							else 
							{
								if ($OverridePlanning > $worstSupplyDate)
									$worstSupplyDate = $OverridePlanning;
							}
						}
					}
					else 
						$ProductInformation .= $item->getName().' does not manage stock<br>';
				}
			}			
			else			
				$ProductInformation .= $item->getName().' complete<br>';
		}
		
		//define values
		$fullstockDateTimeStamp = $worstSupplyDate;
		$fullstockComments = $ProductInformation;
		
		//avoid holy day (if set)
		if (Mage::getStoreConfig('planning/fullstock/avoid_holy_days') == 1)
		{
			$daysToAdd = $this->DaysUntilNotHolyDay($fullstockDateTimeStamp);
			if ($daysToAdd > 0)
			{
				$fullstockDateTimeStamp += 3600 * 24 * $daysToAdd;
				$fullstockComments .= 'add '.$daysToAdd.' days to avoid holy day<br>';
			}
		}
		
		//add security (max date)
		$considerationDateTimestamp = strtotime($this->getConsiderationDate());
		$mode = Mage::getStoreConfig('planning/fullstock/maxdate_calculation_mode');
		$value = Mage::getStoreConfig('planning/fullstock/maxdate_calculation_value');
		$diff = $fullstockDateTimeStamp - $considerationDateTimestamp;
		$newDiff = 0;
		if ($value > 0)
		{
			switch ($mode)
			{
				case 'days':
					$newDiff += $diff + $value * 3600 * 24;
					$fullstockComments .= 'add '.$value.' days to calculate max date<br>';
					break;
				case 'percent':
					$newDiff += $diff * (1 + $value / 100);
					$fullstockComments .= 'add '.$value.'% to calculate max date<br>';
					break;
			}
		}
		$maxDateTimestamp = $considerationDateTimestamp + $newDiff;
		
		//store values
		$this->setpsop_fullstock_date(date('Y-m-d', $fullstockDateTimeStamp));
		$this->setpsop_fullstock_date_max(date('Y-m-d', $maxDateTimestamp));
		$this->setpsop_fullstock_comments($fullstockComments);
	}
	
	/**
	 * Set shipping information
	 *
	 */
	public function setShippingInformation($order, $quoteMode = false)
	{
		$shippingDateTimeStamp = null;
		$maxDateTimestamp = null;
		$shippingComments = null;

		//get shipment for order
		$shipmentDate = null;
		$shipments = $order->getShipmentsCollection();
		if ($shipments)
		{
			foreach ($shipments as $shipment)
			{
				if ($shipmentDate == null)
					$shipmentDate = $shipment->getCreatedAt();
			}
			if ($shipmentDate != null)
			{			
				$shippingComments .= 'Order shipped on '.$shipmentDate.'<br>';			
				$this->setpsop_shipping_date($shipmentDate);
				$this->setpsop_shipping_comments($shippingComments);
				return $this;
			}
		}
				
		//if no fullstock date, do not compute
		$fullstockDate = $this->getFullstockDate();
		$fullstockDateTimestamp = strtotime($fullstockDate);
		if ($fullstockDate == null)
		{
			$this->setpsop_shipping_date(null);
			$this->setpsop_shipping_date_max(null);
			$this->setpsop_shipping_comments('');
			return $this;
		}

		//add preparation duration
		$orderPreparationDuration = $this->getPreparationDurationForOrder($order);
		$shippingDateTimeStamp = strtotime($fullstockDate) + $orderPreparationDuration * 3600 * 24;
		$shippingComments .= 'add '.$orderPreparationDuration.' days to prepare order<br>';
		
		//avoid holy day (if set)
		if (Mage::getStoreConfig('planning/shipping/avoid_holy_days') == 1)
		{
			$daysToAdd = $this->DaysUntilNotHolyDay($shippingDateTimeStamp);
			if ($daysToAdd > 0)
			{
				$shippingDateTimeStamp += 3600 * 24 * $daysToAdd;
				$shippingComments .= 'add '.$daysToAdd.' days to avoid holy day<br>';
			}
		}
		
		//add security (max date)
		$mode = Mage::getStoreConfig('planning/shipping/maxdate_calculation_mode');
		$value = Mage::getStoreConfig('planning/shipping/maxdate_calculation_value');
		$diff = $orderPreparationDuration * 3600 * 24;
		$newDiff = 0;
		if ($value > 0)
		{
			switch ($mode)
			{
				case 'days':
					$newDiff += $diff + $value * 3600 * 24;
					$shippingComments .= 'add '.$value.' days to calculate max date<br>';
					break;
				case 'percent':
					$newDiff += $diff * (1 + $value / 100);
					$shippingComments .= 'add '.$value.'% to calculate max date<br>';
					break;
			}
		}
		$maxDateTimestamp = strtotime($this->getpsop_fullstock_date_max()) + $newDiff;
		
		//avoid holy day for max date (if set)
		if (Mage::getStoreConfig('planning/shipping/avoid_holy_days') == 1)
		{
			$daysToAdd = $this->DaysUntilNotHolyDay($maxDateTimestamp);
			if ($daysToAdd > 0)
			{
				$maxDateTimestamp += 3600 * 24 * $daysToAdd;
			}
		}

		
		//store values
		if ($shippingDateTimeStamp != null)
		{
			$this->setpsop_shipping_date(date('Y-m-d', $shippingDateTimeStamp));
			$this->setpsop_shipping_date_max(date('Y-m-d', $maxDateTimestamp));
			$this->setpsop_shipping_comments($shippingComments);
		}
		
	}
	
	/**
	 * Set delivery information
	 *
	 * @param unknown_type $order
	 */
	public function setDeliveryInformation($order, $quoteMode = false)
	{
		$deliveryDateTimeStamp = null;
		$maxDateTimestamp = null;
		$deliveryComments = null;

		//if no shipping date, do not compute
		$shippingDate = $this->getShippingDate();
		$shippingDateTimestamp = strtotime($shippingDate);
		if ($shippingDate == null)
		{
			$this->setpsop_delivery_date(null);
			$this->setpsop_delivery_date_max(null);
			$this->setpsop_delivery_comments('');
			return $this;
		}
		
		//define shipping date
		if (!$quoteMode)
			$carrier = $order->getshipping_method();
		else 
			$carrier = $order->getShippingAddress()->getShippingMethod();
		$country = '';
		if ($order->getShippingAddress() != null)
			$country = $order->getShippingAddress()->getcountry();
		$shippingDelay = mage::helper('purchase/ShippingDelay')->getShippingDelayForCarrier($carrier, $country);
		$deliveryDateTimeStamp = $shippingDateTimestamp + $shippingDelay * 3600 * 24;
		$deliveryComments .= 'add '.$shippingDelay.' days for shipping delay with '.$carrier.' to '.$country.'<br>';
				
		//avoid holy day (if set)
		if (Mage::getStoreConfig('planning/delivery/avoid_holy_days') == 1)
		{
			$daysToAdd = $this->DaysUntilNotHolyDay($deliveryDateTimeStamp);
			if ($daysToAdd > 0)
			{
				$deliveryDateTimeStamp += 3600 * 24 * $daysToAdd;
				$deliveryComments .= 'add '.$daysToAdd.' days to avoid holy day<br>';
			}
		}
		
		//add security (max date)
		$mode = Mage::getStoreConfig('planning/shipping/maxdate_calculation_mode');
		$value = Mage::getStoreConfig('planning/shipping/maxdate_calculation_value');
		$diff = $deliveryDateTimeStamp - $shippingDateTimestamp;
		$newDiff = 0;
		if ($value > 0)
		{
			switch ($mode)
			{
				case 'days':
					$newDiff += $diff + $value * 3600 * 24;
					$deliveryComments .= 'add '.$value.' days to calculate max date<br>';
					break;
				case 'percent':
					$newDiff += $diff * (1 + $value / 100);
					$deliveryComments .= 'add '.$value.'% to calculate max date<br>';
					break;
			}
		}
		$maxDateTimestamp = strtotime($this->getpsop_shipping_date_max()) + $newDiff;

		
		//store values
		if ($deliveryDateTimeStamp != null)
		{
			$this->setpsop_delivery_date(date('Y-m-d', $deliveryDateTimeStamp));
			$this->setpsop_delivery_date_max(date('Y-m-d', $maxDateTimestamp));
			$this->setpsop_delivery_comments($deliveryComments);
		
		}
		
	}
		
	/*****************************************************************************************************************************
	******************************************************************************************************************************
	****************** TOOLS *****************************************************************************************************
	******************************************************************************************************************************
	******************************************************************************************************************************/

	/**
	 * Add day until date is not holy day
	 *
	 * @param unknown_type $dateTimestamp
	 */
	public function DaysUntilNotHolyDay($dateTimestamp)
	{
		$retour = 0;
		
		$loop = true;
		while ($loop)
		{
			if ($this->isHolyDay($dateTimestamp))
			{
				$retour += 1;
				$dateTimestamp += 3600 * 24;
			}
			else 
				$loop = false;
		}
		
		return $retour;
	}
	
	/**
	 * Function to check if a date is holy day
	 *
	 * @param unknown_type $dateTimestamp
	 * @return unknown
	 */
	public function isHolyDay($dateTimestamp)
	{
		$retour = false;
		
		//check weekend
		$dayId = date('w', $dateTimestamp);
		$weekendDay = Mage::getStoreConfig('general/locale/weekend');
		$pos = strpos($weekendDay, $dayId);
		if (!($pos === false))
			$retour = true;
		
		//todo (manage real holy days)
			
		return $retour;
	}
	
	/**
	 * Return invoiced date for order
	 *
	 * @param unknown_type $order
	 */
	public function getOrderInvoicedDate($order)
	{
		$retour = null;
		
		//browse all invoices
		$invoices = $order->getInvoiceCollection();
		foreach($invoices as $invoice)
		{
			$retour = $invoice->getcreated_at();
		}
		
		return $retour;
	}
	
	/**
	 * Return delay for payment
	 *
	 * @param unknown_type $paymentMethod
	 * @return unknown
	 */
	public function getPaymentDelay($paymentMethod)
	{
		$retour = 0;
		
		//check if method belong to immediate ones
		$immediateMethods = Mage::getStoreConfig('planning/quote_options/immediate_payment_method');
		$pos = strpos($immediateMethods, $paymentMethod);
		
		if ($pos === false)
			$retour = Mage::getStoreConfig('planning/quote_options/delayed_payment_delay');	

		//die('-'.$paymentMethod.'-'.$immediateMethods.'-'.$retour.'-'.$pos);
			
		return $retour;
	}
	
	/**
	 * Return preparation duration for order
	 *
	 * @param unknown_type $order
	 */
	public function getPreparationDurationForOrder($order)
	{
		return Mage::getStoreConfig('planning/shipping/order_preparation_duration');		
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
	    	if ($this->getpsop_fullstock_date() != $this->getOrigData('psop_fullstock_date'))
	    		$UpdateSupplyNeeds = true;
	    	if ($this->getpsop_fullstock_date_force() != $this->getOrigData('psop_fullstock_date_force'))
	    		$UpdateSupplyNeeds = true;
	    		
	    	//update supply needs for products
	    	if ($UpdateSupplyNeeds)
	    	{
	    		$order = mage::getModel('sales/order')->load($this->getpsop_order_id());
	    		foreach($order->getAllItems() as $item)
				{
					$productId = $item->getproduct_id();
			    	Mage::dispatchEvent('purchase_update_supply_needs_for_product', array('product_id'=>$productId, 'from' => 'Order planning update'));
				}
	    	}
	    	
    }
	
}