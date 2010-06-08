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
class MDN_Purchase_Helper_MagentoVersionCompatibility extends Mage_Core_Helper_Abstract
{
	
	/**
	 * Return cost column name
	 *
	 */
	public function getSalesOrderItemCostColumnName()
	{
		switch ($this->getVersion())
		{
			case '1.0':
			case '1.1':
			case '1.2':
			case '1.3':
				return 'cost';
				break;
			case '1.4':
				return 'base_cost';
				break;
			default :
				return 'base_cost';				
				break;
		}
	}
	
	/**
	 * return version
	 *
	 * @return unknown
	 */
	private function getVersion()
	{
		$version = mage::getVersion();
		$t = explode('.', $version);
		return $t[0].'.'.$t[1];
	}
	
}