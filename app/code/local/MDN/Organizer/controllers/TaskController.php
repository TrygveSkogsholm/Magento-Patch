<?php


class MDN_Organizer_TaskController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Affiche la liste des taches
	 *
	 */
	public function ListAction()
	{
		$this->loadLayout();
        $this->renderLayout();
	}
	public function DashboardAction()
	{
		$this->loadLayout();
        $this->renderLayout();
	}
	
	/**
	 * Nouveau
	 *
	 */
	public function NewAction()
	{
		$this->loadLayout();
        $this->renderLayout();
	}
		
	/**
	 * Edition
	 *
	 */
	public function EditAction()
	{
		//recupere les infos
    	$ot_id = Mage::app()->getRequest()->getParam('ot_id');
    	
    	//cree le block et le retourne
		$block = $this->getLayout()->createBlock('Organizer/Task_Edit', 'taskedit');
		$block->setotId($ot_id);
		$block->setGuid(Mage::app()->getRequest()->getParam('guid'));
    	$block->setTemplate('Organizer/Task/Edit.phtml');
    	
    	$this->getResponse()->setBody($block->toHtml());
	}
	
	/**
	 * Met a jour (en ajax)
	 *
	 */
	public function SaveAction()
	{
		$ok = true;
		$msg = 'Task saved';
		
		try 
		{
			//save
			$Task = Mage::getModel('Organizer/Task')
				->load($this->getRequest()->getPost('ot_id'))
				->setot_author_user($this->getRequest()->getPost('ot_author_user'))
				->setot_caption($this->getRequest()->getPost('ot_caption'))
				->setot_category($this->getRequest()->getPost('ot_category'))
				->setot_origin($this->getRequest()->getPost('ot_origin'))
				->setot_description($this->getRequest()->getPost('ot_description'))
				->setot_entity_type($this->getRequest()->getPost('ot_entity_type'))
				->setot_entity_id($this->getRequest()->getPost('ot_entity_id'))
				->setot_entity_description($this->getRequest()->getPost('ot_entity_description'))
				->setot_finished($this->getRequest()->getPost('ot_finished'));

			$target = $this->getRequest()->getPost('ot_target_user');
			if ($target > 0)
				$Task->setot_target_user($target);
			else 
				$Task->setot_target_user('');
				
			if ($this->getRequest()->getPost('ot_deadline') != '')
				$Task->setot_deadline($this->getRequest()->getPost('ot_deadline'));
			if ($this->getRequest()->getPost('ot_notify_date') != '')
				$Task->setot_notify_date($this->getRequest()->getPost('ot_notify_date'));
			if ($this->getRequest()->getPost('ot_id') == '')
				$Task->setot_created_at(date('Y-m-d H:i'));
				
			$Task->save();
			
			//Test if we have to notify target
			if ($this->getRequest()->getPost('notify_target') == 1)
			{
				if ($target > 0)
				{
					$Task->notifyTarget();
				}
			}
			
			$ok = true;
		}
		catch (Exception $ex)
		{
			$msg = $ex->getMessage();
			$ok = false;
		}
		
		//Retourne
		$response = array(
            'error'     => (!$ok),
            'message'   => $this->__($msg)
        );	    	
        $response = Zend_Json::encode($response);
        $this->getResponse()->setBody($response);	
	}
	
	/**
	 * Retourne le block avec la liste des taches pour une entité donnée
	 *
	 */
	public function EntityListAction()
	{
		//recupere les infos
    	$entity_type = Mage::app()->getRequest()->getParam('entity_type');
    	$entity_id = Mage::app()->getRequest()->getParam('entity_id');
    	
    	//cree le block et le retourne
		$block = $this->getLayout()->createBlock('Organizer/Task_Grid', 'tasklist');
		$block->setEntityId($entity_id);
		$block->setEntityType($entity_type);
		$block->setShowEntity(Mage::app()->getRequest()->getParam('show_entity'));
		$block->setMode(Mage::app()->getRequest()->getParam('mode'));
		$block->setShowTarget(Mage::app()->getRequest()->getParam('show_target'));
    	$block->setEnableSortFilter(Mage::app()->getRequest()->getParam('enable_sort_filter'));
    	
    	//$block->setTemplate('Organizer/Task/List.phtml');
    	
    	$this->getResponse()->setBody($block->toHtml());
	}
	
	/**
	 * Supprime une tache
	 *
	 */
	public function DeleteAction()
	{
		$ok = true;
		$msg = 'Task deleted';
		
		try 
		{
			//recupere lkes infos
			$otId = Mage::app()->getRequest()->getParam('ot_id');
			$Task = mage::getModel('Organizer/Task')->load($otId);
			$url = $Task->getEntityLink();
			
			//supprime
			$Task->delete();
			
		}
		catch (Exception $ex)
		{
			$msg = $ex->getMessage();
			$ok = false;
		}
		
		//Retourne
		$response = array(
            'error'     => (!$ok),
            'message'   => $this->__($msg)
        );	    	
        $response = Zend_Json::encode($response);
        $this->getResponse()->setBody($response);	

	}
	
	/**
	 * Notify target
	 *
	 */
	public function NotifyAction()
	{
		$otId = Mage::app()->getRequest()->getParam('ot_id');
		$Task = mage::getModel('Organizer/Task')->load($otId);
		$Task->notifyTarget();
	}
}
