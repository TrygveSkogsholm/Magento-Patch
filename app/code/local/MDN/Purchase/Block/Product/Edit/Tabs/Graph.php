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
class MDN_Purchase_Block_Product_Edit_Tabs_Graph extends Mage_Adminhtml_Block_Widget_Form
{
	private $_productId = null;
	private $_product = null;
		
	/**
	 * Définition du numéro de produit
	 *
	 * @param unknown_type $ProductId
	 */
	public function setProductId($ProductId)
	{
		$this->_productId = $ProductId;
		return $this;
	}
	
	/**
	 * Retourne le du produit courant
	 *
	 * @return unknown
	 */
	public function getProduct()
	{
		if ($this->_product == null)
			$this->_product = mage::getModel('catalog/product')->load($this->_productId);
		return $this->_product;
	}
	
	/**
	 * Retourne l'url de l'image correspondant au graph
	 *
	 * @return unknown
	 */
	public function getGraphImageUrl()
	{
	
	}
	
	public function getGroupByAsCombo($name)
	{
		$retour = '<select name="'.$name.'" id="'.$name.'">';

		$retour .= '<option value="day">'.$this->__('Day').'</option>';
		$retour .= '<option value="week">'.$this->__('Week').'</option>';
		$retour .= '<option value="month" selected>'.$this->__('Month').'</option>';
		$retour .= '<option value="year">'.$this->__('Year').'</option>';
		
		$retour .= '</select>';
		return $retour;
	}
}