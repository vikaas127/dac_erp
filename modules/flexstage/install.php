<?php

defined('BASEPATH') or exit('No direct script access allowed');

add_option('flexstage_send_emails_per_cron_run', 100);
add_option('last_flexstage_send_cron', '');

if (!$CI->db->table_exists(db_prefix() . 'flexevents')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexevents` (
  `id` int(11) NOT NULL,
  `name` mediumtext NOT NULL,
  `slug` mediumtext NOT NULL,
  `description` text,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `create_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

  //add option to auto sync attendees to leads
  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents` ADD COLUMN `auto_sync_attendees` tinyint(1) NOT NULL DEFAULT 0');

  //add 'auto_add_to_calendar' column flexstage events to leads
  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents` ADD COLUMN `auto_add_to_calendar` tinyint(1) NOT NULL DEFAULT 0');
}

if (!$CI->db->table_exists(db_prefix() . 'flexcategories')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexcategories` (
  `id` int(11) NOT NULL,
  `name` mediumtext NOT NULL,
  `slug` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexcategories`
  ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `category_id` int(11) NOT NULL;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD CONSTRAINT `category_event_fk` FOREIGN KEY(`category_id`) REFERENCES `' . db_prefix() . 'flexcategories`(`id`) ON DELETE RESTRICT;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `summary` mediumtext;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `type` mediumtext NOT NULL;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `location` mediumtext;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `event_link` mediumtext;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `password` mediumtext;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `longitude` double(15, 12);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `latitude` double(15, 12);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `privacy` mediumtext NOT NULL;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `created_by` int(11) NOT NULL;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents`
  ADD COLUMN `tags` mediumtext;');
}

if (!$CI->db->table_exists(db_prefix() . 'flexsocialchannels')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexsocialchannels` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `channel_id` mediumtext NOT NULL,
  `url` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexsocialchannels`
  ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexsocialchannels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexsocialchannels`
  ADD CONSTRAINT `event_socialchannel_fk` FOREIGN KEY(`event_id`) REFERENCES `' . db_prefix() . 'flexevents`(`id`) ON DELETE RESTRICT;');
}

if (!$CI->db->table_exists(db_prefix() . 'fleximages')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'fleximages` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `file_name` mediumtext NOT NULL,
  `original_file_name` mediumtext NOT NULL,
  `filetype` mediumtext NOT NULL,
  `dateadded` datetime NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `subject` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'fleximages`
  ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'fleximages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'fleximages`
  ADD CONSTRAINT `event_image_fk` FOREIGN KEY(`event_id`) REFERENCES `' . db_prefix() . 'flexevents`(`id`) ON DELETE RESTRICT;');
}

if (!$CI->db->table_exists(db_prefix() . 'flexvideos')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexvideos` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `url` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexvideos`
  ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexvideos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexvideos`
  ADD CONSTRAINT `event_video_fk` FOREIGN KEY(`event_id`) REFERENCES `' . db_prefix() . 'flexevents`(`id`) ON DELETE RESTRICT;');
}

if (!$CI->db->table_exists(db_prefix() . 'flextickets')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flextickets` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` mediumtext NOT NULL,
  `status` mediumtext NOT NULL,
  `quantity` int(11) NOT NULL,
  `paid` tinyint(1) DEFAULT 0,
  `currency` mediumtext,
  `price` double(15, 3),
  `min_buying_limit` int(11),
  `max_buying_limit` int(11),
  `sales_start_date` datetime,
  `sales_end_date` datetime,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flextickets`
  ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flextickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flextickets`
  ADD CONSTRAINT `event_ticket_fk` FOREIGN KEY(`event_id`) REFERENCES `' . db_prefix() . 'flexevents`(`id`) ON DELETE CASCADE;');
}

if (!$CI->db->table_exists(db_prefix() . 'flexspeakers')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexspeakers` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` mediumtext NOT NULL,
  `email` mediumtext NOT NULL,
  `image` mediumtext NOT NULL,
  `show` tinyint(1) DEFAULT 0,
  `bio` text
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexspeakers`
  ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexspeakers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexspeakers`
  ADD CONSTRAINT `event_speaker_fk` FOREIGN KEY(`event_id`) REFERENCES `' . db_prefix() . 'flexevents`(`id`) ON DELETE CASCADE;');
}

if (!$CI->db->table_exists(db_prefix() . 'fmlcustomfields')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'fmlcustomfields` (
`customfieldid` int(11) NOT NULL,
`listid` int(11) NOT NULL,
`fieldname` varchar(150) NOT NULL,
`fieldslug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'fmlcustomfields`
ADD PRIMARY KEY (`customfieldid`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'fmlcustomfields`
MODIFY `customfieldid` int(11) NOT NULL AUTO_INCREMENT;');
}

if (!$CI->db->table_exists(db_prefix() . 'fmlcustomfieldvalues')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'fmlcustomfieldvalues` (
`customfieldvalueid` int(11) NOT NULL,
`listid` int(11) NOT NULL,
`customfieldid` int(11) NOT NULL,
`emailid` int(11) NOT NULL,
`value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'fmlcustomfieldvalues`
ADD PRIMARY KEY (`customfieldvalueid`),
ADD KEY `listid` (`listid`),
ADD KEY `customfieldid` (`customfieldid`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'fmlcustomfieldvalues`
MODIFY `customfieldvalueid` int(11) NOT NULL AUTO_INCREMENT;');
}

if (!$CI->db->table_exists(db_prefix() . 'flexlistemails')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexlistemails` (
`emailid` int(11) NOT NULL,
`listid` int(11) NOT NULL,
`email` varchar(100) NOT NULL,
`dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexlistemails`
ADD PRIMARY KEY (`emailid`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexlistemails`
MODIFY `emailid` int(11) NOT NULL AUTO_INCREMENT;');
}

if (!$CI->db->table_exists(db_prefix() . 'flexemaillists')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexemaillists` (
`listid` int(11) NOT NULL,
`name` mediumtext NOT NULL,
`creator` varchar(100) NOT NULL,
`datecreated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexemaillists`
ADD PRIMARY KEY (`listid`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexemaillists`
MODIFY `listid` int(11) NOT NULL AUTO_INCREMENT;');
}

if (!$CI->db->table_exists(db_prefix() . 'flexinvitationsendlog')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . "flexinvitationsendlog` (
`id` int(11) NOT NULL,
`eventid` int(11) NOT NULL,
`total` int(11) NOT NULL,
`date` datetime NOT NULL,
`iscronfinished` int(11) NOT NULL DEFAULT '0',
`send_to_mail_lists` text
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexinvitationsendlog`
ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexinvitationsendlog`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
}

if (!$CI->db->table_exists(db_prefix() . 'flexinvitationsemailsendcron')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexinvitationsemailsendcron` (
`id` int(11) NOT NULL,
`eventid` int(11) NOT NULL,
`email` varchar(100) NOT NULL,
`emailid` int(11) DEFAULT NULL,
`listid` varchar(11) DEFAULT NULL,
`log_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexinvitationsemailsendcron`
ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexinvitationsemailsendcron`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
}

if (!$CI->db->table_exists(db_prefix() . 'flexticketorders')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexticketorders` (
`id` int(11) NOT NULL,
`eventid` int(11) NOT NULL,
`invoiceid` int(11),
`attendee_name` varchar(100) NOT NULL,
`attendee_email` varchar(100) NOT NULL,
`attendee_mobile` varchar(100),
`attendee_company` varchar(100),
`total_amount` double(15, 3) NOT NULL DEFAULT 0,
`tickets_sent` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexticketorders`
ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexticketorders`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

  //add column indicating whether attendee's email can be found in leads
  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexticketorders` ADD COLUMN `in_leads` tinyint(1) NOT NULL DEFAULT 0');
}

if (!$CI->db->table_exists(db_prefix() . 'flexticketsales')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . 'flexticketsales` (
`id` int(11) NOT NULL,
`ticketorderid` int(11) NOT NULL,
`eventid` int(11) NOT NULL,
`ticketid` int(11) NOT NULL,
`reference_code` varchar(100) NOT NULL,
`quantity` int(11) NOT NULL,
`sub_total` double(15, 3)
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexticketsales`
ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexticketsales`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

//add 'checked_in' column to flexstage ticketsales table
$CI->db->query('ALTER TABLE `' . db_prefix() . 'flexticketsales` ADD COLUMN `checked_in` int(10) NOT NULL DEFAULT 0');
}


//add option to store ticket customer reference id
if (empty(get_option('flexstage_customer_reference_id'))) {
  $data = [
    'company' => 'Flexstage Event Records'
  ];
  $id = $CI->clients_model->add($data);
  add_option('flexstage_customer_reference_id', $id);
}
//create a source that will be used to identify leads that are created from flexstage
if (empty(get_option('flexstage_lead_source'))) {
  $data = [
    'name' => 'Flexstage Events',
  ];
  $CI->load->model('leads_model');
  $id = $CI->leads_model->add_source($data);
  add_option('flexstage_lead_source', $id);
}
//create a status that will be used to identify leads that are created from flexstage
if (empty(get_option('flexstage_lead_status'))) {
  $data = [
    'name' => 'Event Attendees',
  ];
  $CI->load->model('leads_model');
  $id = $CI->leads_model->add_status($data);
  add_option('flexstage_lead_status', $id);
}
//create media storage
flexstage_create_storage_directory();

//create email templates
$CI->load->library('flexstage/tickets_module');
$CI->load->library('flexstage/invitations_module');
$CI->tickets_module->create_email_template();
$CI->invitations_module->create_email_template();

if (empty(get_option('flexstage_color'))) {
  add_option('flexstage_color', FLEXSTAGE_COLOR);
}

if (empty(get_option('flexstage_qrcode_image_width'))) {
  add_option('flexstage_qrcode_image_width', FLEXSTAGE_QRCODE_IMAGE_WIDTH);
}