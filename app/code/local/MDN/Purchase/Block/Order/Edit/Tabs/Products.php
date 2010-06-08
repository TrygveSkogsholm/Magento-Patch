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
class MDN_Purchase_Block_Order_Edit_Tabs_Products extends Mage_Adminhtml_Block_Widget_Form
{
	private $_order = null;
	
	/**
	 * Constructeur: on charge
	 *
	 */
	public function __construct()
	{
		
		parent::__construct();
		
		$this->setTemplate('Purchase/Order/Edit/Tab/Products.phtml');
	}	
	
		
	/**
	 * Retourne l'objet
	 *
	 * @return unknown
	 */
	public function getOrder()
	{
		if ($this->_order == null)
		{
	        $po_num = Mage::app()->getRequest()->getParam('po_num', false);	
	        $model = Mage::getModel('Purchase/Order');
			$this->_order = $model->load($po_num);
		}
		return $this->_order;
	}
	
	/**
	 * Retourne la liste des produits de la commande
	 *
	 */
	public function getProducts()
	{		
		return $this->getOrder()->getProducts();
	}
	
	/**
	 * Retourne le dernier prix d'achat sans frais d'approche pour un produit
	 *
	 * @param unknown_type $ProductId
	 */
	public function GetLastPriceWithoutFees($ProductId)
	{
		$sql = 'select pop_price_ht_base from '.mage::getModel('Purchase/Constant')->getTablePrefix().'purchase_order_product, '.mage::getModel('Purchase/Constant')->getTablePrefix().'purchase_order where pop_order_num = po_num and po_status = \''.MDN_Purchase_Model_Order::STATUS_COMPLETE.'\' and pop_price_ht_base > 0 and pop_product_id = '.$ProductId.' order by po_num DESC LIMIT 1';
		$retour = mage::getResourceModel('sales/order_item_collection')->getConnection()->fetchOne($sql);
		$retour = number_format($retour, 2);
		return $retour;
	}
	
}
	