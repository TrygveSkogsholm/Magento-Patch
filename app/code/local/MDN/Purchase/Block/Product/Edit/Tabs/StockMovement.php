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
class MDN_Purchase_Block_Product_Edit_Tabs_StockMovement extends Mage_Adminhtml_Block_Widget_Form
{
	private $_productId = null;
	
	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Définition du numéro de produit
	 *
	 * @param unknown_type $ProductId
	 */
	public function setProductId($ProductId)
	{
		$this->_productId = $ProductId;
	}
	
	/**
	 * Rtourne les movement de stock pour un produit
	 *
	 * @param unknown_type $ProductId
	 * @return unknown
	 */
	public function getCollection()
	{
		$model = Mage::getModel('Purchase/StockMovement');
		$collection = $model->loadByProduct($this->getProductId());
		return $collection;
	}
	
		
	/**
	 * Retourne un combo avec les types possible
	 *
	 */
	public function GetTypeCombo($name = 'type', $DefaultValue = null)
	{
		$types = mage::getmodel('Purchase/StockMovement')->GetTypes();
		$retour = '<select  id="'.$name.'" name="'.$name.'">';
		foreach ($types as $key => $value)
		{
			$selected = '';
			if ($DefaultValue == $key)
				$selected = ' selected ';
			else 
				$selected = '';
			$retour .= '<option value="'.$key.'" '.$selected.'>'.$this->__($value).'</option>';		
		}
		$retour .= '</select>';
		return $retour;
	}
	
	/**
	 * Retourne l'id du produit courant
	 *
	 * @return unknown
	 */
	public function getProductId()
	{
		return $this->_productId;
	}
}