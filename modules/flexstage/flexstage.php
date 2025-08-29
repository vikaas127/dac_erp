<?php

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Flex Stage - Event Manager
Description: Flex stage your lead generator event manager. it allows you to manage event and nurture new contacts right from your Perfex CRM
Version: 1.0.2
Requires at least: 2.3.*
*/
require_once(__DIR__ . '/vendor/autoload.php');
define('flexstage_MODULE_NAME', 'flexstage');
define('FLEXSTAGE_UPLOAD_FOLDER', FCPATH . 'uploads/' . flexstage_MODULE_NAME . '/');
define('FLEXSTAGE_IMAGES_FOLDER', FLEXSTAGE_UPLOAD_FOLDER . 'images/');
define('FLEXSTAGE_SPEAKERS_FOLDER', FLEXSTAGE_UPLOAD_FOLDER . 'speakers/');
define('FLEXSTAGE_TICKETS_FOLDER', FLEXSTAGE_UPLOAD_FOLDER . 'tickets/');
define('FLEXSTAGE_QR_CODE_FOLDER', FLEXSTAGE_UPLOAD_FOLDER . 'qr_codes/');
define('FLEXSTAGE_MAX_IMAGES', 10);
define('FLEXSTAGE_FIELD_TO', 'flexstage');
define('FLEXSTAGE_COLOR', '#a9b338');
define('FLEXSTAGE_QRCODE_IMAGE_WIDTH', '150');
hooks()->add_action('admin_init', 'flexstage_module_init_menu_items');
hooks()->add_action('admin_init', 'flexstage_permissions');
hooks()->add_action('after_cron_settings_last_tab', 'flexstage_event_invitation_cron_settings_tab');
hooks()->add_action('after_cron_settings_last_tab_content', 'flexstage_event_invitation_cron_settings_tab_content');
hooks()->add_action('invoice_status_changed', flexstage_MODULE_NAME . '_invoice_status_changed');
hooks()->add_action('after_custom_fields_select_options', 'flexstage_custom_filed_select_option');
hooks()->add_action('after_cron_run', 'flexstage_event_invitation_send');

register_merge_fields("flexstage/merge_fields/flexstage_merge_fields");
register_merge_fields("flexstage/merge_fields/flexstage_invitation_merge_fields");

/**
 * Register activation module hook
 */
register_activation_hook(flexstage_MODULE_NAME, 'flexstage_module_activation_hook');

function flexstage_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(flexstage_MODULE_NAME, [flexstage_MODULE_NAME]);

/**
 * Init surveys module menu items in setup in admin_init hook
 * @return null
 */
function flexstage_module_init_menu_items()
{
    $CI = &get_instance();
    if (has_permission('flexstage', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('flexstage', [
            'name' => _l('flexstage'),
            // The name if the item
            'href' => admin_url('flexstage'),
            // URL of the item
            'position' => 20,
            // The menu position, see below for default positions.
            'icon' => 'fa-solid fa-calendar-days',
            // Font awesome icon
        ]);
    }
}

function flexstage_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit' => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('flexstage', $capabilities, _l('flexstage'));
}

function flexstage_event_types()
{
    return [
        [
            'id' => 'location-based',
            'label' => _l('flexstage_location_based'),
        ],
        [
            'id' => 'online',
            'label' => _l('flexstage_online'),
        ],
        [
            'id' => 'hybrid',
            'label' => _l('flexstage_hybrid_event'),
        ]
    ];
}

function flexstage_event_privacy()
{
    return [
        [
            'id' => 'public',
            'label' => _l('flexstage_public'),
        ],
        [
            'id' => 'staff-only',
            'label' => _l('flexstage_staff_only'),
        ],
        [
            'id' => 'customer-only',
            'label' => _l('flexstage_customer_only'),
        ],
        [
            'id' => 'customer-staff',
            'label' => _l('flexstage_customer_staff'),
        ],
    ];
}

function init_flexstage_event_form()
{
    $CI = &get_instance();
    return $CI->load->view('partials/event-form');
}

function init_flexstage_ticket_order()
{
    $CI = &get_instance();
    return $CI->load->view('partials/ticket-order');
}

function init_flexstage_event_navbar()
{
    $CI = &get_instance();
    return $CI->load->view('partials/event-navbar');
}

function init_flexstage_event_header()
{
    $CI = &get_instance();
    return $CI->load->view('partials/event-header');
}

function flexstage_event_details_menu($event_id)
{
    $base_url = admin_url('flexstage/event_details/' . $event_id);
    return [
        [
            'name' => _l('flexstage_basic_info'),
            // The name if the item
            'href' => $base_url . '?key=basic-info',
            // URL of the item
            'icon' => 'fa-solid fa-info',
            // Font awesome icon
            'key' => 'basic-info',
        ],
        [
            'name' => _l('flexstage_tickets'),
            // The name if the item
            'href' => $base_url . '?key=tickets',
            // URL of the item
            'icon' => 'fa-solid fa-ticket',
            // Font awesome icon
            'key' => 'tickets',
        ],
        [
            'name' => _l('flexstage_media'),
            // The name if the item
            'href' => $base_url . '?key=media',
            // URL of the item
            'icon' => 'fa-solid fa-photo-video',
            // Font awesome icon
            'key' => 'media',
        ],
        [
            'name' => _l('flexstage_attendees'),
            // The name if the item
            'href' => $base_url . '?key=attendees',
            // URL of the item
            'icon' => 'fa-solid fa-users',
            // Font awesome icon
            'key' => 'attendees',
        ],
        [
            'name' => _l('flexstage_checkin_attendees'),
            // The name if the item
            'href' => $base_url . '?key=checkin',
            // URL of the item
            'icon' => 'fa-solid fa-check',
            // Font awesome icon
            'key' => 'checkin',
        ],
        [
            'name' => _l('flexstage_email_invitation'),
            // The name if the item
            'href' => $base_url . '?key=email-invitation',
            // URL of the item
            'icon' => 'fa-solid fa-envelope',
            // Font awesome icon
            'key' => 'email-invitation',
        ],
        [
            'name' => _l('flexstage_speakers'),
            // The name if the item
            'href' => $base_url . '?key=speakers',
            // URL of the item
            'icon' => 'fa-solid fa-user',
            // Font awesome icon
            'key' => 'speakers',
        ],
        [
            'name' => _l('flexstage_social_pages'),
            // The name if the item
            'href' => $base_url . '?key=social-pages',
            // URL of the item
            'icon' => 'fa-solid fa-share',
            // Font awesome icon
            'key' => 'social-pages',
        ]

    ];
}

function flexstage_social_channels()
{
    return [
        [
            'id' => 'twitter',
            'label' => _l('flexstage_twitter'),
            'placeholder' => _l('flexstage_placeholder_twitter'),
        ],
        [
            'id' => 'facebook',
            'label' => _l('flexstage_facebook'),
            'placeholder' => _l('flexstage_placeholder_facebook'),
        ],
        [
            'id' => 'youtube',
            'label' => _l('flexstage_youtube'),
            'placeholder' => _l('flexstage_placeholder_youtube'),
        ],
        [
            'id' => 'linkedin',
            'label' => _l('flexstage_linkedin'),
            'placeholder' => _l('flexstage_placeholder_linkedin'),
        ],
        [
            'id' => 'telegram',
            'label' => _l('flexstage_telegram'),
            'placeholder' => _l('flexstage_placeholder_telegram'),
        ],
        [
            'id' => 'instagram',
            'label' => _l('flexstage_instagram'),
            'placeholder' => _l('flexstage_placeholder_instagram'),
        ],
    ];
}

function get_event_social_channel($event_id, $channel_id)
{
    $CI = &get_instance();
    $CI->load->model('flexstage/flexsocialchannel_model');

    $conditions = [
        'event_id' => $event_id,
        'channel_id' => $channel_id
    ];

    $social_channel = $CI->flexsocialchannel_model->get($conditions);

    if (empty($social_channel)) {
        return '';
    }

    return $social_channel['url'];
}

function get_file_path($directory = 'images')
{
    switch ($directory) {
        case 'speakers':
            return FLEXSTAGE_SPEAKERS_FOLDER;
        case 'tickets':
            return FLEXSTAGE_TICKETS_FOLDER;
        case 'qr_codes':
            return FLEXSTAGE_QR_CODE_FOLDER;

        default:
            return FLEXSTAGE_IMAGES_FOLDER;
    }
}

/**
 * Custom image file url helper function
 * @param  array  $file    file from database
 * @param  boolean $preview is preview image, will return url with thumb
 * @return string
 */
function fs_image_file_url($event_id, $file_name, $directory = 'images')
{
    return site_url("uploads/flexstage/$directory/" . $event_id . '/' . $file_name);
}

function save_speaker($post, $limit = 1)
{
    $filesIDS = [];
    $errors = [];
    $directory = 'speakers';
    $field = 'image';

    $CI = &get_instance();

    if (
        isset($_FILES[$field]['name'])
        && ($_FILES[$field]['name'] != '' || is_array($_FILES[$field]['name']) && count($_FILES[$field]['name']) > 0)
    ) {
        if (!is_array($_FILES[$field]['name'])) {
            $_FILES[$field]['name'] = [$_FILES[$field]['name']];
            $_FILES[$field]['type'] = [$_FILES[$field]['type']];
            $_FILES[$field]['tmp_name'] = [$_FILES[$field]['tmp_name']];
            $_FILES[$field]['error'] = [$_FILES[$field]['error']];
            $_FILES[$field]['size'] = [$_FILES[$field]['size']];
        }

        $path = get_file_path($directory) . $post['event_id'] . '/';

        // When updating a speaker with an image file
        // delete the previous file.
        if (array_key_exists('id', $post)) {
            $CI->load->model('flexstage/flexspeaker_model');
            $CI->flexspeaker_model->remove_image($post['id']);
        }


        for ($i = 0; $i < $limit; $i++) {
            $upload_file_name = $_FILES[$field]['name'][$i];

            if (_perfex_upload_error($_FILES[$field]['error'][$i])) {
                $errors[$upload_file_name] = _perfex_upload_error($_FILES[$field]['error'][$i]);

                continue;
            }

            // Get the temp file path
            $tmpFilePath = $_FILES[$field]['tmp_name'][$i];
            $filetype = $_FILES[$field]['type'][$i];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                _maybe_create_upload_path($path);
                $originalFilename = unique_filename($path, $upload_file_name);
                $filename = app_generate_hash() . '.' . get_file_extension($originalFilename);

                // In case client side validation is bypassed
                if (!_upload_extension_allowed($filename)) {
                    continue;
                }

                $newFilePath = $path . $filename;
                // Upload the file into the event uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $post = array_merge($post, [
                        'image' => $filename,
                    ]);

                    if (array_key_exists('id', $post)) {
                        $id = $post['id'];
                        unset($post['id']);
                        $saved_or_insert_id = $CI->db
                            ->where('id', $id)
                            ->update(db_prefix() . 'flexspeakers', $post);
                    } else {
                        $CI->db->insert(db_prefix() . 'flexspeakers', $post);
                        $saved_or_insert_id = $CI->db->insert_id();
                    }

                    if ($saved_or_insert_id) {
                        if (is_image($newFilePath)) {
                            create_img_thumb($path, $filename);
                        }
                        array_push($filesIDS, $saved_or_insert_id);
                    } else {
                        unlink($newFilePath);

                        return false;
                    }
                }
            }
        }
    } else {
        if (array_key_exists('id', $post)) {
            $id = $post['id'];
            unset($post['id']);
            $saved_or_insert_id = $CI->db
                ->where('id', $id)
                ->update(db_prefix() . 'flexspeakers', $post);
        } else {
            $CI->db->insert(db_prefix() . 'flexspeakers', $post);
            $saved_or_insert_id = $CI->db->insert_id();
        }

        array_push($filesIDS, $saved_or_insert_id);
    }


    if (count($errors) > 0) {
        $message = '';
        foreach ($errors as $filename => $error_message) {
            $message .= $filename . ' - ' . $error_message . '<br />';
        }
        header('HTTP/1.0 400 Bad error');
        echo $message;
        die;
    }

    if (count($filesIDS) > 0) {
        return true;
    }

    return false;
}

/**
 * Handles upload for media images
 * @param  mixed $event_id event id
 * @return boolean
 */
function handle_event_image_uploads($event_id, $limit)
{
    $filesIDS = [];
    $errors = [];

    if (
        isset($_FILES['file']['name'])
        && ($_FILES['file']['name'] != '' || is_array($_FILES['file']['name']) && count($_FILES['file']['name']) > 0)
    ) {
        // hooks()->do_action('before_upload_project_attachment', $event_id);

        if (!is_array($_FILES['file']['name'])) {
            $_FILES['file']['name'] = [$_FILES['file']['name']];
            $_FILES['file']['type'] = [$_FILES['file']['type']];
            $_FILES['file']['tmp_name'] = [$_FILES['file']['tmp_name']];
            $_FILES['file']['error'] = [$_FILES['file']['error']];
            $_FILES['file']['size'] = [$_FILES['file']['size']];
        }

        $path = get_file_path() . $event_id . '/';

        for ($i = 0; $i < $limit; $i++) {
            $upload_file_name = $_FILES['file']['name'][$i];

            if (_perfex_upload_error($_FILES['file']['error'][$i])) {
                $errors[$upload_file_name] = _perfex_upload_error($_FILES['file']['error'][$i]);

                continue;
            }

            // Get the temp file path
            $tmpFilePath = $_FILES['file']['tmp_name'][$i];
            $filetype = $_FILES['file']['type'][$i];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                _maybe_create_upload_path($path);
                $originalFilename = unique_filename($path, $upload_file_name);
                $filename = app_generate_hash() . '.' . get_file_extension($originalFilename);

                // In case client side validation is bypassed
                if (!_upload_extension_allowed($filename)) {
                    continue;
                }

                $newFilePath = $path . $filename;
                // Upload the file into the event uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $CI = &get_instance();
                    $data = [
                        'event_id' => $event_id,
                        'file_name' => $filename,
                        'original_file_name' => $originalFilename,
                        'filetype' => $filetype,
                        'dateadded' => to_sql_date(date('Y-m-d H:i:s'), true),
                        'uploaded_by' => get_staff_user_id(),
                        'subject' => $originalFilename,
                    ];

                    $CI->db->insert(db_prefix() . 'fleximages', $data);

                    $insert_id = $CI->db->insert_id();
                    if ($insert_id) {
                        if (is_image($newFilePath)) {
                            create_img_thumb($path, $filename);
                        }
                        array_push($filesIDS, $insert_id);
                    } else {
                        unlink($newFilePath);

                        return false;
                    }
                }
            }
        }
    }

    if (count($errors) > 0) {
        $message = '';
        foreach ($errors as $filename => $error_message) {
            $message .= $filename . ' - ' . $error_message . '<br />';
        }
        header('HTTP/1.0 400 Bad error');
        echo $message;
        die;
    }

    if (count($filesIDS) > 0) {
        return true;
    }

    return false;
}

function flexstage_ticket_statuses()
{
    return [
        [
            'id' => 'open',
            'label' => _l('flexstage_ticket_status_open'),
        ],
        [
            'id' => 'closed',
            'label' => _l('flexstage_ticket_status_closed'),
        ],
        [
            'id' => 'sold_out',
            'label' => _l('flexstage_ticket_status_sold_out'),
        ],
    ];
}

function init_flexstage_home_link()
{
    $CI = &get_instance();
    return $CI->load->view('partials/event-home-link');
}

function flexstage_system_mail_lists()
{
    return [
        ['name' => 'leads'],
        ['name' => 'clients'],
        ['name' => 'staff'],
    ];
}

function flexstage_event_invitation_cron_settings_tab()
{
    get_instance()->load->view('flexstage/settings_tab');
}

function flexstage_event_invitation_cron_settings_tab_content()
{
    get_instance()->load->view('flexstage/settings_tab_content');
}


function flexstage_create_storage_directory()
{
    flextage_create_folder(FLEXSTAGE_UPLOAD_FOLDER);
    flextage_create_folder(FLEXSTAGE_SPEAKERS_FOLDER);
    flextage_create_folder(FLEXSTAGE_IMAGES_FOLDER);
    flextage_create_folder(FLEXSTAGE_TICKETS_FOLDER);
    flextage_create_folder(FLEXSTAGE_QR_CODE_FOLDER);
}

function flextage_create_folder($folder)
{
    if (!is_dir($folder)) {
        mkdir($folder, 0777);
        $fp = fopen(rtrim($folder, '/') . '/' . 'index.html', 'w');
        fclose($fp);
    }
}

function flexstage_event_invitation_send($cronManuallyInvoked)
{
    $CI = &get_instance();
    $CI->load->library(flexstage_MODULE_NAME . '/' . 'invitations_module');
    $CI->invitations_module->send($cronManuallyInvoked);
}

/**
 * Determine if a user can access an event
 *
 * @param array $data
 * @return boolean
 */
function user_can_not_access_event(array $data)
{
    return !$data['event'] || (!$data['event']['status'] && !is_staff_logged_in());
}


function get_flexstage_client_url($event_slug, $page = '')
{
    if (isset($event_slug) && $event_slug != '') {
        $url = 'flexstage/event/';

        if (isset($page) && $page != '') {
            $url .= $page . '/';
        }

        $url .= $event_slug;

        return base_url($url);
    }

    throw new Exception("event slug can not be empty");
}

function flexstage_format_date($date)
{
    return date('D, d M Y H:i A', strtotime($date));
}

function flexstage_invoice_status_changed($data)
{
    if ($data['status'] == Invoices_model::STATUS_PAID) {
        $ticket_order = flexstage_get_ticket_order_by_invoice($data['invoice_id']);
        $event = flexstage_get_event($ticket_order['eventid']);

        if($event['auto_sync_attendees']){
            flexstage_sync_lead($ticket_order['id']);
        }

        flexstage_send_tickets_by_invoice($data['invoice_id']);
    }
}
function flexstage_send_tickets_by_invoice($invoice_id)
{
    $invoice = flexstage_get_invoice($invoice_id);

    if (flexstage_is_customer($invoice->clientid)) {
        $CI = &get_instance();
        $CI->load->library(flexstage_MODULE_NAME . '/' . 'tickets_module');

        return $CI->tickets_module->send_by_invoice($invoice_id);
    }

    return false;
}

function flexstage_send_tickets_by_ticketorder($ticket_order_id)
{
    $CI = &get_instance();
    $CI->load->library(flexstage_MODULE_NAME . '/' . 'tickets_module');

    return $CI->tickets_module->send_by_ticketorder($ticket_order_id);
}

function flexstage_get_invoice($invoice_id)
{
    $CI = &get_instance();
    $CI->load->model('invoices_model');

    return $CI->invoices_model->get($invoice_id);
}

function flexstage_get_paid_invoices()
{
    $CI = &get_instance();
    $CI->load->model('invoices_model');

    $conditions = [
        'status' => Invoices_model::STATUS_PAID
    ];

    return $CI->invoices_model->get('', $conditions);
}

function flexstage_get_paid_ticketorders($event_id)
{
    $CI = &get_instance();
    $CI->load->model('flexstage/flexticketorder_model');

    $ticket_orders = [];

    $paid_invoice_ids = implode(',', array_column(flexstage_get_paid_invoices(), 'id'));
    
    $conditions = [
        'invoiceid' => "in($paid_invoice_ids)",
        'eventid' => $event_id
    ];
    $ticket_orders = $CI->flexticketorder_model->all($conditions);
    $conditions = [
        'total_amount =' => 0,
        'eventid' => $event_id
    ];

    $ticket_orders = array_merge($ticket_orders, $CI->flexticketorder_model->all($conditions));

    return $ticket_orders;
}

function flexstage_get_paid_ticketsales($event_id)
{
    $CI = &get_instance();
    $CI->load->model('flexstage/flexticketsale_model');

    $ticket_order_ids = array_column(flexstage_get_paid_ticketorders($event_id), 'id');
    
    $conditions = [
        'eventid' => $event_id
    ];

    return $CI->flexticketsale_model->all_paid($conditions, $ticket_order_ids);
}

function flexstage_is_customer($client_id)
{
    $customer_reference_id = get_option('flexstage_customer_reference_id');
    return $client_id == $customer_reference_id;
}

function flexstage_is_paid_invoice($invoice_id)
{
    $invoice = flexstage_get_invoice($invoice_id);
    if (is_object($invoice)) {
        return $invoice->status == Invoices_model::STATUS_PAID;
    }
}

function flexstage_is_paid_ticketorder($ticket_order_id)
{
    $ticketorder = flexstage_get_ticket_order($ticket_order_id);

    if($ticketorder['invoiceid']){
        return flexstage_is_paid_invoice($ticketorder['invoiceid']);
    }

    return $ticketorder['total_amount'] == 0;
}

function flexstage_get_base_currency()
{
    $CI = &get_instance();
    $CI->load->model('currencies_model');

    return $CI->currencies_model->get_base_currency();
}

function flexstage_custom_filed_select_option($selected)
{
    echo "<option value='" . FLEXSTAGE_FIELD_TO . "' " . ($selected == 'flexstage' ? 'selected' : '') . ">" . _l('flexstage_registration_form') . "</option>";
}

function flexstage_format_venue($event_type, $event_link = '', $event_location = '')
{
    if (!$event_type) {
        return '';
    }

    switch ($event_type) {
        case 'online':
            return "<a href='$event_link'>$event_link</a>";
        case 'location-based':
            return $event_location;
        default:
            return "<a href='$event_link'>$event_link</a> OR $event_location";
    }
}

function flexstage_get_client_event_url($event_slug)
{
    return site_url('flexstage/event/' . $event_slug);
}

function flexstage_render_event_location($map_id = 'map')
{
    $CI = &get_instance();
    return $CI->load->view('client/details/location', [
        'map_id' => $map_id
    ]);
}

/**
 * Sync event attendee with leads table
 *
 * @param mixed $ticket_order_id
 * @return bool
 */
function flexstage_sync_lead($ticket_order_id)
{
    $CI = &get_instance();
    $CI->load->model('flexstage/flexticketorder_model');
    $CI->load->model('leads_model');

    $conditions = [
        'id' => $ticket_order_id
    ];

    $ticket_order = $CI->flexticketorder_model->get($conditions);
    $existing_lead = $CI->leads_model->get_lead_by_email($ticket_order['attendee_email']);

    if (!$existing_lead) {
        $data = [
            'name' => $ticket_order['attendee_name'],
            'email' => $ticket_order['attendee_email'],
            'company' => $ticket_order['attendee_company'],
            'phonenumber' => $ticket_order['attendee_mobile'],
            'source' => get_option('flexstage_lead_source'),
            'status' => get_option('flexstage_lead_status'),
            'description'=>get_option('flexstage_lead_source'),
            'address' => "",
            'city' => "",
            'assigned' => "",
        ];

        $CI->leads_model->add($data);
    }

    $data = [
        'in_leads' => 1
    ];

    return $CI->flexticketorder_model->update($ticket_order['id'], $data);
}

function flexstage_get_event($event_id){
    $CI = &get_instance();
    $CI->load->model('flexstage/flexstage_model');
    $event = $CI->flexstage_model->get_event($event_id);

    if (!$event) {
        throw new Exception(_l('flexstage_event_not_found'));
    }

    return $event;
}

function flexstage_add_to_calendar($event_id)
{
    $CI = &get_instance();
    $CI->load->model('flexstage/flexcalendarevent_model');
    $event = flexstage_get_event($event_id);

    $calendar_event_data = [
        'title' => 'Flexstage - ' . $event['name'],
        'userid' => get_staff_user_id(),
        'reminder_before' => true,
        'start' => $event['start_date'],
        'end' => $event['end_date'],
    ];

    $calendar_event = $CI->flexcalendarevent_model->get($calendar_event_data);

    if (!$calendar_event) {
        $calendar_event_data = array_merge($calendar_event_data, [
            'color' => get_option('flexstage_color')
        ]);

        return $CI->flexcalendarevent_model->add($calendar_event_data);
    }

    return false;
}

function flexstage_check_in_attendee($ticket_sale_id)
{
    $CI = &get_instance();
    $CI->load->model('flexstage/flexticketsale_model');

    $ticket_sale = $CI->flexticketsale_model->get([
        'id' => $ticket_sale_id
    ]);

    if (!$ticket_sale) {
        throw new Exception(_l('flexstage_ticket_sale_not_found'));
    }

    $data = [
        'checked_in' => ($ticket_sale['checked_in'] + 1)
    ];

    return $CI->flexticketsale_model->update($ticket_sale_id, $data);
}

/**
 * General function for PDF documents logic
 * @param  string $class  full class path
 * @param  mixed $params  params to pass in class constructor
 * @return object
 */
function flexstage_app_pdf($path, ...$params)
{
    $basename = ucfirst(basename(strbefore($path, EXT)));

    if (!endsWith($path, EXT)) {
        $path .= EXT;
    }

    // $path = hooks()->apply_filters("{$type}_pdf_class_path", $path, ...$params);

    include_once($path);

    return (new $basename(...$params))->prepare();
}

/**
 * Prepare ticketsale pdf
 * @param  int $ticketsale_id ticketsale id
 * @return mixed
 */
function ticketsale_pdf($ticketsale_id)
{
    return flexstage_app_pdf(APP_MODULES_PATH . flexstage_MODULE_NAME . '/libraries/pdf/Ticketsale_pdf', $ticketsale_id);
}

function flexstage_get_ticket_purchaser_name($ticketorder_id)
{
    $CI = &get_instance();
    $CI->load->model('flexstage/flexticketorder_model');

    $ticketorder = $CI->flexticketorder_model->get([
        'id' => $ticketorder_id
    ]);

    if (!$ticketorder) {
        throw new Exception(_l('flexstage_ticket_order_record_not_found'));
    }

    return $ticketorder['attendee_name'];
}

function flexstage_get_ticket_order($ticketorder_id)
{
    $CI = &get_instance();
    $CI->load->model('flexstage/flexticketorder_model');

    $ticketorder = $CI->flexticketorder_model->get([
        'id' => $ticketorder_id
    ]);

    if (!$ticketorder) {
        throw new Exception(_l('flexstage_ticket_order_record_not_found'));
    }

    return $ticketorder;
}
function flexstage_get_ticket_order_by_invoice($invoice_id)
{
    $CI = &get_instance();
    $CI->load->model('flexstage/flexticketorder_model');

    $ticketorder = $CI->flexticketorder_model->get([
        'invoiceid' => $invoice_id
    ]);

    if (!$ticketorder) {
        throw new Exception(_l('flexstage_ticket_order_record_not_found'));
    }

    return $ticketorder;
}

/**
 * Generate QR Code using the provided data parameter
 *
 * @param string $data
 * @return mixed
 */
function flexstage_get_qr_code($data)
{
    $options = new QROptions;

    $options->version = 7;
    $options->outputType = QROutputInterface::GDIMAGE_PNG;
    $options->scale = 20;
    $options->outputBase64 = false;
    $options->bgColor = [255, 255, 255];
    $options->imageTransparent = false;
    #$options->transparencyColor   = [233, 233, 233];
    $options->drawCircularModules = true;
    $options->drawLightModules = true;
    $options->circleRadius = 0.4;
    $options->keepAsSquare = [
        QRMatrix::M_FINDER_DARK,
        QRMatrix::M_FINDER_DOT,
        QRMatrix::M_ALIGNMENT_DARK,
    ];
    $options->moduleValues = [
            // finder
        // QRMatrix::M_FINDER_DARK => [0, 63, 255], // dark (true)
        QRMatrix::M_FINDER_DOT => [0, 63, 255], // finder dot, dark (true)
        QRMatrix::M_FINDER => [233, 233, 233], // light (false), white is the transparency color and is enabled by default
            // alignment
        QRMatrix::M_ALIGNMENT_DARK => [255, 0, 255],
        QRMatrix::M_ALIGNMENT => [233, 233, 233],
            // timing
        QRMatrix::M_TIMING_DARK => [255, 0, 0],
        QRMatrix::M_TIMING => [233, 233, 233],
            // format
        QRMatrix::M_FORMAT_DARK => [67, 159, 84],
        QRMatrix::M_FORMAT => [233, 233, 233],
            // version
        QRMatrix::M_VERSION_DARK => [62, 174, 190],
        QRMatrix::M_VERSION => [233, 233, 233],
            // data
        QRMatrix::M_DATA_DARK => [0, 0, 0],
        QRMatrix::M_DATA => [233, 233, 233],
            // darkmodule
        QRMatrix::M_DARKMODULE => [0, 0, 0],
            // separator
        QRMatrix::M_SEPARATOR => [233, 233, 233],
            // quietzone
        QRMatrix::M_QUIETZONE => [233, 233, 233],
            // logo (requires a call to QRMatrix::setLogoSpace()), see QRImageWithLogo
        QRMatrix::M_LOGO => [233, 233, 233],
    ];

    return (new QRCode($options))->render($data);
}

/**
 * Get QR Code Image
 *
 * @param string $data
 * @return string
 */
function flexstage_get_qr_image($data)
{
    $width = get_option('flexstage_qrcode_image_width');
    if (!$width) {
        $width = FLEXSTAGE_QRCODE_IMAGE_WIDTH;
    }

    $file_name = 'qr_code.png';
    $file_path = FLEXSTAGE_QR_CODE_FOLDER . $file_name;

    file_put_contents($file_path, flexstage_get_qr_code($data));

    return '<img width="' . $width . 'px" src="' . $file_path . '"/>';
}