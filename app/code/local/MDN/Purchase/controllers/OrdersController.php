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
class MDN_Purchase_OrdersController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction()
    {

    }
    
    /**
     * Affiche la liste
     *
     */
	public function ListAction()
    {
    	$this->loadLayout();
        $this->renderLayout();
    }
        
    /**
     * Edition 
     *
     */
	public function EditAction()
    {
    	$this->loadLayout();
    	$OrderId = $this->getRequest()->getParam('po_num');
    	Mage::register('purchase_order_id', $OrderId);
		$this->renderLayout();
    }
            
    /**
     * New 
     *
     */
	public function NewAction()
    {
    	$this->loadLayout();
		$this->renderLayout();
    }
    
    /**
     * Création d'une nouvelle commande
     *
     */
    public function createAction()
    {
    	//recupere le fournisseur
    	$sup_num = $this->getRequest()->getParam('supplier');
    	
    	//cree la commande
    	$model = mage::getModel('Purchase/Order');
    	$order = $model
    		->setpo_sup_num($sup_num)
    		->setpo_date(date('Y-m-d'))
    		->setpo_currency(Mage::getStoreConfig('purchase/purchase_order/default_currency'))
    		->setpo_tax_rate(Mage::getStoreConfig('purchase/purchase_order/default_shipping_duties_taxrate'))
    		->setpo_order_id($model->GenerateOrderNumber())
    		->setpo_status('new')
			->save();
    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order successfully Created'));
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Orders/Edit/po_num/'.$order->getId());
    	
    }
    
    /**
     * Suppression d'une commande
     *
     */
    public function deleteAction()
    {
    	//recupere le id
    	$po_num = $this->getRequest()->getParam('po_num');
    	
    	//Supprime les movement de stock
    	$collection = mage::getModel('Purchase/StockMovement')
    		->getCollection()
    		->addFieldToFilter('sm_po_num', $po_num);
    	foreach ($collection as $item)
    	{
    		$item->delete();
    	}
    	
    	$order = mage::getModel('Purchase/Order')->load($po_num);
		foreach ($order->getProducts() as $item)
		{
			$productId = $item->getpop_product_id();
	    	Mage::dispatchEvent('purchase_update_supply_needs_for_product', array('product_id'=>$productId));
		}

    	
    	//Supprime les produits
    	$collection = mage::getModel('Purchase/OrderProduct')
    		->getCollection()
    		->addFieldToFilter('pop_order_num', $po_num);
    	foreach ($collection as $item)
    	{
    		$item->delete();
    	}
    	
    	//Supprime la commande
    	$order->delete();
    	
    	//Confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order successfully Deleted'));
    	    	
    	//Redirige
    	$this->_redirect('Purchase/Orders/List');
    }
    
    /**
     * Sauvegarde une commande modifiée
     *
     */
    public function saveAction()
    {
    	$tab = '';
    	
    	//Modifie la commande
    	$order = mage::getModel('Purchase/Order')->load($this->getRequest()->getPost('po_num'));
    	$order->setpo_date($this->getRequest()->getPost('po_date'));
    	//HERE IS CHANGE!
    	$order->setpo_ship_to($this->getRequest()->getPost('ship_to'));
    	$order->setship_speed($this->getRequest()->getPost('ship_speed'));
    	
    	if ($this->getRequest()->getPost('po_supply_date') != '')
	    	$order->setpo_supply_date($this->getRequest()->getPost('po_supply_date'));
    	$order->setpo_carrier($this->getRequest()->getPost('po_carrier'));
    	$order->setpo_payment_type($this->getRequest()->getPost('po_payment_type'));
    	$order->setpo_currency($this->getRequest()->getPost('po_currency'));
    	$order->setpo_supplier_order_ref($this->getRequest()->getPost('po_supplier_order_ref'));
    	if ($this->getRequest()->getPost('po_invoice_date') != '')
	    	$order->setpo_invoice_date($this->getRequest()->getPost('po_invoice_date'));
    	$order->setpo_invoice_ref($this->getRequest()->getPost('po_invoice_ref'));
    	if ($this->getRequest()->getPost('po_payment_date') != '')
	    	$order->setpo_payment_date($this->getRequest()->getPost('po_payment_date'));
    	$order->setpo_currency_change_rate($this->getRequest()->getPost('po_currency_change_rate'));
    	$order->setpo_shipping_cost($this->getRequest()->getPost('po_shipping_cost'));
    	if ($order->getpo_currency_change_rate() > 0)
	    	$order->setpo_shipping_cost_base($order->getpo_shipping_cost() / $order->getpo_currency_change_rate());
    	$order->setpo_zoll_cost($this->getRequest()->getPost('po_zoll_cost'));
    	if ($order->getpo_currency_change_rate() > 0)
	    	$order->setpo_zoll_cost_base($order->getpo_zoll_cost() / $order->getpo_currency_change_rate());
    	$order->setpo_comments($this->getRequest()->getPost('po_comments'));
    	
    	
    	
    	
    	$order->setpo_tax_rate($this->getRequest()->getPost('po_tax_rate'));
    	$order->setpo_status($this->getRequest()->getPost('po_status'));
    	$order->setpo_order_id($this->getRequest()->getPost('po_order_id'));
    	if ($this->getRequest()->getPost('po_paid') == 1)
	    	$order->setpo_paid(1);
	    else 
	    	$order->setpo_paid(0);
    	if ($this->getRequest()->getPost('po_external_extended_cost') == 1)
	    	$order->setpo_external_extended_cost(1);
	    else 
	    	$order->setpo_external_extended_cost(0);
    	if ($this->getRequest()->getPost('po_data_verified') == 1)
	    	$order->setpo_data_verified(1);
	    else 
	    	$order->setpo_data_verified(0);
	    $order->save();
	    
    	//Met a jour les lignes produits
    	$products = $order->getProducts();
    	foreach ($products as $item)
    	{
    		//verifie si on doit la supprimer
    		if ($this->getRequest()->getPost('delete_'.$item->getId()) == 1)
    		{
    			$item->delete();
    		}
    		else 
    		{    		
	    		$item->setpop_product_name($this->getRequest()->getPost('pop_product_name_'.$item->getId()));
	    		$item->setpop_supplier_ref($this->getRequest()->getPost('pop_supplier_ref_'.$item->getId()));
	    		$item->setpop_qty($this->getRequest()->getPost('pop_qty_'.$item->getId()));
	    		$item->setpop_price_ht($this->getRequest()->getPost('pop_price_ht_'.$item->getId()));
	    		if ($order->getpo_currency_change_rate() > 0)
		    		$item->setpop_price_ht_base($item->getpop_price_ht() / $order->getpo_currency_change_rate());
	    		$item->setpop_eco_tax($this->getRequest()->getPost('pop_eco_tax_'.$item->getId()));
	    		if ($order->getpo_currency_change_rate() > 0)
		    		$item->setpop_eco_tax_base($item->getpop_eco_tax() / $order->getpo_currency_change_rate());
	    		$item->setpop_tax_rate($this->getRequest()->getPost('pop_tax_rate_'.$item->getId()));
	    		$item->save();
    		}
    	}
    	
    	//check if we have to add products
    	if ($this->getRequest()->getPost('add_product') != '')
    	{
			$productsToAdd = $this->_decodeInput($this->getRequest()->getPost('add_product'));
			foreach($productsToAdd as $key => $value)
			{
				//retrieves values
				$productId = $key;
				$qty = $value['qty'];
				if ($qty == '')
					$qty = 1;
					
				//add product
				$order->AddProduct($productId, $qty);
			}
			
	    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Products added'));
			$tab = 'tab_products';
			$order->resetProducts();
    	}
    	
    	//add supply needs
    	$supplyNeedsIds = explode(',', $this->getRequest()->getPost('supply_needs_ids'));
    	foreach($supplyNeedsIds as $supplyNeedId)
    	{
    		$supplyNeed = mage::getModel('Purchase/SupplyNeeds')->load($supplyNeedId);
    		if ($supplyNeed->getsn_needed_qty() > 0)
				$order->AddProduct($supplyNeed->getsn_product_id(), $supplyNeed->getsn_needed_qty());
    	}
    	if ($this->getRequest()->getPost('supply_needs_ids') != '')
    	{
    		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Supply Needs added'));
			$tab = 'tab_products';
			$order->resetProducts();
    	}
    	
    	//verifie si on doit ajouter un mouvement de stock
    	foreach ($products as $item)
    	{
    		if ($this->getRequest()->getPost('add_sm_qty_'.$item->getpop_product_id()) > 0)
    		{
    			//recupere les informations
    			$productId = $item->getpop_product_id();
    			$qty = $this->getRequest()->getPost('add_sm_qty_'.$item->getpop_product_id());
    			
    			//ajoute le mouvement de stock
    			mage::getModel('Purchase/StockMovement')
    				->setsm_product_id($productId)
    				->setsm_qty($qty)
    				->setsm_coef(1)
    				->setsm_description(mage::helper('purchase')->__('Purchase Order #').$order->getpo_order_id().mage::helper('purchase')->__(' from ').$order->getSupplier()->getsup_name())
    				->setsm_type('supply')
    				->setsm_date($this->getRequest()->getPost('add_sm_date'))
    				->setsm_po_num($order->getId())
    				->save();
    				
    			//met a jour la qte recue pour ce produit & son stock
    			$item->updateDeliveredQty();
    		}
    	}
    	
	    //Réparti les frais d'approche
	    $order->dispatchExtendedCosts();
	    
	    //If completely delivered, set status to complete
	    if ($order->isCompletelyDelivered())
    		$order->setpo_status(MDN_Purchase_Model_Order::STATUS_COMPLETE);	
	    
    	//update missing prices flag
    	$order->setpo_missing_price($order->hasMissingPrices());
    		
	    //met a jour les prix des produits de la commande (si commande complete)
	    if (Mage::getStoreConfig('purchase/purchase_order/store_product_cost'))
	    {
		    if ($order->getpo_status() == MDN_Purchase_Model_Order::STATUS_COMPLETE)
		    {
			    foreach ($products as $item)
		    	{
		    		$product = mage::getModel('catalog/product')->load($item->getpop_product_id());
		    		$cost = mage::getModel('Purchase/SupplyNeeds')->ComputeProductCost($product);
		    		//Stock le prix d'achat que si ce dernier est positif
		    		if (($cost > 0) && ($cost != $product->getCost()))
			    		$product->setcost($cost)->save();
		    	}
		    }
	    }

	    //Stock les infos d'association supplier / product (si finished)
    	if ($order->getpo_status() == MDN_Purchase_Model_Order::STATUS_COMPLETE)
	    	$order->updateProductSupplierAssociation();
    	
	    //Update delivery progress
	    $order->computeDeliveryProgress();
	    
	    //notify supplier
	    $Notify = $this->getRequest()->getPost('send_to_customer');
	    if ($Notify == 1)
	    {
	    	$order->notifySupplier($this->getRequest()->getPost('email_comment'));
	    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Supplier notified'));
	    	if ($this->getRequest()->getPost('ch_change_order_status_to_pending') == "1")
				$order->setpo_status(MDN_Purchase_Model_Order::STATUS_WAITING_FOR_DELIVERY)->save();
	    }
	    	    	    
		//update products delivery date 	
		mage::helper('BackgroundTask')->AddTask('Update delivery date for order '.$order->getId(), 
						'purchase',
						'UpdateProductsDeliveryDate',
						$order->getId()
						);	    
	    
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order successfully Saved'));
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Orders/Edit/po_num/'.$order->getId().'/tab/'.$tab);
    	
    }
         
    /**
     * Impression 
     *
     */
	public function PrintAction()
    {
		try 
    	{
    		//recupere la commande
			$po_num = $this->getRequest()->getParam('po_num');
			$order = Mage::getModel('Purchase/Order')->load($po_num);
			
			$obj = mage::getModel('Purchase/Pdf_Order');
			//$obj = new MDN_Purchase_Model_Pdf_Order();
			$pdf = $obj->getPdf(array($order));
	        $this->_prepareDownloadResponse(mage::helper('purchase')->__('Purchase Order #').$order->getpo_order_id().'.pdf', $pdf->render(), 'application/pdf');    		
    	}
    	catch (Exception $ex)
    	{
    		die("Erreur lors de la génération du PDF de bon de commande fournisseur: ".$ex->getMessage());
    	}
    }   
    
    /**
     * Met a jour les liens avec les fournisseurs pour toutes les commandes
     * a faire juste pour la premiere importation !!!!
     *
     */
    public function updateProductSupplierAssociationAction()
    {
    	//Regarde si ya un numéro de commande mini a partir duquel partir
    	$min = $this->getRequest()->getParam('min');
    	if (!$min)
    		$min = 0;
    	
    	
    	$collection = mage::getModel('Purchase/Order')->getCollection();
    	foreach($collection as $order)
    	{
    		if ($order->getId() >= $min)
    		{
	    		echo "<p>Order #".$order->getId();
	    		
	    		//Stock les couts en euro
	    		if ($order->getpo_currency_change_rate() > 0)
	    		{
			    	$order->setpo_shipping_cost_base($order->getpo_shipping_cost() / $order->getpo_currency_change_rate());
			    	$order->setpo_zoll_cost_base($order->getpo_zoll_cost() / $order->getpo_currency_change_rate());
	    		}
		    	$order->save();

		    	//réparti les frais d'approche par produits
		    	$products = $order->getProducts();
		    	foreach ($products as $item)
		    	{
		    		if ($order->getpo_currency_change_rate() > 0)
		    		{
			    		$item->setpop_price_ht_base($item->getpop_price_ht() / $order->getpo_currency_change_rate());
			    		$item->setpop_eco_tax_base($item->getpop_eco_tax() / $order->getpo_currency_change_rate());
		    		}
		    		$item->save();
		    	}
		    	
		    	//Si commande finie, on calcul les frais d'approche
				if ($order->getpo_status() == MDN_Purchase_Model_Order::STATUS_COMPLETE)
		    	{
		    		$order->dispatchExtendedCosts();
		    		$order->updateProductSupplierAssociation();
		    	}
		    	
	   		    //met a jour les prix des produits de la commande
	   		    if (Mage::getStoreConfig('purchase/purchase_order/store_product_cost'))
	   		    {
				    foreach ($products as $item)
			    	{
			    		$product = mage::getModel('catalog/product')->load($item->getpop_product_id());
			    		$cost = mage::getModel('Purchase/SupplyNeeds')->ComputeProductCost($product);
			    		if ($cost > 0)
				    		$product->setcost($cost)->save();
			    	}
	   		    }
    		}
    	}
    }
        
    /**
     * Méthode débile qui genere les entetes HTTP pour demander à l'utilisateur d'ouvrir ou enregistrer le PDF
     *
     * @param unknown_type $fileName
     * @param unknown_type $content
     * @param unknown_type $contentType
     */
    protected function _prepareDownloadResponse($fileName, $content, $contentType = 'application/octet-stream', $contentLength = null)
    {
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', strlen($content))
            ->setHeader('Content-Disposition', 'attachment; filename='.$fileName)
            ->setBody($content);
    }
             
    /**
     * Importer des produits dans une commande a partir des supply needs 
     *
     */
	public function ImportFromSupplyNeedsAction()
    {
    	$this->loadLayout();
    	
    	//set grid mode to add products in order
    	$orderId = $this->getRequest()->getParam('po_num');
    	$block = $this->getLayout()->getBlock('importfromsupplyneeds')->setMode('import', $orderId);
		$this->renderLayout();
    }   
    
    /**
     * Importe dans la commande les produits sélectionnés
     *
     */
    public function CreateFromSupplyNeedsAction()
    {
    	//Recupere le no de commande
    	$po_num = $this->getRequest()->getParam('po_num');
    	$order = mage::getModel('Purchase/Order')->load($po_num);
    	$Products = $order->getProducts();
    	
    	//parcourt les produits à ajouter
    	$data = $this->getRequest()->getParams();
		foreach ($data as $key => $value)
		{
			//Si c une case a cocher
			if (!(strpos($key, 'ch_') === false))
			{
				//Recupere les infos
				$ProductId = str_replace('ch_', '', $key);
				$Qty = $this->getRequest()->getParam('qty_'.$ProductId);

				//Verifie que le produit ne soit pas déja ajouté a la commande
				$ok = true;
				foreach ($Products as $Product)
				{
					if ($Product->getpop_product_id() == $ProductId)
					{
						$ok = false;
						break;
					}
				}
				
				if (($Qty > 0) && ($ok))
				{				
					//ajoute a la commande
					$order->AddProduct($ProductId, $Qty);
				}
			}
		}
    	
    }
    
    /**
     * Cree une commande et ajoute des produits dedans
     *
     */
    public function CreateOrderAndAddProductsAction()
    {
    	//recupere le fournisseur
    	$sup_num = $this->getRequest()->getParam('supplier_create');
    	
    	//cree la commande
    	$order = mage::getModel('Purchase/Order')
    		->setpo_sup_num($sup_num)
    		->setpo_date(date('Y-m-d'))
    		->setpo_currency(Mage::getStoreConfig('purchase/purchase_order/default_currency'))
    		->setpo_tax_rate(Mage::getStoreConfig('purchase/purchase_order/default_shipping_duties_taxrate'))
    		->setpo_order_id(mage::getModel('Purchase/Order')->GenerateOrderNumber())
    		->save();
    	
    	//rajoute les produits
    	$data = $this->getRequest()->getParams();
		foreach ($data as $key => $value)
		{
			//Si c une case a cocher
			if (!(strpos($key, 'ch_') === false))
			{
				//Recupere les infos
				$ProductId = str_replace('ch_', '', $key);
				$Qty = $this->getRequest()->getParam('qty_'.$ProductId);
				if ($Qty > 0)
					$order->AddProduct($ProductId, $Qty);	
			}
		}
    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order successfully Created'));
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Orders/Edit/po_num/'.$order->getId());
    }
    
        
    /**
     * Méthode pour mettre a jour les dates prévisionnelles d'appro pour les produits (et modifier les dates prévisionnelles des commandes
     *
     */
    public function UpdateProductsDeliveryDateAction()
    {
		//Recupere la commande
    	$po_num = $this->getRequest()->getParam('po_num');
    	$order = mage::getModel('Purchase/Order')->load($po_num);	
    	
    	//met a jour
    	if ($order->UpdateProductsDeliveryDate())
    	{
	    	//confirme
	    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Products delivery date successfully Updated'));    		
    	}
		else 
		{
	    	//confirme
	    	Mage::getSingleton('adminhtml/session')->addError($this->__('Delivery date incorrect'));    		
		}
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Orders/Edit/po_num/'.$order->getId());   	
    }
    
    /**
     * Create serializer block for a grid
     *
     * @param string $inputName
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock
     * @param array $productsArray
     * @return Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Ajax_Serializer
     */
    protected function _createSerializerBlock($inputName, Mage_Adminhtml_Block_Widget_Grid $gridBlock, $productsArray)
    {
        return $this->getLayout()->createBlock('adminhtml/catalog_product_edit_tab_ajax_serializer')
            ->setGridBlock($gridBlock)
            ->setProducts($productsArray)
            ->setInputElementName($inputName);
    }
    
    /**
     * Output specified blocks as a text list
     */
    protected function _outputBlocks()
    {
        $blocks = func_get_args();
        $output = $this->getLayout()->createBlock('adminhtml/text_list');
        foreach ($blocks as $block) {
            $output->insert($block, '', true);
        }
        $this->getResponse()->setBody($output->toHtml());
    }
    
    protected function _decodeInput($encoded)
    {
        parse_str($encoded, $data);
        foreach($data as $key=>$value) {
            parse_str(base64_decode($value), $data[$key]);
        }

        return $data;
    }

    
    /**
     * Méthode pour la grille d'ajout de produit dans une commande fournisseur
     *
     */
    public function ProductSelectionGridAction()
    {  	
    	$po_num = $this->getRequest()->getParam('po_num');
    	$gridBlock = $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_ProductSelection')
    		->setOrderId($po_num)
            ->setGridUrl($this->getUrl('*/*/ProductSelectionGridOnly', array('_current' => true, 'po_num' => $po_num)));
        $serializerBlock = $this->_createSerializerBlock('add_product', $gridBlock, $gridBlock->getSelectedProducts());
        $this->_outputBlocks($gridBlock, $serializerBlock);
    }
    
    /**
     * Return supply needs grid in ajax (first call)
     *
     */
    public function SupplyNeedsGridAction()
    {
    	$po_num = $this->getRequest()->getParam('po_num');
    	$gridBlock = $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_SupplyNeeds')
    		->setOrderId($po_num)
            ->setGridUrl($this->getUrl('*/*/SupplyNeedsGridOnly', array('_current' => true, 'po_num' => $po_num)));
        $serializerBlock = $this->_createSerializerBlock('add_product', $gridBlock, $gridBlock->getSelectedProducts());
        $this->_outputBlocks($gridBlock, $serializerBlock);    	
    }
    
    public function SupplyNeedsGridOnlyAction()
    {
    	$po_num = $this->getRequest()->getParam('po_num');
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_SupplyNeeds')->setOrderId($po_num)->toHtml()
	        );
    }  
    
    public function ProductSelectionGridOnlyAction()
    {
    	$po_num = $this->getRequest()->getParam('po_num');
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('Purchase/Order_Edit_Tabs_ProductSelection')->setOrderId($po_num)->toHtml()
	        );
    }        
        
    /**
     * Ajoute un produit a une commande
     *
     */
    public function AddProductToOrderAction()
    {
    	//recupere les infos
    	$ProductId = $this->getRequest()->getParam('ProductId');
    	$OrderId = $this->getRequest()->getParam('OrderId');
    	$order = mage::getModel('Purchase/Order')->load($OrderId);
    	
    	//Verifie si le produit est déja présent dans la commande
    	$Products = $order->getProducts();
    	$ok = true;
    	foreach ($Products as $Product)
    	{
    		if ($Product->getpop_product_id() == $ProductId)
    		{
    			Mage::getSingleton('adminhtml/session')->addError($this->__('Product already exists in order'));  
    			$ok = false;
    			break;
    		}
    	}
    	
    	//Ajoute le produit a la commande
    	if ($ok)
    	{
	    	try 
	    	{   
	    		$order->AddProduct($ProductId);	
								
		    	//Confirme
		    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Products successfully Added'));   
	    	}
	    	catch (Exception $ex)
	    	{
	    		Mage::getSingleton('adminhtml/session')->addError($this->__('Error adding Product'));  
	    	}
    	} 	
    	
    	//redirige
    	$this->_redirect('Purchase/Orders/Edit/po_num/'.$OrderId);   
    }

    
    /**
     * Export des commandes au format csv
     *
     */
    public function exportCsvAction()
    {
    	$fileName   = 'purchase_orders.csv';
        $content    = $this->getLayout()->createBlock('Purchase/Order_Grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }
    
    /**
     * Ajoute plrs produits à une commande
     *
     */
    public function massAddToOrderAction()
    {
    	
    	//Recupere les ids
    	$ids = $this->getRequest()->getPost('product_ids');
    	$orderId = $this->getRequest()->getParam('order_id');
    	$order = mage::getModel('Purchase/Order')->load($orderId);
    	foreach($ids as $id)
    	{
    		$order->AddProduct($id);	
    	}
    	
		//Confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Products successfully Added'));   
    	
    	//Redirige
		$this->_redirect('Purchase/Orders/Edit/po_num/'.$orderId);   
    }
}