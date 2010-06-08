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
class MDN_Purchase_Block_Planning_Cart extends Mage_Checkout_Block_Cart_Abstract
{
	private $_planning = null;
	
	/**
	 * Return delivery msg
	 *
	 */
	public function getDeliveryMsg()
	{
		
		$deliveryDate = mage::helper('core')->formatDate($this->getPlanning()->getpsop_anounced_date(), 'medium');
		$deliveryMaxDate = mage::helper('core')->formatDate($this->getPlanning()->getpsop_anounced_date_max(), 'medium');
		
		//define message
		$retour = '<div>';
		$retour .= mage::helper('purchase')->__('Your order should be delivered on <b>%s</b>', $deliveryDate);
		$retour .= '<br>'.mage::helper('purchase')->__('At worst, we commit you deliver on <b>%s</b>', $deliveryMaxDate);
		if (false)
			$retour .= '<br>'.mage::helper('purchase')->__('Those information implies we receive you payment under %s days', $paymentMethodDelay);
		$retour .= '</div>';
		
		return $retour;
	}
	
	/**
	 * Return planning object
	 *
	 * @return unknown
	 */
	public function getPlanning()
	{
		if ($this->_planning == null)
		{
			$quote = $this->getQuote();
			$this->_planning = mage::helper('purchase/Planning')->getEstimationForQuote($quote);
		}
		return $this->_planning;
	}
	
	/**
	 * Return comments
	 *
	 * @return unknown
	 */
	public function getComments()
	{
		$retour = '';
		
		$retour.= $this->getPlanning()->getpsop_consideration_comments().'<br>';
		$retour.= $this->getPlanning()->getpsop_fullstock_comments().'<br>';
		$retour.= $this->getPlanning()->getpsop_shipping_comments().'<br>';
		$retour.= $this->getPlanning()->getpsop_delivery_comments().'<br>';
		
		return $retour;
	}
	
}