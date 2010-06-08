<?php
 
$installer = $this;
 
$installer->startSetup();
 
$installer->run("
 
CREATE TABLE  `{$this->getTable('client_computer_operation')}` (
`cco_id` INT NOT NULL AUTO_INCREMENT ,
`cco_operation` VARCHAR( 50 ) NOT NULL ,
`cco_file` VARCHAR( 255 ) NOT NULL ,
`cco_param` VARCHAR( 255 ) NOT NULL ,
`cco_date` DATETIME NOT NULL,
`cco_name` VARCHAR( 255 ) NOT NULL,
PRIMARY KEY (  `cco_id` )
) ENGINE = MYISAM

    ");
 
$installer->endSetup();
