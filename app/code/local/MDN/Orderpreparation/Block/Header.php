<?php

/**
 * Block pour l'index de la page de préparation de commandes
 *
 */
class MDN_OrderPreparation_Block_Header extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        
        $this->setTemplate('Orderpreparation/Header.phtml');
    }
    
    /**
     * return button list
     *
     */
    public function getButtons()
    {
    	$retour = array();
    	
    	//select orders
    	$item = array();
    	$item['position'] = count($retour) + 1;
    	$item['onclick'] = "document.location.href='".Mage::helper('adminhtml')->getUrl('OrderPreparation/OrderPreparation')."'";
    	$item['caption'] = $this->__('Select orders');
    	$retour[] = $item;
    	
    	//print picking list
    	if (mage::getStoreConfig('orderpreparation/order_preparation_step/show_print_picking_list') == 1)
    	{
	    	$item = array();
	    	$item['position'] = count($retour) + 1;
	    	$item['onclick'] = "ajaxCall('".Mage::helper('adminhtml')->getUrl('OrderPreparation/OnePagePreparation/PrintPickingList')."')";
	    	$item['caption'] = $this->__('Print picking list');
	    	$retour[] = $item;
    	}
    	
    	//download picking list
    	if (mage::getStoreConfig('orderpreparation/order_preparation_step/show_download_picking_list') == 1)
    	{
	    	$item = array();
	    	$item['position'] = count($retour) + 1;
	    	$item['onclick'] = "document.location.href='".Mage::helper('adminhtml')->getUrl('OrderPreparation/OnePagePreparation/DownloadPickingList')."'";
	    	$item['caption'] = $this->__('Download picking list');
	    	$retour[] = $item;
    	}
    	        	
    	//Create shipments & invoices
    	if (mage::getStoreConfig('orderpreparation/order_preparation_step/show_create_shipments_invoices') == 1)
    	{
	    	$item = array();
	    	$item['position'] = count($retour) + 1;
	    	$item['onclick'] = "document.location.href='".Mage::helper('adminhtml')->getUrl('OrderPreparation/OrderPreparation/Commit')."'";
	    	$item['caption'] = $this->__('Create shipments/invoices');
	    	$retour[] = $item;
    	}
    		    	
    	//Download documents
    	if (mage::getStoreConfig('orderpreparation/order_preparation_step/show_download_documents') == 1)
    	{
	    	$item = array();
	    	$item['position'] = count($retour) + 1;
	    	$item['onclick'] = "document.location.href='".Mage::helper('adminhtml')->getUrl('OrderPreparation/OrderPreparation/DownloadDocuments')."'";
	    	$item['caption'] = $this->__('Download documents');
	    	$retour[] = $item;
    	}
    	
    	//process orders
    	$item = array();
    	$item['position'] = count($retour) + 1;
    	$item['onclick'] = "document.location.href='".Mage::helper('adminhtml')->getUrl('OrderPreparation/OnePagePreparation')."'";
    	$item['caption'] = $this->__('Process orders');
    	$retour[] = $item;

    	    	    	
    	//Import trackings
    	$item = array();
    	$item['position'] = count($retour) + 1;
    	$item['onclick'] = "document.location.href='".Mage::helper('adminhtml')->getUrl('OrderPreparation/CarrierTemplate/ImportTracking')."'";
    	$item['caption'] = $this->__('Shipping label / Trackings');
    	$retour[] = $item;
    	
    	//Notify customers
    	$item = array();
    	$item['position'] = count($retour) + 1;
    	$item['onclick'] = "ajaxCall('".Mage::helper('adminhtml')->getUrl('OrderPreparation/OrderPreparation/NotifyCustomers')."')";
    	$item['caption'] = $this->__('Notify customers');
    	$retour[] = $item;
    	    	
    	//Finish
    	$item = array();
    	$item['position'] = count($retour) + 1;
    	$item['onclick'] = "document.location.href='".Mage::helper('adminhtml')->getUrl('OrderPreparation/OrderPreparation/Finish')."'";
    	$item['caption'] = $this->__('Finish');
    	$retour[] = $item;
    	
    	return $retour;
    }

    
}