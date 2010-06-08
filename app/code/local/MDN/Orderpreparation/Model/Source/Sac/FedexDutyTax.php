<?php

class MDN_Orderpreparation_Model_Source_Sac_FedexDutyTax extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	  

    public function toOptionArray($isMultiselect=false)
    {
        if (!$this->_options)
        {
            $this->getAllOptions();
        	
        }
        return $this->_options;
    }
    
		
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = array(
            	array(
                    'value' => '1',
                    'label' => Mage::helper('Orderpreparation')->__('Bill Sender'),
                ),
                array(
                    'value' => '2',
                    'label' => Mage::helper('Orderpreparation')->__('Bill Recipient'),
                ),
                array(
                    'value' => '3',
                    'label' => Mage::helper('Orderpreparation')->__('Bill Third Party'),
                )
             );
        }
        return $this->_options;
    }
}