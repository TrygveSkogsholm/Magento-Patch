<?php


class MDN_Purchase_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
	/**
	 * Surcharge la m�thode retournant lm'url du bouton cancel 
	 * pour pouvoir mettre a jour le stock qd la commande est annul�e
	 *
	 * @return unknown
	 */
    public function getCancelUrl()
    {
        return $this->getUrl('Purchase/Misc/Cancelorder');
    }

}
