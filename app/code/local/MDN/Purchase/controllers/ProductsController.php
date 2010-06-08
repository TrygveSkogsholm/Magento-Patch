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
class MDN_Purchase_ProductsController extends Mage_Adminhtml_Controller_Action
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
     * Edition
     *
     */
	public function EditAction()
    {
    	$productId = $this->getRequest()->getParam('product_id');
    	$product = mage::getModel('catalog/product')->load($productId);
    	if (!$product->getId())
    	{
			Mage::getSingleton('adminhtml/session')->addError($this->__('Product Deleted'));
			$this->_redirect('Purchase/Products/List');		
    	}
    	else 
    	{
	    	$this->loadLayout();
	        $this->renderLayout();
    	}
    }
    
    /**
     * Sav le produit
     *
     */
    public function SaveAction()
    {
    	//recupere les infos
    	$ProductId = $this->getRequest()->getPost('product_id');
    	$StockMini = $this->getRequest()->getPost('notity_stock_qty');
    	$UseConfigStockMini = $this->getRequest()->getPost('use_config_notify_stock_qty');
    	if ($UseConfigStockMini == '')
    		$UseConfigStockMini = 0;
    	$exclude_from_supply_needs = $this->getRequest()->getPost('exclude_from_supply_needs');
    	if ($exclude_from_supply_needs == '')
    		$exclude_from_supply_needs = 0;
    	$DefaultSupplyDelay = $this->getRequest()->getPost('default_supply_delay');

    	//define price to store
    	if (mage::getStoreConfig('tax/calculation/price_includes_tax'))
	    	$productPrice = $this->getRequest()->getPost('price_ttc');
    	else 
	    	$productPrice = $this->getRequest()->getPost('price');    	
    	
    	//met a jour
    	$product = mage::getModel('catalog/product')->load($ProductId);
    	$product->setdefault_supply_delay($DefaultSupplyDelay);
    	$product->setexclude_from_supply_needs($exclude_from_supply_needs);
    	$product->setprice($productPrice);
    	$product->setmanual_supply_need_qty($this->getRequest()->getPost('manual_supply_need_qty'));
    	$product->setmanual_supply_need_comments($this->getRequest()->getPost('manual_supply_need_comments'));
    	$product->setmanual_supply_need_date($this->getRequest()->getPost('manual_supply_need_date'));
    	$product->setpurchase_tax_rate($this->getRequest()->getPost('purchase_tax_rate'));
    	$product->save();
    	
    	$product->getStockItem()
    			->setnotify_stock_qty($StockMini)
    			->setuse_config_notify_stock_qty($UseConfigStockMini)
    			->save();

	    //confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Product successfully Saved'));
    	
    	//Redirige vers la fiche créée
    	$this->_redirect('Purchase/Products/Edit/product_id/'.$ProductId);
    }
    
    /**
     * Lie un supplier au produit
     *
     */
    public function LinkSupplierAction()
    {
    	//recupere les infos
		$product_id = $this->getRequest()->getParam('product_id');    	
		$supplier_id = $this->getRequest()->getParam('supplier_id');    	
		
		//insere dans la base
		mage::getModel('Purchase/ProductSupplier')
			->setpps_product_id($product_id)
			->setpps_supplier_num($supplier_id)
			->save();
    }
        
    /**
     * Lie un supplier au produit
     *
     */
    public function LinkManufacturerAction()
    {
    	//recupere les infos
		$product_id = $this->getRequest()->getParam('product_id');    	
		$manufacturer_id = $this->getRequest()->getParam('manufacturer_id');    	
		
		//insere dans la base
		mage::getModel('Purchase/ProductManufacturer')
			->setppm_product_id($product_id)
			->setppm_manufacturer_num($manufacturer_id)
			->save();
    }
    
    /**
     * Retourne en ajax les informations sur l'association entre un produit et un manufacturer
     *
     */
    public function GetManufacturerInformationAction()
    {
    	//recupere l'objet
    	$object = mage::GetModel('Purchase/ProductManufacturer')
    				->load($this->getRequest()->getParam('ppm_id'));
    			
    	//retourne en ajax
    	$this->getResponse()->setHeader('Content-type', 'application/x-json');
        $this->getResponse()->setBody($object->toJson());
    }
    
    /**
     * Retourne la liste des manufacturer associés (juste le tableau)
     *
     */
    public function getAssociatedManufacturersAction()
    {
    	//recupere les infos
    	$product_id = Mage::app()->getRequest()->getParam('product_id');
    	
    	//cree le block et le retourne
    	$this->loadLayout();	//Charge le layout pour appliquer le theme pour l'admin
		$block = $this->getLayout()->createBlock('Purchase/Product_Edit_Tabs_AssociatedManufacturers', 'associatedmanufacturers');
    	$block->setProductId($product_id);
    	$block->setTemplate('Purchase/Product/Edit/Tab/AssociatedManufacturers.phtml');
    	
    	$this->getResponse()->setBody($block->toHtml());
    }

    /**
     * Supprime l'association avec un manufacturer
     *
     */
    public function DeleteAssociatedManufacturerAction()
    {
    	//recupere l'id
    	$ppm_id = Mage::app()->getRequest()->getParam('ppm_id');
    	
    	//supprime
		Mage::getModel('Purchase/ProductManufacturer')    	
			->load($ppm_id)
			->delete();
			
    }
    
    /**
     * Sauvegarde les informations sur un manufacturer associé a un produit
     *
     */
    public function SaveAssociatedManufacturerAction()
    {
    	//recupere l'id
    	$ppm_id = $this->getRequest()->getParam('ppm_id');
    	
    	//met a jour & save
    	$object = mage::getModel('Purchase/ProductManufacturer')->load($ppm_id);
    	$object->setppm_comments($this->getRequest()->getParam('ppm_comments'));
    	$object->setppm_reference($this->getRequest()->getParam('ppm_reference'));
    	$object->save();
    }
        
    /**
     * Retourne la liste des suppliers associés (juste le tableau)
     *
     */
    public function getAssociatedSuppliersAction()
    {
    	//recupere les infos
    	$product_id = Mage::app()->getRequest()->getParam('product_id');
    	
    	//cree le block et le retourne
    	$this->loadLayout();	//Charge le layout pour appliquer le theme pour l'admin
    	$block = $this->getLayout()->createBlock('Purchase/Product_Edit_Tabs_AssociatedSuppliers', 'associatedsuppliers');
    	$block->setProductId($product_id);
    	$block->setTemplate('Purchase/Product/Edit/Tab/AssociatedSuppliers.phtml');
    	
    	$this->getResponse()->setBody($block->toHtml());
    }
    
    /**
     * Supprime l'association avec un supplier
     *
     */
    public function DeleteAssociatedSupplierAction()
    {
    	//recupere l'id
    	$pps_id = Mage::app()->getRequest()->getParam('pps_id');
    	
    	//supprime
		Mage::getModel('Purchase/ProductSupplier')    	
			->load($pps_id)
			->delete();
			
    }
        
    /**
     * Retourne en ajax les informations sur l'association entre un produit et un supplier
     *
     */
    public function GetSupplierInformationAction()
    {
    	
    	//recupere l'objet
    	$object = mage::GetModel('Purchase/ProductSupplier')
    				->load($this->getRequest()->getParam('pps_id'));

    	//retourne en ajax
    	$this->getResponse()->setHeader('Content-type', 'application/x-json');
        $this->getResponse()->setBody($object->toJson());
    }
        
    /**
     * Sauvegarde les informations sur un supplier associé a un produit
     *
     */
    public function SaveAssociatedSupplierAction()
    {
    	//recupere l'id
    	$pps_num = $this->getRequest()->getParam('pps_num');
    	
    	//met a jour & save
    	$object = mage::getModel('Purchase/ProductSupplier')->load($pps_num);
    	$object->setpps_comments($this->getRequest()->getParam('pps_comments'));
    	$object->setpps_reference($this->getRequest()->getParam('pps_reference'));
    	$object->setpps_last_price($this->getRequest()->getParam('pps_last_price'));
    	$object->setpps_last_unit_price($this->getRequest()->getParam('pps_last_unit_price'));
    	$object->setpps_price_position($this->getRequest()->getParam('pps_price_position'));
    	$object->save();
    }
    
    /**
     * Calcul le prix de revient d'un produit
     *
     */
    public function ComputeBuyPriceAction()
    {
    	//recupere le produit & calcul
    	if (Mage::getStoreConfig('purchase/purchase_order/store_product_cost'))
    	{
	    	$product_id = Mage::app()->getRequest()->getParam('product_id');
	    	$product = mage::getModel('catalog/product')->load($product_id);
	    	$cost = mage::getmodel('Purchase/SupplyNeeds')->ComputeProductCost($product);
	    	if ($cost > 0)
		    	$product->setcost($cost)->save();
    	}
    	    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Product costs successfully computed'));
    	
    	//Redirige vers la fiche
    	$this->_redirect('Purchase/Products/Edit/product_id/'.$product_id);
    	
    }
    
    /**
     * Recalcul la qte commandée du produit et son stock
     *
     */
    public function UpdateStockAction()
    {
    	    	
    	//recupere le produit & calcul
    	$product_id = Mage::app()->getRequest()->getParam('product_id');
    	$product = mage::getModel('catalog/product')->load($product_id);
    	    	
    	//met a jour les qte
    	$model = mage::getModel('Purchase/Productstock');
    	$model->UpdateOrderedQty($product);
    	mage::helper('purchase/ProductReservation')->UpdateReservedQty($product);
    	
    	mage::getModel('Purchase/StockMovement')->ComputeProductStock($product->getId());
    	
    	//confirme
    	Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Product stocks successfully updated'));
    	
    	//Redirige vers la fiche
    	$this->_redirect('Purchase/Products/Edit/product_id/'.$product_id);
    }
        
    /**
     * Importer les produits à créer pour l'importation des 
     *
     */
    public function ImportProductsToCreateAction()
    {
    	//definit le chemin
    	$path = '/home/mdn/www/var/product_to_create.txt';
    	if (!file_exists($path))
    	{
    		die("Fichier introuvable");
    	}	

    	//charge le fichier
    	$f = fopen($path, 'r');
    	$content = fread($f, filesize($path));    	
    	fclose($f);
    	
    	$category_id = 516;
    	
    	//parse les lignes
    	$lines = explode("\n", $content);
    	for($i=0;$i<count($lines);$i++)
    	{
    		$t_fields = explode(';', $lines[$i]);
    		if (count($t_fields) > 1)
    		{
    			//cree le produit
	    		$ref = $t_fields[0];
	    		$description = $t_fields[1];

	    		//verifie si le produit existe
	    		$id = mage::getmodel('catalog/product')->getIdBySku($ref);
	    		if (!$id)
	    		{
	    			//cree le produit
	    			$product = mage::getModel('catalog/product')
	    				->settype_id('simple')
		    			->setattribute_set_id('4')
		    			->setWebsiteIds(array(1))
		    			->setname($description)
	    				->setsku($ref)
	    				->settax_class_id(2)
	    				->setprice(0)
						->setstatus(2)				//enabled
				    	->setvisibility(1)			//visibility nowhere
				    	->setcategory_ids($category_id)		//Produits auto générés
	    				->save();
		
		    		//rajoute la gestion de stock
			    	$my_stock = Mage::getModel('cataloginventory/stock_item');
					$my_stock->setproduct_id($product->getId())
							->setuse_config_manage_stock(0)
							->setmanage_stock(1)
							->setqty(0)
							->setis_in_stock(1)
							->setstock_id(1)
							->setuse_config_notify_stock_qty(0)
							->setnotify_stock_qty(0)
							->setuse_config_backorders(0)
							->setbackorders(1)
							->save();
		    				
					echo "<p>Creation produit ".$description;
	    		}
	    		else
	    			echo "<p>Produit ".$description." existe déja";    			
	   		}
    	}
    	
    	die("<p>Import fini");

    }
    
        
    /**
     * Export des commandes au format csv
     *
     */
    public function exportCsvAction()
    {
    	$fileName   = 'purchase_products.csv';
        $content    = $this->getLayout()->createBlock('Purchase/Product_Grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }
    
    /**
     * Retourne la grille avec les mouvements de stock pour un produit
     *
     */
    public function StockMovementGridAction()
    {
    	$this->loadLayout();
     	$ProductId = $this->getRequest()->getParam('product_id');
		$Block = $this->getLayout()->createBlock('Purchase/Product_Edit_Tabs_StockMovementGrid');
		$Block->setProductId($ProductId);
        $this->getResponse()->setBody($Block->toHtml());
    }
    
    /**
     * Method for associated order ajax refresh
     *
     */
    public function AssociatedOrdersGridAction()
    {
    	$this->loadLayout();
     	$ProductId = $this->getRequest()->getParam('product_id');
		$Block = $this->getLayout()->createBlock('Purchase/Product_Edit_Tabs_AssociatedOrdersGrid');
		$Block->setProductId($ProductId);
        $this->getResponse()->setBody($Block->toHtml());
    }
    
	public function StockGraphAction()
	{
		$from = $this->getRequest()->getParam('from');
		$to = $this->getRequest()->getParam('to');
		$productId = $this->getRequest()->getParam('product_id');
		$groupBy = $this->getRequest()->getParam('groupby');
		
		$displayStock = $this->getRequest()->getParam('displaystock');
		$displayOutgoing = $this->getRequest()->getParam('displayoutgoing');
		$displayIngoing = $this->getRequest()->getParam('displayingoing');

		mage::helper('purchase/ProductStockGraph')->getGraphImage($productId, $from, $to, $groupBy, $displayStock, $displayOutgoing, $displayIngoing);
			die('');
		
	}
}