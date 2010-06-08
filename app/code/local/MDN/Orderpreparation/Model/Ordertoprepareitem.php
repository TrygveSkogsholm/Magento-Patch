<?php


/**
 * Classe service pour la préparation des commandes
 *
 */
class MDN_Orderpreparation_Model_OrderToPrepareItem  extends Mage_Core_Model_Abstract
{
	
	private $_SelectedOrders = null;
	
	/*****************************************************************************************************************************
	* ***************************************************************************************************************************
	* Constructeur
	*
	*/
	public function _construct()
	{
		parent::_construct();
		$this->_init('Orderpreparation/ordertoprepareitem');
	}
}