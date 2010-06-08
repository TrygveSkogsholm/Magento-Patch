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
class MDN_Purchase_SupplyNeedsController extends Mage_Adminhtml_Controller_Action
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
     * Display grid
     *
     */
	public function GridAction()
    {
    	$this->loadLayout();
        $this->renderLayout();
    }
    
    /**
     * rafraichit le cache pour les supply needs
     *
     */
    public function RefreshListAction()
    {
		//create backgroundtask group
    	$taskGroup = 'refresh_suppy_needs';
		mage::helper('BackgroundTask')->AddGroup($taskGroup, mage::helper('purchase')->__('Refresh Supply Needs'), 'Purchase/SupplyNeeds/Grid');
		
		//empty table
		Mage::getResourceModel('Purchase/SupplyNeeds')->TruncateTable();
		
    	//collect product ids
    	$ids = mage::getModel('Purchase/SupplyNeeds')->getCandidateProductIds();
		
		for ($i=0;$i<count($ids);$i++)		
		{
			//add tasks to group
			$productId = $ids[$i]['product_id'];
			mage::helper('BackgroundTask')->AddTask('Update supply needs for product #'.$productId, 
									'purchase',
									'updateSupplyNeedsForProduct',
									$productId,
									$taskGroup
									);	
		}

		//execute task group
		mage::helper('BackgroundTask')->ExecuteTaskGroup($taskGroup);

    }
    
    /**
     * Create a purchase order and add products
     *
     */
    public function CreatePurchaseOrderAction()
    {
    	//init vars
    	$supplyNeedsIds = $this->getRequest()->getPost('supply_needs_product_ids');
    	$sup_num = $this->getRequest()->getPost('supplier');
    	
    	//cree la commande
    	$order = mage::getModel('Purchase/Order')
    		->setpo_sup_num($sup_num)
    		->setpo_date(date('Y-m-d'))
    		->setpo_currency(Mage::getStoreConfig('purchase/purchase_order/default_currency'))
    		->setpo_tax_rate(Mage::getStoreConfig('purchase/purchase_order/default_shipping_duties_taxrate'))
    		->setpo_order_id(mage::getModel('Purchase/Order')->GenerateOrderNumber())
    		->save();
    	
    	//rajoute les produits
		foreach ($supplyNeedsIds as $supplyNeedId)
		{
			//retrieve information
			$supplyNeed = mage::getModel('Purchase/SupplyNeeds')->load($supplyNeedId);
			if ($supplyNeed)
			{
				$qty = $supplyNeed->getsn_needed_qty();
				$productId = $supplyNeed->getsn_product_id();
				
				//add product
				$order->AddProduct($productId, $qty);	
			}
		}
    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order successfully Created'));
    	$this->_redirect('Purchase/Orders/Edit', array('po_num' => $order->getId()));
    	
    }
}