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
class MDN_Purchase_Model_StockMovement  extends Mage_Core_Model_Abstract
{
	
	/*****************************************************************************************************************************
	* ***************************************************************************************************************************
	* Constructeur
	*
	*/
	public function _construct()
	{
		parent::_construct();
		$this->_init('Purchase/stockmovement');
	}
	
	/**
	 * Charge par produit
	 *
	 * @param unknown_type $ProductId
	 */
	public function loadByProduct($ProductId)
	{
		//charge la liste pour le produit
		$collection = $this->getCollection()->addFilter('sm_product_id', $ProductId);
		return $collection;
	}
	
	/**
	 * Retourne les types possibles pour un mouvement de stock
	 *
	 */
	public function GetTypes()
	{
		$retour = array();
		$retour['supply'] = 'supply';
		$retour['order'] = 'order';
		$retour['rma'] = 'rma';		
		$retour['donation'] = 'donation';		
		$retour['lost'] = 'lost/broken';		
		$retour['loan'] = 'loan';		
		$retour['return'] = 'Customer Return';
		$retour['adjustment'] = 'Adjustment';
		$retour['creditmemo'] = 'Creditmemo';
		
		return $retour;
	}
	
	/**
	 * Retourne le coef à partir d'un type
	 *
	 * @param unknown_type $type
	 * @return unknown
	 */
	public function GetTypeCoef($type)
	{
		$retour = 0;
		switch($type)
		{
			case 'supply':
				$retour = 1;
				break;
			case 'rma':
				$retour = -1;
				break;
			case 'order':
				$retour = -1;
				break;
			case 'donation':
				$retour = -1;
				break;
			case 'lost':
				$retour = -1;
				break;
			case 'loan':
				$retour = -1;
				break;
			case 'return':
				$retour = 1;
				break;
			case 'adjustment':
				$retour = 1;
				break;
			case 'creditmemo':
				$retour = 1;
				break;
		}
		return $retour;
	}
	
	/**
	 * Calcul le stock pour un produit
	 *
	 * @param unknown_type $ProductId
	 */
	public function ComputeProductStock($ProductId)
	{
		
		//recupere la somme
		$qty = (int)mage::getModel('Purchase/Productstock')->ComputeProductStock($ProductId);
		
		
		//met a jour le stock du produit
		if (Mage::getModel('cataloginventory/stock_item')->loadByProduct($ProductId)->getManageStock())
		{
			//Charge le produit
			$ProductStock =  Mage::getModel('cataloginventory/stock_item')->loadByProduct($ProductId);
			$OldProductStockQty = $ProductStock->getqty();				
					
			//Sav
			$ProductStock->setqty($qty);
			//Si la qte en stock est positive, on repasse le produit en in_stock
			if ($qty > 0)
				$ProductStock->setis_in_stock(1);			
			$ProductStock->save();

	       	//recupere le produit correspondant
        	$product = mage::getModel('catalog/product')->load($ProductId);
        	
        	//met a jour les qte
        	$model = mage::getModel('Purchase/Productstock');
        	$model->UpdateOrderedQty($product);
        	mage::helper('purchase/ProductReservation')->UpdateReservedQty($product);
					
			//Gere les réservations
			mage::helper('purchase/ProductReservation')->reserveProductForOrders($ProductId);
			
		}
		
	}
	
	/**
	 * Plan product stock calculation & reservation after creation
	 *
	 */
    protected function _afterSave()
    {
			mage::helper('BackgroundTask')->AddTask('Update stock for product '.$this->getsm_product_id(), 
							'purchase',
							'UpdateProductStock',
							$this->getsm_product_id()
							);	

    }

    /**
     * after delete
     *
     */
    protected function _afterDelete()
    {
			mage::helper('BackgroundTask')->AddTask('Update stock for product '.$this->getsm_product_id(), 
							'purchase',
							'UpdateProductStock',
							$this->getsm_product_id()
							);	    	
    }

	
}