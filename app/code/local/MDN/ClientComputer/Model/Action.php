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
class MDN_ClientComputer_Model_Action extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		parent::_construct();
		$this->_init('ClientComputer/Action');
	}	
	
	/**
	 * Convert object to string
	 *
	 * @return unknown
	 */
	public function convertToString()
	{
		$content = 'operation='.$this->getcco_operation()."\n";
		$content .= 'file='.$this->getcco_file()."\n";
		$content .= 'param='.$this->getcco_param()."\n";
		$content .= 'name='.$this->getcco_name()."\n";
		
		return $content;
	}
	
	/**
	 * Init values from a string
	 *
	 * @param unknown_type $t_string
	 */
	public function fromString($t_string)
	{
		for($i=0;$i<count($t_string);$i++)
		{
			$t = explode('=', $t_string[$i]);
			switch ($t[0])
			{
				case 'operation':
					$this->setcco_operation($t[1]);				
					break;
				case 'file':
					$this->setcco_file($t[1]);				
					break;
				case 'param':
					$this->setcco_param($t[1]);				
					break;
				case 'name':
					$this->setcco_name($t[1]);				
					break;
			}
		}
		
		return $this;
	}
	
}