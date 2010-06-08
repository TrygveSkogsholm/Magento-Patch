<?php
/*
 * Created on Jun 26, 2008
 *
 */

class MDN_Orderpreparation_OnePagePreparationController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
				
		if ($this->getRequest()->getParam('confirm') == 1)
		{
			Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Information saved.'));
		}
		
		$this->loadLayout();

		//set current order
		$previousOrderId = $this->getRequest()->getParam('previous_order_id');
		$forceOrderId = $this->getRequest()->getParam('force_order_id');
		$currentOrder = null;
		if ($forceOrderId != null)
		{
			$currentOrder = mage::getModel('Orderpreparation/Ordertoprepare')->load($forceOrderId, 'order_id');
		}
		else 
		{
			$collection = mage::helper('Orderpreparation/OnePagePreparation')->getOrderList('*');
			$currentOrder = $this->getCurrentOrder($previousOrderId, $collection);
		}
		if ($currentOrder == null)
		{
    		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('All orders have been processed.'));
			$this->_redirect('OrderPreparation/CarrierTemplate/ImportTracking');
			return true;
		}
		else 
		{
			$order = mage::getModel('sales/order')->load($currentOrder->getorder_id());
			$this->getLayout()->getBlock('onepagepreparation')->setCurrentOrder($order);
		}

		
		$this->renderLayout();
	}
	
	/**
	 * retrieve current order
	 *
	 * @param unknown_type $previousOrderId
	 * @param unknown_type $collection
	 * @return unknown
	 */
	private function getCurrentOrder($previousOrderId, $collection)
	{
		$selectNext = false;
		if ($previousOrderId == '')
			$selectNext = true;
		foreach ($collection as $item)
		{
			if ($selectNext)
				return $item;
			else 
			{
				if ($item->getorder_id() == $previousOrderId)
					$selectNext = true;
			}
		}
		
		//we've reach the end of the list
		return null;
	}
	
	/**
	 * Save order preparation data in ajax
	 *
	 */
	public function saveAction()
	{
		try 
		{
	    	//retrieve datas
			$orderId = $this->getRequest()->getPost('order_id');		
			$order = mage::getModel('sales/order')->load($orderId);
			$orderPreparation = mage::getModel('Orderpreparation/Ordertoprepare')->load($orderId, 'order_id');
			$weight = $this->getRequest()->getPost('real_weight');		
			$create = $this->getRequest()->getPost('create');	
			$printDocuments = $this->getRequest()->getPost('print_documents');	
			$printShippingLabel = $this->getRequest()->getPost('print_shipping_label');	
			
			//join custom values
			$customValuesString = '';
			$carrierTemplate = mage::helper('Orderpreparation/CarrierTemplate')->getTemplateForOrder($order);
			if ($carrierTemplate)
			{
				$customValues = $this->getRequest()->getPost('custom_values');	
				foreach ($carrierTemplate->getCustomFields() as $field)
				{
					if (isset($customValues[$field->getCode()]))
					{
						$value = $customValues[$field->getCode()];
						$value = str_replace(';', ' ', $value);
						$customValuesString .= $field->getCode().'='.$value.';';	
					}
				}
			}
			
			//save
			if ($orderPreparation->getId())
			{
				//save main information
				$orderPreparation
					->setreal_weight($weight)
					->setcustom_values($customValuesString)
					->save();
					
				//save items information
				foreach ($orderPreparation->getItems() as $item)
				{
					$qty = $this->getRequest()->getPost('qty_'.$item->getId());
					if ($qty >= 0)
						$item->setqty($qty)->save();
				}
				
			}			

			//create shipment & invoice
			if ($create == 1)
				$this->createInvoiceAndShipment($orderId);
			
			//print documents
			if ($printDocuments == 1)
			{
				$pdf = mage::helper('Orderpreparation/OnePagePreparation')->getPdfDocumentsForOrder($orderId);							
				if ($pdf != null)
				{
					//print
					$fileName = 'order_'.$orderId.'.pdf';
					mage::helper('ClientComputer')->printDocument($pdf->render(), $fileName, 'Order preparation : print documents for order #'.$orderId);
				}
			}
			
			//print shipping label
			if ($printShippingLabel == 1)
			{
				$order = mage::getModel('sales/order')->load($orderId);
		 		$template = mage::helper('Orderpreparation/CarrierTemplate')->getTemplateForOrder($order);
		 		if ($template != null)
		 		{
		 			$collection = mage::getModel('Orderpreparation/ordertoprepare')
		 								->getCollection()
		 								->addFieldToFilter('order_id', $orderId);
					$content = $template->createExportFile($collection);
					mage::helper('ClientComputer')->copyFile($content, $template->getct_export_filename(), 'directory_'.$template->getct_shipping_method(), 'Print shipping labels for order #'.$order->getIncrementId());
					if ($template->getct_export_witness_filename() != '')
					{
						mage::helper('ClientComputer')->copyFile('XXX', $template->getct_export_witness_filename(), 'directory_'.$template->getct_shipping_method(), 'Witness file for order #'.$order->getIncrementId());
					}
		 		}
			}
			
	    	//Confirm
    	    $response = array(
                'error'     => false
            );	

		}
	    catch (Exception $ex)
    	{
			//manage exception    		
		    $response = array(
                'error'     => true,
                'message'   => $this->__($ex->getMessage())
            );		
    	}

		//return result
	    if (is_array($response)) {
            $response = Zend_Json::encode($response);
            $this->getResponse()->setBody($response);
        }
		
	}
	
	/**
	 * Create shipment & invoice for order
	 */
	private function createInvoiceAndShipment($orderId)
	{
		//retrieve data
		$orderPreparation = mage::getModel('Orderpreparation/Ordertoprepare')->load($orderId, 'order_id');
		$order = mage::getModel('sales/order')->load($orderId);
		
		//create shipment
		if (!Mage::getModel('Orderpreparation/ordertoprepare')->ShipmentCreatedForOrder($order->getid()))
		{
			if ($order->canShip())
			{			
				Mage::getModel('Orderpreparation/ordertoprepare')->CreateShipment($order);	
                $order->UpdateProductsOrdererQty();					
			}
		}
		
		//create invoice
		if (!Mage::getModel('Orderpreparation/ordertoprepare')->InvoiceCreatedForOrder($order->getid()))
		{
			Mage::getModel('Orderpreparation/ordertoprepare')->CreateInvoice($order);		
		}					
	}
	
/**
	 * Print documents for 1 order
	 *
	 */
	public function PrintDocumentsAction()
	{
		//retrieve information
		$orderId = $this->getRequest()->getParam('order_id');
		$pdf = mage::helper('Orderpreparation/OnePagePreparation')->getPdfDocumentsForOrder($orderId);
				
		if ($pdf != null)
		{
			//print
			$fileName = 'order_'.$orderId.'.pdf';
			mage::helper('ClientComputer')->printDocument($pdf->render(), $fileName, 'Order preparation : print documents for order #'.$orderId);
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
     * Print picking list
     *
     */
    public function PrintPickingListAction()
    {
	    $obj = mage::getModel('Orderpreparation/Pdf_SelectedOrdersProductsSummaryPdf');
		$pdf = $obj->getPdf(array());
		
		//print
		$fileName = 'picking_list.pdf';
		try 
		{
			mage::helper('ClientComputer')->printDocument($pdf->render(), $fileName, 'Order preparation : Print picking list');
			
		    $response = array(
	            'error'     => false,
	            'message'	=> $this->__('Picking list printed')
		        );	
		
		}
		catch (Exception $ex)
		{
		    $response = array(
	            'error'     => true,
	            'message'	=> $ex->getMessage()
		        );				
		}
		
    	//return response
        $response = Zend_Json::encode($response);
        $this->getResponse()->setBody($response);

    }
        
    /**
     * Print picking list
     *
     */
    public function DownloadPickingListAction()
    {
	    $obj = mage::getModel('Orderpreparation/Pdf_SelectedOrdersProductsSummaryPdf');
		$pdf = $obj->getPdf(array());
		$this->_prepareDownloadResponse('picking_list.pdf', $pdf->render(), 'application/pdf');  
    }
    
    /**
     * Download pdf with invoice & shipment
     *
     */
    public function DownloadDocumentsAction()
    {
 		$orderId = $this->getRequest()->getParam('order_id');
     	$pdf = mage::helper('Orderpreparation/OnePagePreparation')->getPdfDocumentsForOrder($orderId);
     	if ($pdf != null)
     	{
	     	$fileName = 'order_'.$orderId.'.pdf';
	     	$this->_prepareDownloadResponse($fileName, $pdf->render(), 'application/pdf');      		
     	}
     	else 
     		die('There is no document to download');
    }
    
    /**
     * Send shipping file to carrier software
     *
     */
    public function PrintShippingLabelAction()
    {
    	$orderId = $this->getRequest()->getParam('order_id');
		$order = mage::getModel('sales/order')->load($orderId);
 		$template = mage::helper('Orderpreparation/CarrierTemplate')->getTemplateForOrder($order);
 		if ($template != null)
 		{
 			$collection = mage::getModel('Orderpreparation/ordertoprepare')
 								->getCollection()
 								->addFieldToFilter('order_id', $orderId);
			$content = $template->createExportFile($collection);
			mage::helper('ClientComputer')->copyFile($content, $template->getct_export_filename(), 'directory_'.$template->getct_shipping_method(), 'Print shipping labels for order #'.$order->getIncrementId());
			if ($template->getct_export_witness_filename() != '')
			{
				mage::helper('ClientComputer')->copyFile('XXX', $template->getct_export_witness_filename(), 'directory_'.$template->getct_shipping_method(), 'Witness file for order #'.$order->getIncrementId());
			}
 		}
 		else 
 			die('Unable to find template for this order with shipping method = '.$order->getshipping_method());
    }
    
    /**
     * Download file for shipping software for the current order
     *
     */
 	public function DownloadShippingLabelAction()
 	{
 		$orderId = $this->getRequest()->getParam('order_id');
 		$order = mage::getModel('sales/order')->load($orderId);
 		$template = mage::helper('Orderpreparation/CarrierTemplate')->getTemplateForOrder($order);
 		if ($template != null)
 		{
 			$collection = mage::getModel('Orderpreparation/ordertoprepare')
 								->getCollection()
 								->addFieldToFilter('order_id', $orderId);
			$content = $template->createExportFile($collection);
			$this->_prepareDownloadResponse($template->getct_export_filename(), $content, 'text/plain');
 		}
 		else 
 			die('Unable to find template for this order with shipping method = '.$order->getshipping_method());
 	}
 	
 		
	/**
	 * Download file to import in carrier software
	 *
	 */
	public function DownloadCarrierExportFileAction()
	{
		$templateId = $this->getRequest()->getParam('template_id');
		$template = mage::getModel('Orderpreparation/CarrierTemplate')->load($templateId);
		if ($template->getId())
		{
			$orderPreparationCollection = mage::getModel('Orderpreparation/ordertoprepare')->getCollection();
			$content = $template->createExportFile($orderPreparationCollection);
			
			$this->_prepareDownloadResponse($template->getct_export_filename(), $content, 'text/plain');
		}
	}
	
	public function endAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}
	
}