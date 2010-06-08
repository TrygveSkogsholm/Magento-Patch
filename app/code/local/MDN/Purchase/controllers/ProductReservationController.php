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
class MDN_Purchase_ProductReservationController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Reserve product
	 *
	 */
	public function ReserveAction()
	{
		//retrieve datas
		$productId = $this->getRequest()->getParam('product_id');
		$orderId = $this->getRequest()->getParam('order_id');
		$product = mage::getModel('catalog/product')->load($productId);		
		$model = mage::helper('purchase/ProductReservation');
		$order = mage::getModel('sales/order')->load($orderId);
		
		//calculate qty
		mage::helper('purchase/ProductReservation')->reserveProductForOrder($orderId, $productId);
		
		//redirect
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Product reserved'));
		if ($this->getRequest()->getParam('return_to_order') == '')
			$this->_redirect('Purchase/Products/Edit', array('product_id' => $productId, 'tab' => 'pending_orders'));
		else 
			$this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
	}
	
	/**
	 * Release product
	 *
	 */
	public function ReleaseAction()
	{
		$productId = $this->getRequest()->getParam('product_id');
		$orderId = $this->getRequest()->getParam('order_id');
		$product = mage::getModel('catalog/product')->load($productId);
		
		mage::helper('purchase/ProductReservation')->releaseProductForOrder($orderId, $productId);
		
		//redirect
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Product released'));
		if ($this->getRequest()->getParam('return_to_order') == '')
			$this->_redirect('Purchase/Products/Edit', array('product_id' => $productId, 'tab' => 'pending_orders'));
		else 
			$this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
	}
}
