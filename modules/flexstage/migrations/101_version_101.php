<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        //add 'auto_add_to_calendar' column flexstage events to leads
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexevents` ADD COLUMN `auto_add_to_calendar` tinyint(1) NOT NULL DEFAULT 0');
        //add 'checked_in' column to flexstage ticketsales table
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexticketsales` ADD COLUMN `checked_in` int(10) NOT NULL DEFAULT 0');

        //this will create the storage directory for the flexstage module e.g qrcode
        try{
            flexstage_create_storage_directory();
        }catch(Exception $e){
            log_activity('Flexstage: ' . $e->getMessage());
        }
    }
}