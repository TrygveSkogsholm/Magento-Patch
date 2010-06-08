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
class MDN_Purchase_Block_Contact_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('ContactsGrid');
        $this->_parentTemplate = $this->getTemplate();
        //$this->setTemplate('Shipping/List.phtml');	
        $this->setEmptyText(Mage::helper('customer')->__('No Items Found'));
    }

    /**
     * Charge la collection
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {		            
		//charge
        $collection = Mage::getModel('Purchase/Contact')
        	->getCollection()
        	->join('Purchase/Supplier',
		           'pc_entity_id=sup_id')
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
                               
        $this->addColumn('firstname', array(
            'header'=> Mage::helper('purchase')->__('Firstname'),
            'index' => 'pc_firstname'
        ));
        
        $this->addColumn('lasname', array(
            'header'=> Mage::helper('purchase')->__('Lastname'),
            'index' => 'pc_lastname',
        ));
        
        $this->addColumn('phone', array(
            'header'=> Mage::helper('purchase')->__('Phone'),
            'index' => 'pc_phone',
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
    	return $this->getUrl('Purchase/Contacts/Edit', array())."pc_num/".$row->getId();
    }
    
}
