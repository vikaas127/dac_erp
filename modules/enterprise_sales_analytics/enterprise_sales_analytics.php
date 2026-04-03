<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Enterprise Sales Analytics
Description: Advanced BI dashboards for leads, pipeline, revenue and sales analytics
Version: 1.0
Author: Techdotbit
*/

define('ENTERPRISE_SALES_ANALYTICS_MODULE', 'enterprise_sales_analytics');

hooks()->add_action('admin_init', 'esa_init_menu_items');

function esa_init_menu_items()
{
    $CI = &get_instance();

    if (!is_admin()) {
        return;
    }

    /* MAIN MENU */

    $CI->app_menu->add_sidebar_menu_item('sales-intelligence', [
        'name'     => 'Sales Intelligence',
        'icon'     => 'fa fa-chart-line',
        'position' => 5,
    ]);



    /* EXECUTIVE DASHBOARD */

    $CI->app_menu->add_sidebar_children_item('sales-intelligence', [
        'slug' => 'esa-executive',
        'name' => 'Executive Overview',
        'href' => admin_url('enterprise_sales_analytics/executive_overview'),
        'position' => 1,
    ]);



    /* LEADS ANALYTICS */

    $CI->app_menu->add_sidebar_children_item('sales-intelligence', [
        'slug' => 'esa-leads',
        'name' => 'Leads Analytics',
        'href' => admin_url('enterprise_sales_analytics/leads_dashboard'),
        'position' => 2,
    ]);



    /* SALES ANALYTICS */

    $CI->app_menu->add_sidebar_children_item('sales-intelligence', [
        'slug' => 'esa-sales',
        'name' => 'Sales Analytics',
        'href' => admin_url('enterprise_sales_analytics/sales_dashboard'),
        'position' => 3,
    ]);



    /* STAFF PERFORMANCE */

    $CI->app_menu->add_sidebar_children_item('sales-intelligence', [
        'slug' => 'esa-staff',
        'name' => 'Staff Performance',
        'href' => admin_url('enterprise_sales_analytics/staff_dashboard'),
        'position' => 4,
    ]);

}