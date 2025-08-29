<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        // Set total_amount to 0
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexticketorders`  ALTER total_amount SET DEFAULT 0');
        // Make total_amount not null
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'flexticketorders`  MODIFY total_amount INT NOT NULL');
    }
}