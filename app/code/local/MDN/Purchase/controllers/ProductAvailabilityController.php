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
class MDN_Purchase_ProductAvailabilityController extends Mage_Adminhtml_Controller_Action
{
	
    /**
     * Affiche la liste
     *
     */
	public function ListAction()
    {
    	$this->loadLayout();
        $this->renderLayout();
    }
    
    /**
     * Add a range
     *
     */
    public function AddRangeAction()
    {
    	mage::helper('purchase/ProductAvailability')->newRange();
    		   	
    	//Confirm & redirect
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('New range added'));	
		$this->_redirect('Purchase/ProductAvailability/List');
    }
    
    /**
     * Save datas
     *
     */
    public function SaveAction()
    {   	
    	//retrieve and remove items
    	$config = $this->getRequest()->getPost('config');
    	$targetConfig = array();
    	for ($i=0;$i<count($config);$i++)
    	{
    		if (!isset($config[$i]['delete']))
    			$targetConfig[] = $config[$i];
    	}
    	
    	//save
    	mage::helper('purchase/ProductAvailability')->saveConfig($targetConfig);
    	
    	//Confirm & redirect
		Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Data saved'));	
		$this->_redirect('Purchase/ProductAvailability/List');
    }
    
}