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
class MDN_BackgroundTask_Helper_Data extends Mage_Core_Helper_Abstract
{

	/**
	 * Add a task to execute
	 *
	 * @param unknown_type $task
	 */
	public function AddTask($description, $helper, $method, $params, $groupCode = null)
	{
		//if group is set, check  if group exists
		$group = null;
		if ($groupCode != null)
		{
			$group = mage::getResourceModel('BackgroundTask/Taskgroup')->loadByGroupCode($groupCode);
			if ($group == null)
				throw new Exception('Task group '.$groupCode.' doesnt exist');				
		}
		
		//insert task
		$task = mage::getModel('BackgroundTask/task')
				->setbt_created_at(date('Y-m-d h:i'))
				->setbt_description($description)
				->setbt_helper($helper)
				->setbt_method($method)
				->setbt_params(serialize($params))
				->setbt_group_code($groupCode)
				->save();
				
		//update group task count
		if ($group != null)
			$group->setbtg_task_count($group->getbtg_task_count() + 1)->save();
		else 
		{
			//if immediate group and task do not belong to a group
			if (mage::getStoreConfig('backgroundtask/general/immediate_mode') == 1)
				$task->execute();
		}
	}
	
	/**
	 * Add a new task group
	 *
	 * @param unknown_type $groupCode
	 * @param unknown_type $description
	 * @param unknown_type $redirectUrl
	 */
	public function AddGroup($groupCode, $description, $redirectUrl)
	{
		//if group exists, exit
		$group = mage::getResourceModel('BackgroundTask/Taskgroup')->loadByGroupCode($groupCode);
		if (!$group)
		{		
			$group = mage::getModel('BackgroundTask/Taskgroup')
				->setbtg_code($groupCode)
				->setbtg_description($description)
				->setbtg_redirect_url($redirectUrl)
				->save();
		}
		return $group;
	}
	
	/**
	 * Execute a task group
	 * redirect to controller
	 *
	 * @param unknown_type $groupName
	 */
	public function ExecuteTaskGroup($groupCode)
	{
		$url = Mage::helper('adminhtml')->getUrl('BackgroundTask/Admin/executeTaskGroup', array('group_code' => $groupCode));
		Mage::app()->getResponse()->setRedirect($url);
	}
	
	
	/**
	 * Execute tasks (main module method)
	 *
	 */
	public function ExecuteTasks($refuseDebug = false)
	{
		$debug = '<h1>Execute Tasks</h1>';
		$startTime = time();
		$hasTask = true;
		$maxExecutionTime = mage::getStoreConfig('backgroundtask/general/max_execution_time');
		while (((time() - $startTime) < $maxExecutionTime) && ($hasTask))
		{
			//collect next task to execute
			$task = $this->getNextTaskToExecute();
			
			//execute task
			if ($task)
			{
				$debug .= '<br>Executing task #'.$task->getId().' ('.$task->getbt_description().')';
				$task->execute();
				$debug .= ' ---> '.$task->getbt_status();
				if ($task->getbt_status() == 'error')
				{
					$this->notifyDevelopper('Task #'.$task->getId().' failed.');
				}
			}
			else 
			{
				//no task to execute, quit loop
				$hasTask = false;
			}
		}
		$debug .= '<br>End executing tasks';		

		//delete tasks
		$debug .= '<br>Delete tasks';		
		mage::getResourceModel('BackgroundTask/Task')->deleteTasks();
		
		//print debug information if enabled
		if ($refuseDebug == false)
		{
			if (mage::getStoreConfig('backgroundtask/general/debug') == 1)
				echo $debug;
		}
	}
	
	/**
	 * Collect next task to execute
	 *
	 */
	public function getNextTaskToExecute()
	{
		$collection = mage::getResourceModel('BackgroundTask/Task_Collection')->getNextTaskToExecute();
		foreach($collection as $item)
		{
			return $item;
		}
	}
	
	/**
	 * Notify developper by email
	 *
	 */
	public function notifyDevelopper($msg)
	{
		$email = mage::getStoreConfig('backgroundtask/general/debug');
		if ($email != '')
		{
			mail($email, 'Magento Background Task notification', $msg);
		}
	}
}

?>