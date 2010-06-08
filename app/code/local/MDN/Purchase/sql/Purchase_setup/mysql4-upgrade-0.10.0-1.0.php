<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2009 Maison du Logiciel (http://www.maisondulogiciel.com)
 * @author : Olivier ZIMMERMANN
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer=$this;
/* @var $installer Mage_Eav_Model_Entity_Setup */

$installer->startSetup();
																						
//Cree la table pour les taux de tva produit
$installer->run("
	CREATE TABLE IF NOT EXISTS `{$this->getTable('purchase_tva_rates')}` (
	  `ptr_id` int(11) NOT NULL auto_increment,
	  `ptr_name` varchar(25) NOT NULL,
	  `ptr_value` decimal(6,2) NOT NULL,
	  PRIMARY KEY  (`ptr_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");

//Insere les taux par défaut
$installer->run("
	INSERT INTO `{$this->getTable('purchase_tva_rates')}` (`ptr_id` ,`ptr_name` ,`ptr_value`)
	VALUES 
	(null, 'No tax', '0'),
	(null , 'Tva 5.5%', '5.5'), 
	(null, 'Tva 19.6%', '19.6');
");

																																											
$installer->endSetup();
