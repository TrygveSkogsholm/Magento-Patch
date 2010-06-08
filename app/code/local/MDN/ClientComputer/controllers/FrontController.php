<?php
class MDN_ClientComputer_FrontController extends Mage_Core_Controller_Front_Action
{
	/**
	 * List actions to perform
	 *
	 */
	public function ListAction()
	{
		$this->checkPassword();
		$xml = mage::helper('ClientComputer')->getActionsAsXml();
		die($xml);
	}
	
	/**
	 * Download file
	 *
	 */
	public function downloadFileAction()
	{
		$this->checkPassword();
	
		$fileName = $this->getRequest()->getParam('filename');
		$filepath = mage::helper('ClientComputer')->getExchangeDirectory().$fileName;
		if (file_exists($filepath))
		{
			die(file_get_contents($filepath));
		}
		else 
			die('unable to find file');
	}
	
	/**
	 * delete an action
	 *
	 */
	public function deleteFileAction()
	{
		$this->checkPassword();

		$fileName = $this->getRequest()->getParam('filename');
		$filepath = mage::helper('ClientComputer')->getExchangeDirectory().$fileName;
		unlink($filepath);
		
	}
	
	/**
	 * check password
	 *
	 */
	private function checkPassword()
	{
		$password = $this->getRequest()->getParam('password');
		if ($password != mage::getStoreConfig('clientcomputer/general/password'))
			die('Access denied');
	}
}
    