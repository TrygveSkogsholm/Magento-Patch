<?php

/**
 * Surcharge de la classe shipment
 *
 */
class MDN_Purchase_Model_Sales_Order_Shipment extends Mage_Sales_Model_Order_Shipment
{
	 /**
     * Surcharge la méthode after save pour mettre a jour les stocks
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterSave()
    {
    	try 
    	{
	    	//appel le parent
	    	parent::_afterSave();
	    	
	    	//Define if shipment just created
	    	$creation = ($this->getentity_id() != $this->getOrigData('entity_id'));
			if ($creation)
			{
		    	
		    	//Create stock movements
		    	$order = $this->getOrder();
		    	foreach ($this->getAllItems() as $item) 
		    	{
		    		//retrieve informaiton
					$qty = $this->getRealShippedQtyForItem($item);		
					
					try 
					{
		            	$StockMovement = mage::getmodel('Purchase/StockMovement')
		            		->setsm_product_id($item->getproduct_id())
		            		->setsm_type('order')
		            		->setsm_coef(-1)
		            		->setsm_qty($qty)
		            		->setsm_date(date('Y-m-d'))
		            		->setsm_ui($item->getId())
		            		->setsm_description(mage::helper('purchase')->__('Order #').$this->getOrder()->getincrement_id())
		            		->save();
					}
					catch (Exception $ex)
					{
						//nothing, db constraint exception because stock movement already exists
	            	}
	            	
	            	//reset reserved qty
	            	$orderItem = $item->getOrderItem();
	            	$orderItem->setreserved_qty(0)->save();
	            	
		        }
		        
		        //update order planning
				$orderId = $this->getOrder()->getId();
				mage::helper('BackgroundTask')->AddTask('Update planning for order '.$orderId, 
								'purchase/Planning',
								'updatePlanning',
								$orderId
								);							

				//Met a jour les qte commandées pour le produit
				$order->UpdateProductsOrdererQty;
			}
    	}
    	catch (Exception $ex)
    	{
    		mage::log($ex->getMessage());
    	}

 		
    	return $this;
    }
    
    /**
     * Return real shipped qty for an item
     * Welcome in magento.....
     *
     * @param unknown_type $item
     */
    public function getRealShippedQtyForItem($item)
    {
    	//init vars
    	$qty = $item->getQty();	
    	$orderItem = $item->getOrderItem();
    	$orderItemParentId = $orderItem->getparent_item_id();
    	
    	//define if we have to multiply qty by parent qty
    	$mustMultiplyByParentQty = false;
		if ($orderItemParentId > 0) 
		{
			$parentOrderItem = mage::getmodel('sales/order_item')->load($orderItemParentId);
			if ($parentOrderItem->getId())
			{
				//if shipped together
				if (($parentOrderItem->getproduct_type() == 'bundle') && (!$parentOrderItem->isShipSeparately()))
				{
					$mustMultiplyByParentQty = true;
					$qty = ($orderItem->getqty_ordered() / $parentOrderItem->getqty_ordered());
				}
			}
		}
		
		//if multiply by parent qty
		if ($mustMultiplyByParentQty)
		{
			$parentShipmentItem = null;    	
	    	foreach($item->getShipment()->getAllItems() as $ShipmentItem)
	    	{
	    		if ($ShipmentItem->getorder_item_id() == $orderItemParentId)
	    			$parentShipmentItem = $ShipmentItem;
	    	}
	    	if ($parentShipmentItem)
	    	{
	    		$qty = $qty * $parentShipmentItem->getQty();    
	    	}
		}

    	return $qty;
    }
}