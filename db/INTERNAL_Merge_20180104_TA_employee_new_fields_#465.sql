/**********************************************************************
2017-01-04
Tamara Astakhova
Employee new fields
#465 - #474
**********************************************************************/

/**************     #465 **********************/
ALTER TABLE employee ADD COLUMN salary_or_stipend VARCHAR(45) NOT NULL DEFAULT '';

/*****************   #466 **************************/
CREATE TABLE `employee_financial_benefits_description_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) DEFAULT NULL,
  `financial_benefits_description_option` varchar(128) NOT NULL DEFAULT '',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `modified_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestamp_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`financial_benefits_description_option`),
  KEY `created_by` (`created_by`),
  KEY `modified_by` (`modified_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

CREATE TABLE `employee_to_financial_benefits_description_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) DEFAULT NULL,
  `employee_id` int(11) NOT NULL,
  `employee_financial_benefits_description_option_id` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `timestamp_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_cat` (`employee_financial_benefits_description_option_id`,`employee_id`),
  KEY `employee_id` (`employee_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE _system ADD COLUMN display_employee_financial_benefits_description_options TINYINT(1) NOT NULL DEFAULT '1';


 

