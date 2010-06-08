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
class MDN_Purchase_Block_Manufacturer_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('ManufacturerGrid');
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
        $collection = Mage::getModel('Purchase/Manufacturer')
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
            'header'=> Mage::helper('purchase')->__('Id'),
            'index' => 'man_id',
        ));
                          
        $this->addColumn('Name', array(
            'header'=> Mage::helper('purchase')->__('Name'),
            'index' => 'man_name',
        ));
        
        $this->addColumn('Contact', array(
            'header'=> Mage::helper('purchase')->__('Contact'),
            'index' => 'man_contact',
        ));

        $this->addColumn('Phone', array(
            'header'=> Mage::helper('purchase')->__('Phone'),
            'index' => 'man_tel',
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
    	return $this->getUrl('Purchase/Manufacturers/Edit', array())."man_id/".$row->getId();
    }
    
    /**
     * Url pour ajouter un Custom Shipping
     *
     */
    public function getNewUrl()
    {
		return $this->getUrl('Purchase/Manufacturers/New', array());
    }
    
}
