<?php
/**
 Hey anyone who sees this stuff, Trygve.... Trygve of Velo Orange here.

 I am creating this picking list from scratch because the module we bought doesn't seem to be
 easily modified to make what we need.

 In OrderController.php I created a picking list action that should call this with an order array
 object as the argument.

 in Grid.php over in adminhtml I added the mass action for this.

 I intend to also create a special page to do this easily.

 This is going to be heavy on the comments because I am a newbe with this stuff.
 */
class Mage_Sales_Model_Order_Pdf_PickingList extends MDN_Orderpreparation_Model_Pdf_Pdfhelper
{

	public function getPdf($order = array())
	{
		//Some kinda translation function
		$this->_beforeGetPdf();

		//No idea what this does, it's in pdf abstract
		$this->_initRenderer('shipment');

		//creating an instance of a pdf
		$pdf = new Zend_Pdf();
		
		//Connecting this with the new pdf object (I think)
		$this->_setPdf($pdf);

		//Creatomg a style, I think it works similar to css styles
		$style = new Zend_Pdf_Style();

		//This is important, creating a page which is not the same as a PDF
		$page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);

		//Add this page to the pages[] array in the pdf object!
		$pdf->pages[] = $page;

		//Don't forget this, $y is not defined in abstract so it must be defined
		//before you use it. It 0 is bottom of the page (yea what the hec)
		// 800 is the top (including page margins ect 840 is absolute top). oh and 600 wide
		$this->y = 790;

		// some nice color cpu saving
		$white = new Zend_Pdf_Color_GrayScale(1);
		$black = new Zend_Pdf_Color_GrayScale(0);
		$grey = new Zend_Pdf_Color_GrayScale(0.5);
		$darkGrey = new Zend_Pdf_Color_GrayScale(0.2);
		/*
		 * Here starts the drawing of the header info section
		 */

		//DATE
		$this->_setFontRegular($page, 10);
		$page->drawText(Mage::helper('sales')->__('Order Date: ') . Mage::helper('core')->formatDate($order->getCreatedAtStoreDate(), 'medium', false), 40, $this->y + 7, 'UTF-8');

		//line setup don't forget it!
		$page->setLineColor($black);
		$page->setLineWidth(1);

		//Going down to headers of first row
		//$this->y -=11;

		//headers are white in dark background this effects rectangle
		$page->setFillColor($grey);

		//header box
		//$page->drawRectangle(25, $this->y-4, 570, $this->y +11);
		$page->drawRectangle(380, $this->y-140, 570, $this->y -155);
		$page->drawLine(25, $this->y-4, 570, $this->y-4);

		//NUMBER easy read boxes
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.85));
		$page->drawRectangle(25, $this->y-5, 244, $this->y -59,Zend_Pdf_Page::SHAPE_DRAW_FILL);
		$page->drawRectangle(335, $this->y-5, 390, $this->y -59,Zend_Pdf_Page::SHAPE_DRAW_FILL);

		$page->drawLine(390, $this->y-4, 390, $this->y-60);

		$page->drawLine(25, $this->y-60, 570, $this->y-60);
		$page->setFillColor($black);

		//easy read order lines
		$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.6));
		$page->drawLine(244, $this->y-5, 244, $this->y-59);
		$page->drawLine(274, $this->y-5, 274, $this->y-59);
		$page->drawLine(304, $this->y-5, 304, $this->y-59);
		$page->drawLine(335, $this->y-5, 335, $this->y-59);
		$page->setLineColor($black);

		$this->y -=50;

		$this->_setFontRegular($page, 55);
		$page->drawText('#'.$order->getRealOrderId(), 30, $this->y, 'UTF-8');

		$this->_setFontRegular($page, 30);

		//International orders require special attention
		if($order->getShippingAddress()->getCountry() != 'US')
		{
			$this->_setFontBold($page, 45);
			$page->drawText('INT '.$order->getShippingAddress()->getCountry(), 395, $this->y, 'UTF-8');
			$this->_setFontRegular($page, 30);
		}
		else
		{
			$this->_setFontRegular($page, 32);
			$actualWidth = 175;
			$initialWidth = $this->widthForStringUsingFontSize2($order->getShippingAddress()->getRegion(), $page->getFont(), $page->getFontSize());

			if ($initialWidth >= $actualWidth)
			{
				$FontSize = $page->getFontSize();
				$fontType = $page->getFont();
				$changingString = $order->getShippingAddress()->getRegion();
				while ($this->widthForStringUsingFontSize2($changingString, $fontType, $FontSize) >= $actualWidth)
				{
					$FontSize -=1;
				}
				$this->_setFontRegular($page, $FontSize);
			}

			$page->drawText($order->getShippingAddress()->getRegion(), 395, $this->y+8, 'UTF-8');
		}
		$this->_setFontItalic($page, 8);
		//second row end first
		$this->y -=40;

		//customer ID
		$page->drawText('Account #', 306, $this->y+20, 'UTF-8');
		$this->_setFontRegular($page, 30);
		$page->drawText($order->getcustomer_id(), 306, $this->y-4, 'UTF-8');
		$page->drawLine(380, $this->y+30, 380, $this->y-40);

		// Customer full last name

		$actualWidth = 275;
		$initialWidth = $this->widthForStringUsingFontSize2($order->getShippingAddress()->getName(), $page->getFont(), $page->getFontSize());

		if ($initialWidth >= $actualWidth)
		{
			$FontSize = $page->getFontSize();
			$fontType = $page->getFont();
			$changingString = $order->getShippingAddress()->getName();
			while ($this->widthForStringUsingFontSize2($changingString, $fontType, $FontSize) >= $actualWidth)
			{
				$FontSize -=1;
			}
			$this->_setFontRegular($page, $FontSize);
		}

		$page->drawText($order->getShippingAddress()->getName(), 25, $this->y, 'UTF-8');
		$page->drawLine(304, $this->y+30, 304, $this->y-10);

		// Shipping Speed
		// Setup bold logic here
		$this->_setFontRegular($page, 25);
		$page->drawText($order->getShippingDescription(), 385, $this->y, 'UTF-8');

		$page->drawLine(25, $this->y-10, 570, $this->y-10);
		//end second row

		//starting from the top on this one (because of comments box)
		$this->y -=10;
		$page->drawRectangle(25, $this->y, 380, $this->y -95,Zend_Pdf_Page::SHAPE_DRAW_STROKE);
		$page->drawLine(380, $this->y-20, 570, $this->y-20);
		$page->drawLine(380, $this->y-40, 570, $this->y-40);

		//Comments Box
		if ($order->getState() == 'holded')
		{
			$this->_setFontBold($page, 25);
			$caption = $this->WrapTextToWidth($page, '< HELD >', 345);
			$offset = $this->DrawMultilineText($page, $caption, 30, $this->y-35, 40, 0.2, 40);
			$this->_setFontRegular($page, 25);
			$TEST = "Bla bla bla edwseufrsdifosdjf sjdfsd sdfju sdsjds sasdh ashd as shds ah flaw nas jdnwnfa wu w nufs a uwn fk a s jwhf js nsd js wnuwf wufn w alsk  fwi nafu sufanw sunf wk sin kw ausn usun uf ajw i sijfwn sufsdfj sidh waks ira wj nfa winjf wie nwi nwi niasn isn fis wi nfu su hdai wji sa hsie hsi hiw lksjd iwj is ia wjmifiuwfi w ifiwifnwif nwifnwifnwia aisnfasisda asidsai s sidjuhfsidf jdf df dfd hfbdh fdl sdfhd bd";
			$caption = $this->WrapTextToWidth($page, $TEST, 865);
			$offset = $this->DrawMultilineText($page, $caption, 30, $this->y-50, 10, 0.2, 10);
		}
		else
		{
			$TEST = "Bla bla bla edwseufrsdifosdjf sjdfsd sdfju sdsjds sasdh ashd as shds ah flaw nas jdnwnfa wu w nufs a uwn fk a s jwhf js nsd js wnuwf wufn w alsk  fwi nafu sufanw sunf wk sin kw ausn usun uf ajw i sijfwn sufsdfj sidh waks ira wj nfa winjf wie nwi nwi niasn isn fis wi nfu su hdai wji sa hsie hsi hiw lksjd iwj is ia wjmifiuwfi w ifiwifnwif nwifnwifnwia aisnfasisda asidsai s sidjuhfsidf jdf df dfd hfbdh fdl sdfhd bd";
			$caption = $this->WrapTextToWidth($page, $TEST, 865);
			$offset = $this->DrawMultilineText($page, $caption, 30, $this->y-15, 10, 0.2, 10);		
		}
		
		//Shipping & Cost
		$this->_setFontRegular($page, 15);
		$page->drawText($order->getComment(), 27, $this->y, 'UTF-8');
		$this->_setFontRegular($page, 18);
		$page->drawText('Shipping: '.$order->formatPriceTxt($order->getShippingAmount()), 383, $this->y-16.5, 'UTF-8');
		$page->drawText('SubTotal: '.$order->formatPriceTxt($order->getSubtotal()), 383, $this->y-36.5, 'UTF-8');

		//pick check row
		$this->y -=55;

		//headers are white in dark background this effects text
		$page->setFillColor($white);
		$this->_setFontRegular($page, 10);

		//pick check header
		$page->drawText('Picked', 385, $this->y+4, 'UTF-8');
		$page->drawText('Checked', 431, $this->y+4, 'UTF-8');
		$page->drawText('Packed', 482, $this->y+4, 'UTF-8');
		$page->drawText('Labeled', 526, $this->y+4, 'UTF-8');

		$page->drawRectangle(380, $this->y+15, 427.5, $this->y -40,Zend_Pdf_Page::SHAPE_DRAW_STROKE);
		$page->drawRectangle(427.5, $this->y+15, 475, $this->y -40,Zend_Pdf_Page::SHAPE_DRAW_STROKE);
		$page->drawRectangle(475, $this->y+15, 522.5, $this->y -40,Zend_Pdf_Page::SHAPE_DRAW_STROKE);
		$page->drawRectangle(522.5, $this->y+15, 570, $this->y -40,Zend_Pdf_Page::SHAPE_DRAW_STROKE);

		$this->y -=60;

		//Item Table Headers
		$page->setFillColor($grey);
		$page->drawRectangle(25, $this->y+20, 570, $this->y);
		$page->setFillColor($white);
		$this->_setFontRegular($page, 16);
		$page->drawText('Shelf', 70, $this->y+4, 'UTF-8');
		$page->drawText('Qty', 161, $this->y+4, 'UTF-8');
		$page->drawText('SKU', 220, $this->y+4, 'UTF-8');
		$page->drawText('Product', 310, $this->y+4, 'UTF-8');
		$page->drawLine(25, $this->y, 570, $this->y);

		$this->y -=22;

		// Here come the ITEMS!



		// this creates a array which contains all products and their fields
		// most of it is taken up by the bin location logic
		$n = 0;
		//count the number of bundles
		$bundleID = 0;
		foreach ($order->getAllItems() as $item)
		{


			$productId = Mage::getModel('catalog/product')->getIdBySku($item->getSku());
			try{
				$product = Mage::getModel('catalog/product')->load($productId);

				// Don't print parents
				if ($product->isSuper() == false)
				{
					// If it's a bundle item add it to the bundled item array
					if ($product->isComposite() == true) {
						$children = $item->getChildrenItems();
						foreach ($children as $child)
						{
							$bundleItems[$bundleID][]=$child->getSku();
						}
						$bundleID += 1;
						//$page->drawText($options['attributes_info'], 3500, $this->y, 'UTF-8');
							
					}
					else
					{

						//Bin location Code - VELO ORANGE ADDITION
						//retrieve string
						$rawBinLocationData = $product->getData('binlocation');
						if ($rawBinLocationData != '')
						{
							//split string into individual bin locations
							$BinLocationData = explode(',', $rawBinLocationData);

							//determine the type of each location
							// Assign values for the 6 normal bin locations,
							// create an array for the rest called genericLocation.
							foreach ($BinLocationData as $location)
							{
								//Find the tag
								$trimmedLocation = trim($location);
								$location = $trimmedLocation;
								if(isset($location[1]) == true)
								{
									if ($location[1] == '!' || $location[1] == '$' || $location[1] == '%')
									{$tag = ($location[0].$location[1]);}
									else if ($location[0] == '!' || $location[0] == '$' || $location[0] == '%')
									{$tag = $location[0];}
									else
									{$tag = '';}
								}
								else
								{$tag = '';}
								//Done finding the tag

								//remove tag
								$trimmed = trim($location, $tag);
								$location = $trimmed;

								switch ($tag)
								{
									case '!':
										$Primary = $location;
										break;
									case '$':
										$PrimarySoverstock = $location;
										break;
									case '%':
										$Primaryoverstock = $location;
										break;
									case '!!':
										$Secondary = $location;
										break;
									case '$$':
										$SecondarySoverstock = $location;
										break;
									case '%%':
										$SecondaryOverstock = $location;
										break;
										// if there is no tag add it to generic
									default:
										$genericLocation = $location;
								}
							}

							// assign the final variable
							if (isset($Primary) == true)
							{$displayBinLocation = $Primary;}
							else if (isset($PrimarySoverstock) == true)
							{$displayBinLocation = $PrimarySoverstock;}
							else if (isset($Primaryoverstock) == true)
							{$displayBinLocation = $Primaryoverstock;}
							else if (isset($Secondary) == true)
							{$displayBinLocation = $Secondary;}
							else if (isset($SecondarySoverstock) == true)
							{$displayBinLocation = $SecondarySoverstock;}
							else if (isset($SecondaryOverstock) == true)
							{$displayBinLocation = $SecondaryOverstock;}
							else
							{$displayBinLocation = $genericLocation;}
						}
						else
						{
							$displayBinLocation = '?';
						}
						// end bin location code.

						$Products[$n] = array("BinLocation"=>$displayBinLocation,"Qty"=>number_format($item->getQtyOrdered()),"SKU"=>$product->getSku(),"Name"=>$product->getName());
						$n=$n+1;
					}
				}
			}
			catch (Exception $e)
			{
				$Products[$n] = array("BinLocation"=>'Error',"Qty"=>'SKU',"SKU"=>'Not',"Name"=>'Found');
				$n=$n+1;
			}
		}

		//Sort it right!
		$order1='asc';
		$natsort=FALSE;
		$case_sensitive=FALSE;
		if(is_array($Products) && count($Products)>0)
		{
			foreach(array_keys($Products) as $key)
			$temp[$key]=$Products[$key]["BinLocation"];
			if(!$natsort)
			($order1=='asc')? asort($temp) : arsort($temp);
			else
			{
				($case_sensitive)? natsort($temp) : natcasesort($temp);
				if($order1!='asc')
				$temp=array_reverse($temp,TRUE);
			}
			foreach(array_keys($temp) as $key)
			(is_numeric($key))? $sorted[]=$Products[$key] : $sorted[$key]=$Products[$key];
			$sorted;
		}
		$Products = $sorted;


		//how long is it?
		$numofproducts = count($Products);
		//write it out then, this is merely drawing the data in the products array

		for ($i = 0; $i < $numofproducts; ++$i)
		{
			if ($this->y < 15) {
				$page = $this->newPageCustom(array('table_header' => true),$order->getRealOrderId());
			}

			if(($i%2) == 0)
			{
				$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.9));
				$page->drawRectangle(25, $this->y+21, 570, $this->y+1,Zend_Pdf_Page::SHAPE_DRAW_FILL);
			}


			$this->_setFontRegular($page, 18);

			//check box
			$page->setFillColor($white);
			$page->setLineColor($darkGrey);
			$page->drawRectangle(26, $this->y+20, 44, $this->y+2);

			$page->setFillColor($darkGrey);

			//color for columns
			$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.8));

			//large bin location logic
			if ((strlen($Products[$i]["BinLocation"])) <= 4)
			{
				$page->drawText($Products[$i]["BinLocation"], 70, $this->y+4, 'UTF-8');
			}
			else if ((strlen($Products[$i]["BinLocation"])) <= 46)
			{
				$this->_setFontRegular($page, 8);

				$caption = $this->WrapTextToWidth($page, $Products[$i]["BinLocation"], 80);
				$offset = $this->DrawMultilineText($page, $caption, 50, $this->y+13, 10, 0.2, 10);
			}
			else
			{
				$this->_setFontRegular($page, 8);
				$caption = $this->WrapTextToWidth($page, 'Bin location string too long(somehow)', 80);
				$offset = $this->DrawMultilineText($page, $caption, 50, $this->y+13, 10, 0.2, 10);
			}

			$page->drawLine(150, $this->y+21, 150, $this->y);

			//greater than one logic
			if($Products[$i]["Qty"]==1)
			{
				$this->_setFontRegular($page, 20);
				$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.4));
				$this->drawTextInBlock($page, $Products[$i]["Qty"], 150, $this->y+3, 60, 22);
			}
			else
			{
				$this->_setFontBold($page, 20);
				$page->setFillColor($black);
				$this->drawTextInBlock($page, $Products[$i]["Qty"], 150, $this->y+3, 60, 22);
			}
			$page->drawLine(210, $this->y+21, 210, $this->y);

			//small sku logic - Outdated thanks to Annette
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.1));
			if(strlen($Products[$i]["SKU"]) <= 7)
			{
				$this->_setFontBold($page, 13);
				$page->drawText(substr($Products[$i]["SKU"], 0, 11), 216, $this->y+5, 'UTF-8');
			}
			else
			{
				$this->_setFontBold($page, 13);
				$page->drawText(substr($Products[$i]["SKU"], 0, 11), 216, $this->y+5, 'UTF-8');
			}
			$page->drawLine(305, $this->y+21, 305, $this->y);

			//name changing size
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.3));
			$this->_setFontRegular($page, 12);
			$actualWidth = 260;
			$initialWidth = $this->widthForStringUsingFontSize2($Products[$i]["Name"], $page->getFont(), $page->getFontSize());

			if ($initialWidth >= $actualWidth)
			{
				$FontSize = $page->getFontSize();
				$fontType = $page->getFont();
				$changingString = $Products[$i]["Name"];
				while ($this->widthForStringUsingFontSize2($changingString, $fontType, $FontSize) >= $actualWidth)
				{
					$FontSize -=1;
				}
				$this->_setFontRegular($page, $FontSize);
			}

			$page->drawText($Products[$i]["Name"], 310, $this->y+6, 'UTF-8');

			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.6));

			$page->setLineColor($grey);
			$page->drawLine(25, $this->y, 570, $this->y);

			$this->y -=22;
		}

		//Bundle Message
		if ($bundleID != 0)
		{
			$this->_setFontRegular($page, 9);
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.4));
			$page->drawText('Bundle these products.', 30, $this->y, 'UTF-8');
			$this->y -=14;
			//For each bundle group (items that are bundled under one item)
			$currentX = 30;
			foreach ($bundleItems as $BundleGroup)
			{
				$lasty1 = $this->y+10;
				//For each item in the bundle
				foreach ($BundleGroup as $bundleItem)
				{
					$page->drawText($bundleItem, $currentX, $this->y, 'UTF-8');
					$this->y -=10;
				}
				$page->drawRectangle($currentX-3, $lasty1, $currentX+67, $this->y+7,Zend_Pdf_Page::SHAPE_DRAW_STROKE);
				$currentX +=70;
			}
		}
		//some more translation stuff


		return $pdf;
	}

	/**
	 * Create new page and assign to PDF object
	 *
	 * @param array $settings
	 * @return Zend_Pdf_Page
	 */
	public function newPageCustom(array $settings = array(),$ordernumber)
	{
		/* Add new table head */
		$page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
		$this->_getPdf()->pages[] = $page;
		$this->y = 800;

		if (!empty($settings['table_header'])) {



			$this->_setFontRegular($page, 8);
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.1));
			$page->drawText('#'.$ordernumber, 30, $this->y+20, 'UTF-8');
			$this->y -=8;
			//Item Table Headers
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.5));
			$page->drawRectangle(25, $this->y+20, 570, $this->y);
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
			$this->_setFontRegular($page, 16);
			$page->drawText('Shelf', 70, $this->y+4, 'UTF-8');
			$page->drawText('Qty', 161, $this->y+4, 'UTF-8');
			$page->drawText('SKU', 220, $this->y+4, 'UTF-8');
			$page->drawText('Product', 310, $this->y+4, 'UTF-8');
			$page->drawLine(25, $this->y, 570, $this->y);
			$this->y -=22;
		}
		return $page;
	}

}