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
class MDN_Purchase_TestController extends Mage_Adminhtml_Controller_Action
{
	//Liste des taux de tax
	public function indexAction()
	{
		die('totto');
	}
	
	public function showShipmentAction()
	{
		$shipmentId = $this->getRequest()->getParam('shipment_id');
		$shipment = mage::getModel('sales/order_shipment')->load($shipmentId);
		foreach($shipment->getAllItems() as $item)
		{
			echo '<p>'.$item->getqty().'x '.$item->getName();
		}
		die('<p>---> Shipment : '.$shipmentId);
	}
	
}