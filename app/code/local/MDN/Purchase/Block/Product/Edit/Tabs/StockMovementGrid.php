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
class MDN_Purchase_Block_Product_Edit_Tabs_StockMovementGrid extends Mage_Adminhtml_Block_Widget_Grid
{
	private $_productId = null;
		
	/**
	 * Définition du numéro de produit
	 *
	 * @param unknown_type $ProductId
	 */
	public function setProductId($ProductId)
	{
		$this->_productId = $ProductId;
		return $this;
	}
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('StockMovementGrid');
        $this->_parentTemplate = $this->getTemplate();
        //$this->setTemplate('Shipping/List.phtml');	
        $this->setEmptyText('Aucun elt');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('product_stock_movement');
        $this->setDefaultSort('sm_date', 'DESC');
    }

    /**
     * Charge la collection
     *
     * @return unknown
     */
    protected function _prepareCollection()
    {		            
		//charge les mouvements de stock
		$model = Mage::getModel('Purchase/StockMovement');
		$collection = $model->loadByProduct($this->_productId);
                 
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
                               
        $this->addColumn('sm_date', array(
            'header'=> Mage::helper('sales')->__('Date'),
            'index' => 'sm_date',
            'type' => 'date',
            'align' => 'center'
        ));
        
        $this->addColumn('type', array(
            'header'=> Mage::helper('sales')->__('Type'),
            'index' => 'sm_type',
            'align' => 'center',
            'renderer'  => 'MDN_Purchase_Block_Product_Widget_Column_Renderer_StockMovementType'
        ));
        
        $this->addColumn('Qty', array(
            'header'=> Mage::helper('sales')->__('Qty'),
            'index' => 'sm_qty',
            'align' => 'center',
            'renderer'  => 'MDN_Purchase_Block_Product_Widget_Column_Renderer_StockMovementQty'
        ));
        
        $this->addColumn('Description', array(
            'header'=> Mage::helper('sales')->__('Description'),
            'index' => 'sm_description'
        ));
  
        $this->addColumn('action', array(
            'header'    => Mage::helper('Orderpreparation')->__('Action'),
            'index'     => 'sm_id',
            'type'      => 'action',
            'align'		=> 'center',
            'filter'    => false,
            'sortable'  => false,
            'actions'   => array(
                array(
                    'caption' =>  Mage::helper('Orderpreparation')->__('Delete'),
                    'url'     => array('base'=>'*/StockMovement/Delete/sm_id/$sm_id'),
                    'field'   => 'sm_id'
                )
            )
        ));
        
        return parent::_prepareColumns();
    }

     public function getGridUrl()
    {
        return $this->getUrl('*/*/StockMovementGrid', array('_current'=>true, 'product_id' => $this->_productId));
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
    	//return $this->getUrl('Purchase/Products/Edit', array())."product_id/".$row->getId();
    }
		
	/**
	 * Retourne un combo avec les types possible
	 *
	 */
	public function GetTypeCombo($name = 'type', $DefaultValue = null)
	{
		$types = mage::getmodel('Purchase/StockMovement')->GetTypes();
		$retour = '<select  id="'.$name.'" name="'.$name.'">';
		foreach ($types as $key => $value)
		{
			$selected = '';
			if ($DefaultValue == $key)
				$selected = ' selected ';
			else 
				$selected = '';
			$retour .= '<option value="'.$key.'" '.$selected.'>'.$this->__($value).'</option>';		
		}
		$retour .= '</select>';
		return $retour;
	}    
		
	/**
	 * Retourne l'id du produit courant
	 *
	 * @return unknown
	 */
	public function getProductId()
	{
		return $this->_productId;
	}
}
