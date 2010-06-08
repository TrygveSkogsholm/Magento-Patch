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
class MDN_Purchase_Block_Planning_Graph extends Mage_Core_Block_Template
{
	private $_planning = null;
	private $_order = null;
	
	/**
	 * Return planning
	 *
	 */
	public function getPlanning()
	{
		if ($this->_planning == null)
			$this->_planning = $this->getOrder()->getPlanning();
		return $this->_planning;
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function getOrder()
	{
		if ($this->_order == null)
			$this->_order = Mage::registry('current_order');
		return $this->_order;
	}
	
	/**
	 * 
	 *
	 */
	public function IsConsidered()
	{
		return ($this->getPlanning()->getConsiderationDate() != null);
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function isPrepared()
	{
		return $this->isShipped();
	}
	
	/**
	 * 
	 *
	 */
	public function allProductsAreReserved()
	{
		return $this->getOrder()->allProductsAreReserved();
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function isShipped()
	{
		return $this->getOrder()->IsCompletelyShipped();
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function getEstimatedDeliveryDate()
	{
		return $this->getPlanning()->getDeliveryDate();
	}
}