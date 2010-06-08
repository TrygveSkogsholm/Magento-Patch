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
class MDN_Purchase_Model_Order  extends Mage_Core_Model_Abstract
{
	private $_products = null;
	private $_currency = null;
	
	//Purchase order statuses
	const STATUS_NEW = 'new';
	const STATUS_INQUIRY = 'inquiry';
	const STATUS_WAITING_FOR_DELIVERY = 'waiting_for_delivery';
	const STATUS_COMPLETE = 'complete';
	
	/*****************************************************************************************************************************
	* ***************************************************************************************************************************
	* Constructeur
	*
	*/
	public function _construct()
	{
		parent::_construct();
		$this->_init('Purchase/Order');
	}
	
		
	/**
	 * Retourne la liste des produits de la commande
	 *
	 */
	public function getProducts()
	{
		if ($this->_products == null)
		{
			$this->_products = mage::getModel('Purchase/OrderProduct')
				->getCollection()
				->addFieldToFilter('pop_order_num', $this->getId())
		       	->join('catalog/product',
			           'entity_id=pop_product_id ');
		}			
		return $this->_products;
	}
	
	public function resetProducts()
	{
		$this->_products = null;
	}
	
	/**
	 * Retourne le total HT
	 *
	 */
	public function getTotalHt()
	{
		$retour = 0;
		foreach($this->getProducts() as $item)
		{
			$retour += round($item->getRowTotal(), 2);
		}
		
		//rajoute les frais
		$retour += round($this->getShippingAmountHt(), 2);
		$retour += round($this->getZollAmountHt(), 2);
		return $retour;
	}
	
	public function getTotalWithOutDuty()
	{
		$retour = 0;
		foreach($this->getProducts() as $item)
		{
			$retour += round($item->getRowTotal(), 2);
		}
		return $retour;
	}
	
	public function getShip()
	{
		$retour = 0;
		$retour += round($this->getShippingAmountTtc(), 2);
		$retour += round($this->getZollAmountTtc(), 2);
		return $retour;
	}
	
	/**
	 * Retourne le total TTC
	 *
	 */
	public function getTotalTtc()
	{
		$retour = 0;
		foreach($this->getProducts() as $item)
		{
			$retour += round($item->getRowTotalWithTaxes(), 2);
		}
		//rajoute les fdp
		$retour += round($this->getShippingAmountTtc(), 2);
		$retour += round($this->getZollAmountTtc(), 2);
		return $retour;
	}
	
	/**
	 * Retourne le montant des taxes
	 *
	 */
	public function getTaxAmount()
	{
		return $this->getTotalTtc() - $this->getTotalHt();
	}
	
	/**
	 * Retourne le montant des frais de port sans taxe
	 *
	 */
	public function getShippingAmountHt()
	{
		return $this->getpo_shipping_cost();
	}
	
	/**
	 * Retourne le montant des frais de port avec taxes
	 *
	 */
	public function getShippingAmountTtc()
	{
		$value = $this->getpo_shipping_cost() * (1 + $this->getpo_tax_rate() / 100);
		return round($value, 2);
	}
	
	/**
	 * Retourne le montant des frais de douane sans taxes
	 *
	 */
	public function getZollAmountHt()
	{
		return $this->getpo_zoll_cost();
	}
			
	/**
	 * Retourne le montant des frais de douane avec taxes
	 *
	 */
	public function getZollAmountTtc()
	{
		$value = $this->getpo_zoll_cost() * (1 + $this->getpo_tax_rate() / 100);
		return round($value, 2);
	}
	
	/**
	 * Retourne le fournisseur
	 *
	 */
	public function getSupplier()
	{
		$supplier = mage::getModel('Purchase/Supplier')->load($this->getpo_sup_num());
		return $supplier;
	}
		/**
	 * This retrives the ship to variable which is set on the order page tab "Ship to Address"
	 *
	 */
	public function getShipTo()
	{
		$Address = ($this->getpo_ship_to());
		return $Address;
	}
	
	public function getShipSpeed()
	{
		$Speed = ($this->getship_speed());
		return $Speed;
	}
	/**
	 * Retourne l'objet currency lié à la commande
	 *
	 */
	public function getCurrency()
	{
		if ($this->_currency == null)
		{
			$this->_currency = mage::getModel('directory/currency')->load($this->getpo_currency());
		}
		return $this->_currency;
	}
		
	/**
	 * Retourne l'objet currency en euro
	 *
	 */
	public function getEuroCurrency()
	{
		return mage::getModel('directory/currency')->load('EUR');
	}
	
	/**
	 * Cree & met a jour les associations entre fournisseur et produit
	 *
	 */
	public function updateProductSupplierAssociation()
	{
		//parcourt les produits
		foreach($this->getProducts() as $item)
		{
			//Verifie si l'association existe déja
			$ProductSupplier = null;
			$Collection = mage::getModel('Purchase/ProductSupplier')
				->getCollection()
				->addFieldToFilter('pps_product_id', $item->getpop_product_id())
				->addFieldToFilter('pps_supplier_num', $this->getpo_sup_num());
			
			//Si existe pas			
			if (sizeof($Collection) == 0)
			{
				$ProductSupplier = mage::getModel('Purchase/ProductSupplier');
				$ProductSupplier->setpps_product_id($item->getpop_product_id());
				$ProductSupplier->setpps_supplier_num($this->getpo_sup_num());
			}
			else 
			{
				//Si existe on recupere
				foreach($Collection as $item2)
				{
					$ProductSupplier = $item2;
					break;
				}
			}
			
			//met a jour (si date est inférieure a la date de notre commande)
			if ((strtotime($this->getpo_date()) >= strtotime($ProductSupplier->getpps_last_order_date())) || ($ProductSupplier->getpps_last_order_date() == null))
			{
				if ($item->getpop_supplier_ref() != '')
					$ProductSupplier->setpps_reference($item->getpop_supplier_ref());
				$ProductSupplier->setpps_last_order_date($this->getpo_date());
				$ProductSupplier->setpps_last_price($item->getUnitPriceWithExtendedCosts_base());
				$ProductSupplier->setpps_last_unit_price($item->getpop_price_ht());
			
				//save
				$ProductSupplier->save();
			}

		}		
	}
	
	/**
	 * Réparti les frais d'approche sur les lignes produit
	 *
	 */
	public function dispatchExtendedCosts()
	{
		//recupere le mode de repartition
		$RepartitionMode = Mage::getStoreConfig('purchase/purchase_order/cost_repartition_method');
		
		//calcul les montant HT
		$TotalProductHt = 0;
    	$TotalProductHt_base = 0;
    	$TotalQty = 0;
    	foreach ($this->getProducts() as $item)
    	{
   		
    		$TotalProductHt += $item->getRowTotalWithTaxes();
    		$TotalProductHt_base += $item->getRowTotal_base();
    		$TotalQty += $item->getpop_qty();
    	}
    	
    	//Si pas de valorisation...
    	if ($TotalProductHt == 0)
    		return;
    	
    	//Réparti les frais d'approche (selon la méthode retenue)
	    $TotalExtendedCosts = $this->getpo_shipping_cost() + $this->getpo_zoll_cost();
	    $TotalExtendedCosts_base = $this->getpo_shipping_cost_base() + $this->getpo_zoll_cost_base();
	    foreach ($this->getProducts() as $item)
    	{
    		$itemTotal = $item->getRowTotalWithTaxes();
    		switch ($RepartitionMode)
    		{
    			case 'by_qty':
		    		$item->setpop_extended_costs($TotalExtendedCosts / $TotalQty);
		    		$item->setpop_extended_costs_base($TotalExtendedCosts_base / $TotalQty);
		    		$item->save();
    				break;
    			case 'by_amount':
		    		$item->setpop_extended_costs(($itemTotal/$TotalProductHt)*$TotalExtendedCosts);
		    		$item->setpop_extended_costs_base($TotalExtendedCosts_base / $TotalProductHt_base * ($item->getpop_price_ht_base() + $item->getpop_eco_tax_base()));
		    		$item->save();
    				break;
    			default:
    				die('Repartition Cost Method not set');
    		}

    	}
	}
	
		
	/**
	 * Retourne les mouvements de stock liés à la commande
	 *
	 */
	public function getStockMovements()
	{
		$collection = mage::getModel('Purchase/StockMovement')
			->getCollection()
			->addFieldToFilter('sm_po_num', $this->getId());
		
		return $collection;
	}
	
	/**
	 * Genere le no de la commande
	 *
	 */
	public function GenerateOrderNumber()
	{
		//prefix
		$retour = date('Ymd').'BC';
		
		//Trouve le prochain
		for ($i=1;$i<30;$i++)
		{
			//définit le numéro
			$temp = $retour.$i;
			
			//Verifie si existe
			$collection = Mage::getModel('Purchase/Order')
				->getCollection()
				->addFieldToFilter('po_order_id', $temp);
			
			if (sizeof($collection) == 0)
				return $temp;
		}
		
		return $retour;
	}
	
	/**
	 * Méthode pour mettre a jour les dates prévisionnelles de réception des produits
	 *
	 */
	public function UpdateProductsDeliveryDate()
	{
		//plan update product delivery date for each product in order
	    foreach ($this->getProducts() as $item)
    	{
    		$productId = $item->getpop_product_id();
    		
    		//plan task
			mage::helper('BackgroundTask')->AddTask('Update product delivery date for product #'.$productId, 
				'purchase',
				'updateProductDeliveryDate',
				$productId
				);	 
    	}
		
	}
	    
    /**
     * Ajoute un produit a une commande
     *
     * @param unknown_type $ProductId
     * @param unknown_type $order
     */
    public function AddProduct($ProductId, $qty = 1)
    {
    	$purchaseOrderProduct = $this->getPurchaseOrderItem($ProductId);
    	
    	//if product is not present
    	if ($purchaseOrderProduct == null)
    	{
			$ProductSupplierModel = mage::getModel('Purchase/ProductSupplier');
		    $Product = mage::getModel('catalog/product')->load($ProductId);
	    	$ref = $this->getSupplier()->getProductReference($ProductId);
	    	$supplierId = $this->getpo_sup_num();
	    	
	    	$price = 0;
	    	if (Mage::getStoreConfig('purchase/purchase_order/auto_fill_price'))
		    	$price = $ProductSupplierModel->getProductForSupplier($ProductId, $supplierId);
			if (Mage::getStoreConfig('purchase/purchase_order/use_product_cost'))
		    	$price = $Product->getCost();
		    	
			mage::getModel('Purchase/OrderProduct')
				->setpop_order_num($this->getId())
				->setpop_product_id($ProductId)
				->setpop_product_name($Product->getname())
				->setpop_qty($qty)
				->setpop_supplier_ref($ref)
				->setpop_price_ht($price)
				->setpop_price_ht_base($price)
				->setpop_tax_rate($Product->getPurchaseTaxRate())
				->save();
    	}
    	else 
    	{
    		//if product already belong to the PO, increase qty
    		$purchaseOrderProduct->setpop_qty($purchaseOrderProduct->getpop_qty() + $qty)->save();
    	}
    }
    
    /**
     * Return a purchase order item for a product id
     *
     * @param unknown_type $productId
     */
    public function getPurchaseOrderItem($productId)
    {
    	foreach ($this->getProducts() as $product)
    	{
    		if ($product->getpop_product_id() == $productId)
    			return $product;
    	}
    	return null;
    }
    
    /**
     * return statuses
     *
     */
    public function getStatuses()
    {
		$retour = array();
		$retour[MDN_Purchase_Model_Order::STATUS_NEW] = mage::helper('purchase')->__('New');
		$retour[MDN_Purchase_Model_Order::STATUS_INQUIRY] = mage::helper('purchase')->__('Inquiry');
		$retour[MDN_Purchase_Model_Order::STATUS_WAITING_FOR_DELIVERY] = mage::helper('purchase')->__('Waiting for delivery');
		$retour[MDN_Purchase_Model_Order::STATUS_COMPLETE] = mage::helper('purchase')->__('Complete');
		return $retour;	
    }

    /**
     * Compute deliveries progress
     *
     */
    public function computeDeliveryProgress()
    {
    	$progress = 0;
    	$qtyCount = 0;
    	$deliveredCount = 0;
    	
    	foreach($this->getProducts() as $item)
    	{
    		$qtyCount += $item->getpop_qty();
    		$deliveredCount += $item->getpop_supplied_qty();
    	}
    	if ($qtyCount > 0)
	    	$progress = $deliveredCount / $qtyCount * 100;
    	$this->setpo_delivery_percent($progress)->save();
    }
    
    /**
     * Send order per email to supplier
     *
     */
    public function notifySupplier($msg)
    {
    	//retrieve information
    	$email = $this->getSupplier()->getsup_mail();
    	if ($email == '')
    		return false;
    	$cc = Mage::getStoreConfig('purchase/notify_supplier/cc_to');
    	$identity = Mage::getStoreConfig('purchase/notify_supplier/email_identity');
    	$emailTemplate = Mage::getStoreConfig('purchase/notify_supplier/email_template');
    	
    	if ($emailTemplate == '')
    		die('Email template is not set (system > config > purchase)');
    	
    	//get pdf
    	$Attachment = null;
		$pdf = Mage::getModel('Purchase/Pdf_Order')->getPdf(array($this));
    	$Attachment = array();
    	$Attachment['name'] = mage::helper('purchase')->__('Purchase Order #').$this->getpo_order_id().'.pdf';
    	$Attachment['content'] = $pdf->render();
    	
    	//definies datas
	    $data = array
	    	(
	    		'company_name'=>Mage::getStoreConfig('purchase/notify_supplier/company_name'),
	    		'message'=>$msg
	    	);
    	
    	//send email
    	$translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
    	Mage::getModel('core/email_template')
            ->setDesignConfig(array('area'=>'adminhtml'))
            ->sendTransactional(
                $emailTemplate,
                $identity,
                $email,
                '',
                $data,
                null,
                $Attachment);
                
        //send email to cc
        if ($cc != '')
        {
        	Mage::getModel('core/email_template')
	            ->setDesignConfig(array('area'=>'adminhtml'))
	            ->sendTransactional(
	                $emailTemplate,
	                $identity,
	                $cc,
	                '',
	                $data,
	                null,
	                $Attachment);
        }
        
        $translate->setTranslateInline(true);
        $this->setpo_supplier_notification_date(date('y-m-d H:i'))->save();
        
        return true;
    }
    
    /**
     * Return true if all products are delivered
     *
     */
    public function isCompletelyDelivered()
    {
    	$totalQty = 0;
    	
    	foreach($this->getProducts() as $item)
    	{
    		if ($item->getpop_qty() > $item->getpop_supplied_qty())
    			return false;
    		$totalQty += $item->getpop_qty();
    	}
    	
    	if ($totalQty > 0)
	    	return true;
	    else 
	    	return false;
    }
    
    protected function _afterSave()
    {
	    parent::_afterSave();
	    	
	    //update supply needs    	
    	if ($this->getpo_status() == MDN_Purchase_Model_Order::STATUS_WAITING_FOR_DELIVERY)
    	{    	
    		foreach ($this->getProducts() as $item)
    		{
    			$productId = $item->getpop_product_id();
		    	Mage::dispatchEvent('purchase_update_supply_needs_for_product', array('product_id'=>$productId));
    		}
    	}
    }
    
   
    /**
     * Method to check if product prices are missing
     *
     */
    public function hasMissingPrices()
    {
    	foreach($this->getProducts() as $item)
    	{
    		if ($item->getpop_price_ht() == 0)
    		return true;
    	}
    	
    	return false;
    }
}