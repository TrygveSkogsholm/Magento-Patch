<?php


/**
 * 
 *
 */
class MDN_Orderpreparation_Model_OrderToPreparePending  extends Mage_Core_Model_Abstract
{
	
	/*****************************************************************************************************************************
	* ***************************************************************************************************************************
	* Constructeur
	*
	*/
	public function _construct()
	{
		parent::_construct();
		$this->_init('Orderpreparation/ordertopreparepending');
	}
}