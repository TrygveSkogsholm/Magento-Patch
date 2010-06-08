<?php

class MDN_Orderpreparation_Model_Source_Sac_WeightType extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
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
                    'value' => 'KGS',
                    'label' => Mage::helper('Orderpreparation')->__('KGS'),
                ),
                array(
                    'value' => 'LBS',
                    'label' => Mage::helper('Orderpreparation')->__('LBS'),
                )              
            );
        }
        return $this->_options;
    }
}