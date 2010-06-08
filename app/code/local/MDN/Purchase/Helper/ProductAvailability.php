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
class MDN_Purchase_Helper_ProductAvailability extends Mage_Core_Helper_Abstract
{
	const kPath = 'purchase/product_availability/ranges';
	
	/**
	 * Read config
	 *
	 * @return unknown
	 */
	public function getConfig()
	{
    	//init or retrieve config
    	$config = Mage::getStoreConfig(self::kPath);

    	if ($config == '')
    		$config = array();
    	else 
	    	$config = unserialize($config);
    	
    	return $config;
	}
	
	/**
	 * Save config
	 *
	 * @param unknown_type $config
	 */
	public function saveConfig($config)
	{
		//save in database
		$data = serialize($config);
		Mage::getConfig()->saveConfig(self::kPath, $data);
		
		//update in cache
		Mage::getConfig()->reinit();
	}
	
	/**
	 * Add a new range
	 *
	 */
	public function newRange()
	{
		$config = $this->getConfig();
		
		$range = array();
		$range['from'] = 0;
		$range['to'] = 0;
		$range['label'] = '';
		$config[] = $range;		
		
		$this->saveConfig($config);
	}
	
	/**
	 * Return label matching to delay & store
	 *
	 * @param unknown_type $storeId
	 * @param unknown_type $days
	 */
	public function getLabel($storeCode, $days)
	{
		//default value		
		$retour = mage::helper('purchase')->__('Average supply delay : %s days', $days);
		
		//parse config
		$config = $this->getConfig();
		if (is_array($config))
		{
			for($i=0;$i<count($config);$i++)
			{
				if (($days >= $config[$i]['from']) && ($days <= $config[$i]['to']))
				{
					$retour = mage::helper('purchase')->__('Average supply delay : ').$config[$i]['label'];
					
					//check for store values
					if (isset($config[$i][$storeCode]))
						if ($config[$i][$storeCode] != '')
							$retour = mage::helper('purchase')->__('Average supply delay : ').$config[$i][$storeCode];
					
				}
			}
		}
				
		return $retour;
	}
	
}