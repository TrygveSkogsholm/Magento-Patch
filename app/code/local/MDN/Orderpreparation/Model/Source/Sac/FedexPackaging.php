<?php

class MDN_Orderpreparation_Model_Source_Sac_FedexPackaging extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
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
                    'value' => '01',
                    'label' => Mage::helper('Orderpreparation')->__('Customer packaging'),
                ),
                array(
                    'value' => '02',
                    'label' => Mage::helper('Orderpreparation')->__('FedEx Pak'),
                ),
                array(
                    'value' => '03',
                    'label' => Mage::helper('Orderpreparation')->__('FedEx Box'),
                ),
                array(
                    'value' => '04',
                    'label' => Mage::helper('Orderpreparation')->__('FedEx Tube'),
                ),
                array(
                    'value' => '06',
                    'label' => Mage::helper('Orderpreparation')->__('FedEx Envelope'),
                ),
                array(
                    'value' => '15',
                    'label' => Mage::helper('Orderpreparation')->__('FedEx 10KG Box'),
                ),
                array(
                    'value' => '25',
                    'label' => Mage::helper('Orderpreparation')->__('FedEx 25KG Box'),
                )                
            );
        }
        return $this->_options;
    }
}