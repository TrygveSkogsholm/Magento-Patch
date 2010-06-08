<?php


class MDN_Purchase_Model_Catalog_Config extends Mage_Catalog_Model_Config
{
    /**
     * Surcharge pour que ordered_qty soit toujours chargés dans les listes
     *
     * @return array
     */
    public function getProductAttributes()
    {
    	$return = parent::getProductAttributes();
    	$return[] = 'ordered_qty';
    	return $return;
    }
}