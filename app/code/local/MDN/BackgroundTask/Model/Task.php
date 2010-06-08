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
class MDN_BackgroundTask_Model_Task extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		parent::_construct();
		$this->_init('BackgroundTask/Task');
	}	
	
	/**
	 * Execute task
	 *
	 */
	public function execute()
	{
		$error = false;
		$status = 'success';
		try 
		{
			//Collect helper
			$helper = mage::helper($this->getbt_helper());				
			$params = unserialize($this->getbt_params());
			$helper->{$this->getbt_method()}($params);
			
		}
		catch (Exception  $ex)
		{
			$error = $ex->getMessage();
			$error .= $ex->getTraceAsString();
			$status = 'error';
		}
		
		//Save execution information
		$this->setbt_executed_at(date('Y-m-d H:i'))
			 ->setbt_result_description($error)
			 ->setbt_result($status)
			 ->save();
			 
		return $this;
	}
}