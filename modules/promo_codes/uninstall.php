<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$db_prefix = db_prefix();

$table_promo_codes = $db_prefix . 'promo_codes';
$table_promo_usage = $db_prefix . 'promo_codes_usage';

foreach ([$table_promo_usage, $table_promo_codes] as $table) {
    if ($CI->db->table_exists($table)) {
        $CI->db->query("DROP TABLE `" . $table . "`;");
    }
}