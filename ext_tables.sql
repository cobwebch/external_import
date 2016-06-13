CREATE TABLE tx_externalimport_domain_model_log (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	status tinyint(4) DEFAULT '0' NOT NULL,
	configuration varchar(255) DEFAULT '' NOT NULL,
	message text,
	PRIMARY KEY (uid)
);
