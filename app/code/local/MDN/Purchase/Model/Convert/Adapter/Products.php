<?php

class MDN_Purchase_Model_Convert_Adapter_Products extends Mage_Dataflow_Model_Convert_Container_Abstract
{
	private $_collection = null;
	const k_lineReturn = "\r\n";
	
	 /**
     * Load product collection Id(s)
     *
     */
    public function load()
    {
    	//Recupere les paramétrages par défaut
    	$DefaultManageStock = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');
    	if ($DefaultManageStock == '')
    		$DefaultManageStock = 1;
		$DefaultNotifyStockQty = Mage::getStoreConfig('cataloginventory/item_options/notify_stock_qty');
    	if ($DefaultNotifyStockQty == '')
    		$DefaultNotifyStockQty = 0;
    		
    	//Charge la collection
        $this->_collection = Mage::getModel('Catalog/Product')
        	->getCollection()
        	->addAttributeToSelect('name')
        	->addAttributeToSelect('ordered_qty')
        	->addAttributeToSelect('price')
        	->addAttributeToSelect('cost')
            ->addAttributeToSelect('status')
            ->addAttributeToSelect('visibility')
            ->joinField('stock_qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->joinField('notify_stock_qty',
                'cataloginventory/stock_item',
                'notify_stock_qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->joinField('use_config_notify_stock_qty',
                'cataloginventory/stock_item',
                'use_config_notify_stock_qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->addExpressionAttributeToSelect('real_notify_stock_qty',
                'if(`_table_stock_qty`.`use_config_notify_stock_qty` = 0, `_table_stock_qty`.`notify_stock_qty`, '.$DefaultNotifyStockQty.')',
                 array())
            ->addExpressionAttributeToSelect('qty_needed',
                '-(`_table_stock_qty`.`qty` - {{ordered_qty}} - if(`_table_stock_qty`.`use_config_notify_stock_qty` = 0, `_table_stock_qty`.`notify_stock_qty`, '.$DefaultNotifyStockQty.'))',
                 array('ordered_qty'))
            ->addExpressionAttributeToSelect('margin',
                'round(({{price}} - {{cost}}) / {{price}} * 100, 2)',
                 array('price', 'cost'));
        	;
			
		//Affiche le nombre de commande chargée
		$this->addException(Mage::helper('dataflow')->__('Loaded %s rows', $this->_collection->getSize()), Mage_Dataflow_Model_Convert_Exception::NOTICE);
    }
    
    /**
     * Enregistre
     *
     */
    public function save()
    {
    	$this->load();
    	
    	//Définit le chemin ou sauver le fichier
    	$path = $this->getVar('path').'/'.$this->getVar('filename');
    	$f = fopen($path, 'w');
    	$fields = $this->getFields();

    	//add header
    	$header = '';
    	foreach($fields as $field)
    	{
    		$header .= $field.';';
    	}
    	fwrite($f, $header.self::k_lineReturn );
    	
    	//add orders
    	foreach($this->_collection as $item)
    	{
    		$line = '';
	    	foreach($fields as $field)
	    	{
	    		$line .= $item->getData($field).';';
	    	}    		
	    	fwrite($f, $line.self::k_lineReturn );    	
    	}
    	
		//Affiche le nombre de commande chargée
		fclose($f);
		$this->addException(Mage::helper('dataflow')->__('Export saved in %s', $path), Mage_Dataflow_Model_Convert_Exception::NOTICE);

    }
    
    /**
     * return fields to export
     *
     */
    public function getFields()
    {
    	$t = array();
    	$t = explode(';', $this->getVar('fields'));
    	return $t;
    }

}