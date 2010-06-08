<?php

/**
 * Block pour modifier l'etat du paiement d'une commande
 *
 */
class MDN_Purchase_Block_Sales_Order_View_Payment extends Mage_Adminhtml_Block_Widget_Form
{
	private $_order = null;
	
	/**
	 * Définit la commande courante
	 *
	 * @param unknown_type $order
	 */
	public function setOrder($order)
	{
		$this->_order = $order;		
	}
	
	/**
	 * Retourne la commande
	 *
	 * @return unknown
	 */
	public function getOrder()
	{
		return $this->_order;
	}
	    
	protected function _prepareLayout()
    {
    	    	
        $onclick = "form_payment.submit();";
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->addData(array(
                'label'   => Mage::helper('purchase')->__('Submit Info'),
                'class'   => 'save',
                'onclick' => $onclick
            ));
        $this->setChild('submit_button', $button);
        
        return parent::_prepareLayout();
    }
    
    /**
     * Retourne l'url pour la soumission du formulaire
     *
     */
    public function getSubmitUrl()
    {
    	return $this->getUrl('Purchase/Misc/Savepayment');
    }
}