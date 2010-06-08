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
class MDN_Purchase_Block_Supplier_Edit_Tabs_Misc extends Mage_Adminhtml_Block_Widget_Form
{
	private $_supplier = null;
	
	/**
	 * Constructeur: on charge le devis
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		
		//charge le manufacturer
        $sup_id = Mage::app()->getRequest()->getParam('sup_id', false);	
        $model = Mage::getModel('Purchase/Supplier');
		$this->_supplier = $model->load($sup_id);
		
		$this->setTemplate('Purchase/Supplier/Edit/Tab/Misc.phtml');
	}
		
	/**
	 * Retourne l'objet manufacturer
	 *
	 * @return unknown
	 */
	public function getSupplier()
	{
		return $this->_supplier;
	}
}