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
class MDN_Purchase_Model_SupplyNeeds  extends Mage_Core_Model_Abstract
{
	
	const _StatusApproComm = 'supply_order';
	const _StatusQtyMini = 'supply_min_qty';
	const _StatusQtyMiniStock0 = 'stock_0';
	const _StatusManualSupplyNeed = 'manual_supply_need';
	
	/**
	* Constructeur
	*
	*/
	public function _construct()
	{
		parent::_construct();
		$this->_init('Purchase/SupplyNeeds');
	}
	
	/**
	 * Retourne le statut du besoin d'appro sous la forme numérique pour faciliter le tri
	 *
	 */
	public function getStatusForSort()
	{
		switch ($this->getsn_status())
		{
			case MDN_Purchase_Model_SupplyNeeds::_StatusApproComm :
				return 0;
				break;
			case MDN_Purchase_Model_SupplyNeeds::_StatusManualSupplyNeed :
				return 1;			
				break;
			case MDN_Purchase_Model_SupplyNeeds::_StatusQtyMini :
				return 2;			
				break;
			case 'OK' :
				return 3;
				break;
		}
	}
	
	/**
	 * Retourne les besoins d'approvisionnement pour tous les produits
	 *
	 */
	public function getSupplyNeeds()
	{
		$retour = array();
		
		//Recupere la collection de la base et la retourne sous la forme d'un tableau
		$collection = $this->getCollection();
		foreach($collection as $item)
		{
			$retour[] = $item;
		}
				
		//Retourne le tout
		return $retour;
	}
	
	/**
	 * Retourne les besoins d'appro pour un produit
	 *
	 * @param unknown_type $ProductId
	 */
	public function getSupplyNeedsForProduct($product)
	{
		$DefaultNotifyStockQty = Mage::getStoreConfig('cataloginventory/item_options/notify_stock_qty');
		$item = array();
		$item['qty_min'] = false;
		$item['dead_line'] = '';
		$item['is_critical'] = false;
		$item['is_warning'] = false;
		$item['notify_stock_qty'] = $DefaultNotifyStockQty;
		
		//recupere le stock
		$stock_item = $product->getStockItem();
		$item['product'] = $product;
		$item['stock'] = $stock_item;
		$item['manual_qty'] = (int)$product->getmanual_supply_need_qty();
		if ($stock_item->getuse_config_notify_stock_qty() == 1)
			$stock_item->setnotify_stock_qty($DefaultNotifyStockQty);
		else 
			$item['notify_stock_qty'] = $stock_item->getnotify_stock_qty();
		
		//Recupere la qte commandée
		$WaitingForDelivery = $this->getWaitingForDeliverQty($product);
		
		//déduis l'état & d'aurtes trucs
		$q = 0;
		$str_comments = "____________________________\n";
		$str_status = "";
		$dead_line = null;
		$orders = null;
		if ($product->getordered_qty() > ($stock_item->getQty() + $WaitingForDelivery))
		{
			$str_status = MDN_Purchase_Model_SupplyNeeds::_StatusApproComm ;
			
			//on rajoute les commandes concernées dans la description
			$orders = $product->GetPendingOrders();
			if (sizeof($orders) > 0)
			{
				$StockAffectatableQty = ($stock_item->getQty() + $WaitingForDelivery);
				foreach($orders as $order)
				{
					//recupere la qte du produit dans la commande
					$ProductQtyInOrder = 0;
					foreach($order->getAllItems() as $OrderItem)
					{
						if ($OrderItem->getproduct_id() == $product->getId())
							$ProductQtyInOrder += ($OrderItem->getqty_ordered() - $OrderItem->getRealShippedQty());
					}
							
					//Définit les infos
					$str_comments .= mage::helper('purchase')->__("Order #");
					$str_comments .= $order->getincrement_id().' (x'.$ProductQtyInOrder.') ('.$order->getPlanning()->getFullstockDate().')';				
					$str_comments .= "\n";

					//define deadline
					if ($ProductQtyInOrder > 0)
					{
						if (($dead_line == null) || (strtotime($order->getPlanning()->getFullstockDate()) < strtotime($dead_line)))
							$dead_line = $order->getPlanning()->getFullstockDate();
					}
					
					$StockAffectatableQty -= $ProductQtyInOrder;				
				}
			}
			else 
			{
				$item['is_warning'] = true;
				$str_comments = "WARNING !!";
			}
			
			//if manual supply date, consider date
			if (($product->getmanual_supply_need_qty() > 0) && ($product->getmanual_supply_need_date() != ''))
			{
				if (strtotime($dead_line) > strtotime($product->getmanual_supply_need_date()))
					$dead_line = $product->getmanual_supply_need_date();
			}
			
			$q = $product->getordered_qty() - $stock_item->getQty() + $stock_item->getnotify_stock_qty() - $WaitingForDelivery + $product->getmanual_supply_need_qty();
		}
		else 
		{
			if ($product->getmanual_supply_need_qty() > ($stock_item->getQty() + $WaitingForDelivery))
			{
				$q = $stock_item->getnotify_stock_qty() - ($stock_item->getQty() + $WaitingForDelivery - $product->getordered_qty()) + $product->getmanual_supply_need_qty();
				$str_status = MDN_Purchase_Model_SupplyNeeds::_StatusManualSupplyNeed;
				if ($product->getmanual_supply_need_date() != '')
					$dead_line = $product->getmanual_supply_need_date();
			}
			else
			{
				if ($stock_item->getnotify_stock_qty() > ($stock_item->getQty() - $product->getordered_qty() + $WaitingForDelivery))
				{
					$q = $stock_item->getnotify_stock_qty() - ($stock_item->getQty() + $WaitingForDelivery - $product->getordered_qty()) + $product->getmanual_supply_need_qty();
					$str_status = MDN_Purchase_Model_SupplyNeeds::_StatusQtyMini;

					$item['qty_min'] = true;
				}
				else 
				{
						$str_status = 'OK';
				}
			}
		}
		
		if ($product->getmanual_supply_need_comments() != '')
		{
			$str_comments .= "____________________________\n";
			$str_comments .= $product->getmanual_supply_need_comments()."\n";
		}
				
		$item['ordered_qty'] = $product->getordered_qty();
		$item['status'] = $str_status;
		$item['details'] = $str_comments;
		$item['needed_qty'] = $q;
		$item['waiting_for_deliver_qty'] = $WaitingForDelivery;
		$item['delta'] = ($q - $item['waiting_for_deliver_qty']);
		$item['dead_line'] = $dead_line;
		$item['is_critical'] = $this->IsCritical($product, $item, $orders);
		
		//define purchase dead line
		$item['purchase_dead_line'] = null;
		if ($dead_line != null)
		{
			$purchase_deadline_timestamp = strtotime($dead_line) - 3600 * 24 * $product->getdefault_supply_delay();
			$item['purchase_dead_line'] = date('Y-m-d', $purchase_deadline_timestamp);
		}
				
		//rajoute les suppliers
		$item['suppliers'] = array();
		$suppliers = mage::getModel('Purchase/ProductSupplier')
			->getCollection()
			->join('Purchase/Supplier', 'sup_id=pps_supplier_num')
			->addFieldToFilter('pps_product_id', $product->getId())
			->setOrder('pps_last_price', 'asc');
		foreach($suppliers as $sup)
		{
			$t = array();
			$t['sup_num'] = $sup->getpps_supplier_num();
			$t['sup_name'] = $sup->getsup_name();
			$t['sup_last_price'] = $sup->getpps_last_price();
			$item['suppliers'][] = $t;
		}

		return $item;
	}
	
	/**
	 * Retourne la qte en attente d'etre livrée pour un produit
	 *
	 * @param unknown_type $product
	 */
	public function getWaitingForDeliverQty($product)
	{
		//définit la requete
		$sql = "
			select sum(pop_qty - pop_supplied_qty) 
			from ".mage::getModel('Purchase/Constant')->getTablePrefix()."purchase_order, ".mage::getModel('Purchase/Constant')->getTablePrefix()."purchase_order_product 
			where po_num  = pop_order_num
			and po_status = '".MDN_Purchase_Model_Order::STATUS_WAITING_FOR_DELIVERY."'
			and pop_product_id = ".$product->getId()."
				";
		
		//recupere le resultat
		$retour = mage::getResourceModel('sales/order_item_collection')->getConnection()->fetchOne($sql);
		
		if ($retour == '')
			$retour = 0;
		
		return $retour;
	}

	/**
	 * Calcul le prix de revient d'un produit
	 *
	 * @param unknown_type $product
	 */
	public function ComputeProductCost($product)
	{
		$retour = 0 ;
		
		//recupere tous les achats de moins de 15 jours
		$collection = mage::getModel('Purchase/OrderProduct')
			->getCollection()
			->join('Purchase/Order','po_num=pop_order_num')
			->addFieldToFilter('pop_product_id', $product->getId())
			->addFieldToFilter('pop_supplied_qty', array('gt'=> 0))
			->addFieldToFilter('po_status', MDN_Purchase_Model_Order::STATUS_COMPLETE)
			->addFieldToFilter('po_date', array('gt'=> date('Y-m-d')))	
			;
		
		//Si on a des enregistrements, on se base sur eux pour le calcul
		if (sizeof($collection) > 0)
		{
			$sum = 0;
			$nb = 0;
			foreach($collection as $item)
			{
				$sum += $item->getUnitPriceWithExtendedCosts();
				$nb += 1;
			}
			$retour = $sum / $nb;
		}
		else 
		{
			//Si pas de commande de moins de 15 jours, on prends la derniere
			$collection = mage::getModel('Purchase/OrderProduct')
				->getCollection()
				->join('Purchase/Order','po_num=pop_order_num')
				->addFieldToFilter('pop_product_id', $product->getId())
				->addFieldToFilter('po_status', MDN_Purchase_Model_Order::STATUS_COMPLETE)
				->addFieldToFilter('pop_supplied_qty', array('gt'=> 0))
				->setOrder('po_date', 'desc');
				
			foreach($collection as $item)
			{
				$retour = $item->getUnitPriceWithExtendedCosts();
				break;
			}
		}
		
		return $retour;
	}
	
	/**
	 * Return ids for product that must be in supply needs
	 *
	 */
	public function getCandidateProductIds()
	{
		//recupere les valeurs par défaut pour le store
		$DefaultManageStock = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');
		if ($DefaultManageStock == '')
			$DefaultManageStock = 1;
		$DefaultNotifyStockQty = Mage::getStoreConfig('cataloginventory/item_options/notify_stock_qty');
		if ($DefaultNotifyStockQty == '')
			$DefaultNotifyStockQty = 0;
		
		//retrieve concerned products
		$sql = "
					select 
							".mage::getModel('Purchase/Constant')->getTablePrefix()."catalog_product_entity.entity_id product_id,
							".mage::getModel('Purchase/Constant')->getTablePrefix()."cataloginventory_stock_item.qty qty,
							".mage::getModel('Purchase/Constant')->getTablePrefix()."cataloginventory_stock_item.notify_stock_qty notify_stock_qty,
							".mage::getModel('Purchase/Constant')->getTablePrefix()."cataloginventory_stock_item.use_config_notify_stock_qty use_config_notify_stock_qty,
							".mage::getModel('Purchase/Constant')->getTablePrefix()."cataloginventory_stock_item.manage_stock manage_stock,
							".mage::getModel('Purchase/Constant')->getTablePrefix()."cataloginventory_stock_item.use_config_manage_stock use_config_manage_stock,
							tbl_ordered_qty.value ordered_qty
							
					from 	".mage::getModel('Purchase/Constant')->getTablePrefix()."catalog_product_entity,
							".mage::getModel('Purchase/Constant')->getTablePrefix()."cataloginventory_stock_item,
							".mage::getModel('Purchase/Constant')->getTablePrefix()."catalog_product_entity_int tbl_status,
							".mage::getModel('Purchase/Constant')->getTablePrefix()."catalog_product_entity_int tbl_ordered_qty
					where 
							".mage::getModel('Purchase/Constant')->getTablePrefix()."catalog_product_entity.entity_id = ".mage::getModel('Purchase/Constant')->getTablePrefix()."cataloginventory_stock_item.product_id
							and ".mage::getModel('Purchase/Constant')->getTablePrefix()."cataloginventory_stock_item.stock_id = 1
							and tbl_status.entity_id = ".mage::getModel('Purchase/Constant')->getTablePrefix()."catalog_product_entity.entity_id
							and tbl_status.attribute_id = ".mage::getModel('Purchase/Constant')->GetProductStatusAttributeId()."
							and tbl_status.value = 1
							and tbl_status.store_id = 0
							and tbl_ordered_qty.entity_id = ".mage::getModel('Purchase/Constant')->getTablePrefix()."catalog_product_entity.entity_id
							and tbl_ordered_qty.attribute_id = ".mage::getModel('Purchase/Constant')->GetProductOrderedQtyAttributeId()."
							and tbl_ordered_qty.store_id = 0
							and ".mage::getModel('Purchase/Constant')->getTablePrefix()."catalog_product_entity.exclude_from_supply_needs = 0
				";	
		$data = mage::getResourceModel('sales/order_item_collection')->getConnection()->fetchAll($sql);
		return $data;

	}
	
	/**
	 * 
	 *
	 * @param unknown_type $productId
	 */
	public function refreshSupplyNeedsForProduct($productId)
	{
		
		//delete product from supply needs
		$this->deleteProductFromSupplyNeeds($productId);
		
		//collect default values
		$DefaultManageStock = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');
		if ($DefaultManageStock == '')
			$DefaultManageStock = 1;
		$DefaultNotifyStockQty = Mage::getStoreConfig('cataloginventory/item_options/notify_stock_qty');
		if ($DefaultNotifyStockQty == '')
			$DefaultNotifyStockQty = 0;
		
		//collect information about product stock
		$product = mage::getModel('catalog/product')->load($productId);
		//if product doesn't exists, leave function
		if (!$product->getId())
			return false;
		$stockObject = $product->getStockItem();
		$stockQty = $stockObject->getQty();
		$manageStock = $stockObject->getManageStock();
		if ($stockObject->getuse_config_notify_stock_qty())
			$stockMini = $DefaultNotifyStockQty;
		else
			$stockMini = $stockObject->getnotify_stock_qty();
		$orderedQty = $product->getordered_qty();
		$waitingForDeliveryQty = $this->getWaitingForDeliverQty($product);
		$excludeFromSupplyNeeds = $product->getexclude_from_supply_needs();
		$productTypeManageStock = Mage::helper('catalogInventory')->isQty($product->getTypeId());
		
		//define if product belongs to supply needs
		$mustBelongToSupplyNeeds = false;
		if (($manageStock == 1) && (!$excludeFromSupplyNeeds) && ($productTypeManageStock))
		{
				if ((int)$stockMini > ((int)$stockQty - (int)$orderedQty) + (int)$product->getmanual_supply_need_qty())
					$mustBelongToSupplyNeeds = true;
				if ((int)$stockQty < ((int)$orderedQty + (int)$product->getmanual_supply_need_qty()))
					$mustBelongToSupplyNeeds = true;
		}
				
		//insert in supply needs (if concerned)
		if ($mustBelongToSupplyNeeds)
		{
			//retrieve information
			$item = $this->getSupplyNeedsForProduct($product);

			//fill description
			$details = mage::helper('purchase')->__('Current stock').' : '.(int)$item['stock']->getQty();
			$details .= "\n".mage::helper('purchase')->__('Stock Mini').' : '.(int)$item['stock']->getnotify_stock_qty();
			$details .= "\n".mage::helper('purchase')->__('Pending orders Qty').' : '.$item['ordered_qty'];
			$details .= "\n".mage::helper('purchase')->__('Qty to be delivered').' : '.$item['waiting_for_deliver_qty'];
			$details .= "\n".mage::helper('purchase')->__('Manual supply need').' : '.$item['manual_qty'];
			$details .= "\n".mage::helper('purchase')->__('Needed Qty').' : '.$item['needed_qty'];
			$details .= "\n".$item['details'];	
			
			
			//Définit les fournisseurs
			$SuppliersName = "";
			$SuppliersIds = "";
			$BestSupplierUnderlined = false;
			for ($j = 0;$j<count($item['suppliers']);$j++)
			{
				if (!$BestSupplierUnderlined && $item['suppliers'][$j]['sup_last_price'] > 0)
					$SuppliersName .= '<u><b>';
				$SuppliersName .= $item['suppliers'][$j]['sup_name'];
				if ($item['suppliers'][$j]['sup_last_price'] > 0)
					$SuppliersName .= ' ('.$item['suppliers'][$j]['sup_last_price'].')';
				if (!$BestSupplierUnderlined && $item['suppliers'][$j]['sup_last_price'] > 0)
				{
					$BestSupplierUnderlined = true;
					$SuppliersName .= '</b></u>';
				}
				$SuppliersName .= ', ';
				$SuppliersIds .= ','.$item['suppliers'][$j]['sup_num'].', ';
			}
			
			//misc
			if ($item['dead_line'] == null)
			{
				$item['dead_line'] = '2099/12/31';
				$item['purchase_dead_line'] = '2099/12/31';
			}

			//insert
			mage::getModel('Purchase/SupplyNeeds')
				->setsn_product_id($product->getId())
				->setsn_product_sku($product->getSku())
				->setsn_manufacturer_id($product->getManufacturer())
				->setsn_manufacturer_name($product->getAttributeText('manufacturer'))
				->setsn_product_name($product->getName())
				->setsn_status($item['status'])
				->setsn_needed_qty($item['needed_qty'])
				->setsn_details($details)
				->setsn_deadline($item['dead_line'])
				->setsn_is_critical($item['is_critical'])
				->setsn_purchase_deadline($item['purchase_dead_line'])
				->setsn_suppliers_ids($SuppliersIds)
				->setsn_suppliers_name($SuppliersName)
				->setsn_is_warning($item['is_warning'])
				->setsn_priority($this->calculateSupplyNeedPriority($item))
				->save();
		}
		else 
		{
			//if product mustn't be in supply needs and get manual supply needs, we reset manual supply needs
			if (Mage::getStoreConfig('purchase/supplyneeds/automatically_delete_manual_supply_need') == 1)
			{
				if ((int)$product->getmanual_supply_need_qty() > 0)
				{
					$product->setmanual_supply_need_qty(0)
							->setmanual_supply_need_comments('')
							->setmanual_supply_need_date(null)
							->save();
				}
			}
		}
	}

	/**
	 * Delete product from supply needs
	 *
	 * @param unknown_type $productId
	 */
	public function deleteProductFromSupplyNeeds($productId)
	{
		$sn = mage::getModel('Purchase/SupplyNeeds')->load($productId, 'sn_product_id');	
		if ($sn->getId())
			$sn->delete();
	}

	/**
	 * Define if a supply need is critical
	 *
	 * @param unknown_type $product
	 * @param unknown_type $supplyNeed
	 * @param unknown_type $pendingOrders
	 */
	public function IsCritical($product, $supplyNeed, $pendingOrders)
	{
		//supply need is critical if only this product is missing in an order
		if (($pendingOrders != null) && (Mage::getStoreConfig('purchase/supplyneeds/is_critical_if_order_only_missing_product') == 1))
		{
			//parse orders
			foreach ($pendingOrders as $order)
			{
				//parse order's products
				$otherProductsAreMissing = false;
				foreach($order->getAllItems() as $OrderItem)
				{
					if ($OrderItem->getproduct_id() != $product->getId())
					{
						$remainingQty = ($OrderItem->getqty_ordered() - $OrderItem->getRealShippedQty() - $OrderItem->getreserved_qty());
						if ($remainingQty < 0)
							$otherProductsAreMissing = true;
					}
				}
				if (!$otherProductsAreMissing)
					return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Define supply need priority
	 *
	 */
	public function calculateSupplyNeedPriority($item)
	{
		$retour = 0;
		if (($item['dead_line'] != null) && ($item['dead_line'] != '2099/12/31'))
			$retour = strtotime($item['dead_line']);
		else 
		{
			switch($item['status'])
			{
				case MDN_Purchase_Model_SupplyNeeds::_StatusApproComm:
					$retour = 6000000000;
					break;
				case MDN_Purchase_Model_SupplyNeeds::_StatusManualSupplyNeed :
					$retour = 7000000000;
					break;
				case MDN_Purchase_Model_SupplyNeeds::_StatusQtyMini :
					$retour = 8000000000;
					break;
				case MDN_Purchase_Model_SupplyNeeds::_StatusQtyMiniStock0 :
					$retour = 9000000000;
					break;
				default:
					$retour = 10000000000;
					break;					
			}
			
			//if is critical, modify priority
			if ($item['is_critical'])
				$retour -= 500;
		}
		return $retour;
	}
	
}