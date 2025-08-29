<?php

defined('BASEPATH') or exit('No direct script access allowed');

update_option('max_retries', 5);
update_option('lockout_time', 60); // 60 min
update_option('max_lockouts', 3);
update_option('extend_lockout', 3);
update_option('reset_retries', 2);
update_option('email_notification_after_no_of_lockouts', 2);
update_option('user_inactivity', 120); // 120 min

add_option('send_mail_if_ip_is_different', '0');
add_option('prevent_user_from_login_more_than_once', '0');

if (!table_exists('perfshield_logs')) {
    get_instance()->db->query('
        CREATE TABLE `' . db_prefix() . 'perfshield_logs` (
		  	`id` int NOT NULL AUTO_INCREMENT,
			`email` varchar(100) NOT NULL,
			`time` int NOT NULL,
			`count` int NOT NULL DEFAULT "0",
			`lockout` int NOT NULL DEFAULT "0",
			`ip` varchar(45) NOT NULL,
			`country` varchar(80) DEFAULT NULL,
			`country_code` char(3) DEFAULT NULL,
			`isp` text,
			`mobile` tinyint(1) NOT NULL DEFAULT "0",
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=' . get_instance()->db->char_set
	);
}

if (!table_exists('blacklist')) {
    get_instance()->db->query('
        CREATE TABLE `' . db_prefix() . 'blacklist` (
		  	`id` int(11) NOT NULL AUTO_INCREMENT,
			`ip_email` varchar(100) DEFAULT NULL,
			`type` varchar(10) DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `email` (`ip_email`)
		) ENGINE=InnoDB DEFAULT CHARSET=' . get_instance()->db->char_set
	);
}

if (table_exists('staff')) {
    $fields = [
        'expiry_date' 	 => 'DATE NULL DEFAULT NULL',
        'device_details' => 'text NULL DEFAULT NULL',
        'is_logged_in' 	 => 'tinyint(1) NOT NULL DEFAULT "0"'
    ];
    foreach ($fields as $field => $definition) {
        if (!get_instance()->db->field_exists($field, db_prefix() . 'staff')) {
            get_instance()->db->query('ALTER TABLE `' . db_prefix() . 'staff` ADD `' . $field . '` ' . $definition);
        }
    }
}

if (table_exists('contacts')) {
    $fields = [
        'expiry_date' 	 => 'DATE NULL DEFAULT NULL',
        'device_details' => 'text NULL DEFAULT NULL',
        'is_logged_in' 	 => 'tinyint(1) NOT NULL DEFAULT "0"'
    ];
    foreach ($fields as $field => $definition) {
        if (!get_instance()->db->field_exists($field, db_prefix() . 'contacts')) {
            get_instance()->db->query('ALTER TABLE `' . db_prefix() . 'contacts` ADD `' . $field . '` ' . $definition);
        }
    }
}

/*End of file install.php */