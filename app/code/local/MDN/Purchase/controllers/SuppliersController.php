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
class MDN_Purchase_SuppliersController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction()
    {
		
    }

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
     * Nouveau 
     *
     */
	public function NewAction()
    {
    	$this->loadLayout();
        $this->renderLayout();
    }
    
    /**
     * Creation d'un 
     *
     */
    public function CreateAction()
    {
    	
    	//Charge les données
    	$Supplier = mage::getModel('Purchase/Supplier');
    	$Supplier->setsup_name($this->getRequest()->getParam('sup_name'));
    	
    	//Cree
    	$Supplier->save();
    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Supplier Created'));
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Suppliers/Edit/sup_id/'.$Supplier->getId());
    	
    }
    
    /**
	 * Edition d'un 
	 *
	 */
	public function EditAction()
    {
    	$this->loadLayout();
        $this->renderLayout();
    }
    
    /**
     * Enregistre les modifs faite
     *
     */
    public function SaveAction()
    {
    	//Charge le manufacturer
    	$Supplier = Mage::getModel('Purchase/Supplier')->load($this->getRequest()->getParam('sup_id'));
    	
    	//Enregistre les modifs
    	$Supplier->setsup_name($this->getRequest()->getParam('sup_name'));
    	$Supplier->setsup_contact($this->getRequest()->getParam('sup_contact'));
    	$Supplier->setsup_address1($this->getRequest()->getParam('sup_address1'));
    	$Supplier->setsup_address2($this->getRequest()->getParam('sup_address2'));
    	$Supplier->setsup_zipcode($this->getRequest()->getParam('sup_zipcode'));
    	$Supplier->setsup_city($this->getRequest()->getParam('sup_city'));
    	$Supplier->setsup_country($this->getRequest()->getParam('sup_country'));
    	$Supplier->setsup_tel($this->getRequest()->getParam('sup_tel'));
    	$Supplier->setsup_fax($this->getRequest()->getParam('sup_fax'));
    	$Supplier->setsup_mail($this->getRequest()->getParam('sup_mail'));
    	$Supplier->setsup_website($this->getRequest()->getParam('sup_website'));
    	$Supplier->setsup_comments($this->getRequest()->getParam('sup_comments'));
    	if ($this->getRequest()->getParam('sup_sale_online'))
    	{
    		if ($this->getRequest()->getParam('sup_sale_online') == 1)
		    	$Supplier->setsup_sale_online(1);   		
		    else 
		    	$Supplier->setsup_sale_online(0);   		
    	}
    	else 
    		$Supplier->setsup_sale_online(0);
    	$Supplier->setsup_account_login($this->getRequest()->getParam('sup_account_login'));
		$Supplier->setsup_account_password($this->getRequest()->getParam('sup_account_password'));
		$Supplier->setsup_order_mini($this->getRequest()->getParam('sup_order_mini'));
		$Supplier->setsup_supply_delay($this->getRequest()->getParam('sup_supply_delay'));
		$Supplier->setsup_supply_delay_max($this->getRequest()->getParam('sup_supply_delay_max'));
		$Supplier->setsup_carrier($this->getRequest()->getParam('sup_carrier'));
		$Supplier->setsup_rma_tel($this->getRequest()->getParam('sup_rma_tel'));
		$Supplier->setsup_rma_mail($this->getRequest()->getParam('sup_rma_mail'));
		$Supplier->setsup_rma_comments($this->getRequest()->getParam('sup_rma_comments'));
    	$Supplier->save();
    	
    	//verifie si on doit en ajouter un
    	$sup_id = $Supplier->getId();
    	if ($this->getRequest()->getParam('add_pms_manufacturer_id') != '')
    	{
    		$NewManufacturer = mage::getModel('Purchase/ManufacturerSupplier');
    		$NewManufacturer->setpms_supplier_id($sup_id);
    		$NewManufacturer->setpms_manufacturer_id($this->getRequest()->getParam('add_pms_manufacturer_id'));
    		$NewManufacturer->setpms_official($this->getRequest()->getParam('add_pms_official'));
    		$NewManufacturer->setpms_price_position($this->getRequest()->getParam('add_pms_price_position'));
    		$NewManufacturer->setpms_gamme($this->getRequest()->getParam('add_pms_gamme'));
    		$NewManufacturer->save();
    	}
    	
    	//Met a jour les autres
    	$collection = Mage::GetModel('Purchase/ManufacturerSupplier')
			->getCollection()
			->addFieldToFilter('pms_supplier_id', $sup_id);
		foreach ($collection as $item)
		{
			if ($this->getRequest()->getParam('pms_official_'.$item->getpms_num()) == '1')
				$item->setpms_official(1);
			else 
				$item->setpms_official(0);
			$item->setpms_price_position($this->getRequest()->getParam('pms_price_position_'.$item->getpms_num()));
			$item->setpms_gamme($this->getRequest()->getParam('pms_gamme_'.$item->getpms_num()));
			$item->save();
		}
    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Supplier Saved'));
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Suppliers/Edit/sup_id/'.$Supplier->getId());
    	
    }
    
    /**
     * Supprime un manufacturer associé à un supplier
     *
     */
    public function DeleteAssociatedManufacturerAction()
    {
    	//recupere l'id
    	$pms_num = Mage::app()->getRequest()->getParam('pms_num');
    	
    	//Supprime
    	mage::getModel('Purchase/ManufacturerSupplier')
    		->load($pms_num)
    		->delete();
    	
    }
    
    /**
     * Retourne la liste des manufacturers associés au fournisseur
     *
     */
    public function getAssociatedManufacturersAction()
    {
    	//recupere les infos
    	$sup_id = Mage::app()->getRequest()->getParam('sup_id');
    	
    	//cree le block et le retourne
    	$block = $this->getLayout()->createBlock('Purchase/Supplier_AssociatedManufacturers', 'associatedmanufacturers');
    	$block->setSupplierId($sup_id);
    	$block->setTemplate('Purchase/Supplier/AssociatedManufacturers.phtml');
    	
    	$this->getResponse()->setBody($block->toHtml());
    }

    public function AssociatedOrdersGridAction()
    {
    	$this->loadLayout();
     	$supId = $this->getRequest()->getParam('sup_id');
		$Block = $this->getLayout()->createBlock('Purchase/Supplier_Edit_Tabs_Orders');
		$Block->setSupplierId($supId);
        $this->getResponse()->setBody($Block->toHtml());
    }
    
}