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
class MDN_Purchase_Model_ProductSupplier  extends Mage_Core_Model_Abstract
{
	
	/*****************************************************************************************************************************
	* ***************************************************************************************************************************
	* Constructeur
	*
	*/
	public function _construct()
	{
		parent::_construct();
		$this->_init('Purchase/ProductSupplier');
	}
	
	/**
	 * Retrieve price for produt
	 *
	 */
	public function getProductForSupplier($productId, $supplierId)
	{
		$value = 0;
		
		$collection = $this->getCollection()
							->addFieldToFilter('pps_product_id', $productId)
							->addFieldToFilter('pps_supplier_num', $supplierId);
		
		foreach($collection as $item)
		{
			$value = $item->getpps_last_unit_price();
		}
							
		
		return $value;
	}
	
	/**
	 * when saving, update supply needs for product
	 *
	 */
    protected function _afterSave()
    {
	    	parent::_afterSave();

	    	
			$productId = $this->getpps_product_id();
	    	Mage::dispatchEvent('purchase_update_supply_needs_for_product', array('product_id'=>$productId));

    }	

	/**
	 * when deleting, update supply needs for product
	 *
	 */
    protected function _afterDelete()
    {
	    	parent::_afterDelete();

			$productId = $this->getpps_product_id();
	    	Mage::dispatchEvent('purchase_update_supply_needs_for_product', array('product_id'=>$productId));

    }	
    
}