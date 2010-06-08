<?php

/**
 * Imprime le récap des produits à préparer pour l'envoi des commandes
 *
 */
class MDN_Orderpreparation_Model_Pdf_SelectedOrdersProductsSummaryPdf extends MDN_Orderpreparation_Model_Pdf_Pdfhelper
{
	
	public function getPdf($bidon = array())
    {
        
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        //on cree le pdf que si il n'est pas déja défini( ca permet de mettre plrs documents dans le mm pdf (genre une facture, un BL ....)
        if ($this->pdf == null)
	        $this->pdf = new Zend_Pdf();
	        
        $style = new Zend_Pdf_Style();
        $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);

        //cree la nouvelle page
        $titre = mage::helper('purchase')->__('Picking List');
        $settings = array();
        $settings['title'] = $titre;
        $settings['store_id'] = 0;
        $page = $this->NewPage($settings);

        //affiche l'entete du tableau
        $this->drawTableHeader($page);

        $this->y -=10;

        //Affiche le récap des produits
	    $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.2));
    	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
    	
    	//charge la collection des produits
        $collection = Mage::getModel('Orderpreparation/ordertoprepare')->GetProductsSummary();
        foreach ($collection as $product)
        {
        	
        	//dessine
        	$page->drawText($product->getqty_to_prepare(), 15, $this->y, 'UTF-8');
        	$page->drawText($product->getAttributeText('manufacturer'), 70, $this->y, 'UTF-8');
        	$page->drawText($product->getSku(), 180, $this->y, 'UTF-8');
        	$page->drawText($product->getName(), 310, $this->y, 'UTF-8');
        	
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
        $page->drawText(mage::helper('purchase')->__('Manufacturer'), 70, $this->y, 'UTF-8');
        $page->drawText(mage::helper('purchase')->__('Sku'), 180, $this->y, 'UTF-8');
        $page->drawText(mage::helper('purchase')->__('Product'), 310, $this->y, 'UTF-8');
                
        //barre grise fin entete colonnes
        $this->y -= 8;
        $page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR,  $this->y);
        
        $this->y -= 15;
	 }
}

