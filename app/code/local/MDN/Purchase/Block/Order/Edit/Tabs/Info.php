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
class MDN_Purchase_Block_Order_Edit_Tabs_Info extends Mage_Adminhtml_Block_Widget_Form
{
	
	private $_order = null;
	private $_supplier = null;
	
	/**
	 * Constructeur: on charge
	 *
	 */
	public function __construct()
	{
		
		$this->_blockGroup = 'Purchase';
        $this->_objectId = 'id';
        $this->_controller = 'order';
		
		parent::__construct();
		
		//charge le manufacturer
        $po_num = Mage::app()->getRequest()->getParam('po_num', false);	
        $model = Mage::getModel('Purchase/Order');
		$this->_order = $model->load($po_num);
		$this->_supplier = mage::getModel('Purchase/Supplier')->load($this->_order->getpo_sup_num());
		
		$this->setTemplate('Purchase/Order/Edit/Tab/Info.phtml');
	}
			
	/**
	 * Retourne l'url pour delete
	 *
	 */
	public function getDeleteUrl()
	{
		return $this->getUrl('Purchase/Orders/Delete').'po_num/'.$this->getOrder()->getId();
	}
	
	/**
	 * Retourne l'objet
	 *
	 * @return unknown
	 */
	public function getOrder()
	{
		return $this->_order;
	}
	
	/**
	 * Retourne le fournisseur
	 *
	 */
	public function getSupplier()
	{
		return $this->_supplier;
	}
	
	/**
	 * Retourne la liste des devises sous la forme d'un combo
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function getCurrencyAsCombo($name = 'currency', $value = '')
	{
		$retour = '<select  id="'.$name.'" name="'.$name.'">';
		$collection = Mage::app()->getLocale()->getOptionAllCurrencies();
		foreach($collection as $item)
		{
			if ($value == $item['value'])
				$selected = ' selected ';
			else 
				$selected = '';
			$retour .= '<option value="'.$item['value'].'" '.$selected.'>'.$item['label'].'</option>';
		}
		$retour .= '</select>';
		return $retour;
	}
	
	/**
	 * Retourne la liste des transporteur sous la forme d'un combo
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function getCarriersAsCombo($name = 'carriers', $value = '')
	{
		$retour = '<select  id="'.$name.'" name="'.$name.'">';
		$collection =  explode(',', Mage::getStoreConfig('purchase/configuration/order_carrier'));
		foreach($collection as $item)
		{
			if (strtolower($value) == strtolower($item))
				$selected = ' selected ';
			else 
				$selected = '';
			$retour .= '<option value="'.$item.'" '.$selected.'>'.$item.'</option>';
		}
		$retour .= '</select>';
		return $retour;
	}
	
	/**
	 * Retourne la liste des modes de paiement sous la forme d'un combo
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function getPaymentModeAsCombo($name = 'carriers', $value = '')
	{
		$retour = '<select  id="'.$name.'" name="'.$name.'">';
		$collection = explode(',', Mage::getStoreConfig('purchase/configuration/order_payment_method'));
		foreach($collection as $item)
		{
			if (strtolower($value) == strtolower($item))
				$selected = ' selected ';
			else 
				$selected = '';
			$retour .= '<option value="'.$item.'" '.$selected.'>'.$item.'</option>';
		}
		$retour .= '</select>';
		return $retour;
	}

	/**
	 * Return statuses as combo
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function getStatusAsCombo($name, $defaultValue = '')
	{
		$retour = '<select  id="'.$name.'" name="'.$name.'">';
		$statuses = $this->getOrder()->getStatuses();
		foreach($statuses as $key => $value)
		{
			if ($key == $defaultValue)
				$selected = ' selected ';
			else 
				$selected = '';
			$retour .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
		$retour .= '</select>';
		return $retour;
				
	}
}
