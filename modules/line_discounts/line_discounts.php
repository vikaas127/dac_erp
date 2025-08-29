<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Line Discounts
Description: Apply line-based discounts to invoices, estimates and proposals
Version: 1.0.0
Author: Halil
Author URI: https://codecanyon.net/user/halilaltndg/portfolio
*/

define('LINE_DISCOUNTS_MODULE_NAME', 'line_discounts');


register_language_files(LINE_DISCOUNTS_MODULE_NAME, [LINE_DISCOUNTS_MODULE_NAME]);


/**
 * Register activation hook
 */
register_activation_hook(LINE_DISCOUNTS_MODULE_NAME, 'line_discounts_activation_hook');

function line_discounts_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}



hooks()->add_action('app_admin_head', 'line_discounts_add_head_components');
hooks()->add_action('app_admin_footer', 'line_discounts_add_footer_components');




// database filter hooks
hooks()->add_filter('before_update_invoice', 'line_discounts_before_save_feature');
hooks()->add_filter('before_invoice_added', 'line_discounts_before_save_feature');

hooks()->add_filter('before_estimate_updated', 'line_discounts_before_save_feature');
hooks()->add_filter('before_estimate_added', 'line_discounts_before_save_feature');

hooks()->add_filter('before_create_proposal', 'line_discounts_before_save_feature');
hooks()->add_filter('before_proposal_updated', 'line_discounts_before_save_feature');

function line_discounts_before_save_feature( $data )
{

    if (isset($data['data']['line_discount_rate'])) {
        unset($data['data']['line_discount_rate']) ;
    }

    return $data;

}




## Database record hooks ##
#invoice
hooks()->add_action('after_invoice_added', function ( $invoice_id ){
    $data = [];
    $data['id'] = $invoice_id;
    $data['new_items'] = get_instance()->input->post( 'newitems' );
    save_discount_info_to_item_table( $data , 'invoice' );
});
hooks()->add_action('invoice_updated', function ( $data ){
    save_discount_info_to_item_table( $data , 'invoice' );
});

#proposal
hooks()->add_action('proposal_created', function ( $proposal_id ){
    $data = [];
    $data['id'] = $proposal_id;
    $data['new_items'] = get_instance()->input->post( 'newitems' );
    save_discount_info_to_item_table( $data , 'proposal' );
});

hooks()->add_action('after_proposal_updated', function ( $proposal_id ){
    $data = [];
    $data['id'] = $proposal_id;
    $data['new_items']  = get_instance()->input->post( 'newitems' );
    $data['items']      = get_instance()->input->post( 'items' );
    save_discount_info_to_item_table( $data , 'proposal' );
});

#estimates
hooks()->add_action('after_estimate_added', function ( $estimate_id ){
    $data = [];
    $data['id'] = $estimate_id;
    $data['new_items'] = get_instance()->input->post( 'newitems' );
    save_discount_info_to_item_table( $data , 'estimate' );
});

hooks()->add_action('after_estimate_updated', function ( $estimate_id ){
    $data = [];
    $data['id'] = $estimate_id;
    $data['new_items']  = get_instance()->input->post( 'newitems' );
    $data['items']      = get_instance()->input->post( 'items' );
    save_discount_info_to_item_table( $data , 'estimate' );
});

hooks()->add_action('estimate_converted_to_invoice', function ( $data ){

    if ( !empty( $data['invoice_id'] ) && !empty( $data['estimate_id'] ) )
    {

        $invoice_id = $data['invoice_id'];
        $estimate_id = $data['estimate_id'];

        $items = get_instance()->db->select('item_order, description , rate , line_discount_rate')
                                    ->from(db_prefix().'itemable')
                                    ->where('rel_type', 'estimate')
                                    ->where('rel_id', $estimate_id)
                                    ->get()->result();

        if ( !empty( $items ) )
        {

            foreach ( $items as $item )
            {
                get_instance()->db->set('line_discount_rate',$item->line_discount_rate)
                                ->where('item_order',$item->item_order)
                                ->where('description',$item->description)
                                ->where('rate',$item->rate)
                                ->where('rel_id',$invoice_id)
                                ->where('rel_type','invoice')
                                ->update(db_prefix().'itemable');
            }

        }


    }

});



// PDF hooks
hooks()->add_filter('items_table_class', 'line_discounts_modify_items_table_class', 10, 5);


/**
 * Add CSS and JS files
 */
function line_discounts_add_head_components()
{
    $CI = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    if ( ! ( strpos($viewuri, 'admin/invoices') !== false ||
        strpos($viewuri, 'admin/estimates') !== false ||
        strpos($viewuri, 'admin/proposals') !== false ) )
    {
        return;
    }

    echo '<link href="' . module_dir_url(LINE_DISCOUNTS_MODULE_NAME, 'assets/css/line_discounts.css') . '" rel="stylesheet" type="text/css" />';
}

function line_discounts_add_footer_components()
{
    $CI = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    if ( ! ( strpos($viewuri, 'admin/invoices') !== false ||
             strpos($viewuri, 'admin/estimates') !== false ||
           strpos($viewuri, 'admin/proposals') !== false ) )
    {
        return;
    }

    echo "<script> 
                var ld_discount_text = '"._l('line_discount_rate')."'; 
                var ld_tax_text = '"._l('invoice_table_tax_heading')."'; 
          </script>";

    echo '<script src="' . module_dir_url(LINE_DISCOUNTS_MODULE_NAME, 'assets/js/line_discounts.js') . '"></script>';
}

/**
 * Modify items table class to include discount column
 */
function line_discounts_modify_items_table_class($class, $transaction, $type, $for, $admin_preview)
{

    if ( ( $for == 'pdf' || $for == 'html' ) && ($type == 'invoice' || $type == 'estimate'  || $type == 'proposal' )) {

        require_once(__DIR__ . '/libraries/Line_discounts_items_table.php');
        return new Line_discounts_items_table($transaction, $type, $for, $admin_preview);
    }

    return $class;
}



function save_discount_info_to_item_table( $data , $rel_type )
{

    $invoice_id = $data["id"];

    if ( !empty( $data["new_items"] ) )
    {
        foreach ( $data["new_items"] as $item )
        {
            $item_order = $item["order"];
            $description = $item["description"];

            $line_discount_rate = $item['line_discount_rate'];

            get_instance()->db->set('line_discount_rate',$line_discount_rate)
                                ->where('item_order',$item_order)
                                ->where('description',$description)
                                ->where('rel_id',$invoice_id)
                                ->where('rel_type',$rel_type)
                                ->update(db_prefix().'itemable');

        }

    }

    if ( !empty( $data["items"] ) )
    {
        foreach ( $data["items"] as $item )
        {

            $item_id = $item["itemid"];
            $line_discount_rate = $item['line_discount_rate'];

            get_instance()->db->set('line_discount_rate',$line_discount_rate)->where('id',$item_id)->update(db_prefix().'itemable');

        }

    }

}