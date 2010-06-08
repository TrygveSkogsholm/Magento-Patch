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
class MDN_Purchase_Block_SupplyNeeds_List extends Mage_Adminhtml_Block_Widget_Form
{
	private $_collection = null;
	private $_collectionDisplayed = null;
	private $_supplierFilter = '';
	private $_manufacturerFilter = '';
	private $_statusFilter = '';
	private $_deadlineMaxFilter = '';
	public static $_sortBy = '';
	
	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		
		//Définit les parametres
		if (Mage::app()->getRequest()->getParam('supplier', false) != '')
			$this->_supplierFilter = Mage::app()->getRequest()->getParam('supplier', false);
		if (Mage::app()->getRequest()->getParam('manufacturer', false) != '')
			$this->_manufacturerFilter = Mage::app()->getRequest()->getParam('manufacturer', false);
		if (Mage::app()->getRequest()->getParam('status', false) != '')
			$this->_statusFilter = Mage::app()->getRequest()->getParam('status', false);
		if (Mage::app()->getRequest()->getParam('deadline_max', false) != '')
			$this->_deadlineMaxFilter = Mage::app()->getRequest()->getParam('deadline_max', false);
		if (Mage::app()->getRequest()->getParam('sort_by', false) != '')
			MDN_Purchase_Block_SupplyNeeds_List::$_sortBy = Mage::app()->getRequest()->getParam('sort_by', false);
		else 
			MDN_Purchase_Block_SupplyNeeds_List::$_sortBy  = 'status';
			
		$this->getList();
	}
	
	/**
	 * Retourne la liste des besoins d'appro
	 *
	 */
	public function getList()
	{
		if ($this->_collection == null)
		{
			//recupere la liste
			$this->_collection = mage::getModel('Purchase/SupplyNeeds')->getSupplyNeeds();
			
			//la filtre si besoin
			if (($this->supplierFilter() != '') || ($this->manufacturerFilter() != '') || ($this->statusFilter() != '') || ($this->deadlineMaxFilter() != ''))
			{
				$retour = array();
				foreach($this->_collection as $item)
				{
					$ok = true;			
					
					//Filtre sur fournisseur
					if ((($this->supplierFilter() != '')) && $ok)
					{
						$ok = false;
						$pos = strpos($item->getsn_suppliers_ids(), ','.$this->supplierFilter().',');
						if (!($pos === false))
							$ok = true;
					}
					
					//Filtre sur manufacturer
					if (($this->manufacturerFilter() != '') && $ok)
					{
						$ok = false;
						if ($item->getsn_manufacturer_id() == $this->manufacturerFilter())
							$ok = true;
					}
					
					//filtre sur le statut
					if (($this->statusFilter() != '') && $ok)
					{
						$ok = false;
						if ($item->getsn_status() == $this->statusFilter())
							$ok = true;
					}
					
					//filtre la dead_line_max
					if (($this->deadlineMaxFilter() != '') && $ok)
					{
						$ok = false;
						if (strtotime($item['dead_line']) <= strtotime($this->deadlineMaxFilter()))
							$ok = true;
					}
					
					//Si passé tous les filtres, on ajoute
					if ($ok)
						$retour[] = $item;
				}
				
				$this->_collectionDisplayed = $retour;
			}
			else 
				$this->_collectionDisplayed = $this->_collection;
	
			//Tri la collection
			usort($this->_collectionDisplayed, array("MDN_Purchase_Block_SupplyNeeds_List", "sortCollection"));
		}
	
		return $this->_collectionDisplayed;
	}
		      
    /**
     * Méthode pour trier les produits
     *
     */
    public static function sortCollection($a, $b)
    {
    	switch(MDN_Purchase_Block_SupplyNeeds_List::$_sortBy)
    	{
    		case 'manufacturer':
			  	if (strtolower($a->getsn_manufacturer_name()) < strtolower($b->getsn_manufacturer_name()))
		    		return -1;
		    	else 
		    		return 1;  
    			break;
    		case 'sku':
			  	if (strtolower($a->getsn_product_sku()) < strtolower($b->getsn_product_sku()))
		    		return -1;
		    	else 
		    		return 1;  
    			break;
    		case 'description':
			  	if (strtolower($a->getsn_product_name()) < strtolower($b->getsn_product_name()))
		    		return -1;
		    	else 
		    		return 1;  
    			break;
    		case 'needed_qty':
			  	if ($a->getsn_needed_qty() < $b->getsn_needed_qty())
		    		return -1;
		    	else 
		    		return 1;  
    			break;
    		case 'dead_line':
    			if (($a->getsn_deadline() == '') && ($b->getsn_deadline() != ''))
    				return 1;
    			if (($a->getsn_deadline() != '') && ($b->getsn_deadline() == ''))
    				return -1;
			  	if ($a->getsn_deadline() < $b->getsn_deadline())
			  	{
		    		return -1;
			  	}
		    	else 
		    	{
		    		if ($a->getsn_deadline() > $b->getsn_deadline())
		    		{
			    		return 1;
		    		}
		    		else 
		    		{
		    			//Si supply needs égaux, on joue sur le status
					  	if (strtolower($a->getsn_status()) < strtolower($b->getsn_status()))
				    		return -1;
				    	else 
				    	{
				    		if (strtolower($a->getsn_status()) > strtolower($b->getsn_status()))
				    		{
					    		return 1;  
				    		}
				    	}
		    			break;
		    		}
		    	}
    			break;
    		case 'purchase_dead_line':
    			if (($a->getsn_purchase_deadline() == '') && ($b->getsn_purchase_deadline() != ''))
    				return 1;
    			if (($a->getsn_purchase_deadline() != '') && ($b->getsn_purchase_deadline() == ''))
    				return -1;
			  	if ($a->getsn_purchase_deadline() < $b->getsn_purchase_deadline())
		    		return -1;
		    	else 
		    		return 1;
    			break;
    		case 'status':
			  	if ($a->getStatusForSort() < $b->getStatusForSort())
		    		return -1;
		    	else 
		    	{
		    		if ($a->getStatusForSort() > $b->getStatusForSort())
		    			return 1;  
		    		else 
		    		{
		    			//if equal sort using deadline
			    		if ($a->getsn_deadline() > $b->getsn_deadline())
				    		return 1;
				    	else 
				    		return -1;
		    		}
		    	}
    			break;
    	}
 	
    }
	
	/**
	 * Filtre sur le fournisseur
	 *
	 * @return unknown
	 */
	public function supplierFilter()
	{
		return $this->_supplierFilter;
	}
		
	/**
	 * Filtre sur le fabricant
	 *
	 * @return unknown
	 */
	public function manufacturerFilter()
	{
		return $this->_manufacturerFilter;
	}	
	
	/**
	 * Filtre sur le status
	 *
	 * @return unknown
	 */
	public function statusFilter()
	{
		return $this->_statusFilter;
	}
			
	/**
	 * Filtre sur le status
	 *
	 * @return unknown
	 */
	public function deadlineMaxFilter()
	{
		return $this->_deadlineMaxFilter;
	}
	
	/**
	 * Cle de tri
	 *
	 * @return unknown
	 */
	public function sortBy()
	{
		return MDN_Purchase_Block_SupplyNeeds_List::$_sortBy;
	}
	
	/**
	 * Retourne la liste des fournisseurs sous la forme de combo
	 *
	 */
	public function getSupplierListAsCombo($name = 'supplier', $value='')
	{
		$retour = '<select id="'.$name.'"  name="'.$name.'">';
		$retour .= '<option value="" ></option>';

		//recupere la liste des suppliers
		$suppliers = mage::getModel('Purchase/Supplier')
			->getCollection()
			->setOrder('sup_name', 'asc');
		
		//ajoute au menu
		foreach ($suppliers as $supplier)
		{
			if ($value == $supplier->getId())
				$selected = ' selected ';
			else 
				$selected = '';
			$retour .= '<option value="'.$supplier->getId().'" '.$selected.'>'.$supplier->getsup_name().'</option>';
		}
		
		$retour .= '</select>';
		return $retour;
	}
	
	/**
	 * Liste des fabricants
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function getManufacturerListAsCombo($name = 'manufacturer', $value='')
	{
		$retour = '<select  id="'.$name.'" name="'.$name.'">';
		$retour .= '<option value="" ></option>';
		
		//recupere la liste des manufacturers
		$product = Mage::getModel('catalog/product');
		$attributes = Mage::getResourceModel('eav/entity_attribute_collection')
		    ->setEntityTypeFilter($product->getResource()->getTypeId())
	    	->addFieldToFilter('attribute_code', 'manufacturer') // This can be changed to any attribute code
	    	->load(false);
 		$attribute = $attributes->getFirstItem()->setEntity($product->getResource());
		$manufacturers = $attribute->getSource()->getAllOptions(false);
		
		//ajoute au menu
		foreach ($manufacturers as $manufacturer)
		{
			if ($value == $manufacturer['value'])
				$selected = ' selected ';
			else 
				$selected = '';
			$retour .= '<option value="'.$manufacturer['value'].'" '.$selected.'>'.$manufacturer['label'].'</option>';
		}
		
		$retour .= '</select>';
		return $retour;
	}
		
	/**
	 * Liste des Status
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function getStatusListAsCombo($name = 'status', $value='')
	{
		$retour = '<select  id="'.$name.'" name="'.$name.'">';
		$retour .= '<option value=""></option>';
		if ($value == MDN_Purchase_Model_SupplyNeeds::_StatusApproComm)
			$selected = ' selected ';
		else 
			$selected = '';
		$retour .= '<option value="'.MDN_Purchase_Model_SupplyNeeds::_StatusApproComm.'" '.$selected.'>'.$this->__('Supply for orders').'</option>';
		if ($value == MDN_Purchase_Model_SupplyNeeds::_StatusQtyMini)
			$selected = ' selected ';
		else 
			$selected = '';
		$retour .= '<option value="'.MDN_Purchase_Model_SupplyNeeds::_StatusQtyMini.'" '.$selected.'>'.$this->__('Supply for mini qty').'</option>';
		$retour .= '</select>';
		return $retour;
	}
	
	/**
	 * Retourne la liste des fournisseur ssous la forme d'un combo
	 */
	public function getSuppliersAsCombo($name='supplier')
	{
		$retour = '<select  id="'.$name.'" name="'.$name.'">';

		//charge la liste des pays
		$collection = Mage::getModel('Purchase/Supplier')
			->getCollection()
			->setOrder('sup_name', 'asc');
		foreach ($collection as $item)
		{
			$retour .= '<option value="'.$item->getsup_id().'">'.$item->getsup_name().'</option>';
		}
		
		$retour .= '</select>';
		return $retour;
	}
}