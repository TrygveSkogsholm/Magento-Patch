<?php

class MDN_ClientComputer_Model_System_Config_Source_Mode extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	    protected $_options;

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
        	$this->_options = array();
    		$this->_options[] = array('value' => 'ftp','label' => 'Ftp');
    		$this->_options[] = array('value' => 'webservice','label' => 'Webservice');
        }
        return $this->_options;
    }
}
