<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Flexevent extends ClientsController
{
    public function index($slug = '')
    {
        $this->load->model('flexstage_model');

        $data['event'] = $this->flexstage_model->get_event($slug);

        $this->load->helper('flexstage/flexstage');
        $event = fs_get_event($slug);
        
        if (!$event || fs_user_can_not_access_event($event) || fs_hide_by_event_privacy($event['privacy'])) {
            show_404();
        }
        $data['event'] = $event;
        $data['images'] = fs_get_event_images($event);
        $data['tickets'] = fs_get_event_tickets($event);
        $data['socials'] = fs_get_event_socials($event);
        $data['currency'] = null;
        $data['tickets_count'] = count($data['tickets']);

        if ($data['tickets_count']) {
            $data['currency'] = $this->currencies_model->get($data['tickets'][0]['currency']);
        }
        $data['price_range'] = fs_get_price_range($data['tickets'], $data['currency']);
        $data['video'] = fs_get_event_videos($event);
        $data['speakers'] = fs_get_event_speakers($event);
        $data['social_view'] = $this->load->view('client/details/social', $data, true);
        $data['header'] = $this->load->view('client/navigation', array('menu' => fs_event_client_menus($data)), true);
        $data['footer'] = $this->load->view('client/footer', array($data), true);
        $data['description_view'] = $this->load->view('client/details/description', $data, true);
        // $data['location_view'] = ;
        $data['speaker_view'] = $this->load->view('client/details/speakers', $data, true);
        $data['media_view'] = $this->load->view('client/details/media', $data, true);
        $this->data($data);
        //if it is private or public
        //popup for pin will show if it is private
        $this->app_css->theme('flexslick-css', module_dir_url('flexstage', 'assets/css/slick.css'));
        $this->app_css->theme('flexjquerysteps-css', module_dir_url('flexstage', 'assets/third-party/jquery-steps/jquery-steps.css'));
        $this->app_css->theme('flexevent-css', module_dir_url('flexstage', 'assets/css/flexevent.css'));

        $this->disableNavigation()
            ->disableSubMenu();
        $this->title($event['name']);
        no_index_customers_area();
        $this->view('client/index');

        $this->app_scripts->theme('flexjquerysteps-js', module_dir_url('flexstage', 'assets/third-party/jquery-steps/jquery-steps.js'));
        // $this->app_scripts->theme('flexstage-js', module_dir_url('flexstage', 'assets/js/flexstage.js'));
        $this->layout();
    }

    public function tickets($slug = '')
    {
        $this->load->helper('flexstage/flexstage');
        $data['event'] = fs_get_event($slug);

        if (user_can_not_access_event($data)) {
            show_404();
        }

        $data['tickets'] = fs_get_event_tickets($data['event']);
        $data['currency'] = null;
        $data['header'] = $this->load->view('client/navigation', array('menu' => fs_event_client_menus($data)), true);
        $data['tickets_count'] = count($data['tickets']);
        $data['ticket_sales_quantity'] = [];

        if ($post = $this->input->post()) {
            $this->db->trans_begin();

            try {
                $customer_reference_id = get_option('flexstage_customer_reference_id');

                if (empty($customer_reference_id)) {
                    throw new Exception(_l('flexstage_empty_customer_reference'));
                }

                $this->load->model('payment_modes_model');
                $this->load->model('flexstage/flexticket_model');
                $this->load->model('flexstage/flexticketorder_model');
                $this->load->model('flexstage/flexticketsale_model');

                $ticket_order_data = [
                    'eventid' => $data['event']['id'],
                    'attendee_name' => $post['attendee_name'],
                    'attendee_email' => $post['attendee_email'],
                    'attendee_mobile' => array_key_exists('attendee_mobile', $post) ? $post['attendee_mobile'] : '',
                    'attendee_company' => array_key_exists('attendee_company', $post) ? $post['attendee_company'] : '',
                ];

                $ticket_order_id = $this->flexticketorder_model->add($ticket_order_data);

                if (!$ticket_order_id) {
                    throw new Exception(_l('flexstage_order_failed'));
                }

                if (isset($post['custom_fields'])) {
                    $custom_fields = $post['custom_fields'];
                    unset($post['custom_fields']);
                }

                if (isset($custom_fields)) {
                    handle_custom_fields_post($ticket_order_id, $custom_fields);
                }

                $payment_modes = $this->payment_modes_model->get();
                $ticket_items = [];
                $total_amount = 0;
                $order = 1;

                foreach ($post['tickets'] as $ticket_id => $quantity) {
                    $conditions = [
                        'id' => $ticket_id
                    ];

                    $ticket = $this->flexticket_model->get($conditions);
                    $subtotal = $quantity * $ticket['price'];

                    $ticket_sale_data = [
                        'ticketorderid' => $ticket_order_id,
                        'eventid' => $data['event']['id'],
                        'ticketid' => $ticket['id'],
                        'quantity' => $quantity,
                        'sub_total' => $subtotal,
                        'reference_code' => fs_get_ticket_reference_code()
                    ];

                    $this->flexticketsale_model->add($ticket_sale_data);

                    $total_amount += $subtotal;

                    $ticket_items[] = [
                        'description' => $ticket['name'],
                        'long_description' => '',
                        'qty' => $quantity,
                        'rate' => $ticket['price'],
                        'order' => $order,
                        'unit' => ''
                    ];
                    $order++;
                }
                
                //instead of going to the invoice page if the ticket is free, it will go to the event page
                if($total_amount == 0){
                    if($data['event']['auto_sync_attendees']){
                        flexstage_sync_lead($ticket_order_id);
                    }
                    if(flexstage_send_tickets_by_ticketorder($ticket_order_id)){
                        set_alert('success', _l('flexstage_your_registration_is_successful'));
                    }
                    $redirect_url = fs_get_event_url($data['event'], 'success');
                }else{
                    $due_after = get_option('invoice_due_after') > 0 ? get_option('invoice_due_after') : 30;

                    $client_note = _l('flexstage_client_note', $data['event']['name']) . ' <br/><a href="' . fs_get_event_url($data['event']) . '">' . _l("flexstage_go_back_event_details") . '</a>';
                    
                    $invoice_data = [
                        'allowed_payment_modes' => array_pluck($payment_modes, 'id'),
                        'currency' => $ticket['currency'],
                        'clientid' => $customer_reference_id,
                        'number' => get_option('next_invoice_number'),
                        'number_format' => get_option('invoice_number_format'),
                        'newitems' => $ticket_items,
                        'subtotal' => $total_amount,
                        'total' => $total_amount,
                        'duedate' => date('Y-m-d', strtotime('+' . $due_after . ' DAY')),
                        'billing_street' => '',
                        'billing_city' => '',
                        'billing_state' => '',
                        'billing_zip' => '',
                        'billing_country' => '',
                        'clientnote' => $client_note
                    ];
    
                    $invoice_id = $this->invoices_model->add($invoice_data);
                    
                    if(!$invoice_id){
                        throw new Exception(_l('flexstage_invoice_generation_failed'));
                    }

                    $ticket_order_data = [
                        'invoiceid' => $invoice_id,
                        'total_amount' => $total_amount
                    ];

                    $this->flexticketorder_model->update($ticket_order_id, $ticket_order_data);
                    $invoice = flexstage_get_invoice($invoice_id);
                    $redirect_url = base_url('invoice/' . $invoice->id . '/' . $invoice->hash);
                }

                $this->db->trans_commit();
                redirect($redirect_url);
            } catch (\Throwable $th) {
                $this->db->trans_rollback();
                set_alert('danger', $th->getMessage());
                redirect(fs_get_event_url($data['event'], 'tickets'));
            }


        }

        foreach($data['tickets'] as $ticket){
            $data['ticket_sales_quantity'][$ticket['id']] = fs_get_event_ticket_sales_quantity($ticket['id']);
        }

        if ($data['tickets_count']) {
            $data['currency'] = $this->currencies_model->get_base_currency();
        } else {
            redirect(fs_get_event_url($data['event']));
        }

        $this->app_css->theme('flexevent-css', module_dir_url('flexstage', 'assets/css/flexevent.css'));
        $this->app_css->theme('flexjquerysteps-css', module_dir_url('flexstage', 'assets/third-party/jquery-steps/jquery-steps.css'));

        $this->disableNavigation()
            ->disableSubMenu();

        $this->data($data);
        $this->title("Tickets for " . $data['event']['name']);
        no_index_customers_area();
        $this->view('client/event-tickets');

        $this->app_scripts->theme('flexjquerysteps-js', module_dir_url('flexstage', 'assets/third-party/jquery-steps/jquery-steps.js'));
        $this->layout();
    }

    public function success($slug = '')
    {
        $this->load->helper('flexstage/flexstage');
        $data['event'] = fs_get_event($slug);

        if (user_can_not_access_event($data)) {
            show_404();
        }
        $data['tickets'] = fs_get_event_tickets($data['event']);
        $data['header'] = $this->load->view('client/navigation', array('menu' => fs_event_client_menus($data)), true);
        $this->app_css->theme('flexevent-css', module_dir_url('flexstage', 'assets/css/flexevent.css'));

        $this->disableNavigation()
            ->disableSubMenu();

        $this->data($data);
        $this->title("Successful Registration");
        no_index_customers_area();
        $this->view('client/event-success');
        $this->layout();
    }
}
