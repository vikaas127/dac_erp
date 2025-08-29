<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Promotional Codes
Description: This module provides functionality for creating, managing, and applying promo codes for discounts and promotions.
Version: 1.0.0
Requires at least: 3.1.*
Author: ulutfa
Author URI: https://codecanyon.net/user/ulutfa
*/
define('PROMO_CODES_MODULE_NAME', 'promo_codes');

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(PROMO_CODES_MODULE_NAME, [PROMO_CODES_MODULE_NAME]);

/**
 * Register activation module hook
 */
register_activation_hook(PROMO_CODES_MODULE_NAME, 'promo_codes_module_activation_hook');

/**
 * Load the module helper
 */
$CI = &get_instance();
//$CI->load->helper(PROMO_CODES_MODULE_NAME . '/promo_codes');

function promo_codes_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

hooks()->add_action('admin_init', function () {

    $CI = &get_instance();

    if (staff_can('view', PROMO_CODES_MODULE_NAME)) {
        $CI->app_menu->add_sidebar_menu_item(PROMO_CODES_MODULE_NAME, [
            'slug'     => PROMO_CODES_MODULE_NAME,
            'name'     => _l('promo_codes'),
            'icon'     => 'fa fa-tag',
            'href'     => admin_url('promo_codes'),
            'position' => 45,
        ]);
    }

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities(PROMO_CODES_MODULE_NAME, $capabilities, _l(PROMO_CODES_MODULE_NAME));


    // Settings page menu, view and handling
    $settings_tab = [
        'name'     => _l('settings_group_' . PROMO_CODES_MODULE_NAME),
        'view'     => PROMO_CODES_MODULE_NAME . '/settings',
        'position' => 45,
        'icon'     => 'fa fa-tag',
    ];

    try {
        if (method_exists($CI->app, 'add_settings_section_child')) {
            $CI->app->add_settings_section_child('finance', PROMO_CODES_MODULE_NAME, $settings_tab);
        } else {
            $CI->app_tabs->add_settings_tab(PROMO_CODES_MODULE_NAME, $settings_tab);
        }
    } catch (\Throwable $th) {
        //throw $th;
    }
    hooks()->add_filter('before_settings_updated', function ($data) {
        $key = 'promo_codes_settings_disabled_sales_objects';
        if (isset($data['settings'][$key])) {
            $data['settings'][$key] = json_encode($data['settings'][$key]);
        }
        return $data;
    });
});

// Bind event for sales object UI. Add input to apply code and show applied codes.
hooks()->add_action('app_init', function () {

    $CI = &get_instance();
    $CI->load->library(PROMO_CODES_MODULE_NAME . '/promo_codes_service');
    $CI->load->model(PROMO_CODES_MODULE_NAME . '/promo_codes_model');

    $supported_sales_objects = $CI->promo_codes_service->getSupportedSalesObjects();
    foreach ($supported_sales_objects as $sales_object_type) {

        hooks()->add_action('after_total_summary_' . $sales_object_type . 'html', function ($sales_object) use ($sales_object_type) {

            // Proposal case: Ensure only customer proposals is permitted
            if (isset($sales_object->rel_type) && $sales_object->rel_type !== 'customer') {
                return;
            }

            $CI = &get_instance();

            // Applied promo codes
            $applied = $CI->promo_codes_model->get_sales_object_applied_codes($sales_object->id, $sales_object_type);

            $showInput = $CI->promo_codes_service->canApplyCodeToSalesObject($sales_object_type);
            if (!empty($applied) && get_option('promo_codes_disallow_multiple_code') == '1') {
                $showInput = false;
            }

            if ($showInput) {
                // Show input on sale item view to apply promo codes
                $CI->load->view(
                    PROMO_CODES_MODULE_NAME . '/hooks/promo_codes_add_input_field',
                    [
                        'sales_object_type' => $sales_object_type,
                        'sales_object_id' => $sales_object->id
                    ]
                );
            }

            // Show list of applied code
            if (!empty($applied)) {
                $CI->load->view(
                    PROMO_CODES_MODULE_NAME . '/hooks/show_applied_promo_codes',
                    [
                        'applied' => $applied,
                        'currency' => get_base_currency(),
                        'sales_object_type' => $sales_object_type,
                        'sales_object_id' => $sales_object->id
                    ]
                );
            }
        });
    }
});


/**
 * Implementation to add support for stripe subscription
 */
hooks()->add_action('app_customers_footer', function () {
    // Render client script to manage promo codes
    $sales_object_type = 'subscription';
    if (stripos(uri_string(), $sales_object_type . '/') !== false) {
        $CI = &get_instance();
        if ($CI->promo_codes_service->canApplyCodeToSalesObject($sales_object_type)) {
            $CI->load->view(
                PROMO_CODES_MODULE_NAME . '/hooks/subscription_html'
            );
        }
    }
});
hooks()->add_filter('stripe_subscription_session_data', function ($sessionData, $subscription_hash) {
    // Write to session
    $CI = &get_instance();
    $CI->session->set_userdata(['promo_codes_subscription_' . $subscription_hash . '_promo_codes' => $CI->input->post('promo_codes', true)]);
    return $sessionData;
}, 1000, 2);
hooks()->add_action('app_init', function () {
    // Use proxy class to apply promo codes in session to subscription before creation.
    $sales_object_type = 'subscription';
    if (stripos(uri_string(), $sales_object_type . '/') !== false) {
        $CI = &get_instance();
        $CI->load->library('stripe_subscriptions');
        $CI->load->library(PROMO_CODES_MODULE_NAME . '/promo_codes_stripe_subscriptions');
        $CI->stripe_subscriptions = $CI->promo_codes_stripe_subscriptions;
    }
}, 1000);