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
class MDN_Purchase_Block_Misc_Errors extends Mage_Adminhtml_Block_Widget_Form
{
	
	/**
	 * Retourne la liste des erreurs
	 *
	 */
	public function getList()
	{
		$retour = array();
		$ProductStockModel = mage::getModel('Purchase/Productstock');
		
		//Recupere la liste des produits
		$sql = '
				select 
					product_id,
					qty,
					tbl_ordered.value ordered_qty,
					tbl_reserved.value reserved_qty,
					tbl_name.value name
				from 
					'.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity,
					'.mage::getModel('Purchase/Constant')->getTablePrefix().'cataloginventory_stock_item,
					'.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity_int tbl_ordered,
					'.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity_int tbl_reserved,
					'.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity_varchar tbl_name
				where
					'.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity.entity_id = '.mage::getModel('Purchase/Constant')->getTablePrefix().'cataloginventory_stock_item.product_id
					and '.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity.entity_id = tbl_ordered.entity_id
					and '.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity.entity_id = tbl_reserved.entity_id
					and tbl_reserved.attribute_id = '.mage::getModel('Purchase/Constant')->GetProductReservedQtyAttributeId().'
					and tbl_ordered.attribute_id = '.mage::getModel('Purchase/Constant')->GetProductOrderedQtyAttributeId().'
					and tbl_name.entity_id = tbl_ordered.entity_id
					and tbl_name.attribute_id = '.mage::getModel('Purchase/Constant')->GetProductNameAttributeId().'
					and '.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity.type_id = \'simple\'
				order by 
					'.mage::getModel('Purchase/Constant')->getTablePrefix().'catalog_product_entity.entity_id
				';
		$data = mage::getResourceModel('sales/order_item_collection')->getConnection()->fetchAll($sql);	
		for ($i=0;$i<count($data);$i++)
		{
			$AddProduct = false;
			$StockProblem = false;
			$OrderedProblem = false;
			$ReservedProblem = false;
			$productId = $data[$i]['product_id'];
			
			//Recupere le stock calcué via les mouvements de stock
			$CalculatedStock = (int)$ProductStockModel->ComputeProductStock($productId);
			if ($CalculatedStock != $data[$i]['qty'])
			{
				$AddProduct = true;
				$StockProblem = true;
			}
						
			//Recupere le stock calcué via les mouvements de stock
			$OrderedQty = (int)$ProductStockModel->GetOrderedQty($productId);
			if ($OrderedQty != $data[$i]['ordered_qty'])
			{
				$AddProduct = true;
				$OrderedProblem = true;
			}
									
			//Recupere le stock calcué via les mouvements de stock
			$ReservedQty = (int)mage::helper('purchase/ProductReservation')->GetReservedQty($productId);
			if ($ReservedQty != $data[$i]['reserved_qty'])
			{
				$AddProduct = true;
				$ReservedProblem = true;
			}
			
			if ($ReservedQty > $CalculatedStock)
				$AddProduct = true;
			
			if ($ReservedQty > $OrderedQty)
				$AddProduct = true;
				
			//Si le produit a un pb
			if ($AddProduct)
			{
				$item = array();
				$item['product_name'] = '<a href="'.$this->getUrl('Purchase/Products/Edit', array('product_id' => $productId)).'">'.$data[$i]['name'].' ('.$productId.')</a>';
				$item['ordered_qty'] = '';
				$item['reserved_qty'] = '';
				$item['product_id'] = $productId;
				$item['other'] = '';
				$item['stock'] = '';
				if ($StockProblem)
					$item['stock'] = '<font color="red">Error ('.((int)$data[$i]['qty']).' / '.$CalculatedStock.')</font>';
				else 
					$item['stock'] = $CalculatedStock;
				if ($OrderedProblem)
					$item['ordered_qty'] = '<font color="red">Error ('.((int)$data[$i]['ordered_qty']).' / '.$OrderedQty.')</font>';
				else 
					$item['ordered_qty'] = $OrderedQty;
				if ($ReservedProblem)
					$item['reserved_qty'] = '<font color="red">Error ('.((int)$data[$i]['reserved_qty']).' / '.$ReservedQty.')</font>';
				else 
					$item['reserved_qty'] = $ReservedQty;
				if ($ReservedQty > $CalculatedStock)
					$item['other'] .= '<font color="red">Reserved qty > stock</font>';
				if ($ReservedQty > $OrderedQty)
					$item['other'] .= '<font color="red">Reserved qty > ordered qty</font>';
					
				$retour[] = $item;
			}
		}
		
		//retourne
		return $retour;
	}

}