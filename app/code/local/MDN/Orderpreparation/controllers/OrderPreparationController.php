<?php
/*
 * Created on Jun 26, 2008
 *
 */

//Controlleur pour la préparation des commandes coté admin
class MDN_Orderpreparation_OrderPreparationController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Ecran principal pour la préparation des commandes
	 *
	 */
	public function indexAction()
    {
    	/*
    	$this->loadLayout();
        $this->renderLayout();
        */
    	
    	$this->loadLayout();
    	$block = $this->getLayout()->createBlock('Orderpreparation/OrderPreparationContainer');
        $this
        	->_addContent($this->getLayout()->createBlock('Orderpreparation/Header'))
        	->_addContent($this->getLayout()->createBlock('Orderpreparation/Widget_Tab_OrderPreparationTab'))
        	->renderLayout();
    }
    
    /**
     * Edition du commentaire & réservation de produit pour une commande
     *
     */
    public function editAction()
    {
        $this->loadLayout();
        //transmet la commande au bloc
        $orderId = $this->getRequest()->getParam('order_id');
        $order = mage::getModel('sales/order')->load($orderId);
        $this->getLayout()->getBlock('ordercontentgrid')->setOrder($order);
        $this->getLayout()->getBlock('progressgraph')->setOrder($order);
        $this->renderLayout();
    }
    
    /**
     * Ajoute les commandes aux commandes sélectionnées pour la préaparaiton de commandes
     *
     */
    public function massAddToSelectionAction()
	{

		//create task group
		$taskGroup = 'mass_add_to_selected_orders';
		mage::helper('BackgroundTask')->AddGroup($taskGroup, $this->__('Add orders to selected orders'), 'OrderPreparation/OrderPreparation/');
		
    	//Create task to add orders
		$orderIds = $this->getRequest()->getPost('full_stock_orders_order_ids');
		if (!empty($orderIds)) 
		{
	    	//Create task to add orders
			foreach ($orderIds as $orderId)
			{
				mage::helper('BackgroundTask')->AddTask('Add order #'.$orderId.' to selected orders', 
										'Orderpreparation',
										'addToSelectedOrders',
										$orderId,
										$taskGroup
										);	
			}

			//execute task group
			mage::helper('BackgroundTask')->ExecuteTaskGroup($taskGroup);

		}
		else 
		{
			Mage::getSingleton('adminhtml/session')->addError($this->__('No order to add'));
			$this->_redirect('OrderPreparation/OrderPreparation/');
		}
    	
    }
    
    /**
     * Ajoute une commande a la sélection
     *
     */
    public function AddToSelectionAction()
    {
    	$orderId = $this->getRequest()->getParam('order_id');
    	if (Mage::getModel('Orderpreparation/ordertoprepare')->AddSelectedOrder($orderId))
    		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order successfully added.'));
    	else 
    		Mage::getSingleton('adminhtml/session')->addError($this->__('Unable to add order'));
		
    	//redirige sur la page de sélection des commandes
    	$this->_redirect('OrderPreparation/OrderPreparation/');
    }
        
    /**
     * Supprime les commandes de la sélection
     *
     */
    public function massRemoveFromSelectionAction()
    {
    	//recupere les infos & ajoute les commandes
		$orderIds = $this->getRequest()->getPost('order_ids');
		if (!empty($orderIds)) 
		{
			foreach ($orderIds as $orderId)
			{
				Mage::getModel('Orderpreparation/ordertoprepare')->RemoveSelectedOrder($orderId);
			}
		}

		
    	//confirme
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Orders successfully removed.'));
		
    	//redirige sur la page de sélection des commandes
    	$this->RefreshListAction();
    }
    
    
    /**
     * Ajoute une commande a la sélection
     *
     */
    public function RemoveFromSelectionAction()
    {
    	$orderId = $this->getRequest()->getParam('order_id');
    	Mage::getModel('Orderpreparation/ordertoprepare')->RemoveSelectedOrder($orderId);
    	    	
		//confirme
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order successfully removed.'));
		
	    //redirige
	    $this->_redirect('OrderPreparation/OrderPreparation/');
    }

    /**
     * Cree les envois pour les commandes sélectionnées
     * Et Cree un document avec tout (bon de préparation de commande, bon de livraison, factures? )
     *
     */
    public function DownloadDocumentsAction()
    {
    	try 
    	{	
			$pdf = new Zend_Pdf();
    		
			//list orders
			$collection = mage::getModel('Orderpreparation/ordertoprepare')
				->getCollection()
				->setOrder('order_id', 'asc');
			
			//Add comments
			if (Mage::getStoreConfig('orderpreparation/printing_options/print_comments') == 1)
			{
				$CommentsModel = Mage::getModel('Orderpreparation/Pdf_SelectedOrdersComments');
				$pdf = $CommentsModel->getPdf($collection);
				for ($i=0;$i<count($CommentsModel->pages);$i++)
					$pdf->pages[] = $CommentsModel->pages[$i];
			}
			
			//rajoute les autres éléments
			foreach ($collection as $item)
			{
				if (Mage::getStoreConfig('orderpreparation/printing_options/print_shipments') == 1)
				{
					//recupere le shipment (si existe)
					$ShipmentId = $item->getshipment_id();
					if ($ShipmentId > 0)
					{
						//recupere le shipment
						$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($ShipmentId);
						if ($shipment->getId())
						{
							
							//Genere le pdf de la facture
							$ShipmentPdfModel = Mage::getModel('sales/order_pdf_shipment');
							$ShipmentPdfModel->pdf = $pdf;
							$ShipmentPdfModel = $ShipmentPdfModel->getPdf(array($shipment));
							
							//Ajoute le pdf du shipment au pdf principal
							for ($i=0;$i<count($ShipmentPdfModel->pages);$i++)
							{
								$pdf->pages[] = $ShipmentPdfModel->pages[$i];
							}
						}						
					}
				}
				
				//ajoute la facture au PDF
				if (Mage::getStoreConfig('orderpreparation/printing_options/print_invoices') == 1)
				{
					$InvoiceId = $item->getinvoice_id();
					if ($InvoiceId > 0)
					{
						//Recupere l'invoice
						$invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($InvoiceId);
						if ($invoice->getId())
						{
							//define printing count
							$printingCount = 1;
							if (mage::getStoreConfig('orderpreparation/printing_options/print_invoice_twice_if_taxless') == 1)
							{
								if ($invoice->getbase_tax_amount() == 0)
									$printingCount++;
							}
							$CurrentPaymentMethod = $invoice->getOrder()->getPayment()->getMethodInstance()->getcode();
							$PaymentMethodsTwice = mage::getStoreConfig('orderpreparation/printing_options/print_invoice_twice_if_payment_method');
							$pos = strpos($PaymentMethodsTwice, $CurrentPaymentMethod);
							if (!($pos === false))
								$printingCount++;
							
							//Add to pdf
							for ($printingNumber=0;$printingNumber<$printingCount;$printingNumber++)
							{
								//Genere le pdf de la facture
								$InvoicePdfModel = Mage::getModel('sales/order_pdf_invoice');
								$InvoicePdfModel->pdf = $pdf;
								$InvoicePdfModel = $InvoicePdfModel->getPdf(array($invoice));
							
								//Ajoute le pdf de la facture au pdf principal
								for ($i=0;$i<count($InvoicePdfModel->pages);$i++)
								{
									//$pdf->pages[] = $InvoicePdfModel->pages[$i];
								}
							}
						}
					}			
				}
			}
			
			$this->_prepareDownloadResponse('documents.pdf', $pdf->render(), 'application/pdf');    		
    	}
    	catch (Exception $ex)
    	{
    		die("Erreur lors de la génération du PDF de facture: ".$ex->getMessage().'<p>'.$ex->getTraceAsString());
    	}
    }
    
    /**
     * Create invoices & shipments for selected orders
     *
     */
    public function CommitAction()
    {

    	//Create task group
    	$taskGroup = 'create_shipments_and_invoices';
		mage::helper('BackgroundTask')->AddGroup($taskGroup, $this->__('Create shipments and invoices'), 'OrderPreparation/OrderPreparation/');
    	
		//Browse selected orders and create tasks
		$OrdersToPrepare = Mage::getModel('Orderpreparation/ordertoprepare')->getSelectedOrders();
		foreach ($OrdersToPrepare as $OrderToPrepare)
		{
			//Create task for current selected order
			mage::helper('BackgroundTask')->AddTask('Create shipment & invoice for order #'.$OrderToPrepare->getId(), 
						'Orderpreparation',
						'createShipmentAndInvoices',
						$OrderToPrepare->getId(),
						$taskGroup
						);	
		}
		
		//Execute task group
		mage::helper('BackgroundTask')->ExecuteTaskGroup($taskGroup);
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
     * Génere le fichier pour export
     *
     */
    public function ExportToCarrierSoftwareAction()
    {
    	try 
    	{
    		//recupere la liste des shipments et le transporteur concerné
    		$CarrierType = $this->getRequest()->getParam('carrier');
	    	$shipments = Mage::getModel('Orderpreparation/ordertoprepare')->GetShipments($CarrierType);
	    	$model = mage::helper('Orderpreparation')->getCarrierModel($CarrierType);

	    	//retourne le fichier
	    	if ($model)
	    	{
		    	$content = $model->CreateExportFile($shipments);
		    	$this->_prepareDownloadResponse($model->getFileName(), $content, 'text/plain');  
	    	}
	    	else 
	    	{
	    		die("Unable to bind carrier '".$CarrierType."'");
	    	}
	    	
	    	//genere le fichier
    	}
    	catch (Exception $ex)
    	{
			die("Erreur lors de l'export : ".$ex->getMessage());    		
    	}
    }
    
    public function ImportTrackingAction()
    {
    	
    	
    	//recupere le fichier uploadé
    	$carrierCode = $this->getRequest()->getPost('carrier');
    	$CarrierModel = mage::helper('Orderpreparation')->getCarrierModel($carrierCode);
    	$uploader = null;
    	$Error = false;
    	try 
    	{
		    $uploader = new Varien_File_Uploader('tracking_file');
		    $uploader->setAllowedExtensions(array('txt','csv'));    		
    	}
    	catch (Exception $ex)
    	{
	    	$Error = true;
    	}
    	
		if ($Error)
		{
	   		Mage::getSingleton('adminhtml/session')->addError($this->__('An error occured while uploading file.'));
		}
		else 
		{
	    	$path = Mage::app()->getConfig()->getTempVarDir().'/import/';
		    $uploader->save($path);
		    if ($uploadFile = $uploader->getUploadedFileName()) 
		    {
		        //lit le contenu du fichier
		        $path .= $uploadFile;
		        $content = file($path);
		        
		        //importe
		        $nb = $CarrierModel->Importfile($content);
		        
		        //Met a jour le summary pour toutes les selected orders
		        $model = mage::getModel('Orderpreparation/ordertoprepare');
		        $orders = $model->getCollection();
		        foreach ($orders as $order)
		        {
		        	$realOrder = mage::getModel('sales/order')->load($order->getorder_id());
		        	$order->setdetails($model->getDetailsForOrder($realOrder))->save();
		        }
		        
		        //confirme
		        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('File successfully imported: ').$nb.' tracking numbers imported');
		    }
		    else 
		    	Mage::getSingleton('adminhtml/session')->addError($this->__('An error occured while uploading file.'));
		}
		
	    //redirige
	    $this->_redirect('OrderPreparation/OrderPreparation/');

    }

    
    /**
     * Fin, on supprime les enregistrements
     *
     */
    public function FinishAction()
    {
    	Mage::getModel('Orderpreparation/ordertoprepare')->Finish();
    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order preparation complete'));
    	
    	//redirige sur la page de sélection des commandes
    	$this->_redirect('OrderPreparation/OrderPreparation/');
    }
   
    /**
     * Notifier les clients de l'envoi de leur colis
     *
     */
    public function NotifyCustomersAction()
    {
    	//Notifie
    	Mage::getModel('Orderpreparation/ordertoprepare')->NotifyCustomers();
    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Customers successfully notified.'));
    	
    	//redirige sur la page de sélection des commandes
    	$this->_redirect('OrderPreparation/OrderPreparation/');
    	
    }
    
    
    
	public function EndsWith($FullStr, $EndStr)
	{
	        // Get the length of the end string
	    $StrLen = strlen($EndStr);
	        // Look at the end of FullStr for the substring the size of EndStr
	    $FullStrEnd = substr($FullStr, strlen($FullStr) - $StrLen);
	        // If it matches, it does end with EndStr
	    return $FullStrEnd == $EndStr;
	}

    /**
     * Enregistre les modifs pour une commande 
     * (c'est a dire commentaire & qté reservée par ligne commande)
     *
     */
    public function SaveOrderAction()
    {
    	try 
    	{
    		    //parcourt les data
		    	$order_id = $this->getRequest()->getParam('order_id');
		    	$order = mage::getModel('sales/order')->load($order_id);
		    	$data = $this->getRequest()->getParams();
		    	
		    	//shipment & invoice
		    	$shipment_id = $this->getRequest()->getParam('shipment_id');
		    	$invoice_id = $this->getRequest()->getParam('invoice_id');
		    	$tracking_num = $this->getRequest()->getParam('tracking_num');
		    	if ($shipment_id || $invoice_id)
		    	{
		    		mage::getModel('Orderpreparation/ordertoprepare')->load($order_id, 'order_id')
		    			->setshipment_id($shipment_id)
		    			->setinvoice_id($invoice_id)
		    			->save();
		    	}
		    	
		    	//Si un numéro de tracking a été saisi, on l'ajoute
		    	if ($tracking_num)
		    	{
		    			//met a jour les shipment
						$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipment_id);
						if ($shipment->getOrder())
						{
							//$order = mage::getmodel('sales/order')->load($order_id);
							$Carrier = str_replace('_', '', $order->getshipping_method());
							$track = new Mage_Sales_Model_Order_Shipment_Track();
						    $track->setNumber($tracking_num)
						          ->setCarrierCode($Carrier)
		                    	  ->setTitle('Shipment');
							$shipment->addTrack($track)->save();
						}
		    	}
		    	
		    	//parcourt les champs
		    	foreach ($data as $cle => $value)
		    	{
		    		$t = explode('_', $cle);

		    		if (strpos($cle, 'omments') >= 1)
		    		{
		    			//recupere les infos
		    			$OrderItemId = $t[1];
		    			$OrderItem = mage::getModel('sales/order_item')->load($OrderItemId);
			    		$Comments = $value;
		    			
		    			//met a jour
		    			$OrderItem->setcomments($Comments)->save();
		    		}
		    	}
		    	
				//confirme
			    Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Changes successfully saved'));
    	}
    	catch (Exception $ex)
    	{
    		Mage::getSingleton('adminhtml/session')->addError($this->__('An error occured while saving changes: ').$ex->getMessage().' '.$ex->getTraceAsString());
    	}
	    
    	    //redirige
	    $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order_id));
	        
    }
    
    /*
    *	Imprime la liste des produits d'une commande avec les commentaires
    *
    */
    public function PrintCommentsAction()
    {
    	try
    	{
    		//recupere la commande
    		$orderId = $this->getRequest()->getParam('order_id');
    		$order = Mage::getModel('sales/order')->load($orderId);
    		
	    	//imprime le récap des produits
			$obj = new MDN_Orderpreparation_Model_Pdf_OrderPreparationCommentsPdf();
			$pdf = $obj->getPdf($order);
			$this->_prepareDownloadResponse(mage::helper('purchase')->__('order_comments').'.pdf', $pdf->render(), 'application/pdf');    		
				
    	}
    	catch (Exception $ex)
    	{
    		die("Erreur lors de la génération du PDF de commentaires commande: ".$ex->getMessage().'<p>'.$ex->getTraceAsString());
    	}
    }

    /**
     * Méthode pour stocker les id des fullstock orders & sotckless dans une table
     *
     */
    public function RefreshListAction()
    {
    	//Truncate table
    	Mage::getResourceModel('Orderpreparation/ordertopreparepending')->TruncateTable();
    	
    	//retrieve pendings orders ids
    	$pendingOrderIds = mage::getModel('Orderpreparation/ordertoprepare')->getPendingOrdersIds();
    	
    	//create task group
    	$taskGroup = 'dispatch_pending_orders';
		mage::helper('BackgroundTask')->AddGroup($taskGroup, $this->__('Dispatch pendings orders'), 'OrderPreparation/OrderPreparation/');

		//Create task for each orders
		for($i=0;$i<count($pendingOrderIds);$i++)
		{
				$orderId = $pendingOrderIds[$i];
				mage::helper('BackgroundTask')->AddTask('Dispatch order #'.$orderId, 
										'Orderpreparation',
										'dispatchOrder',
										$orderId,
										$taskGroup
										);	
		}

		//execute task group
		mage::helper('BackgroundTask')->ExecuteTaskGroup($taskGroup);

    }
    

    
    /**
     * Rtourne la liste des selected orders
     *
     */
    public function SelectedOrderGridAction()
    {
    	$this->loadLayout();
        $this->getResponse()->setBody(
        	$this->getLayout()->createBlock('Orderpreparation/SelectedOrders')->toHtml()
        );
    }
    
    /**
     * Rtourne la liste des selected orders
     *
     */
    public function FullStockOrderGridAction()
    {
    	$this->loadLayout();
        $this->getResponse()->setBody(
        	$this->getLayout()->createBlock('Orderpreparation/FullStockOrders')->toHtml()
        );
    }

    
        
    /**
     * Rtourne la liste des selected orders
     *
     */
    public function StocklessOrderGridAction()
    {
    	$this->loadLayout();
        $this->getResponse()->setBody(
        	$this->getLayout()->createBlock('Orderpreparation/StocklessOrders')->toHtml()
        );
    }
}