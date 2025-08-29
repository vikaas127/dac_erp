<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->library(PROMO_CODES_MODULE_NAME . '/promo_codes_service');

$promo_codes_sales_options = $CI->promo_codes_service->getSalesObjectsDropdown(true);

$key = 'promo_codes_settings_disabled_sales_objects';
echo render_select(
    'settings[' . $key . '][]',
    $promo_codes_sales_options,
    ['id', 'name'],
    $key,
    json_decode(get_option($key) ?? '', true),
    ['multiple' => true],
    [],
    '',
    'selectpicker',
    false
);

$key = 'promo_codes_disallow_multiple_code';
render_yes_no_option($key, $key, $key . '_hint');

$key = 'promo_codes_allow_guest';
render_yes_no_option($key, $key, $key . '_hint');