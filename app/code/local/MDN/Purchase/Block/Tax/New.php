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
class MDN_Purchase_Block_Tax_New extends Mage_Adminhtml_Block_Widget_Form
{
	
	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	public function getBackUrl()
	{
		return $this->getUrl('Purchase/Tax/List');
	}
	
}