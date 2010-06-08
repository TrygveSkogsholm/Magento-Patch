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
class MDN_Purchase_Helper_ShippingDelay extends Mage_Core_Helper_Abstract
{
	
	/**
	 * Update carriers list
	 *
	 */
	public function updateCarriers()
	{
		//collect carriers list
		$collection = Mage::getStoreConfig('carriers', 0);
		$shippingDelay = Mage::getStoreConfig('planning/delivery/default_shipping_delay');
		
		foreach($collection as $carrier)
		{
			if (isset($carrier['model']))
			{
				$instance = mage::getModel($carrier['model']);
				$methods = $instance->getAllowedMethods();
				if ($methods)
				{
					foreach ($methods as $code => $title)
					{
						//check if carrier present
						if (!$this->carrierIsPresent($code))
						{
							//add a row for this carrier
							mage::getModel('Purchase/ShippingDelay')
								->setpsd_carrier($code)
								->setpsd_carrier_title($title)
								->setpsd_default($shippingDelay)
								->save();
						}
					}
				}
			}
		}
		
	}
	
	/**
	 * Check if a carrier is present in table
	 *
	 * @param unknown_type $code
	 */
	public function carrierIsPresent($code)
	{
		$collection = mage::getModel('Purchase/ShippingDelay')->getCollection()->addFieldToFilter('psd_carrier', $code);
		
		return ($collection->getSize() > 0);
	}
	
	/**
	 * Return shipping delay for carrier
	 *
	 */
	public function getShippingDelayForCarrier($ShippingMethod, $Country)
	{
		$return = Mage::getStoreConfig('planning/delivery/default_shipping_delay');

		//define carrier
		$Carrier = '';
		$t = explode('_', $ShippingMethod);
		if (count($t) > 0)
			$Carrier = $t[0];
		
		//load shipping delay for carrier
		$item = mage::getModel('Purchase/ShippingDelay')->load($Carrier, 'psd_carrier');
		if ($item->getId())
		{
			$return = $item->getpsd_default();
			
			//check in exceptions
			if ($item->getpsd_exceptions() != '')
			{
				$exceptions = explode(',', $item->getpsd_exceptions());
				for($i=0; $i<count($exceptions); $i++)
				{
					$values = explode(':', $exceptions[$i]);
					if (count($values) == 2)
					{
						if ($Country == $values[0])
							$return = $values[1];
					}
				}
			}
		}
		
		return $return;
	}
	
}