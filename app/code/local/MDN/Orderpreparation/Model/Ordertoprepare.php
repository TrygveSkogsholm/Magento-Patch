<?php
/**
 * Classe service pour la préparation des commandes
 *
 */


class MDN_Orderpreparation_Model_OrderToPrepare  extends Mage_Core_Model_Abstract
{
	
	private $_SelectedOrders = null;
	
	private $_FullStockOrdersFromCache = null;
	private $_StockLessOrdersFromCache = null;
	private $_IgnoredOrdersFromCache = null;
		
	private $_SelectedOrdersIds = null;
	
	/*
	* Constructeur
	*
	*/
	public function _construct()
	{
		parent::_construct();
		$this->_init('Orderpreparation/ordertoprepare');
	}
	
	/*
	 * Retourne les ids des commandes sélectionnées
	 *
	 * @return unknown
	 */
	public function getSelectedOrdersIds()
	{
		//Si liste vide, on charge
		if ($this->_SelectedOrdersIds == null)
		{
			$this->_SelectedOrdersIds = array();
			$this->_SelectedOrdersIds[] = 0;
			foreach ($this->getCollection() as $order)
			{
				$this->_SelectedOrdersIds[] = $order->getorder_id();
			}	
		}
		return $this->_SelectedOrdersIds;
	}
	
	
	/**
	 * Obtenir la liste des commandes sélectionnées
	 *
	 * @return unknown
	 */
	public function getSelectedOrders()
	{
		//si collection pas déja chargée, on le fait
		if ($this->_SelectedOrders == null)
		{
			//////////////////////////////////////////////////////////////////////
			//charge la liste des commandes sélectionnées
			$list_selected = $this->getSelectedOrdersIds();	
			$this->_SelectedOrders = Mage::getResourceModel('sales/order_collection')
		        ->addAttributeToSelect('shipping_method')
		        ->addAttributeToSelect('shipping_description')
		        ->addFieldToFilter('entity_id', array('in'=>$list_selected))			//on ne prend en compte les commandes sélectionnées
		        ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id', null, 'left')
		        ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id', null, 'left')
		        ->joinAttribute('shipping_company', 'order_address/company', 'shipping_address_id', null, 'left')
		        ->addExpressionAttributeToSelect('shipping_name',
		            'CONCAT({{shipping_firstname}}, " ", {{shipping_lastname}}, " (", {{shipping_company}}, ")")',
		            array('shipping_firstname', 'shipping_lastname', 'shipping_company'))
		        ->joinTable(
		        	mage::getModel('Purchase/Constant')->getTablePrefix().'order_to_prepare',
		        	'order_id=entity_id',
					array(
							'order_to_prepare_id' => 'id',
			                'order_id' => 'order_id',
			                'real_weight' => 'real_weight',
			                'ship_mode' => 'ship_mode',
			                'package_count' => 'package_count',
			                'details' => 'details'
			             )
			        );
		}
		return $this->_SelectedOrders;
	}

	/**
	 * Retourne la liste des full stock orders a partir du cache
	 *
	 */
	public function getFullStockOrdersFromCache()
	{
		if ($this->_FullStockOrdersFromCache == null)
		{
			$this->_FullStockOrdersFromCache = mage::getModel('Orderpreparation/ordertopreparepending')
	    		->getCollection()
	    		->addFieldToFilter('opp_type', 'fullstock');
		}
		return $this->_FullStockOrdersFromCache;
	}
	
	/**
	 * Return ignored orders
	 *
	 */
	public function getIgnoredOrdersFromCache()
	{
		if ($this->_IgnoredOrdersFromCache == null)
		{
			$this->_IgnoredOrdersFromCache = mage::getModel('Orderpreparation/ordertopreparepending')
	    		->getCollection()
	    		->addFieldToFilter('opp_type', 'ignored');
		}
		return $this->_IgnoredOrdersFromCache;
	}
	
	/**
	 * Retourne la liste des full stock orders a partir du cache
	 *
	 */
	public function getStockLessOrdersFromCache()
	{
		if ($this->_StockLessOrdersFromCache == null)
		{
			$this->_StockLessOrdersFromCache = mage::getModel('Orderpreparation/ordertopreparepending')
	    		->getCollection()
	    		->addFieldToFilter('opp_type', 'stockless');
		}
		return $this->_StockLessOrdersFromCache;
	}
	
	/**
	 * Ajouter une commande à la liste des commandes sélectionnées
	 *
	 * @param unknown_type $order
	 */
	public function AddSelectedOrder($orderId)
	{
		//si ya un pb
		if (!$this->CanAddOrder($orderId))
		{
			return false;	
		}
		
		//charge la commande
		$order = Mage::getModel('sales/order')->load($orderId);
		
		//ajoute la commande
		$OrderItem = Mage::getModel('Orderpreparation/ordertoprepare')
			->setorder_id($orderId);
		
		//Rajoute les details sur la commande
		$OrderItem->setdetails($this->getDetailsForOrder($order));
			
		//regarde si ya déja une facture associée à la commande
		$invoices = $order->getInvoiceCollection();
		if (sizeof($invoices) > 0)
		{
			foreach($invoices as $invoice)
			{
				$OrderItem->setinvoice_id($invoice->getincrement_id());
			}
		}
		
		
		//ajoute les produits
		$NbAddedProducts = 0;
		$IsConfigArray = array();
		$addedProducts = array();
    	foreach ($order->getItemsCollection() as $item)
    	{
    		//echo "<br>item:".$item->getName();
    		//si il reste une qte de ce produit à livrer
    		$remaining_qty = ($item->getqty_ordered() - $item->getRealShippedQty());
    		//echo "<br>remaingin_qty: ".$remaining_qty;
    		if ($remaining_qty > 0)
    		{
    			//recupere le produit
    			$productid = $item->getproduct_id();
    			$product = mage::getModel('catalog/product')->load($productid);
    			
    			//si le produit gere le stock
    			if (Mage::getModel('cataloginventory/stock_item')->loadByProduct($productid)->getManageStock())
    			{
    				//echo "<br>gere stock: ";
    				//si pas réservé, on ajoute la qte a hauteur de ce que l'on a de disponible
    				if ($item->getreserved_qty() == 0)
    				{
    					//echo "<br>pas reservé: ";
		    			$productStock = $product->GetAvailableQty();
		    			if ($remaining_qty > $productStock)
		    				$remaining_qty = $productStock;    					
    				}
    				else 
    				{
    					if ($item->getreserved_qty() < $remaining_qty)
    						$remaining_qty = $item->getreserved_qty();
    				}
    			}
    			
    			//if product is bundle, set remaining_qty to 1
    			//if ($item->getproduct_type() == 'bundle')
    			//	$remaining_qty = 1;
    			    			
    			//insert dans la base (avec la qté expédiable, cad qu'il peut y avoir une qte restante à livrer...)
    			if ($remaining_qty > 0)
    			{
	    			$SubItem = Mage::getModel('Orderpreparation/ordertoprepareitem')
	    				->setorder_id($orderId)
	    				->setproduct_id($productid)
	    				->setqty($remaining_qty)
	    				->setqty_custom(($item->isShipSeparately()?1:0))
	    				->setorder_item_id($item->getId())
	    				->save();
	    			    				
	    			//Fill added product array (to compute weight)
	    			$addedProducts[] = array('product_id' => $productid, 'qty' => $remaining_qty);
    			}
    		}
    	}
    	
    			
		//Compute order weight
		$model = mage::getModel('Orderpreparation/OrderWeightCalculation');
		$weight = $model->calculateOrderWeight($addedProducts);	
		$OrderItem->setreal_weight($weight);
		
		//store payment validated and shipping method
		$shippingMethod = $order->getshipping_description();
		$OrderItem->setcarrier($shippingMethod);
		$OrderItem->save();
    	
    	//Supprime la commande du cache
    	$this->removeOrderFromOrderToPreparePending($order);
    	
    	return true;   	

	}
	
	/**
	 * Méthode permettant de savoir si une commande peut etre ajoutée pour une préparation
	 *
	 * @param unknown_type $orderId
	 */
	public function CanAddOrder($orderId)
	{
		$debug = '';
		
		//parcourt les produits
		$NbAddedProducts = 0;
		
		$order = Mage::getModel('sales/order')->load($orderId);
    	foreach ($order->getItemsCollection() as $item)
    	{
    		$productid = $item->getproduct_id();
    		$debug .= '<br>Product '.$productid;
    		
    		//si il reste une qte de ce produit à livrer et qu'il gere les stocks
    		$remaining_qty = ($item->getqty_ordered() - $item->getRealShippedQty());
    		$debug .= ' - remaining_qty '.$remaining_qty;
    		$ManageStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productid)->getManageStock();
    		if ($ManageStock)
    		{
	    		if (($remaining_qty > 0))
	    		{   			
	    			//recupere la qte de ce produit déja ajouté dans la préparation de commande
	    			$AlreadyAddedQty = $this->GetTotalAddedQtyForProduct($productid);
	    			
	    			//si produit réservé, on valide direct
	    			if ($item->getreserved_qty() > 0)
	    			{
	    				$NbAddedProducts += 1;
	    			}
	    			else 
	    			{
	    				//si le stock est suffisant
	    				$product = mage::getModel('catalog/product')->load($productid);
			    		$stock = $product->GetAvailableQty();
			    		if ($stock < ($remaining_qty + $AlreadyAddedQty))
			    		{
			    			Mage::getSingleton('adminhtml/session')->addError(mage::Helper('Orderpreparation')->__('Order ').$order->getincrement_id().': '.$item->getname().' '.mage::Helper('Orderpreparation')->__('not added'));
			    			//return false;
			    		}
			    		else 
			    			$NbAddedProducts += 1;
	    			}
	    		}
    		}
    		else 
    			$NbAddedProducts += 1;
    	}
    	
    	if ($NbAddedProducts == 0)
    		return false;
    		
		return true;
		
	}
		
	/**
	 * Supprime une commande de la liste des commandes sélectionnées
	 *
	 * @param unknown_type $order
	 */
	public function RemoveSelectedOrder($orderId)
	{

		//Supprime la commande
		Mage::getModel('Orderpreparation/ordertoprepare')->load($orderId, 'order_id')->delete();
		
		//la réinsere dans la bonne catégorie (fullstock, stockless ou aucune)
		$order = mage::getModel('sales/order')->load($orderId);
		$this->DispatchOrder($order);
	}
	
	/**
	 * Delete order to prepare items
	 *
	 */
	protected function _afterDelete()
	{
		//supprime les lignes 'item'
		$collection = Mage::getModel('Orderpreparation/ordertoprepareitem')
			->getCollection()
			->addFieldToFilter('order_id', $this->getorder_id());
			
		foreach($collection as $item)
		{
			$item->delete();
		}
	}
	
	/*
	 * Retourne la commande associée à cet order_to_prepare
	 *
	 */
	public function GetOrder()
	{
		$order = Mage::getModel('sales/order')->load($this->getorder_id());
		return $order;
	}
	
	/*
	 * Retourne la liste des produits nécessaire à la préparation des commandes
	 * Retourne une tableau associatif donc cle = product_id et valeur = qty
	 *
	 */
	public function GetProductsSummary()
	{
		$retour = array();
		
		$collection = Mage::getModel('Orderpreparation/ordertoprepareitem')
			->getCollection();
		foreach ($collection as $item)
		{
			//si le parent du produit est un bundle is_config, on ne l'affiche pas
			if ($item->getis_config() == 0)
			{
				//recupere les infos
				$product_id = $item->getproduct_id();
				$product = mage::getmodel('catalog/product')->load($product_id);
				$qty = $item->getqty();
				
				//si le produit est un virtual, on ne l'ajoute pas
				if (Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id)->getManageStock())
				{
					if (isset($retour[$product_id]))
						$retour[$product_id]->setqty_to_prepare($retour[$product_id]->getqty_to_prepare() + $qty);
					else 
					{
						$product->setqty_to_prepare($qty);
						$retour[$product_id] = $product;
					}
				}
			}
		}
		
		//tri la liste
		usort($retour,  array ("MDN_Orderpreparation_Model_OrderToPrepare", "sortProductPerManufacturer"));
		
		return $retour;
	}
	      
    /**
     * Méthode pour trier les produits par manufacturer
     *
     */
    public static  function sortProductPerManufacturer($a, $b)
    {
    	if ($a->getAttributeText('manufacturer') < $b->getAttributeText('manufacturer'))
    		return -1;
    	else 
    		return 1;   	
    }
	
	/*
	 * Retourne la qté totale ajoutée dans la préparation de produit pour un produit donné
	 *
	 * @param unknown_type $ProductId
	 */
	public function GetTotalAddedQtyForProduct($ProductId)
	{
		$collection = Mage::getModel('Orderpreparation/ordertoprepareitem')
			->getCollection()
	        ->addFieldToFilter('product_id', $ProductId);
	        
	    $retour = 0;
	    foreach ($collection as $item)
	    {
	    	$retour += $item->getqty();
	    }
	    
	    return $retour;
	}
	
	
	/**
	 * Retourne les éléments à envoyer pour une commande
	 *
	 * @param unknown_type $OrderId
	 */
	public function GetItemsToShip($OrderId)
	{
		$collection = Mage::getModel('Orderpreparation/ordertoprepareitem')
			->getCollection()
	        ->addFieldToFilter('order_id', $OrderId)
	        ->setOrder('order_item_id', 'asc');
	        
	    return $collection;
	}
		
	/*
	 * Retourne les éléments à envoyer pour une commande sous la forme d'un tableau
	 *
	 * @param unknown_type $OrderId
	 */
	public function GetItemsToShipAsArray($OrderId)
	{
		$collection = Mage::getModel('Orderpreparation/ordertoprepareitem')
			->getCollection()
	        ->addFieldToFilter('order_id', $OrderId);
	    
	    $retour = array();
	    foreach ($collection as $item)
	    {
	    	$retour[$item->getorder_item_id()] = $item->getqty();
	    }
		    
	    return $retour;
	}
	
	/*
	 * Flag une commande pour dire que le shipment a été créé
	 *
	 * @param unknown_type $OrderId
	 */
	public function StoreShipmentId($OrderId, $ShipmentId)
	{
		$this->load($OrderId, 'order_id')->setshipment_id($ShipmentId)->save();
	}
	
	/**
	 * Associe un numéro de facture à une commande
	 *
	 * @param unknown_type $OrderId
	 * @param unknown_type $InvoiceId
	 */
	public function StoreInvoiceId($OrderId, $InvoiceId)
	{
		$this->load($OrderId, 'order_id')->setinvoice_id($InvoiceId)->save();
	}
	
	/*
	 * Méthode pour savoir si un shipement a déja été créé pour la commande
	 *
	 */
	public function ShipmentCreatedForOrder($OrderId)
	{
		$item = $this->load($OrderId, 'order_id');
		if ($item->getshipment_id() != 0)
			return true;
		else 
			return false;
	}
	
	/*
	 * Cree une expe pour une commande
	 *
	 */
	public function CreateShipment(&$order)
	{
		try
		{
			//cree le shipment
			$convertor = Mage::getModel('sales/convert_order');
            $shipment = $convertor->toShipment($order);
            
			//parcourt les éléments de la commande
			$items = $this->GetItemsToShipAsArray($order->getid());
			foreach ($order->getAllItems() as $orderItem) 
			{
				//skip les cas spéciaux
				if (!$orderItem->isDummy(true) && !$orderItem->getQtyToShip()) {
                    continue;
                }
                if ($orderItem->getIsVirtual()) {
                    continue;
                }
				//si l'elt fait partie de ceux que l'on doit ajouter
				if (isset($items[$orderItem->getitem_id()]))
				{
					//ajout au shipment
					$ShipmentItem = $convertor->itemToShipmentItem($orderItem);
					$ShipmentItem->setQty($items[$orderItem->getitem_id()]);
                	$shipment->addItem($ShipmentItem);
				}
			
			}
			
			//sauvegarde le shipmeent
			$shipment->register();		
	        $shipment->getOrder()->setIsInProcess(true);
	        $transactionSave = Mage::getModel('core/resource_transaction')
	            ->addObject($shipment)
	            ->addObject($shipment->getOrder())
	            ->save();
	            
			//on associe le shipment a notre commande
			$this->StoreShipmentId($order->getid(), $shipment->getincrement_id());
			
		}
		catch (Exception $ex)
		{
			throw new Exception('Error while creating Shipment for Order '.$order->getincrement_id().': '.$ex->getMessage().' - '.$ex->getTraceAsString());
		}
	}
	
	/**
	 * Cree les shipments pour les commandes sélectionnées
	 * et retourne le nombre de shipments créés
	 */
	public function CreateShipments()
	{
		//parcourt les commandes
		$orders = $this->getSelectedOrders();
		$createdShipmentCount = 0;
		foreach ($orders as $order)
		{
			if (!$this->ShipmentCreatedForOrder($order->getid()))
			{
				$this->CreateShipment($order);				
			}
		}
		
		return $createdShipmentCount;
	}
	
		
	/*
	 * Méthode pour savoir si une invoice a déja été créé pour la commande
	 *
	 */
	public function InvoiceCreatedForOrder($OrderId)
	{
		$item = $this->load($OrderId, 'order_id');
		if (($item->getinvoice_id() == null) || ($item->getinvoice_id() == ''))
			return false;
		else 
			return true;
	}
	

	/**
	 * Cree une facture pour une commande
	 *
	 * @param unknown_type $order
	 */
	public function CreateInvoice(&$order)
	{
		try 
		{
			
			if (($order->canShip()))
			{
				return 0;
			}
			
			//verifie si il faut forcer la date de la facture
			$order_to_prepare = mage::getModel('Orderpreparation/OrderToPrepare')->load($order->getId(), 'order_id');
			$force_order_date = null;
			if (($order_to_prepare->getforce_invoice_date() != '0000-00-00') && ($order_to_prepare->getforce_invoice_date() != null))
				$force_order_date = date_create($order_to_prepare->getforce_invoice_date());
				
			//on cree la facture
			$convertor = Mage::getModel('sales/convert_order');
            $invoice = $convertor->toInvoice($order);
	    	
			//parcourt les éléments de la commande
			foreach ($order->getAllItems() as $orderItem) 
			{
				//ajout au invoice
				$InvoiceItem = $convertor->itemToInvoiceItem($orderItem);
				$InvoiceItem->setQty($orderItem->getqty_ordered());
            	$invoice->addItem($InvoiceItem);
			}
		
			//sauvegarde la facture
			$invoice->collectTotals();	
			$invoice->register();
	        $invoice->getOrder()->setIsInProcess(true);
	        $transactionSave = Mage::getModel('core/resource_transaction')
	            ->addObject($invoice)
	            ->addObject($invoice->getOrder())
	            ->save();
	        //$invoice->pay();
	        $invoice->save();
	        
			//link order & invoice
			$this->StoreInvoiceId($order->getid(), $invoice->getincrement_id());
									
			//validate payment
	    	$payment = Mage::getModel('sales/order_payment');
	    	$payment->setMethod('banktransfer');
	    	$payment->setOrder($order);
	    	$order->addPayment($payment);
	    	$payment->pay($invoice);
	    	$payment->save();
	    	
			//$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
    		$order->save(); 
    		
			return 1;
		}
		catch (Exception $ex)
		{
			throw new Exception('Error while creating Invoice for Order '.$order->getincrement_id().': '.$ex->getMessage().' - '.$ex->getTraceAsString());
		}
	}
	
	/**
	 * Cree les Factures pour les commandes sélectionnées
	 * Seulement si la commande a été totalement expédiées
	 * et retourne le nombre de factures créées
	 */
	public function CreateInvoices()
	{
		//parcourt les commandes
		$orders = $this->getSelectedOrders();
		$createdInvoicesCount = 0;
		foreach ($orders as $order)
		{
			//si la facture n'a pas été créée
			if (!$this->InvoiceCreatedForOrder($order->getid()))
			{
				//si tous les éléments de la commande ont été envoyés
				if ($order->IsCompletelyShipped())
				{
					$this->CreateInvoice($order);
				}
			}
		}
		
		return $createdInvoicesCount;
	}
	
	/**
	 * Retourne la liste des shipments (en rajoutant les poids réels saisis)
	 *
	 */
	public function GetShipments($carrier, $carrier2 = null)
	{
		//met dans un array les shipment
		$shipments = array();
		$OrderToPrepare = $this->getCollection()->setOrder('order_id', 'asc');
		foreach ($OrderToPrepare as $item)
		{
			//recupere le shipment
			$obj = Mage::getModel('sales/order_shipment')->loadByIncrementId($item->getshipment_id());
			$order = $obj->getOrder();
			$t = explode('_', strtolower($order->getshipping_method()));
			$realShippingMethod = $t[0];
			if (($realShippingMethod == $carrier) || ($realShippingMethod == $carrier2))
			{
				//rajoute le poids réel
				$obj->setreal_weight($item->getreal_weight());
				$obj->setship_mode($item->getship_mode());
				$obj->setpackage_count($item->getpackage_count());
				//le rajoute a la liste
				$shipments[] = $obj;
			}
		}
			
		return $shipments;
	}
	
	/**
	 * Plan customer notification
	 *
	 */
	public function NotifyCustomers()
	{
		//pour chaque envoi
		foreach ($this->getCollection() as $item)
		{
			try 
			{
				//Shipment notification
				if ($item->getshipment_id() > 0)
				{
					$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($item->getshipment_id());
					if (!$shipment->getEmailSent())
					{		
						mage::helper('BackgroundTask')->AddTask('Notify Shipment #'.$shipment->getId(), 
																'Orderpreparation',
																'notifyShipment',
																$shipment->getId()
																);	
					}
				}
								
				//invoice notification
				if ($item->getinvoice_id() > 0)
				{
					$invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($item->getinvoice_id());
					if (!$invoice->getEmailSent())
					{
						mage::helper('BackgroundTask')->AddTask('Notify Invoice #'.$invoice->getId(), 
										'Orderpreparation',
										'notifyInvoice',
										$invoice->getId()
										);	
					}
				}				
			}
			catch (Exception $ex)
			{
				Mage::getSingleton('adminhtml/session')->addError('Error while notifying for order '.$item->getorder_id().' (invoice id: '.$item->getinvoice_id().') : '.$ex->getMessage());
			}
		}
	}
	
	/**
	 * Fin, on supprime les enregistrements
	 *
	 */
	public function Finish()
	{
		foreach ($this->getCollection() as $item)
		{
			$this->RemoveSelectedOrder($item->getorder_id());
			
			//dispatch order if invoice not created
			if ($item->getinvoice_id() == '')
			{
				$order = mage::getModel('sales/order')->load($item->getorder_id());
				$this->DispatchOrder($order);
			}
		}
	}
	
	    /**
     * Retourne sous la forme de texte les détails pour une commande
     *
     * @param unknown_type $order
     */
    public function getDetailsForOrder($order, $ShowInvoiceShipment = true)
    { 
    	//gernere la chaine et retourne
    	$retour = '';
    	$retour .= ''.mage::helper('Orderpreparation')->__('Total').': '.number_format($order->getgrand_total(), 2);
    	$retour .= "<br>Date: ".mage::helper('core')->formatDate($order->getcreated_at(), 'long');
    	
    	//Définit l'etat du paiement
    	$retour .= "<br>".mage::helper('Orderpreparation')->__('Payment').": ";
    	if ($order->getpayment_validated() == 1)
    		$retour .= '<font color="green">'.mage::helper('Orderpreparation')->__('Yes').'</font>';
    	else
    		$retour .= '<font color="red">'.mage::helper('Orderpreparation')->__('No').'</font>';
    	
    	$retour .= "<br>".mage::helper('Orderpreparation')->__('Status').": ".$order->getstatus();
    	$retour .= "<br>".mage::helper('Orderpreparation')->__('Carrier').": ".$order->getshipping_description();
    	if ($order->getPayment())
	    	$retour .= "<br>".mage::helper('Orderpreparation')->__('Payment').": ".$order->getPayment()->getMethodInstance()->gettitle();
	    if ($ShowInvoiceShipment)
	    {
	    	$OrderToPrepare = mage::getModel('Orderpreparation/ordertoprepare')->load($order->getId(), 'order_id');
		    if (($OrderToPrepare))
		    {
				$invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($OrderToPrepare->getinvoice_id());	
		    	$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($OrderToPrepare->getshipment_id());
		    	if ($OrderToPrepare->getinvoice_id() != '')
			    	$retour .= '<br>'.mage::helper('Orderpreparation')->__('Invoice').': <a target="_new" href="'.Mage::helper('adminhtml')->getUrl('adminhtml/sales_order_invoice/print', array('invoice_id' => $invoice->getId())).'"> '.$OrderToPrepare->getinvoice_id().'</a>';
			    if ($OrderToPrepare->getshipment_id())
			    	$retour .= '<br>'.mage::helper('Orderpreparation')->__('Shipment').': <a target="_new" href="'.Mage::helper('adminhtml')->getUrl('adminhtml/sales_order_shipment/print', array('invoice_id' => $shipment->getId())).'"> '.$OrderToPrepare->getshipment_id().'</a>';
		    }

		    //Rajoute les no de tracking
		    $tracking_txt = '';
	    	foreach($order->getTracksCollection() as $track)
	    	{
	    		
	    		$obj = $track->getNumberDetail(); 
	    		if (is_object($obj))
		    		$tracking_txt .= $track->getNumberDetail()->gettracking().'<br>';
		    	else 
		    	{
					if (is_array($obj))
			    		$tracking_txt .= $obj["number"].'<br>';
			    	else 
			    		$tracking_txt .= $obj.'<br>';
		    	}
	    	}
	    	if ($tracking_txt != '')
	    	{
	    		$retour .= "<br>Tracking: ".$tracking_txt;
	    	}
	    }
    	return $retour;
    }
    
    /**
     * Dispatch order in fullstock or stockless tabs
     *
     */
    public function DispatchOrder($order)
    {
    	$this->removeOrderFromOrderToPreparePending($order);
    	
    	//dispatch order if state match
    	if (($order->getStatus() != 'complete') && ($order->getStatus() != 'canceled'))
    	{
	    	//Dispatch order only if it doesn't belong to selected orders
	    	if (!$this->orderBelongsToSelectedOrders($order))
	    	{
	    		if (!$order->IsCompletelyShipped())
	    		{
		    		//dispatch order depending of stock state
		    		$opp_type = 'stockless';
					if ($order->IsFullStock())
			    		$opp_type = 'fullstock';
			    	if (!$order->IsValidForPurchaseProcess())
			    		$opp_type = 'ignored';
			    	$ShipToName = '';
			    	if ($order->getShippingAddress() != null)
				    	$ShipToName = $order->getShippingAddress()->getName();
					$OrderToPreparePending = mage::getModel('Orderpreparation/ordertopreparepending')
								    			->setopp_order_id($order->getId())
								    			->setopp_remain_to_ship($this->getRemainToShipForOrder($order))
								    			->setopp_shipto_name($ShipToName)
								    			->setopp_details($this->getDetailsForOrder($order, false))
								    			->setopp_order_increment_id($order->getIncrementId())
								    			->setopp_type($opp_type)
								    			->setopp_shipping_method($order->getshipping_description())
								    			->setopp_payment_validated($order->getpayment_validated())
								    			->save();
	    		}
	    	}
    	}
    }
    
    /**
     * Function to know if an order belong to selected orders
     *
     * @param unknown_type $order
     */
    public function orderBelongsToSelectedOrders($order)
    {
    	$retour = true;
		$OrderToPrepare = mage::getModel('Orderpreparation/ordertoprepare')->load($order->getId(), 'order_id');
		if (!$OrderToPrepare->getId())
			$retour = false;
		return $retour;
    }
	
    /**
     * remove order from OrderToPreparePending (table containing fullstock & stockless orders)
     *
     * @param unknown_type $order
     */
    public function removeOrderFromOrderToPreparePending($order)
    {
    	$OrderToPreparePending = mage::getModel('Orderpreparation/ordertopreparepending')->load($order->getId(), 'opp_order_id');
    	if ($OrderToPreparePending->getId())
    		$OrderToPreparePending->delete();
    }
    
    /**
     * Retourne sous la forme de texte la liste des éléments restant a shippé pour une commande 
     *
     * @param unknown_type $order
     */
    public function getRemainToShipForOrder($order)
    {
    	$retour = '';
    	//parcourt la liste des produits
    	foreach ($order->getItemsCollection() as $item)
    	{
    		$remaining_qty = $item->getRemainToShipQty();
    		if ($remaining_qty > 0)
    		{
	    		$productId = $item->getproduct_id();
	    		$productStockManagement = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
	    		if ($productStockManagement->getManageStock())
	    		{
	    			//si la qté est réservée
	    			if ($item->getreserved_qty() >= $remaining_qty)
	    			{
	    				$retour .= "<font color=\"green\">".((int)$remaining_qty).'x '.$item->getName()."</font>";
	    			}
	    			else 
	    			{
	    				if (($item->getreserved_qty() < $remaining_qty) && ($item->getreserved_qty() > 0))
	    				{
	    					$retour .= "<font color=\"orange\">".((int)$remaining_qty).'x '.$item->getName()." (".$item->getreserved_qty().'/'.$remaining_qty.")</font>";
	    				}
	    				else 
	    				{
		    				$product =  Mage::getModel('catalog/product')->load($productId);
				    		$stock = $productStockManagement->getQty() - $product->getreserved_qty();
				    		if ($remaining_qty <= $stock)
								$retour .= ((int)$remaining_qty).'x '.$item->getName();
							else 
								$retour .= "<font color=\"red\">".((int)$remaining_qty).'x '.$item->getName()."</font>";
	    				}
	    			}
					$retour .= "<br>";
	    		}
	    		else 
	    			$retour .= "<i>".$item->getName()."</i><br>";
    		}
    		else 
    		{
    			//si produit déja envoyé, on l'affiche barré
    			$retour .= "<s>".((int)$item->getqty_ordered()).'x '.$item->getName()."</s><br>";
    		}
    	}
    	
    	return $retour;
    }
    
    /**
     * Return an array with pending orders ids
     *
     */
    public function getPendingOrdersIds()
    {
    	$retour = array();

    	//retrieve collecition
 	  	$collection = Mage::getResourceModel('sales/order_collection')
	        ->addAttributeToFilter('status', array('holded', 'pending', 'processing', 'closed', 'pending_paypal', 'pending_payment'))	
	        ->addFieldToFilter('entity_id', array('nin'=>$this->getSelectedOrdersIds()))			//on ne prend pas en compte les commandes déja sélectionnées
	        ->addAttributeToSort('increment_id', 'asc');  
	        
	    foreach ($collection as $item)
	    {
	    	$retour[] = $item->getId();	
	    }
	    
    	return $retour;
    }
    
    /**
     * Return items
     *
     * @return unknown
     */
    public function getItems()
    {
    	$collection = mage::getModel('Orderpreparation/ordertoprepareitem')
    					->getCollection()
    					->addFieldToFilter('order_id', $this->getorder_id());
    	return $collection;
    }
}