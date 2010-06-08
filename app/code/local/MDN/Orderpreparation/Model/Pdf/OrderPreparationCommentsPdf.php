<?php

/**
 * Imprime le récap des produits à préparer pour l'envoi des commandes
 *
 */
class MDN_Orderpreparation_Model_Pdf_OrderPreparationCommentsPdf extends MDN_Orderpreparation_Model_Pdf_Pdfhelper
{
	
	public function getPdf($order = array())
    {
        
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $this->pdf = new Zend_Pdf();
        $style = new Zend_Pdf_Style();
        $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);

        //cree la nouvelle page
        $titre = mage::helper('purchase')->__('Order #').$order->getincrement_id().' '.mage::helper('purchase')->__('Comments');
        $settings = array();
        $settings['title'] = $titre;
        $settings['store_id'] = 0;
        $page = $this->NewPage($settings);

        //cartouche
        $txt_date = "Date :  ".mage::helper('core')->formatDate($order->getCreatedAt(), 'long');
        $txt_order = mage::helper('purchase')->__('Order #').$order->getId();
        //$adresse_fournisseur = Mage::getStoreConfig('sales/identity/address');
        $customer = mage::getmodel('customer/customer')->load($order->getCustomerId());
        $adresse_client = mage::helper('purchase')->__('Shipping Address').":\n".$this->FormatAddress($order->getShippingAddress(), '', false, $customer->gettaxvat());
        $adresse_fournisseur = mage::helper('purchase')->__('Billing Address').":\n".$this->FormatAddress($order->getBillingAddress(), '', false, $customer->gettaxvat());
        $this->AddAddressesBlock($page, $adresse_fournisseur, $adresse_client, $txt_date, $txt_order);

        //Rajoute le carrier et la date d'expe prévue & les commentaires
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
        $this->y -=15;
        $page->drawText(mage::helper('purchase')->__('Shipping').' : '.$order->getShippingDescription(), 15, $this->y, 'UTF-8');
        $this->y -=15;
        $comments = $this->WrapTextToWidth($page, $order->getmdn_comments(), 550);
        $offset = $this->DrawMultilineText($page, $comments, 15, $this->y, 10, 0.2, 11);
        $this->y -=10 + $offset;
        $page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR,  $this->y);
                
        //affiche l'entete du tableau
        $this->drawTableHeader($page);
        $this->y -=10;

        //Affiche le récap des produits
	    $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.2));
    	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
        foreach ($order->getAllItems() as $item)
        {
        	//recupere le produit
        	$product = mage::getModel('catalog/product')->load($item->getproduct_id());
        	
        	//dessine
        	$page->drawText((int)$item->getqty_ordered(), 15, $this->y, 'UTF-8');
        	$page->drawText($product->getSku(), 70, $this->y, 'UTF-8');
        	$page->drawText($product->getName(), 200, $this->y, 'UTF-8');
        	$page->drawText($item->getreserved_qty(), 560, $this->y, 'UTF-8');
        	
        	//rajoute les commentaires
        	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC ), 8);
        	$this->y -= $this->_ITEM_HEIGHT;
        	$caption = $this->WrapTextToWidth($page, $item->getcomments(), 300);
        	$offset = $this->DrawMultilineText($page, $caption, 200, $this->y, 10, 0.2, 11);
        	$this->y -= $offset;
        	
        	//rajoute une ligne de séparation
        	$page->setLineWidth(0.5);
	        $page->drawLine(10, $this->y-4, $this->_BLOC_ENTETE_LARGEUR,  $this->y-4);
	        $this->y -= $this->_ITEM_HEIGHT;
  	        
        	//si on a plus la place de rajouter le footer, on change de page
        	if ($this->y < ($this->_BLOC_FOOTER_HAUTEUR + 40))
        	{
        		$this->drawFooter($page);
        		$page = $this->NewPage($settings);
        		$this->drawTableHeader($page);
        	}  
        }
        
        //dessine le pied de page
        $this->drawFooter($page);
	        
        //rajoute la pagination
        $this->AddPagination($this->pdf);
        
        $this->_afterGetPdf();

        return $this->pdf;
    }
    
	 
	 /**
	  * Dessine l'entete du tableau avec la liste des produits
	  *
	  * @param unknown_type $page
	  */
	 public function drawTableHeader(&$page)
	 {
	 	
        //entetes de colonnes
        $this->y -= 15;
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12);
        
	 	$page->drawText(mage::helper('purchase')->__('Qty'), 15, $this->y, 'UTF-8');
        $page->drawText(mage::helper('purchase')->__('Sku'), 70, $this->y, 'UTF-8');
        $page->drawText(mage::helper('purchase')->__('Name'), 200, $this->y, 'UTF-8');
        $page->drawText(mage::helper('purchase')->__('Reserved'), 520, $this->y, 'UTF-8');
                
        //barre grise fin entete colonnes
        $this->y -= 8;
        $page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR,  $this->y);
        
        $this->y -= 15;
	 }
}

