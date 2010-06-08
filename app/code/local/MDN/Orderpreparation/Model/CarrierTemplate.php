<?php

class MDN_Orderpreparation_Model_CarrierTemplate extends Mage_Core_Model_Abstract
{
	private $_fields = null;
	private $_customFields = null;
	
	private $_myData = null;

	public function _construct()
	{
		parent::_construct();
		$this->_init('Orderpreparation/CarrierTemplate');
	}
	
	/**
	 * Return fields
	 *
	 * @param unknown_type $type
	 * @return unknown
	 */
	public function getFields($type = null)
	{
		$collection = mage::getModel('Orderpreparation/CarrierTemplateField')
						->getCollection()
						->addFieldToFilter('ctf_template_id', $this->getId());
		if ($type != null)
			$collection->addFieldToFilter('ctf_type', $type);
		$collection->setOrder('ctf_position', 'asc');		
		$this->_fields = $collection;

		return $this->_fields;
	}

	/**
	 * Create export file
	 *
	 * @param unknown_type $orderPreparationCollection
	 */
	public function createExportFile($orderPreparationCollection)
	{
		$retour = '';
		
		//add header
		if ($this->getct_export_add_header() == 1)
		{
			$header = '';
			foreach($this->getFields('export') as $field)
			{
				$header .= $this->getFieldDelimiter('export').$field->getctf_name().$this->getFieldDelimiter('export').$this->getFieldSeparator('export');
			}
			$header .= $this->getLineDelimiter();
			$retour .= $header;
		}
		else 
		{
			if ($this->getct_export_custom_header() != '')
			{
				$header = $this->getct_export_custom_header().$this->getLineDelimiter();
				$retour .= $header;				
			}
		}
		
		//add orders
		foreach($orderPreparationCollection as $orderToPrepare)
		{
			if (!$this->isOrderShippingMethodMatches($orderToPrepare->GetOrder()))
				continue;
				
			$line = '';
			$currentData = $this->getDataArray($orderToPrepare);
			foreach($this->getFields('export') as $field)
			{
				$field->setParentTemplate($this);
				$line .= $this->getFieldDelimiter('export').$field->getValue($currentData).$this->getFieldDelimiter('export').$this->getFieldSeparator('export');
			}
			$line .= $this->getLineDelimiter();
			$retour .= $line;
		}
		
		return $retour;
	}

	/**
	 * Import tracking file
	 *
	 */
	public function importTrackingFile($t_lines)
	{
		$importedTrackingCount = 0;
		$skippedTrackingCount = 0;
		$debug = '';
		
		for($i=0;$i<count($t_lines);$i++)
		{
			//skip first line (if required)
			$line = $t_lines[$i];
			if (($i == 0) && ($this->getct_import_skip_first_record()))		
				continue;	

			$tracking = null;
			$shipmentReference = null;
				
			//parse fixed format line
			if ($this->getct_import_format() == 'fixed')
			{
				$currentPosition = 0;

				foreach ($this->getFields('import') as $field)
				{
					$size = $field->getctf_size();
					$fieldValue = substr($line, $currentPosition, $size);
					
					switch ($field->getctf_content()) {
						case 'tracking':
							$tracking = trim($fieldValue);
							break;
						case 'shipment':
							$shipmentReference = trim($fieldValue);
							break;
					}
					
					$currentPosition += $size;
				}
			}
			
			//parse delimiter format line
			if ($this->getct_import_format() == 'delimiter')
			{
				//split fields
				$t_columns = explode($this->getFieldSeparator('import'), $line);		
				foreach ($this->getFields('import') as $field)
				{
					if (isset($t_columns[$field->getctf_position()]))
					{
						$fieldValue = $t_columns[$field->getctf_position()];
						if ($this->getFieldDelimiter('import') != '')
							$fieldValue = str_replace($this->getFieldDelimiter('import'), '', $fieldValue);
						
						switch ($field->getctf_content()) {
							case 'tracking':
								$tracking = $fieldValue;
								break;
							case 'shipment':
								$shipmentReference = $fieldValue;
								break;
						}
					}
				}
			}		
						
			//add tracking
			if (($tracking != null) && ($shipmentReference != null))
			{
				$debug .= 'process tracking '.$tracking.' for shipment #'.$shipmentReference."\n";
				$shipment = mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentReference);
				if ($shipment->getId())
				{
					if (!$this->shipmentContainsTracking($shipment, $tracking))
					{
						try 
						{
							$debug .=  'import tracking='.$tracking.' for shipment='.$shipment->getincrement_id()."\n";
							$track = new Mage_Sales_Model_Order_Shipment_Track();
						    $track->setNumber($tracking)
						          ->setCarrierCode($this->getct_shipping_method())
		                    	  ->setTitle('Tracking');
							$shipment->addTrack($track)->save();
							$importedTrackingCount++;								
						}
						catch (Exception $ex)
						{
							$debug .= 'Error for line #'.$i.' : '.$ex->getMessage()."\n";
							$skippedTrackingCount++;
						}
					}
					else 
					{
						$skippedTrackingCount++;
						$debug .= 'Tracking already exist for line #'.$i."\n";
					}
						
				}
				else 
				{
					$skippedTrackingCount++;
					$debug .= 'Unable to retrieve shipment for line #'.$i."\n";
				}
			}
			else 
				$debug .= 'Unable to retrieve shipment or/and tracking for line #'.$i."\n";
			
		}
		
		mage::log($debug);
		$msg = mage::helper('Orderpreparation')->__('Tracking import complete : %s tracking imported, %s tracking skipped', $importedTrackingCount, $skippedTrackingCount);
		return $msg;
	}
	
	/**
	 * Create an array with every data that can be used in field content
	 *
	 * @param unknown_type $orderToPrepare
	 */
	public function getDataArray($orderToPrepare)
	{
		$this->_myData = array();
		$order = mage::getModel('sales/order')->load($orderToPrepare->getorder_id());
		$shipment = mage::getModel('sales/order_shipment')->loadByIncrementId($orderToPrepare->getshipment_id());
		$address = $order->getShippingAddress();
		if (!$order->getShippingAddress())
			$address = $order->getBillingAddress();
		
		//customer information
		$this->_myData['cust_ref'] = $order->getcustomer_id();
		$this->_myData['cust_ref2'] = str_replace(' ', '', strtoupper($address->getFirstname().substr($address->getLastname(), 0, 1)));;
		$this->_myData['prefix'] = $address->getprefix();
		$this->_myData['company'] = $address->getcompany();
		$this->_myData['firstname'] = $address->getfirstname();
		$this->_myData['lastname'] = $address->getlastname();
		$this->_myData['email'] = $address->getemail();
		
		//address
		$this->_myData['street1'] = $address->getStreet(1);
		$this->_myData['street2'] = $address->getStreet(2);;
		$this->_myData['street3'] = $address->getStreet(3);
		$this->_myData['region'] = $address->getregion();
		$this->_myData['country'] = '';
		$this->_myData['country_code'] = $address->getCountry();
		$this->_myData['postcode'] = $address->getPostcode();
		$this->_myData['city'] = $address->getcity();
		$this->_myData['telephone'] = $address->gettelephone();
		
		//shipment & order
		$this->_myData['order_ref'] = $order->getincrement_id();
		$this->_myData['order_date'] = $order->getcreated_at();
		$this->_myData['shipment_ref'] = $shipment->getincrement_id();
		$this->_myData['shipment_date'] = $shipment->getcreated_at();
		$this->_myData['order_total'] = $order->getbase_grand_total();
		$this->_myData['weight'] = $orderToPrepare->getreal_weight();
		
		//add custom fields
		$value = $orderToPrepare->getcustom_values();
		$rows = explode(';', $value);
		foreach($rows as $row)
		{
			$fields = explode('=', $row);
			if (count($fields) == 2)
				$this->_myData[$fields[0]] = $fields[1];
		}
				
		return $this->_myData;
	}
	
	public function getUsableCodes()
	{
		$retour = array();
		
		$retour[] = 'cust_ref';
		$retour[] = 'cust_ref2';
		$retour[] = 'prefix';
		$retour[] = 'company';
		$retour[] = 'firstname';
		$retour[] = 'lastname';
		$retour[] = 'email';
		
		$retour[] = 'street1';
		$retour[] = 'street2';
		$retour[] = 'street3';
		$retour[] = 'region';
		$retour[] = 'country';
		$retour[] = 'country_code';
		$retour[] = 'postcode';
		$retour[] = 'city';
		$retour[] = 'telephone';
		
		$retour[] = 'order_ref';
		$retour[] = 'order_date';
		$retour[] = 'shipment_ref';
		$retour[] = 'shipment_date';
		$retour[] = 'order_total';
		$retour[] = 'weight';
		
		//add customer fields
		foreach($this->getCustomFields() as $field)
		{
			$retour[] = $field->getCode();
		}
		
		return $retour;
	}
	
	/**
	 * return char separator between fields for export file
	 *
	 * @return unknown
	 */
	private function getFieldSeparator($type)
	{
		$code = '';
		if ($type == 'import')
			$code = $this->getct_import_file_separator();
		else 
			$code = $this->getct_export_file_separator();
		
		switch ($code)
		{
			case 'coma':
				return ',';
				break;
			case 'semicolon':
				return ';';
				break;
			case 'tab':
				return chr(9);
				break;
		}
	}
	
	
	/**
	 * return char separator between fields for export file
	 *
	 * @return unknown
	 */
	private function getFieldDelimiter($type)
	{
		$code = '';
		if ($type == 'import')
			$code = $this->getct_import_file_delimiter();
		else 
			$code = $this->getct_export_file_delimiter();
		
		switch ($code)
		{
			case 'quote':
				return "'";
				break;
			case 'doublequote':
				return '"';
				break;
		}
	}
	
	/**
	 * Return char to end line
	 *
	 * @return unknown
	 */
	private function getLineDelimiter()
	{
		switch ($this->getct_export_line_end())
		{
			case 'n':
				return chr(13);
				break;
			case 'r':
				return chr(10);		
				break;
			case 'rn':
				return chr(10).chr(13);				
				break;
		}
	}

	/**
	 * return custom fields
	 *
	 */
	public function getCustomFields()
	{
		if ($this->_customFields == null)
		{
			$this->_customFields = array();
			foreach ($this->getFields('export') as $field)
			{
				if ($field->isCustomField())
					$this->_customFields[] = $field;
			}
		}	
		return $this->_customFields;
	}
	
	/**
	 * 
	 *
	 * @param unknown_type $order
	 */
	public function isOrderShippingMethodMatches($order)
	{
		$shippingMethod = $order->getshipping_method();
		$pos = strpos($shippingMethod, $this->getct_shipping_method());
		if ($pos === false)
			return false;
		else 
			return true;
	}
	
	/**
	 * Check if a tracking number as already been imported
	 *
	 * @param unknown_type $shipment
	 * @param unknown_type $tracking
	 */
	private function shipmentContainsTracking($shipment, $tracking)
	{
		$exist = false;
		
		if ($shipment->getOrder())
		{
			foreach ($shipment->getOrder()->getTracksCollection() as $track)
			{
				if (is_object($track->getNumberDetail()))
				{
					if ($track->getNumberDetail()->gettracking() == $tracking)
						$exist = true;
				}	
			}
		}		
		return $exist;
	}
	
	/**
	 * Return client directory name to automatically print shipping label
	 *
	 * @return unknown
	 */
	public function getClientDirectoryName()
	{
		return 'directory_'.$this->getct_shipping_method();		
	}

}