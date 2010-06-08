<?php
/**
 * Order information tab
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MDN_Orderpreparation_Block_Adminhtml_Sales_Order_View_Tab_Preparation
    extends Mage_Adminhtml_Block_Sales_Order_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	
	public $OrderToPrepare = null;
	
    protected function _construct()
    {
        parent::_construct();
        
        $this->setTemplate('sales/order/view/tab/Preparation.phtml');
    }
	
    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Retrieve source model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getOrder();
    }
    	
	/**
	 * Retourne l'url pour imprimer la liste de la commande avec les commentaires & réservations
	 *
	 */
	public function getPrintUrl()
	{
		return $this->getUrl('OrderPreparation/OrderPreparation/PrintComments/', array('order_id' => $this->GetOrder()->getid()));
	}
	
	/**
	 * Récupere l'enregistrement associé dans order_to_prepare
	 *
	 */
	public function getOrderToPrepare()
	{
		if ($this->OrderToPrepare == null)
		{
			$this->OrderToPrepare = mage::getModel('Orderpreparation/ordertoprepare')->load($this->getOrder()->getId(), 'order_id');
			if (!$this->OrderToPrepare->getId())
				$this->OrderToPrepare = null;
		}
		return $this->OrderToPrepare;
	}
	
	/**
	 * Retourne les shipments liés à la commande
	 *
	 */
	public function getOrderShipments()
	{
		$collection = null;
		if ($this->getOrder())
		{
			$collection = $this->getOrder()->getShipmentsCollection();			
		}
		return $collection;
	}
		
	/**
	 * Retourne les factures liées à la commande
	 *
	 */
	public function getOrderInvoices()
	{
		$collection = null;
		if ($this->getOrder())
		{
			$collection = $this->getOrder()->getInvoiceCollection();			
		}
		return $collection;		
	}
    
	public function getShipmentsAsCombo($name, $value)
	{
		$ComboShipment = '<select id="'.$name.'" name="'.$name.'">';
		$ComboShipment .= '<option value=""></option>';
		$collection = $this->getOrderShipments();
		foreach ($collection as $shipment)
		{
			$selected = '';
			if ($shipment->getincrement_id() == $value)
				$selected = ' selected ';
			$ComboShipment .= '<option value="'.$shipment->getincrement_id().'" '.$selected.'>'.$shipment->getincrement_id().' ('.$shipment->getcreated_at().')</option>';
		}
		$ComboShipment .= '</select>';
		return $ComboShipment;
	}
	
	public function getInvoicesAsCombo($name, $value)
	{
		$ComboInvoice = '<select id="'.$name.'" name="'.$name.'">';
		$ComboInvoice .= '<option value=""></option>';
		$collection = $this->getOrderInvoices();
		foreach ($collection as $invoice)
		{
			$selected = '';
			if ($invoice->getincrement_id() == $value)
				$selected = ' selected ';
			$ComboInvoice .= '<option value="'.$invoice->getincrement_id().'"'.$selected.'>'.$invoice->getincrement_id().' ('.$invoice->getcreated_at().')</option>';
		}
		$ComboInvoice .= '</select>';
		
		return $ComboInvoice;
	}

	public function getReservedColumnHtml($orderItem)
	{
		//recupere les infos
    	$value = $orderItem->getreserved_qty();

    	//recupere le produit
    	$productId = $orderItem->getproduct_id();
    	$product = mage::getModel('catalog/product')->load($productId);
    	$retour = '';
    	
    	//si le produit ne gere pas les stocks
    	if ($product->getStockItem()->getManageStock())
		{
			if (($orderItem->getqty_ordered() - $orderItem->getRealShippedQty()) == 0)
			{
				$retour = $this->__('Shipped');
			}
			else 
			{
		    	$remainingQty = (int)$orderItem->getqty_ordered() - $orderItem->getRealShippedQty();
		    	$reserveUrl = Mage::helper('adminhtml')->getUrl('Purchase/ProductReservation/Reserve', array('product_id' => $productId, 'order_id' => $orderItem->getOrderId(), 'return_to_order' => 1));
		    	$releaseUrl = Mage::helper('adminhtml')->getUrl('Purchase/ProductReservation/Release', array('product_id' => $productId, 'order_id' => $orderItem->getOrderId(), 'return_to_order' => 1));
		    	
				if (($orderItem->getreserved_qty() == 0) && ($remainingQty > 0))
					$retour .= '<a href="'.$reserveUrl.'">'.mage::helper('purchase')->__('Reserve').'</a><br>';	
				if ($orderItem->getreserved_qty() > 0)
					$retour .= '<a href="'.$releaseUrl.'">'.mage::helper('purchase')->__('Release').'</a>';
			}
		}
		else 
		{
			$retour = "<font color=\"red\">".$this->__('No Stock Management')."</font>";  
		}
		
    	//retourne
        return $retour;
	}
	
    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return Mage::helper('sales')->__('Preparation');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('Preparation');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}