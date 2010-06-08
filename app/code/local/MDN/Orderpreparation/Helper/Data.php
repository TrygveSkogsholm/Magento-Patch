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
class MDN_Orderpreparation_Helper_Data extends Mage_Core_Helper_Abstract
{
	
	/*##########################################################################################################################
	############################################################################################################################
	METHODS USED WITH BACKGROUNDTASK MODULE	
	############################################################################################################################
	##########################################################################################################################*/
	
	/**
	 * Notify Shipment
	 *
	 * @param unknown_type $shipmentId
	 */
	public function notifyShipment($shipmentId)
	{
			$shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
			if (!$shipment->getEmailSent())
			{		
				$shipment->sendEmail(true);
				$shipment->setEmailSent(true)->save();
			}
	}
	
	/**
	 * Notify Invoice
	 *
	 * @param unknown_type $invoiceId
	 */
	public function notifyInvoice($invoiceId)
	{
			$invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
			if (!$invoice->getEmailSent())
			{

				$invoice->sendEmail(true);
				$invoice->setEmailSent(true)->save();

			}
	
	}
	
	/**
	 * Add an order to selected orders
	 *
	 * @param unknown_type $orderId
	 */
	public function addToSelectedOrders($orderId)
	{
		//Charge le numéro de commande à partir du no de l'enregistrement dans le cache
		$RealOrderId = mage::getModel('Orderpreparation/ordertopreparepending')
			->load($orderId)
			->getopp_order_id();
		Mage::getModel('Orderpreparation/ordertoprepare')->AddSelectedOrder($RealOrderId);

	}
	
	/**
	 * Create invoice & shipment for an order
	 *
	 * @param unknown_type $orderToPrepareId
	 */
	public function createShipmentAndInvoices($orderId)
	{		
			//Load order to prepare
			$error = '';
			$order = mage::getModel('sales/order')->load($orderId);
			$OrderToPrepare = mage::getModel('Orderpreparation/ordertoprepare')->load($orderId, 'order_id');
						
			//if order cancelled, return
			if ($order->getStatus() == 'canceled')
				return false;			
			
			//si la commande n'a pas de shipment on la traite
			if (!Mage::getModel('Orderpreparation/ordertoprepare')->ShipmentCreatedForOrder($order->getid()))
			{
				try 
				{	
					if ($order->canShip())
					{			
						Mage::getModel('Orderpreparation/ordertoprepare')->CreateShipment($order);	
		                $order->UpdateProductsOrdererQty();					
					}
				}
				catch (Exception $ex)
				{
					$error .= 'Erreur lors de la création du shipment<br>'.$ex.'<br>'.$ex->getTraceAsString();
				}	
			}
			
			//si la commande n'a pas de facture, on la traite
			if (!Mage::getModel('Orderpreparation/ordertoprepare')->InvoiceCreatedForOrder($order->getid()))
			{
				try
				{
					Mage::getModel('Orderpreparation/ordertoprepare')->CreateInvoice($order);		
				}
				catch (Exception $ex)
				{
					$error .= 'Erreur lors de la création du invoice<br>'.$ex.'<br>'.$ex->getTraceAsString();
				}
			}	
	
			//Upda order to prepare cache details
			$OrderToPrepare = mage::getModel('Orderpreparation/ordertoprepare')->load($orderId, 'order_id');
			$OrderToPrepare->setdetails(Mage::getModel('Orderpreparation/ordertoprepare')->getDetailsForOrder($order))->save();

			//raise error if exists
			if ($error != '')
				throw new Exception($error);
	}
	
	/**
	 * Dispatch pending order to fullstock or stockless tab
	 *
	 * @param unknown_type $orderId
	 */
	public function dispatchOrder($orderId)
	{
		$order = mage::getModel('sales/order')->load($orderId);
		mage::getmodel('Orderpreparation/ordertoprepare')->DispatchOrder($order);
	}
	
}

?>