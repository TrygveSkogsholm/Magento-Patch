<?php

/*
* Rajouter une colonne "payment validated" dans la liste des commandes
* dans l'interface d'admin
*/
class MDN_Purchase_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
	
	/**
	 * Add After Column
	 *
	 * @param unknown_type $columnId
	 * @param unknown_type $column
	 * @param unknown_type $indexColumn
	 * @return unknown
	 */
	public function addAfterColumn($columnId, $column,$indexColumn) {
		$columns = array();
		foreach ($this->_columns as $gridColumnKey => $gridColumn) {
			$columns[$gridColumnKey] = $gridColumn;
			if($gridColumnKey == $indexColumn) {
				$columns[$columnId] = $this->getLayout()->createBlock('adminhtml/widget_grid_column')
		                ->setData($column)
		                ->setGrid($this);
		        $columns[$columnId]->setId($columnId);         
			}
		}
		$this->_columns = $columns;
        return $this;
	}
	
	/**
	 * Add Before column
	 *
	 * @param unknown_type $columnId
	 * @param unknown_type $column
	 * @param unknown_type $indexColumn
	 * @return unknown
	 */
	public function addBeforeColumn($columnId, $column,$indexColumn) {
		$columns = array();
		foreach ($this->_columns as $gridColumnKey => $gridColumn) {
			if($gridColumnKey == $indexColumn) {
				$columns[$columnId] = $this->getLayout()->createBlock('adminhtml/widget_grid_column')
		                ->setData($column)
		                ->setGrid($this);
		        $columns[$columnId]->setId($columnId);         
			}
			$columns[$gridColumnKey] = $gridColumn;
		}
		$this->_columns = $columns;
        return $this;
	}
		
	/**
	 * Ajout de la nouvelle colonne
	 *
	 */
	protected function _prepareColumns()
    {
		parent::_prepareColumns();
        
		//Colonne pour les commentaires
    	$this->addAfterColumn('increment_id', array(
            'header'=> Mage::helper('Organizer')->__('Organizer'),
       		'renderer'  => 'MDN_Organizer_Block_Widget_Column_Renderer_Comments',
            'align' => 'center',
            'entity' => 'order'
        ),'real_order_id');
		
        //Colonne status paiement
        $this->addAfterColumn('payment_validated', array(
            'header'=> Mage::helper('purchase')->__('Payment validated'),
            'width' => '40px',
            'index' => 'payment_validated',
            'align' => 'center',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('purchase')->__('Yes'),
                '0' => Mage::helper('purchase')->__('No'),
            ),
        ),'status');
             
        $this->addAfterColumn('planning', array(
            'header' => Mage::helper('purchase')->__('Planning'),
            'index' => 'planning',
            'renderer'  => 'MDN_Purchase_Block_Widget_Column_Renderer_OrderPlanning',
            'align'	=> 'center',
            'filter'    => false,
            'sortable'  => false
        ),'payment_validated');

    }

    /*
    * Mass actions pour fianet & mageCustomization
    */
    protected function _prepareMassaction()
    {
    	parent::_prepareMassaction();
    	
        $this->getMassactionBlock()
        	->addItem('validate_payment', array(
             'label'=> Mage::helper('purchase')->__('Validate payment'),
             'url'  => $this->getUrl('Purchase/Misc/Validatepayment'),))
        	->addItem('cancel_payment', array(
             'label'=> Mage::helper('purchase')->__('Cancel payment'),
             'url'  => $this->getUrl('Purchase/Misc/Cancelpayment'),))
        ;
        
        //Modifie l'url pour l'annulation en masse des commandes
        $this->getMassactionBlock()->addItem('cancel_order', array(
             'label'=> Mage::helper('sales')->__('Cancel'),
             'url'  => $this->getUrl('Purchase/Misc/massCancel'),
        ));
        
        return $this;
    }

}

?>