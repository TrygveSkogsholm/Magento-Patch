<?php

/**
 * Controller pour différentes actions secondaires (et qui ne justifient pas d'avoir leur propres controller)
 *
 */
class MDN_Purchase_MiscController extends Mage_Adminhtml_Controller_Action
{
	
    /**
     * Initialize order model instance
     *
     * @return Mage_Sales_Model_Order || false
     */
    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return $order;
    }
    
	/**
	 * Annulation d'une commande
	 *
	 */
	public function CancelorderAction()
	{
		
        if ($order = $this->_initOrder()) {
            try {
            	//Annule la commande
                $order->cancel()
                    ->save();
                    
                //Met a jour les qte reservées et commandées
                $order->UpdateProductsOrdererQty();
                
		        //dispatch order in order preparation tab
				mage::helper('BackgroundTask')->AddTask('Dispatch order #'.$order->getId(), 
										'Orderpreparation',
										'dispatchOrder',
										$order->getId()
										);	
                
                //Redirige
                $this->_getSession()->addSuccess(
                    $this->__('Order was successfully cancelled.')
                );
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('Order was not cancelled : ').$e->getMesssage());
            }
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $order->getId()));
        }
	}
	
	/**
	 * Validation du paiement d'une commande
	 *
	 */
	public function ValidatepaymentAction()
	{
		//Met a jour les paiement
		$orderIds = $this->getRequest()->getPost('order_ids');
		if (!empty($orderIds)) 
		{
			foreach ($orderIds as $orderId)
			{
				$order = mage::getModel('sales/order')->load($orderId);
				$order->setpayment_validated(1)->save();
			}
		}
		
		//Confirme
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Payments validated'));	

		
		//redirige
		$this->_redirect('adminhtml/sales_order/');
	}
		
	/**
	 * Devalidation du paiement d'une commande
	 *
	 */
	public function CancelpaymenttAction()
	{
		//Mise a jour des paiements
		$orderIds = $this->getRequest()->getPost('order_ids');
		if (!empty($orderIds)) 
		{
			foreach ($orderIds as $orderId)
			{
				$order = mage::getModel('sales/order')->load($orderId);
				$order->setpayment_validated(0)->save();
			}
		}
				
		//Confirme
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Payments canceled'));	

		
		//redirige
		$this->_redirect('adminhtml/sales_order/');

	}
	
	/**
	 * Modifie l'etat du paiement pour une commande
	 *
	 */
	public function SavepaymentAction()
	{
		//recupere les infos
		$orderId = $this->getRequest()->getParam('order_id');
		$value = $this->getRequest()->getParam('payment_validated');
		
		//Charge la commande et modifie
		$order = mage::getModel('sales/order')->load($orderId);
		$order->setpayment_validated($value)->save();
		
		//Confirme
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Payment state updated'));	

		//redirige
		$this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
	}
	
	/**
	 * Annulation commandes en masse (surchargé pour mettre a jour qte commandées des produits
	 *
	 */
	public function massCancelAction()
	{
		$orderIds = $this->getRequest()->getPost('order_ids', array());
        $countCancelOrder = 0;
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($order->canCancel()) 
            {
                $order->cancel()
                    ->save();
                $countCancelOrder++;
                
                //Met a jour les qte reservées et commandées
                $order->UpdateProductsOrdererQty();
                
            }
        }
        if ($countCancelOrder>0) {
            $this->_getSession()->addSuccess($this->__('%s order(s) successfully canceled and stocks updated', $countCancelOrder));
        }
        else {
            // selected orders is not available for cancel
        }
        $this->_redirect('adminhtml/sales_order');
	}
	
	/**
	 * Identifie les erreurs sur les stocks
	 *
	 */
	public function IdentifyErrorsAction()
	{
		$this->loadLayout();
        $this->renderLayout();
	}
	
	/**
	 * Met a jour les stocks pour un produit
	 *
	 */
	public function ComputeStockAction()
	{
		$ProductId = $this->getRequest()->getParam('product_id');
		$product = mage::getModel('catalog/product')->load($ProductId);
    	    	
    	//met a jour les qte
    	mage::getModel('Purchase/StockMovement')->ComputeProductStock($ProductId);
    	$model = mage::getModel('Purchase/Productstock');
    	$model->UpdateOrderedQty($product);

    	//check and repair reserved qty
    	mage::helper('purchase/ProductReservation')->UpdateReservedQty($product);

    	
		$this->_getSession()->addSuccess('Product stocks updated');
		$this->_redirect('Purchase/Misc/IdentifyErrors');
	}
}