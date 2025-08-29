<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->field_exists('line_discount_rate', db_prefix() . 'itemable')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'itemable` ADD `line_discount_rate` DECIMAL(15,2) DEFAULT 0.00');
}