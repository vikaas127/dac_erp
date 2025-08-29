<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Invoice Builder
Description: This module helps you create beautifully designed invoices for your goods or services.
Version: 1.0.0
Requires at least: 2.3.*
Author: GreenTech Solutions
Author URI: https://codecanyon.net/user/greentech_solutions
*/

define('INVOICES_BUILDER_MODULE_NAME', 'invoices_builder');
define('INVOICES_BUILDER_UPLOAD_FOLDER', module_dir_path(INVOICES_BUILDER_MODULE_NAME, 'uploads'));
define('INVOICES_BUILDER_REVISION', 100);

hooks()->add_action('admin_init', 'invoices_builder_module_init_menu_items');
hooks()->add_action('app_admin_head', 'inv_builder_head_components');
hooks()->add_action('app_admin_footer', 'inv_builder_load_js');

hooks()->add_action('ib_load_component', 'ib_customer_head_component');
hooks()->add_filter('admin_body_class', 'add_body_class');
hooks()->add_action('before_invoice_deleted', 'delete_built_invoice');
hooks()->add_action('sale_invoice_add_option', 'add_sale_invoice_option');
hooks()->add_action('app_customers_footer', 'init_client_js');
hooks()->add_action('invoices_builder_init',INVOICES_BUILDER_MODULE_NAME.'_appint');
hooks()->add_action('pre_activate_module', INVOICES_BUILDER_MODULE_NAME.'_preactivate');
hooks()->add_action('pre_deactivate_module', INVOICES_BUILDER_MODULE_NAME.'_predeactivate');
register_merge_fields('invoices_builder/merge_fields/invoice_built_merge_fields');

define('INVOICES_BUILDER_PATH', 'modules/invoices_builder/uploads/');
/**
 * Register activation module hook
 */
register_activation_hook(INVOICES_BUILDER_MODULE_NAME, 'invoices_builder_module_activation_hook');
/**
 * Load the module helper
 */
$CI = &get_instance();
$CI->load->helper(INVOICES_BUILDER_MODULE_NAME . '/invoices_builder');

function invoices_builder_module_activation_hook() {
	$CI = &get_instance();
	require_once __DIR__ . '/install.php';
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(INVOICES_BUILDER_MODULE_NAME, [INVOICES_BUILDER_MODULE_NAME]);

/**
 * Init invoices builder module menu items in setup in admin_init hook
 * @return null
 */
function invoices_builder_module_init_menu_items() {

	$CI = &get_instance();

	$CI->app_menu->add_sidebar_menu_item('invoices_builder', [
		'name' => _l('invoices_builder'),
		'icon' => 'fa fa-pencil-square',
		'position' => 20,
	]);

	$CI->app_menu->add_sidebar_children_item('invoices_builder', [
        'slug'     => 'invoices_builder-template',
        'name'     => _l('ib_templates'),
        'icon'     => 'fa fa-file menu-icon',
        'href'     => admin_url('invoices_builder/templates'),
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('invoices_builder', [
        'slug'     => 'build-invoice',
        'name'     => _l('ib_build_invoices'),
        'icon'     => 'fa fa-building menu-icon',
        'href'     => admin_url('invoices_builder/build_invoices'),
        'position' => 1,
    ]);

}

/**
 * add head components
 */
function inv_builder_head_components() {
	$CI = &get_instance();

	$viewuri = $_SERVER['REQUEST_URI'];
	if(!(strpos($viewuri, '/admin/invoices_builder/') === false)){
        echo '<link href="' . module_dir_url(INVOICES_BUILDER_MODULE_NAME, 'assets/css/style.css') . '?v=' . INVOICES_BUILDER_REVISION . '"  rel="stylesheet" type="text/css" />';
    }
}

/**
 * add head components
 */
function ib_customer_head_component() {
    $CI = &get_instance();

    $viewuri = $_SERVER['REQUEST_URI'];
    if(!(strpos($viewuri, '/invoices_builder/') === false)){
        echo '<link href="' . module_dir_url(INVOICES_BUILDER_MODULE_NAME, 'assets/css/style.css') . '?v=' . INVOICES_BUILDER_REVISION . '"  rel="stylesheet" type="text/css" />';
    }
}
/**
 * load js
 */
function inv_builder_load_js(){
    $CI = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];
    if(!(strpos($viewuri, '/admin/invoices_builder/template') === false)){
        echo '<script src="' . module_dir_url(INVOICES_BUILDER_MODULE_NAME, 'third_party/canvas/html2canvas.min.js') . '"></script>';
    }

    if(!(strpos($viewuri, '/admin/invoices_builder/build_invoices') === false) ){
    	echo '<script src="' . module_dir_url(INVOICES_BUILDER_MODULE_NAME, 'assets/js/build_invoices/manage.js') .'?v=' . INVOICES_BUILDER_REVISION.'"></script>';
    }

    if(!(strpos($viewuri, '/admin/invoices_builder/templates') === false)){
        echo '<script src="' . module_dir_url(INVOICES_BUILDER_MODULE_NAME, 'assets/js/templates/template_manage.js') . '"></script>';
    }
    if(!(strpos($viewuri, '/admin/invoices_builder/preview') === false)){
        echo '<script src="' . module_dir_url(INVOICES_BUILDER_MODULE_NAME, 'assets/js/templates/preview.js') . '"></script>';
    }
}


/**
 * add body class
 * @param array $class
 */
function add_body_class($class){
    $viewuri = $_SERVER['REQUEST_URI'];
    if(!(strpos($viewuri, '/admin/invoices_builder/') === false)){
        $class[] = 'no-calculate-total';
    }
    return $class;
}

/**
 * { delete built invoice }
 */
function delete_built_invoice($id){
    $CI = &get_instance();
    $CI->db->where('invoice_id', $id);
    $CI->db->delete(db_prefix().'ib_invoices_built');
}

/**
 * Adds a sale invoice option.
 */
function add_sale_invoice_option(){
    render_yes_no_option('show_invoice_builder_menu_instead_of_curremt_invoice_menu', 'show_invoice_builder_menu_instead_of_curremt_invoice_menu');
    echo '<hr>';
}

/**
 * Initializes the client js.
 */
function init_client_js(){
    if(get_option('show_invoice_builder_menu_instead_of_curremt_invoice_menu') == 1){
        include 'modules/invoices_builder/assets/js/client_portal/portal_js.php';
    }
}

function invoices_builder_appint(){
	/**
    $CI = & get_instance();
    require_once 'libraries/gtsslib.php';
    $invoices_builder_api = new InvoiceBuilderLic();
    $invoices_builder_gtssres = $invoices_builder_api->verify_license(true);
    if(!$invoices_builder_gtssres || ($invoices_builder_gtssres && isset($invoices_builder_gtssres['status']) && !$invoices_builder_gtssres['status'])){
         $CI->app_modules->deactivate(INVOICES_BUILDER_MODULE_NAME);
        set_alert('danger', "One of your modules failed its verification and got deactivated. Please reactivate or contact support.");
        redirect(admin_url('modules'));
    }
		*/
}

function invoices_builder_preactivate($module_name){
    if ($module_name['system_name'] == INVOICES_BUILDER_MODULE_NAME) {
			/**
        require_once 'libraries/gtsslib.php';
        $invoices_builder_api = new InvoiceBuilderLic();
        $invoices_builder_gtssres = $invoices_builder_api->verify_license();
        if(!$invoices_builder_gtssres || ($invoices_builder_gtssres && isset($invoices_builder_gtssres['status']) && !$invoices_builder_gtssres['status'])){
             $CI = & get_instance();
            $data['submit_url'] = $module_name['system_name'].'/gtsverify/activate';
            $data['original_url'] = admin_url('modules/activate/'.INVOICES_BUILDER_MODULE_NAME);
            $data['module_name'] = INVOICES_BUILDER_MODULE_NAME;
            $data['title'] = "Module License Activation";
            echo $CI->load->view($module_name['system_name'].'/activate', $data, true);
            exit();
        }
				*/
    }
}

function invoices_builder_predeactivate($module_name){
    if ($module_name['system_name'] == INVOICES_BUILDER_MODULE_NAME) {
			/**
        require_once 'libraries/gtsslib.php';
        $invoices_builder_api = new InvoiceBuilderLic();
        $invoices_builder_api->deactivate_license();
				*/
    }
}
