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
class MDN_Purchase_Model_Observer {
	
	/**
	 * Méthode appelée par le cron pour mettre a jour les qte commandés des produits
	 *
	 */
	public function UpdateStocksForOrders()
	{
		//Parcourt les commandes dont les stocks n'ont pas été mis a jour (stocks_updated = 0)
		echo '<h2>UpdateStocksForOrders</h2>';
		$sql = 'select entity_id from '.mage::getModel('Purchase/Constant')->getTablePrefix().'sales_order where stocks_updated = 0';
		$data = mage::getResourceModel('sales/order_item_collection')->getConnection()->fetchAll($sql);		
		for ($i=0;$i<count($data);$i++)
		{
			//Charge la commande
			$order = mage::getModel('sales/order')->load($data[$i]['entity_id']);
			echo '<p><b>Processing order #'.$order->getId().'</b>';
			
			//Si la commande remplit les conditions
			if ($order->IsValidForPurchaseProcess())
			{
			
				try 
				{
					//Pour chaque produit de la commande
					foreach($order->getAllItems() as $item)
					{
						if ($item->getproduct_id())
						{
							//charge le produit
							$product = mage::getModel('catalog/product')->load($item->getproduct_id());
							if ($product->getId() != '')
							{
								echo '<br>Updating product ordered qty for product #'.$product->getId();	        	
					        					
								//reserve le produit si possible
								$model = mage::getModel('Purchase/Productstock');
								mage::helper('purchase/ProductReservation')->reserveProductForOrder($order->getId(), $product->getId());
				        		
					        	//met a jour les qte (reservée et ordered)
								echo '<br>Update ordered and reserved qty';
					        	$model->UpdateOrderedQty($product);
							}
						}
					}
				}
				catch (Exception $ex)
				{
		        	echo 'Error while browsing products : '.$ex->getMessage().' <br> '.$ex->getTraceAsString();
				}
		        
		        //Specifie que le stock a été mis a jour
		        $sql = 'update '.mage::getModel('Purchase/Constant')->getTablePrefix().'sales_order set stocks_updated = 1 where entity_id = '.$data[$i]['entity_id'];
		        echo '<br>'.$sql;
		        mage::getResourceModel('sales/order_item_collection')->getConnection()->query($sql);	

		        //dispatch order in order preparation tabs
				mage::helper('BackgroundTask')->AddTask('Dispatch order #'.$order->getId(), 
										'Orderpreparation',
										'dispatchOrder',
										$order->getId()
										);	

				//Store planning
				try 
				{
					echo '<br>Store planning';
					$planning = mage::helper('purchase/Planning')->createPlanning($order);
					$planning->setpsop_anounced_date($planning->getpsop_delivery_date());
					$planning->setpsop_anounced_date_max($planning->getpsop_delivery_date_max());
					$planning->save();	
									
				}
				catch (Exception $ex)
				{
					echo '<font color="red">Error creating order planning: '.$ex->getMessage().'</font>';
				}
				
				//update supply needs for products
				/*
				foreach($order->getAllItems() as $item)
				{
					$productId = $item->getproduct_id();
					Mage::dispatchEvent('purchase_update_supply_needs_for_product', array('product_id'=>$productId, 'from' => 'order #'.$order->getId().' placed'));
				}
				*/
				
			}
			else 
				echo '<p>Order doesnt fullfill supplyneeds and reservation requiresites. Stocks not updated';	
	        
		}
	}
	
	
	/**
	 * Appelé lorsqu'une commande est créée...
	 *
	 * @param Varien_Event_Observer $observer
	 * @return none
	 */
	public function sales_order_afterPlace(Varien_Event_Observer $observer)
    {
    	
    	try 
    	{
    		$order = $observer->getEvent()->getOrder();   
    		$order->setpayment_validated(0); 		

    		//stock la valeur cost de chaque élément de la commande
	        foreach($order->getAllItems() as $item)
	        {
	        	//recupere le produit correspondant
	        	//Mage::log($item->getData());
	        	$product = mage::getModel('catalog/product')->load($item->getproduct_id());
	        	
	        	//Stock le cost
	        	if ($product)
	        	{
	        		switch($product->gettype_id())
	        		{
	        			case 'simple':
				        	$item->setData(mage::helper('purchase/MagentoVersionCompatibility')->getSalesOrderItemCostColumnName(), $product->getcost());
	    					break; 
	        			case 'configurable':
	        			case 'bundle':
	        				$item->setData(mage::helper('purchase/MagentoVersionCompatibility')->getSalesOrderItemCostColumnName(), $this->computeCostFromSubProducts($item, $order->getAllItems()));
	        				break;
	        		}
	        	}	
	        }

	        
    	}
    	catch (Exception $ex)
    	{
    		Mage::logException($ex);
    	}
    	
	}
	
	/**
	 * Compute cost from the sum of the costs of subproducts
	 *
	 * @param unknown_type $parentItem
	 * @param unknown_type $items
	 */
	private function computeCostFromSubProducts($parentItem, $items)
	{
		$retour = 0;
		$parentQuoteItemId = $parentItem->getquote_item_id();
		$parentItemQty = $parentItem->getqty_ordered();
		
		foreach($items as $item)
		{
			if ($item->getquote_parent_item_id() == $parentQuoteItemId)
			{
				$product = mage::getModel('catalog/product')->load($item->getproduct_id());
				$retour += $product->getCost() * ($item->getqty_ordered() / $parentItemQty);
			}
		}
		
		return $retour;
	}
	
	/**
	 * Appelé lorsqu'une facture est payée
	 *
	 */
	public function sales_order_invoice_pay(Varien_Event_Observer $observer)
	{
		//si on doit passer payment_validated à true
		if (Mage::getStoreConfig('purchase/configuration/auto_validate_payment') == 1)
		{
			try 
			{
				//recupere les infos
				$order = $observer->getEvent()->getInvoice()->getOrder();  
				$order->setpayment_validated(1)->save();
				
				mage::log('payment_validated set to true for order #'.$order->getId());				
			}
			catch (Exception $ex)
			{
				mage::log('Error when validating payment_validated: '.$ex->getMessage());				
			}
		}
	}
	
	/**
	 * Update supply needs for product
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function purchase_update_supply_needs_for_product(Varien_Event_Observer $observer)
	{
		$productId = $observer->getEvent()->getproduct_id();
		$from = $observer->getEvent()->getfrom();
		$title = 'Update supply needs for product #'.$productId;
		if ($from != '')
			$title .= ' ('.$from.')';
    	mage::helper('BackgroundTask')->AddTask($title, 
						'purchase',
						'updateSupplyNeedsForProduct',
						$productId
						);	
	}

} 
 
 