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
//Controlleur pour la gestion des suppliers
class MDN_Purchase_StockMovementController extends Mage_Adminhtml_Controller_Action
{

	/**
	 * Ajouter un movement de stock
	 *
	 */
	public function AddAction()
    {
    	$response = false;
    	$product_id = $this->getRequest()->getParam('sm_product_id');
    	
    	try 
    	{
	    	//Cree le movement
			mage::getModel('Purchase/StockMovement')
				->setsm_product_id($product_id)
				->setsm_qty($this->getRequest()->getParam('sm_qty'))
				->setsm_coef(mage::getModel('Purchase/StockMovement')->GetTypeCoef($this->getRequest()->getParam('sm_type')))
				->setsm_description($this->getRequest()->getParam('sm_description'))
				->setsm_type($this->getRequest()->getParam('sm_type'))
				->setsm_date($this->getRequest()->getParam('sm_date'))
				->save();
				
			Mage::GetModel('Purchase/StockMovement')->ComputeProductStock($product_id);
				    		
	    	//Confirme
    	    Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Stock movement created'));
    	}
    	catch (Exception $ex)
    	{
    	    Mage::getSingleton('adminhtml/session')->addError($this->__('Error'));
    	}
    	
		//redirect to purchase product sheet
		$this->_redirect('Purchase/Products/Edit', array('product_id' => $product_id, 'tab' => 'tab_stock_movements'));
		
    }
    
    /**
     * Retourne via ajax la liste des stocks movement pour un produit
     *
     */
    public function getProductStockMovementAction()
    {
    	//recupere les infos
    	$ProductId = Mage::app()->getRequest()->getParam('product_id');
    	
    	//cree le block et le retourne
    	$this->loadLayout();	//Charge le layout pour appliquer le theme pour l'admin
    	$block = $this->getLayout()->createBlock('Purchase/Product_StockMovement', 'stockmovement');
    	$block->setProductId($ProductId);
    	$block->setTemplate('Purchase/Product/StockMovement.phtml');   	
    	$this->getResponse()->setBody($block->toHtml());
    }
    
    /**
     * Supprime un mouvement de stock
     *
     */
    public function DeleteAction()
    {

    	//Supprime le mouvement
    	$sm_id = $this->getRequest()->getParam('sm_id');
    	$sm = mage::getModel('Purchase/StockMovement')->load($sm_id);
    	$product_id = $sm->getsm_product_id();
    	$sm->delete();
    		
    	//Met a jour le stock du produit
    	mage::getModel('Purchase/StockMovement')->ComputeProductStock($product_id);
    	
    	//Redirige sur la fiche produit
    	$this->_redirect('Purchase/Products/Edit/product_id/'.$product_id);
    	
    }

    public function ListAction()
    {
    	$this->loadLayout();
        $this->renderLayout();
    }
    
}