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
class MDN_Purchase_TaxController extends Mage_Adminhtml_Controller_Action
{
	//Liste des taux de tax
	public function ListAction()
	{
    	$this->loadLayout();
        $this->renderLayout();
	}
	
	//Liste des taux de tax
	public function EditAction()
	{
    	$this->loadLayout();
        $this->renderLayout();
	}
	
	//Nouveau taux de tax
	public function NewAction()
	{
    	$this->loadLayout();
        $this->renderLayout();
	}
	
	/**
	 * Cree un nouveau taux
	 *
	 */
	public function CreateAction()
	{

        $model = Mage::getModel('Purchase/TaxRates');
		$TaxRate = $model->setptr_name($this->getRequest()->getPost('ptr_name'))
						->setptr_value($this->getRequest()->getPost('ptr_value'))
						->save();
				
		    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Data saved'));
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Tax/Edit/ptr_id/'.$TaxRate->getId());
	}
	
	/**
	 * Enregistre les données modifiées
	 *
	 */
	public function SaveAction()
	{
		$ptr_id = $this->getRequest()->getPost('ptr_id');

        $model = Mage::getModel('Purchase/TaxRates');
		$TaxRate = $model->load($ptr_id);
		$TaxRate->setptr_name($this->getRequest()->getPost('ptr_name'))
				->setptr_value($this->getRequest()->getPost('ptr_value'))
				->save();
				
		    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Data saved'));
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Tax/Edit/ptr_id/'.$TaxRate->getId());
	}
	
	/**
	 * Enregistre les données modifiées
	 *
	 */
	public function DeleteAction()
	{
		$ptr_id = $this->getRequest()->getParam('ptr_id');

        $model = Mage::getModel('Purchase/TaxRates');
		$TaxRate = $model->load($ptr_id)->delete();
		    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Tax Rate deleted'));
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Tax/List');
	}
	
}