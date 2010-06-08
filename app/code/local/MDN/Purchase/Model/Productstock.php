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
class MDN_Purchase_Model_Productstock 
{
	public function _construct()
	{
		parent::_construct();
		$this->_init('Purchase/Productstock');
	}
	
	/**
	 * Retourne la qte commandée
	 *
	 * @param unknown_type $ProductId
	 */
	public function GetOrderedQty($ProductId)
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
			$remainToShip = $orderItem->getRemainToShipQty();
			if ($remainToShip > 0)
				$retour += $remainToShip;
		}

		return $retour;

	}
	
	/**
	 * Met a jour la qte commandée (mais non livrée)
	 */
	public function UpdateOrderedQty($product)
	{

        //recupere la qté
        $orderedQty = $this->GetOrderedQty($product->getId());
        
        //check if record exists
        $sql = 'select count(*) from '.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity_int where entity_id = '.$product->getId().' and attribute_id = '.mage::getModel('Purchase/Constant')->GetProductOrderedQtyAttributeId();
        $res = mage::getResourceModel('sales/order_item_collection')->getConnection()->fetchOne($sql);
        if ($res == 0)
        {
			//insert record
			$sql = 'insert into '.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity_int (value, entity_id, attribute_id) values ('.$orderedQty.', '.$product->getId().', '.mage::getModel('Purchase/Constant')->GetProductOrderedQtyAttributeId().')';
			$res = mage::getResourceModel('sales/order_item_collection')->getConnection()->query($sql);
        }
        else 
        {
			//update record
			$sql = 'update '.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity_int set value = '.$orderedQty.' where entity_id = '.$product->getId().' and attribute_id = '.mage::getModel('Purchase/Constant')->GetProductOrderedQtyAttributeId();
			$res = mage::getResourceModel('sales/order_item_collection')->getConnection()->query($sql);        	
        }
        
        //update supply needs
		$productId = $product->getId();
		Mage::dispatchEvent('purchase_update_supply_needs_for_product', array('product_id'=>$productId, 'from' => 'Update ordered qty'));
        		
		return ;
	}
	
		
	/**
	 * Calcul le stock pour un produit
	 *
	 */
	public function ComputeProductStock($ProductId)
	{
		$sql = '
				select sum(sm_coef  * sm_qty) stock
				from '.mage::getModel('Purchase/Constant')->getTablePrefix().'stock_movement 
				where sm_product_id = '.$ProductId.'
				';
		$data = mage::getResourceModel('sales/order_item_collection')->getConnection()->fetchAll($sql);	
		if (count($data) > 0)
		{
			if ($data[0]['stock'] == '')
				return 0;
			else
				return $data[0]['stock'];
		}
		
		return 0;
	}
}