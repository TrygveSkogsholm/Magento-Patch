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
class MDN_Purchase_Block_Supplier_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('SupplierGrid');
        $this->_parentTemplate = $this->getTemplate();
        //$this->setTemplate('Shipping/List.phtml');	
        $this->setEmptyText(Mage::helper('customer')->__('No Items Found'));
    }

    /**
     * Charge la collection des devis
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {		            
		//charge les devis avec une jointure sur les clients (ma premiere jointure magento :) :) :) :)
        $collection = Mage::getModel('Purchase/Supplier')
        	->getCollection()
        	;
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
   /**
     * Défini les colonnes du grid
     *
     * @return unknown
     */
    protected function _prepareColumns()
    {
     
        $this->addColumn('Id', array(
            'header'=> Mage::helper('sales')->__('Id'),
            'index' => 'sup_id',
            'width' => '50px'
        ));
        
        $this->addColumn('organiser', array(
            'header'=> Mage::helper('Organizer')->__('Organizer'),
       		'renderer'  => 'MDN_Organizer_Block_Widget_Column_Renderer_Comments',
            'align' => 'center',
            'entity' => 'supplier',
            'filter' => false,
            'sort' => false
        ));
                          
        $this->addColumn('sup_name', array(
            'header'=> Mage::helper('sales')->__('Name'),
            'index' => 'sup_name',
        ));
        
        $this->addColumn('sup_contact', array(
            'header'=> Mage::helper('sales')->__('Contact'),
            'index' => 'sup_contact',
        ));
        
        $this->addColumn('sup_tel', array(
            'header'=> Mage::helper('sales')->__('Phone'),
            'index' => 'sup_tel',
        ));


        $this->addColumn('sup_website', array(
            'header'=> Mage::helper('sales')->__('Website'),
            'index' => 'sup_website',
        ));
        

        $this->addColumn('sup_mail', array(
            'header'=> Mage::helper('sales')->__('Mail'),
            'index' => 'sup_mail',
        ));
        
        return parent::_prepareColumns();
    }

     public function getGridUrl()
    {
        return ''; //$this->getUrl('*/*/wishlist', array('_current'=>true));
    }

    public function getGridParentHtml()
    {
        $templateName = Mage::getDesign()->getTemplateFilename($this->_parentTemplate, array('_relative'=>true));
        return $this->fetchView($templateName);
    }
    

    /**
     * Définir l'url pour chaque ligne
     * permet d'accéder à l'écran "d'édition" d'une commande
     */
    public function getRowUrl($row)
    {
    	return $this->getUrl('Purchase/Suppliers/Edit', array())."sup_id/".$row->getId();
    }
    
    /**
     * Url pour ajouter un Custom Shipping
     *
     */
    public function getNewUrl()
    {
		return $this->getUrl('Purchase/Suppliers/New', array());
    }
    
}
