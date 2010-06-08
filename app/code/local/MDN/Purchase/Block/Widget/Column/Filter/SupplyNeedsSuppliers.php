<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Checkbox grid column filter
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class MDN_Purchase_Block_Widget_Column_Filter_SupplyNeedsSuppliers extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Select
{
    protected function _getOptions()
    {
        return $this->getSuppliersAsArray();
    }
    
    public function getCondition()
    {
        if ($this->getValue()) {
        	return array('like' => '%,'.$this->getValue().',%');
        }
    }
    
    /**
     * Return suppliers list as array
     *
     */
    public function getSuppliersAsArray()
    {
		$retour = array();
		$retour[] = array('label' => '', 'value' => '');
		
		//charge la liste des pays
		$collection = Mage::getModel('Purchase/Supplier')
			->getCollection()
			->setOrder('sup_name', 'asc');
		foreach ($collection as $item)
		{
			$retour[] = array('label' => $item->getsup_name(), 'value' => $item->getsup_id());
		}
		return $retour;
    }
}