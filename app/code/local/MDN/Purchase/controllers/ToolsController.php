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
class MDN_Purchase_ToolsController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Display massstockeditor grid
	 *
	 */
	public function MassStockEditorAction()
	{
    	$this->loadLayout();
        $this->renderLayout();
	}

	/**
	 * Save mass stocks
	 *
	 */
	public function MassStockSaveAction()
	{
		//collect data
		$stringStock = $this->getRequest()->getPost('stock');
		$stringStockMini = $this->getRequest()->getPost('stockmini');
		
		//process stock
		$t_stock = explode(',', $stringStock);
		foreach($t_stock as $item)
		{
			if ($item != '')
			{
				//retrieve data
				$t = explode('-', $item);
				$productId = $t[0];
				$qty = $t[1];
				
				//load stockitem and save
				$stockItem = mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
				if ($stockItem->getId())
				{
					if ($stockItem->getqty() != $qty)
						$stockItem->setqty($qty)->save();
				}
			}
		}
		
		//process stock mini
		$t_stockMini = explode(',', $stringStockMini);
		foreach($t_stockMini as $item)
		{
			if ($item != '')
			{
				//retrieve data
				$t = explode('-', $item);
				$productId = $t[0];
				$qtyMini = $t[1];
				
				//load stockitem and save
				$stockItem = mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
				if ($stockItem->getId())
				{
						$stockItem->setnotify_stock_qty($qtyMini)->setuse_config_notify_stock_qty(0)->save();
				}
			}
		}
		
	}
}