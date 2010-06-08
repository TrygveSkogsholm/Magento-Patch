<?php
/**
*This is the overwrite for the customer model class in mage.
change is in beforeSave() function.
 */

/**
 * Customer model
 *
 * @author      Trygve a velo-orange employee>
 */
class MDN_Customer_Model_Customer extends Mage_Customer_Model_Customer
{
   
protected function _beforeSave()
    {
        parent::_beforeSave();

        $storeId = $this->getStoreId();

        /**
        * ORIGINAL CODE
        *
         if (is_null($storeId)) {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }
        */

        if (is_null($storeId) || !$storeId) {
            $this->setStoreId(Mage::app()->getStore(true)->getId());
        }

        $this->getGroupId();
        return $this;
    } 

}
