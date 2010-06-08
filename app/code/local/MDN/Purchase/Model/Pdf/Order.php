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
/**
 * Classe pour l'impression d'un bon de commande fournisseur
 *
 */
class MDN_Purchase_Model_Pdf_Order extends MDN_Purchase_Model_Pdf_Pdfhelper
{

	private $_showPictures = false;
	private $_pictureSize = 70;

	public function getPdf($orders = array())
	{
		$this->_beforeGetPdf();
		$this->_initRenderer('invoice');

		if ($this->pdf == null)
		$this->pdf = new Zend_Pdf();
		else
		$this->firstPageIndex = count($this->pdf->pages);
	  
		$style = new Zend_Pdf_Style();
		$style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);

		foreach ($orders as $order)
		{

			//cree la nouvelle page
			$titre = mage::helper('purchase')->__('Purchase Order');
			$settings = array();
			$settings['title'] = $titre;
			$settings['store_id'] = 0;
			$page = $this->NewPage($settings);
				
			//cartouche
			//$txt_date = "Date :  ".mage::helper('core')->formatDate($order->getCreatedAt(), 'long');
			$txt_date = "Date :  ".date('d/m/Y', strtotime($order->getpo_date()));
			$txt_order = "PO# : ".$order->getpo_order_id();
			$adresse_fournisseur = $order->getSupplier()->getAddressAsText();
			if ($order->getShipTo()!= '')
			{
				$adresse_client = $order->getShipTo();
			}
			else
			{
				$adresse_client = Mage::getStoreConfig('sales/identity/address');
			}
			$this->AddAddressesBlock($page, $adresse_fournisseur, $adresse_client, $txt_date, $txt_order);
				
			 
			//affiche l'entete du tableau
			$this->drawTableHeader($page);

			$this->y -=10;


			//Affiche les lignes produit
			foreach ($order->getProducts() as $item)
			{
				$productId = Mage::getModel('catalog/product')->getIdBySku($item->getSku());
				$product = Mage::getModel('catalog/product')->load($productId);

				//Pour les produits "standards"
				$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.2));
				$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);

				//this is our real SKU
				$caption = $this->WrapTextToWidth($page, $item->getSku(), 55);
				$offset = $this->DrawMultilineText($page, $caption, 15, $this->y, 10, 0.2, 11);
				 
				//display SKU or picture
				if ($this->_showPictures == false)
				{
					$caption = $this->WrapTextToWidth($page, $item->getpop_supplier_ref(), 80);
					$offset = $this->DrawMultilineText($page, $caption, 80, $this->y, 10, 0.2, 11);
				}
				else
				{
					$product = mage::getModel('catalog/product')->load($item->getpop_product_id());
					if ($product->getId())
					{
						$productImagePath = Mage::getBaseDir().'/media/catalog/product'.$product->getsmall_image();
						if (is_file($productImagePath))
						{
							try
							{
								$image = Zend_Pdf_Image::imageWithPath($productImagePath);
								$page->drawImage($image, 10, $this->y-$this->_pictureSize+20, 5+$this->_pictureSize, $this->y+10);
							}
							catch (Exception $ex)
							{

							}
						}
					}
				}

				$caption = $this->WrapTextToWidth($page, $item->getpop_product_name(), 135);
				$offset = $this->DrawMultilineText($page, $caption, 160, $this->y, 10, 0.2, 11);
				//if ($order->getpo_status() != MDN_Purchase_Model_Order::STATUS_INQUIRY )
				$this->drawTextInBlock($page, ($product->getData('hts')), 300, $this->y, 60, 20, 'r');
				$this->drawTextInBlock($page, ($order->getCurrency()->formatTxt($item->getpop_price_ht())), 415, $this->y, 60, 20, 'r');
				$this->drawTextInBlock($page, (int)$item->getpop_qty(), 380, $this->y, 40, 20, 'c');
				//if ($order->getpo_status() != MDN_Purchase_Model_Order::STATUS_INQUIRY )
				//{
				//$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($item->getpop_eco_tax()), 365, $this->y, 40, 20, 'c');
					//$this->drawTextInBlock($page, number_format($item->getpop_tax_rate(), 2).'%', 410, $this->y, 40, 20, 'c');
					$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($item->getRowTotal()), 495, $this->y, 60, 20, 'r');
					//$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($item->getRowTotalWithTaxes($order->getpo_tax_rate())), 520, $this->y, 60, 20, 'r');
					//}	        	getProduct()->getsku()

					if ($this->_showPictures)
					$this->y -= $this->_pictureSize;
					else
					$this->y -= $this->_ITEM_HEIGHT;
					 
					//si on a plus la place de rajouter le footer, on change de page
					if ($this->y < ($this->_BLOC_FOOTER_HAUTEUR + 40))
					{
						$this->drawFooter($page);
						$page = $this->NewPage($settings);
						$this->drawTableHeader($page);
					}


					if ($product->getData('upc') != '')
					{
						$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.2));
						$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_OBLIQUE), 8);
						$this->drawTextInBlock($page, ('UPC: '.$product->getData('upc')), 15, ($this->y + 10), 60, 20, 'l');
						$this->y -= 10;
						$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.2));
						$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
					}

				}

				//rajoute les frais d'expédition
				if ($order->getpo_status() != MDN_Purchase_Model_Order::STATUS_INQUIRY )
				{
					$style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);
					$this->DrawMultilineText($page, mage::helper('purchase')->__('Shipping costs'), 90, $this->y, 10, 0.2, 11);
					//$this->drawTextInBlock($page, number_format($order->getpo_tax_rate(), 2).'%', 410, $this->y, 40, 20, 'c');
					$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($order->getShippingAmountHt()), 450, $this->y, 60, 20, 'r');
					$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($order->getShippingAmountTtc()), 520, $this->y, 60, 20, 'r');
					 
					//rajoute les droits de douane
					//$this->y -= $this->_ITEM_HEIGHT;
					//$style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);
					//$this->DrawMultilineText($page, mage::helper('purchase')->__('Zoll costs'), 90, $this->y, 10, 0.2, 11);
					//$this->drawTextInBlock($page, number_format($order->getpo_tax_rate(), 2).'%', 410, $this->y, 40, 20, 'c');
					//$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($order->getZollAmountHt()), 450, $this->y, 60, 20, 'r');
					//$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($order->getZollAmountTtc()), 520, $this->y, 60, 20, 'r');
				}

				//si on a plus la place de rajouter le footer, on change de page
				if ($this->y < (150))
				{
					$this->drawFooter($page);
					$page = $this->NewPage($settings);
					$this->drawTableHeader($page);
				}

				//barre grise début totaux
				$this->y -= 10;
				$page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR,  $this->y);
	    
				//barre verticale de séparation des totaux
				$VerticalLineHeight = 80;
				$page->drawLine($this->_PAGE_WIDTH / 2, $this->y, $this->_PAGE_WIDTH / 2, $this->y - $VerticalLineHeight);
	    
				//rajoute les libellés & les totaux
				$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 14);
				$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.2));
				$this->y -= 20;
				 
				//Zone commentaires
				$comments = Mage::getStoreConfig('purchase/general/pdf_comment');
				if (($comments != '') && ($comments != null))
				{
					$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.3));
					$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
					$page->drawText(mage::helper('purchase')->__('Bill To:'), 15, $this->y, 'UTF-8');
					//$offset = $this->DrawMultilineText($page, $com, 15, $this->y - 15, 10, 0.3, 11);
					//$this->drawTextInBlock($page, $com, 15, $this->y - 15, 200, 200, 'l');
					$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.3));
					$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12);
					$comments = $this->WrapTextToWidth($page, $comments, $this->_PAGE_WIDTH / 2);
					$this->DrawMultilineText($page, $comments, 15, $this->y - 15, 10, 0.2, 11);
				}
				//if ($order->getpo_status() != MDN_Purchase_Model_Order::STATUS_INQUIRY )
				//{
				$page->drawText(mage::helper('purchase')->__('Sub Total.'), $this->_PAGE_WIDTH / 2 + 10, $this->y, 'UTF-8');
				$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($order->getTotalWithOutDuty()), $this->_PAGE_WIDTH / 2, $this->y, $this->_PAGE_WIDTH / 2 - 30, 40, 'r');
				$this->y -= 20;
				$page->drawText('Estimated Duty.', $this->_PAGE_WIDTH / 2 + 10, $this->y, 'UTF-8');
				$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($order->getTaxAmount()), $this->_PAGE_WIDTH / 2, $this->y, $this->_PAGE_WIDTH / 2 - 30, 40, 'r');
				$this->y -= 20;
				$page->drawText('Grand Total.', $this->_PAGE_WIDTH / 2 + 10, $this->y, 'UTF-8');
				$this->drawTextInBlock($page, $order->getCurrency()->formatTxt($order->getTotalTtc()), $this->_PAGE_WIDTH / 2, $this->y, $this->_PAGE_WIDTH / 2 - 30, 40, 'r');
				//}

				//barre grise fin totaux
				$this->y -= 20;
				$page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR,  $this->y);
	    
				//Rajoute le réglement et le transporteur
				$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
				$this->y -= 20;
				$page->drawText(mage::helper('purchase')->__('Billing Method : ').$order->getpo_payment_type(), 15, $this->y, 'UTF-8');
				$this->y -= 20;
				$page->drawText(mage::helper('purchase')->__('Carrier : ').$order->getpo_carrier(), 15, $this->y, 'UTF-8');
	    
				$this->y -= 20;
				$page->drawText('Shipping Method: '.$order->getShipSpeed(), 15, $this->y, 'UTF-8');
	    
				//ligne de séparation
				$this->y -= 20;
				$page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR,  $this->y);
	    
				//Zone acceptation de la commande
				$this->y -= 20;
				$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12);
				$page->drawText(mage::helper('purchase')->__('Comments : '), 15, $this->y, 'UTF-8');
				$this->y -= 20;
				$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
				$txt = $order->getpo_comments();
				$txt = $this->WrapTextToWidth($page, $txt, $this->_PAGE_WIDTH - 100);
				$this->DrawMultilineText($page, $txt, 15, $this->y, 10, 0.2, 11);

				//dessine le pied de page
				$this->drawFooter($page);
				}

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
	 	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);

	 	$page->drawText(mage::helper('purchase')->__('SKU'), 15, $this->y, 'UTF-8');
	 	$page->drawText(mage::helper('purchase')->__('Model #'), 80, $this->y, 'UTF-8');
	 	$page->drawText(mage::helper('purchase')->__('Description'), 160, $this->y, 'UTF-8');
	 	$page->drawText(mage::helper('purchase')->__('HTS code'), 300, $this->y, 'UTF-8');
	 	$page->drawText(mage::helper('purchase')->__('Unit Price'), 440, $this->y, 'UTF-8');
	 	$page->drawText(mage::helper('purchase')->__('Qty'), 390, $this->y, 'UTF-8');
	 	//$page->drawText(mage::helper('purchase')->__('WEEE'), 365, $this->y, 'UTF-8');
	 	//$page->drawText(mage::helper('purchase')->__('Tax'), 410, $this->y, 'UTF-8');
	 	$page->drawText(mage::helper('purchase')->__('Total HT'), 510, $this->y, 'UTF-8');
	 	//$page->drawText(mage::helper('purchase')->__('Total + Duty'), 530, $this->y, 'UTF-8');

	 	//barre grise fin entete colonnes
	 	$this->y -= 8;
	 	$page->drawLine(10, $this->y, $this->_BLOC_ENTETE_LARGEUR,  $this->y);

	 	$this->y -= 15;
	 }

	}