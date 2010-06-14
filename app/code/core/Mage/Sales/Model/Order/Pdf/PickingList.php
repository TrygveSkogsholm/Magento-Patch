<?php
/**
 Hey anyone who sees this stuff, Trygve.... Trygve of Velo Orange here.

 I am creating this picking list from scratch because the module we bought doesn't seem to be
 easily modified to make what we need.

 In OrderController.php I created a picking list action that should call this with a order
 object as the argument.

 in Grid.php over in adminhtml I added the mass action for this.

 I intend to also create a special page to do this easily.

 This is going to be heavy on the comments because I am a newbe with this stuff.
 */
class Mage_Sales_Model_Order_Pdf_PickingList extends Mage_Sales_Model_Order_Pdf_Abstract
{
	//public function getPdf($invoices = array())
	/*public function getPdf()
	{
	$this->_beforeGetPdf();
	$this->_initRenderer('invoice');

	$pdf = new Zend_Pdf();
	$this->_setPdf($pdf);
	$style = new Zend_Pdf_Style();
	$this->_setFontBold($style, 10);

	$page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
	$pdf->pages[] = $page;

	$page->drawText('hello world', 35, $this->y, 'UTF-8');

	return $pdf;
	}*/


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

		/*
		 * Here starts the drawing of the header info section
		 */

		//DATE
		$this->_setFontRegular($page, 10);
		$page->drawText(Mage::helper('sales')->__('Order Date: ') . Mage::helper('core')->formatDate($order->getCreatedAtStoreDate(), 'medium', false), 40, $this->y + 7, 'UTF-8');

		//line setup don't forget it!
		$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.0));
		$page->setLineWidth(1);

		//Going down to headers of first row
		//$this->y -=11;

		//headers are white in dark background this effects rectangle
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.5));

		//header box
		//$page->drawRectangle(25, $this->y-4, 570, $this->y +11);
		$page->drawRectangle(25, $this->y-160, 570, $this->y -175);
		$page->drawLine(25, $this->y-4, 570, $this->y-4);

		//NUMBER easy read boxes
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.85));
		$page->drawRectangle(25, $this->y-5, 244, $this->y -59,Zend_Pdf_Page::SHAPE_DRAW_FILL);
		$page->drawRectangle(335, $this->y-5, 390, $this->y -59,Zend_Pdf_Page::SHAPE_DRAW_FILL);

		$page->drawLine(390, $this->y-4, 390, $this->y-60);

		$page->drawLine(25, $this->y-60, 570, $this->y-60);
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.0));

		//easy read order lines
		$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.6));
		$page->drawLine(244, $this->y-5, 244, $this->y-59);
		$page->drawLine(274, $this->y-5, 274, $this->y-59);
		$page->drawLine(304, $this->y-5, 304, $this->y-59);
		$page->drawLine(335, $this->y-5, 335, $this->y-59);
		$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.0));

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
			$this->_setFontRegular($page, 26);
			$page->drawText(substr($order->getShippingAddress()->getRegion(), 0, 14), 395, $this->y+8, 'UTF-8');
		}
		$this->_setFontRegular($page, 30);
		//second row end first
		$this->y -=40;

		//customer ID
		$page->drawText($order->getcustomer_id(), 306, $this->y, 'UTF-8');
		$page->drawLine(380, $this->y+30, 380, $this->y-10);

		// Customer last name
		$page->drawText(substr($order->getCustomerLastname(), 0, 18), 25, $this->y, 'UTF-8');
		$page->drawLine(304, $this->y+30, 304, $this->y-10);

		// Shipping Speed
		// Setup bold logic here
		$this->_setFontRegular($page, 25);
		$page->drawText($order->getShippingDescription(), 385, $this->y, 'UTF-8');

		$page->drawLine(25, $this->y-10, 570, $this->y-10);
		//end second row

		//starting from the top on this one (because of comments box)
		$this->y -=10;
		$page->drawRectangle(25, $this->y, 380, $this->y -60,Zend_Pdf_Page::SHAPE_DRAW_STROKE);
		$page->drawLine(380, $this->y-30, 570, $this->y-30);
		$page->drawLine(380, $this->y-60, 570, $this->y-60);


		$this->_setFontRegular($page, 15);
		$page->drawText($order->getComment(), 27, $this->y, 'UTF-8');
		$this->_setFontRegular($page, 30);
		$page->drawText($order->formatPriceTxt($order->getShippingAmount()), 383, $this->y-26, 'UTF-8');
		$page->drawText($order->formatPriceTxt($order->getSubtotal()), 383, $this->y-56, 'UTF-8');

		//pick check row
		$this->y -=75;

		//headers are white in dark background this effects text
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
		$this->_setFontRegular($page, 10);

		//Destination header
		$page->drawText('Picked By', 73, $this->y+4, 'UTF-8');
		$page->drawText('Checked By', 209, $this->y+4, 'UTF-8');
		$page->drawText('Packed By', 345, $this->y+4, 'UTF-8');
		$page->drawText('Labeled By', 481, $this->y+4, 'UTF-8');

		$page->drawRectangle(25, $this->y+15, 161.25, $this->y -60,Zend_Pdf_Page::SHAPE_DRAW_STROKE);
		$page->drawRectangle(161.25, $this->y+15, 297.5, $this->y -60,Zend_Pdf_Page::SHAPE_DRAW_STROKE);
		$page->drawRectangle(297.5, $this->y+15, 433.75, $this->y -60,Zend_Pdf_Page::SHAPE_DRAW_STROKE);
		$page->drawRectangle(433.75, $this->y+15, 570, $this->y -60,Zend_Pdf_Page::SHAPE_DRAW_STROKE);

		$this->y -=78;

		//Item Table Headers
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.4));
		$this->_setFontRegular($page, 18);
		$page->drawText('Shelf', 70, $this->y, 'UTF-8');
		$page->drawText('Qty', 158, $this->y, 'UTF-8');
		$page->drawText('SKU', 220, $this->y, 'UTF-8');
		$page->drawText('Product', 310, $this->y, 'UTF-8');
		$page->drawLine(25, $this->y-6, 570, $this->y-6);

		$this->y -=30;

		// Here come the ITEMS!



		// this creates a array which contains all products and their fields
		// most of it is taken up by the bin location logic
		$n = 0;
		foreach ($order->getAllItems() as $item)
		{

			$productId = Mage::getModel('catalog/product')->getIdBySku($item->getSku());
			try{
				$product = Mage::getModel('catalog/product')->load($productId);
					
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
					$displayBinLocation = 'Unknown';
				}
				// end bin location code.
					
				$Products[$n] = array("BinLocation"=>$displayBinLocation,"Qty"=>number_format($item->getQtyOrdered()),"SKU"=>$product->getSku(),"Name"=>$product->getName());
				$n=$n+1;
			}
			catch (Exception $e)
			{
				$Products[$n] = array("BinLocation"=>'Warning',"Qty"=>'SKU',"SKU"=>'Not',"Name"=>'Found');
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
			if(($i%2) == 0)
			{
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.9));
			$page->drawRectangle(25, $this->y+23, 570, $this->y-7,Zend_Pdf_Page::SHAPE_DRAW_FILL);
			}
			
			
			$this->_setFontRegular($page, 28);
			
			//check box
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
			$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.2));
			$page->drawRectangle(25, $this->y+21, 51, $this->y-5);
			
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.2));
			
			//color for columns
			$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.8));
			
			$page->drawText($Products[$i]["BinLocation"], 70, $this->y, 'UTF-8');
			$page->drawLine(150, $this->y+23, 150, $this->y-7);
			
			//greater than one logic
			if($Products[$i]["Qty"]==1)
			{
				$this->_setFontRegular($page, 30);
				$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.4));
				$page->drawText($Products[$i]["Qty"], 158, $this->y-2, 'UTF-8');
			}
			else
			{
				$this->_setFontBold($page, 30);
				$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.0));
				$page->drawText($Products[$i]["Qty"], 158, $this->y-2, 'UTF-8');
			}
			$page->drawLine(210, $this->y+23, 210, $this->y-7);
			
			//small sku logic
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.1));
			if(strlen($Products[$i]["SKU"]) <= 7)
			{
			$this->_setFontBold($page, 20);
			$page->drawText($Products[$i]["SKU"], 220, $this->y, 'UTF-8');
			}
			else
			{
			$this->_setFontBold($page, 13);
			$page->drawText($Products[$i]["SKU"], 220, $this->y, 'UTF-8');				
			}
			$page->drawLine(305, $this->y+23, 305, $this->y-7);
			
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.3));
			$this->_setFontRegular($page, 16);
			$page->drawText($Products[$i]["Name"], 310, $this->y, 'UTF-8');
				
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0.6));
				
			$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
			$page->drawLine(25, $this->y-8, 570, $this->y-8);
				
			$this->y -=32;
		}


		//some more translation stuff
		$this->_afterGetPdf();

		return $pdf;
	}

	/**
	 * Create new page and assign to PDF object
	 *
	 * @param array $settings
	 * @return Zend_Pdf_Page
	 */
	public function newPage(array $settings = array())
	{
		/* Add new table head */
		$page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
		$this->_getPdf()->pages[] = $page;
		$this->y = 800;

		if (!empty($settings['table_header'])) {
			$this->_setFontRegular($page);
			$page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
			$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
			$page->setLineWidth(0.5);
			$page->drawRectangle(25, $this->y, 570, $this->y-15);
			$this->y -=10;

			$page->setFillColor(new Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
			$page->drawText(Mage::helper('sales')->__('Product'), 35, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('SKU'), 255, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Price'), 380, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('QTY'), 430, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Tax'), 480, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Subtotal'), 535, $this->y, 'UTF-8');

			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
			$this->y -=20;
		}

		return $page;
	}

}
