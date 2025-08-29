<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Flexstage extends AdminController
{
    public function __construct()
    {
        parent::__construct();

    }

    //add methods for an event management system
    public function index()
    {
        if (!has_permission('flexstage', '', 'view')) {
            access_denied('flexstage');
        }

        $this->load->model('flexstage/flexstage_model');

        $data['title'] = _l('flexstage');
        $data['events'] = $this->flexstage_model->get_events();
        $this->app_css->add('flexstage-css', module_dir_url('flexstage', 'assets/css/flexstage.css'), 'admin', ['app-css']);
        $this->load->view('admin/events', $data);
    }

    public function event_details($event_id = 0)
    {
        if (!has_permission('flexstage', '', 'view')) {
            access_denied('flexstage');
        }

        if (!$event_id) {
            redirect(admin_url('flexstage'));
        }

        //get the event details and populate the view
        $this->load->model('flexstage/flexstage_model');
        $this->load->helper('flexstage/flexstage');

        $data['event'] = fs_get_event($event_id);

        if (is_null($data['event'])) {
            redirect(admin_url('flexstage'));
        }

        $key = $this->input->get('key') ?? 'basic-info';
        $data['key'] = $key;
        $data['title'] = _l('flexstage_basic_info');

        switch ($key) {
            case 'tickets':
                $this->load->model('flexstage/flexticket_model');
                $this->load->model('currencies_model');
                $this->load->helper('flexstage/flexstage');

                $data['base_currency'] = flexstage_get_base_currency();

                //if form submitted
                $post = $this->input->post();
                if ($post) {
                    $this->load->library('form_validation');

                    $this->form_validation->set_rules($this->get_ticket_validation_rules());

                    if ($this->form_validation->run()) {
                        $post['sales_start_date'] = to_sql_date($post['sales_start_date'], true);
                        $post['sales_end_date'] = to_sql_date($post['sales_end_date'], true);
                        
                        if (array_key_exists('sales_start_date_now', $post)) {
                            $post['sales_start_date'] = to_sql_date(date('Y-m-d H:i:s'), true);
                            unset($post['sales_start_date_now']);
                        }
                        if (array_key_exists('sales_end_date_event', $post)) {
                            $post['sales_end_date'] = to_sql_date($data['event']['end_date'], true);
                            unset($post['sales_end_date_event']);
                        }

                        if ($post['sales_start_date'] < $post['sales_end_date']) {
                            $post['event_id'] = $data['event']['id'];

                            if (!array_key_exists('currency', $post)) {
                                $post['currency'] = $data['base_currency']->id;
                            }

                            if (array_key_exists('ticket_id', $post)) {
                                $ticket_id = $post['ticket_id'];
                                unset($post['ticket_id']);

                                $saved = $this->flexticket_model->update($ticket_id, $post);

                                if ($saved) {
                                    set_alert('success', _l('flexstage_ticket_updated_successfully'));
                                    redirect(admin_url('flexstage/event_details/' . $event_id . '?key=' . $key));
                                }
                            } else {
                                $insert_id = $this->flexticket_model->add($post);
                                if ($insert_id) {
                                    set_alert('success', _l('flexstage_ticket_added_successfully'));
                                    redirect(admin_url('flexstage/event_details/' . $event_id . '?key=' . $key));
                                }
                            }
                        } else {
                            set_alert('danger', _l('flexstage_start_date_greater_than_end_date'));
                        }
                    } else {
                        $ticket_id = array_key_exists('ticket_id', $post) ? $post['ticket_id'] : '';

                        redirect(fs_get_admin_event_details_url($data['event']['id'], $key, $ticket_id));
                    }
                }

                $data['title'] = _l('flexstage_tickets');
                $data['currencies'] = $this->currencies_model->get();
                $conditions = [
                    'event_id' => $data['event']['id']
                ];
                $data['tickets'] = $this->flexticket_model->all($conditions);

                $ticket_id = $this->input->get('ticket-id');

                if ($ticket_id) {
                    $conditions = [
                        'id' => $ticket_id
                    ];

                    $data['ticket'] = $this->flexticket_model->get($conditions);
                }

                $content = $this->load->view('admin/details/content/tickets', $data, true);
                $this->app_scripts->add('flexstage-js', module_dir_url('flexstage', 'assets/js/flexstage.js'), 'admin', ['app-js']);
                break;
            case 'media':
                $data['title'] = _l('flexstage_media');
                $this->load->model('flexstage/fleximage_model');
                $redirect_url = 'flexstage/event_details/' . $event_id . '?key=' . $key;

                $conditions = [
                    'event_id' => $data['event']['id'],
                ];

                $this->load->model('flexstage/flexvideo_model');

                $data['video'] = $this->flexvideo_model->get($conditions);

                if ($post = $this->input->post()) {

                    $post['event_id'] = $data['event']['id'];
                    if (empty($post['url'])) {
                        redirect(admin_url($redirect_url));
                    }

                    $post['url'] = fs_get_video_player_url($post['url']);

                    if (empty($data['video'])) {
                        $saved = $this->flexvideo_model->add($post);
                        if ($saved) {
                            set_alert('success', _l('flexstage_saved_successfully'));
                            redirect(admin_url($redirect_url));
                        }
                    } else {
                        $saved = $this->flexvideo_model->update($data['video']['id'], $post);
                        if ($saved) {
                            set_alert('success', _l('flexstage_saved_successfully'));
                            redirect(admin_url($redirect_url));
                        }
                    }
                }

                $data['images'] = $this->fleximage_model->all($conditions);
                $content = $this->load->view('admin/details/content/media', $data, true);
                $this->app_scripts->add('flexstage-js', module_dir_url('flexstage', 'assets/js/flexstage.js'), 'admin', ['app-js']);
                break;
            case 'attendees':
                $this->load->helper('flexstage/flexstage');

                $get = $this->input->get();

                if(array_key_exists('send-tickets', $get)){
                    if($get['send-tickets'] && flexstage_is_paid_ticketorder($get['ticketorder-id'])){
                        if(flexstage_send_tickets_by_ticketorder($get['ticketorder-id'])){
                            set_alert('success', _l('flexstage_tickets_sent'));
    
                            redirect(fs_get_admin_event_details_url($data['event']['id'], $key));
                        };
                    }
                }
                if(array_key_exists('sync-lead', $get)){
                    if($get['sync-lead'] && $get['ticketorder-id']){
                        if(flexstage_sync_lead($get['ticketorder-id'])){
                            set_alert('success', _l('flexstage_lead_synced'));
    
                            redirect(fs_get_admin_event_details_url($data['event']['id'], $key));
                        };
                    }
                }

                $data['ticket_orders'] = flexstage_get_paid_ticketorders($event_id);

                $data['currency'] = flexstage_get_base_currency();
                $data['title'] = _l('flexstage_attendees');
                $data['custom_fields'] = get_custom_fields(FLEXSTAGE_FIELD_TO, ['show_on_table' => 1]);
                $content = $this->load->view('admin/details/content/attendees', $data, true);
                break;
            case 'checkin':
                $this->load->model('flexstage/flexticketsale_model');
                $this->load->helper('flexstage/flexstage');

                $get = $this->input->get();
                
                if(array_key_exists('check-in', $get)){
                    if($get['check-in'] && $get['ticketsale-id']){
                        if(flexstage_check_in_attendee($get['ticketsale-id'])){
                            set_alert('success', _l('flexstage_attendee_checked_in'));
    
                            redirect(fs_get_admin_event_details_url($data['event']['id'], $key));
                        };
                    }
                }

                $data['ticket_sales'] = flexstage_get_paid_ticketsales($data['event']['id']);
                $data['currency'] = flexstage_get_base_currency();
                $data['title'] = _l('flexstage_checkin_attendees');
                $content = $this->load->view('admin/details/content/checkin', $data, true);
                break;
            case 'email-invitation':

                $this->load->model('leads_model');
                $this->load->model('flexstage/flexemaillist_model');

                $data['leads_statuses'] = $this->leads_model->get_status();
                $data['customers_groups'] = $this->clients_model->get_groups();
                $data['send_log'] = $this->flexemaillist_model->get_event_send_log($data['event']['id']);
                $data['send_log_count'] = $this->flexemaillist_model->get_send_log_count($data['event']['id']);
                $data['mail_lists'] = $this->flexemaillist_model->get_mail_lists();
                $data['found_custom_fields'] = false;
                $i = 0;
                foreach ($data['mail_lists'] as $mail_list) {
                    $fields = $this->flexemaillist_model->get_list_custom_fields($mail_list['listid']);
                    if (count($fields) > 0) {
                        $data['found_custom_fields'] = true;
                    }
                    $data['mail_lists'][$i]['customfields'] = $fields;
                    $i++;
                }

                if (is_gdpr() && (get_option('gdpr_enable_consent_for_contacts') == '1' || get_option('gdpr_enable_consent_for_leads') == '1')) {
                    $this->load->model('gdpr_model');
                    $data['purposes'] = $this->gdpr_model->get_consent_purposes();
                }

                $data['title'] = _l('flexstage_email_invitation');
                $content = $this->load->view('admin/details/content/email-invitation', $data, true);
                break;
            case 'social-pages':
                //if form submitted
                if ($post = $this->input->post()) {
                    $channels = $post['channels'];

                    foreach ($channels as $channel => $url) {
                        if (empty($url)) {
                            continue;
                        }

                        $this->load->model('flexstage/flexsocialchannel_model');

                        $conditions = [
                            'event_id' => $data['event']['id'],
                            'channel_id' => $channel
                        ];

                        $social_channel = $this->flexsocialchannel_model->get($conditions);

                        $post = [
                            'channel_id' => $channel,
                            'event_id' => $data['event']['id'],
                            'url' => $url
                        ];

                        if (empty($social_channel)) {
                            $saved = $this->flexsocialchannel_model->add($post);
                            if ($saved) {
                                set_alert('success', _l('flexstage_saved_successfully'));
                            }
                        } else {
                            $saved = $this->flexsocialchannel_model->update($social_channel['id'], $post);
                            if ($saved) {
                                set_alert('success', _l('flexstage_saved_successfully'));
                            }
                        }
                    }
                }
                $data['title'] = _l('flexstage_social_pages');
                $content = $this->load->view('admin/details/content/social', $data, true);
                break;
            case 'speakers':
                //if form submitted
                if ($post = $this->input->post()) {
                    $this->load->library('form_validation');

                    if (!empty($post['bio'])) {
                        $post['bio'] = html_escape($post['bio']);
                    }

                    $this->form_validation->set_rules($this->get_speaker_validation_rules());

                    if ($this->form_validation->run()) {
                        $post['event_id'] = $data['event']['id'];

                        if (save_speaker($post)) {
                            set_alert('success', _l('flexstage_speaker_saved_successfully'));
                            redirect(admin_url('flexstage/event_details/' . $event_id . '?key=' . $key));
                        }
                    }
                }

                $this->load->model('flexstage/flexspeaker_model');
                $speaker_id = $this->input->get('speaker-id');

                if ($speaker_id) {
                    $conditions = [
                        'id' => $speaker_id
                    ];

                    $data['speaker'] = $this->flexspeaker_model->get($conditions);
                }

                $data['title'] = _l('flexstage_speakers');
                $conditions = [
                    'event_id' => $data['event']['id']
                ];
                $data['speakers'] = $this->flexspeaker_model->all($conditions);
                $content = $this->load->view('admin/details/content/speakers', $data, true);
                $this->app_scripts->add('flexstage-js', module_dir_url('flexstage', 'assets/js/flexstage.js'), 'admin', ['app-js']);
                break;
            default:
                //if form submitted
                if ($post = $this->input->post()) {
                    $this->load->library('form_validation');

                    $this->form_validation->set_rules($this->get_event_validation_rules());

                    if ($this->form_validation->run()) {
                        $post['slug'] = slug_it($post['name']);

                        $post = array_merge($post, [
                            'start_date' => to_sql_date($post['start_date'], true),
                            'end_date' => to_sql_date($post['start_date'], true),
                        ]);

                        $updated = $this->flexstage_model->update_event($event_id, $post);
                        if ($updated) {
                            set_alert('success', _l('flexstage_event_updated_successfully'));
                            if($post['auto_add_to_calendar']){
                                flexstage_add_to_calendar($event_id);
                            }
                            redirect(admin_url('flexstage'));
                        }
                    }
                }

                $this->load->model('flexstage/flexcategory_model');

                $data['categories'] = $this->flexcategory_model->all();

                $content = $this->load->view('admin/details/content/basic-info', $data, true);
                $this->app_scripts->add('flexstage-js', module_dir_url('flexstage', 'assets/js/flexstage.js'), 'admin', ['app-js']);
                break;

        }
        $data['content'] = $content;
        $this->load->view('admin/details/index', $data);
    }

    /* Send invitation to mail list */
    public function send_invitation($event_id)
    {
        if (!has_permission('flexstage', '', 'edit')) {
            access_denied('flexstage');
        }
        if (!$event_id) {
            redirect(admin_url('flexstage'));
        }

        $this->load->model('flexstage/flexemaillist_model');
        $event_key = $this->input->get('key');

        $_lists = [];
        $_all_emails = [];
        if ($this->input->post('send_invitation_to')) {
            $lists = $this->input->post('send_invitation_to');
            foreach ($lists as $key => $val) {
                // is mail list
                if (is_int($key)) {
                    $list = $this->flexemaillist_model->get_mail_lists($key);
                    $emails = $this->flexemaillist_model->get_mail_list_emails($key);
                    foreach ($emails as $email) {
                        // We don't need to validate emails becuase email are already validated when added to mail list
                        array_push($_all_emails, [
                            'listid' => $key,
                            'emailid' => $email['emailid'],
                            'email' => $email['email'],
                        ]);
                    }

                    if (count($emails) > 0) {
                        array_push($_lists, $list->name);
                    }
                } else {
                    if ($key == 'staff') {
                        // Pass second paramter to get all active staff, we don't need inactive staff
                        // If you want adjustments feel free to pass 0 or '' for all
                        $staff = $this->staff_model->get('', ['active' => 1]);
                        foreach ($staff as $email) {
                            array_push($_all_emails, $email['email']);
                        }
                        if (count($staff) > 0) {
                            array_push($_lists, 'survey_send_mail_list_staff');
                        }
                    } elseif ($key == 'clients') {
                        $whereConsent = '';
                        $where = 'active=1';

                        if ($this->input->post('contacts_consent') && is_array($this->input->post('contacts_consent'))) {
                            $consents = array_map(function ($attr) {
                                return get_instance()->db->escape_str($attr);
                            }, $this->input->post('contacts_consent'));

                            $whereConsent = ' AND ' . db_prefix() . 'contacts.id IN (SELECT contact_id FROM ' . db_prefix() . 'consents WHERE purpose_id IN (' . implode(',', $consents) . ') and action="opt-in" AND date IN (SELECT MAX(date) FROM ' . db_prefix() . 'consents WHERE purpose_id IN (' . implode(', ', $consents) . ') AND contact_id=' . db_prefix() . 'contacts.id))';
                        }
                        if ($this->input->post('ml_customers_all')) {
                            $where .= $whereConsent;
                            $clients = $this->clients_model->get_contacts('', $where);

                            foreach ($clients as $email) {
                                $added = true;
                                array_push($_all_emails, $email['email']);
                            }
                        } else {
                            foreach ($this->input->post('customer_group') as $group_id => $val) {
                                $clients = $this->clients_model->get_contacts('', 'active=1 AND userid IN (select customer_id from ' . db_prefix() . 'customer_groups where groupid =' . $this->db->escape_str($group_id) . ')' . $whereConsent);
                                foreach ($clients as $email) {
                                    $added = true;
                                    array_push($_all_emails, $email['email']);
                                }
                            }
                            $_all_emails = array_unique($_all_emails, SORT_REGULAR);
                        }

                        if (isset($added) > 0) {
                            array_push($_lists, 'survey_send_mail_list_clients');
                        }
                    } elseif ($key == 'leads') {
                        $this->load->model('leads_model');
                        $whereConsent = '';
                        if ($this->input->post('leads_status')) {
                            if ($this->input->post('leads_consent') && is_array($this->input->post('leads_consent'))) {
                                $consents = array_map(function ($attr) {
                                    return get_instance()->db->escape_str($attr);
                                }, $this->input->post('leads_consent'));

                                $whereConsent = ' AND ' . db_prefix() . 'leads.id IN (SELECT lead_id FROM ' . db_prefix() . 'consents WHERE purpose_id IN (' . implode(',', $consents) . ') and action="opt-in" AND date IN (SELECT MAX(date) FROM ' . db_prefix() . 'consents WHERE purpose_id IN (' . implode(', ', $consents) . ') AND lead_id=' . db_prefix() . 'leads.id))';
                            }

                            $statuses = [];
                            foreach ($this->input->post('leads_status') as $status_id => $val) {
                                array_push($statuses, $this->db->escape_str($status_id));
                            }

                            $where = 'status IN (' . implode(',', $statuses) . ')' . $whereConsent;

                            $leads = $this->leads_model->get('', $where);
                            foreach ($leads as $lead) {
                                $added = true;
                                if (!empty($lead['email']) && filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) {
                                    array_push($_all_emails, $lead['email']);
                                }
                            }
                            if (isset($added)) {
                                array_push($_lists, _l('leads'));
                            }
                        } elseif ($this->input->post('leads_all')) {
                            $where = 'lost=0' . $whereConsent;
                            $leads = $this->leads_model->get('', $where);
                            foreach ($leads as $lead) {
                                if (!empty($lead['email']) && filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) {
                                    array_push($_all_emails, $lead['email']);
                                }
                            }
                            if (count($leads)) {
                                array_push($_lists, 'leads');
                            }
                        }
                    }
                }
            }
        } else {
            set_alert('warning', _l('flexstage_no_mail_lists_selected'));
            redirect(admin_url('flexstage/event_details/' . $event_id . '?key=' . $event_key));
        }

        // We don't need to include in query CRON if 0 emails found
        $iscronfinished = 0;
        if (count($_all_emails) == 0) {
            $iscronfinished = 1;
        }
        $log_id = $this->flexemaillist_model->init_invitation_send_log($event_id, $iscronfinished, $_lists);

        foreach ($_all_emails as $email) {
            // Is not from email lists
            if (!is_array($email)) {
                $this->flexemaillist_model->init_invitation_send_cron([
                    'email' => $email,
                    'eventid' => $event_id,
                    'log_id' => $log_id,
                ]);
            } else {
                // Yay its a mail list
                // We will need this info for the custom fields when sending the survey
                //  $this->db->insert(db_prefix() . 'surveysemailsendcron', );
                $this->flexemaillist_model->init_invitation_send_cron([
                    'email' => $email['email'],
                    'eventid' => $event_id,
                    'listid' => $email['listid'],
                    'emailid' => $email['emailid'],
                    'log_id' => $log_id,
                ]);
            }
        }

        $total = count($_all_emails);
        if ($total > 0) {
            set_alert('success', _l('flexstage_send_success_note', $total));
        } else {
            set_alert('warning', 'flexstage_no_emails_failure_note');
        }
        ;
        redirect(admin_url('flexstage/event_details/' . $event_id . '?key=' . $event_key));
    }

    public function add_event()
    {
        if (!has_permission('flexstage', '', 'create')) {
            access_denied('flexstage');
        }

        $this->load->model('flexstage/flexstage_model');
        $this->load->model('flexstage/flexcategory_model');

        //if form submitted
        if ($data = $this->input->post()) {
            $this->load->library('form_validation');

            $this->form_validation->set_rules($this->get_event_validation_rules());

            if ($this->form_validation->run()) {
                $data['created_by'] = get_staff_user_id();
                $data['create_date'] = to_sql_date(date('Y-m-d H:i:s'), true);
                $data['slug'] = slug_it($data['name']);

                $insert_id = $this->flexstage_model->add_event($data);
                if ($insert_id) {
                    set_alert('success', _l('flexstage_event_added_successfully'));

                    if($data['auto_add_to_calendar']){
                        flexstage_add_to_calendar($insert_id);
                    }
                    
                    redirect(admin_url('flexstage'));
                }
            }
        }
        $data['title'] = _l('flexstage_create_event');
        $data['categories'] = $this->flexcategory_model->all();
        $this->app_scripts->add('flexstage-js', module_dir_url('flexstage', 'assets/js/flexstage.js'), 'admin', ['app-js']);
        $this->load->view('admin/event-form', $data);
    }

    public function categories()
    {
        if (!has_permission('flexstage', '', 'view')) {
            access_denied('flexstage');
        }
        $this->load->model('flexstage/flexcategory_model');
        $data['categories'] = $this->flexcategory_model->all();
        $data['title'] = _l('flexstage_categories');
        $this->load->view('admin/categories', $data);
    }

    public function category($id = '')
    {
        if (!has_permission('flexstage', '', 'view')) {
            access_denied('flexstage');
        }
        if ($this->input->post()) {
            $post_data = $this->input->post();

            $this->load->model('flexstage/flexcategory_model');
            if (!$this->input->post('id')) {
                if (!has_permission('flexstage', '', 'create')) {
                    access_denied('flexstage');
                }

                $post_data['slug'] = slug_it($post_data['name']);
                $id = $this->flexcategory_model->add($post_data);

                if ($id) {
                    set_alert('success', _l('flexstage_category_added_successfully'));
                } else {
                    echo json_encode([
                        'id' => $id,
                        'success' => $id ? true : false,
                        'name' => $post_data['name'],
                    ]);
                }
            } else {
                if (!has_permission('flexstage', '', 'edit')) {
                    access_denied('flexstage');
                }

                $id = $post_data['id'];
                unset($post_data['id']);
                $post_data['slug'] = slug_it($post_data['name']);
                $success = $this->flexcategory_model->update($id, $post_data);

                if ($success) {
                    set_alert('success', _l('flexstage_category_updated_successfully'));
                }
            }
        }
    }

    public function delete_category($category_id = 0)
    {
        if (!has_permission('flexstage', '', 'delete')) {
            access_denied('flexstage');
        }

        if (!$category_id) {
            redirect(admin_url('flexstage/categories'));
        }

        $this->db->trans_begin();

        try {
            $this->load->model('flexstage/flexstage_model');
            $this->load->model('flexstage/flexcategory_model');

            $this->flexstage_model->delete_by_category($category_id);
            $response = $this->flexcategory_model->delete($category_id);

            $this->set_delete_alert($response, 'flexstage_category_deleted_successfully');

            $this->db->trans_commit();
            redirect(admin_url('flexstage/categories'));

        } catch (\Throwable $th) {
            $this->db->trans_rollback();
            throw $th;
        }
    }

    public function delete_event($event_id = 0)
    {
        if (!has_permission('flexstage', '', 'delete')) {
            access_denied('flexstage');
        }

        if (!$event_id) {
            redirect(admin_url('flexstage'));
        }

        try {
            $this->load->model('flexstage/flexstage_model');
            $this->load->model('flexstage/flexsocialchannel_model');
            $this->load->model('flexstage/fleximage_model');
            $this->load->model('flexstage/flexvideo_model');

            $conditions = [
                'event_id' => $event_id
            ];

            $this->flexsocialchannel_model->delete($conditions);

            $images = $this->fleximage_model->all($conditions);

            foreach ($images as $image) {
                $this->fleximage_model->remove_file($image['id']);
            }

            $this->flexvideo_model->delete($conditions);
            $response = $this->flexstage_model->delete_event($event_id);

            $this->set_delete_alert($response, 'flexstage_event_deleted_successfully');

            $this->db->trans_commit();
            redirect(admin_url('flexstage'));

        } catch (\Throwable $th) {
            $this->db->trans_rollback();
            throw $th;
        }
    }

    public function upload_image($event_id)
    {
        $this->load->model('flexstage/fleximage_model');

        $conditions = [
            'event_id' => $event_id
        ];

        $images_count = count($this->fleximage_model->all($conditions));
        $limit = FLEXSTAGE_MAX_IMAGES - $images_count;

        if ($limit <= 0) {
            $key = $this->input->get('key');

            redirect(admin_url('flexstage/event_details/' . $event_id . '?key=' . $key));
        }

        handle_event_image_uploads($event_id, $limit);
    }

    public function remove_image($event_id, $image_id)
    {
        $key = $this->input->get('key');
        $this->load->model('flexstage/fleximage_model');
        $this->fleximage_model->remove_file($image_id);
        redirect(admin_url('flexstage/event_details/' . $event_id . '?key=' . $key));
    }

    public function remove_ticket($event_id, $ticket_id)
    {
        $key = $this->input->get('key');
        $this->load->model('flexstage/flexticket_model');
        $conditions = [
            'id' => $ticket_id
        ];

        if ($this->flexticket_model->delete($conditions)) {
            set_alert('success', _l('flexstage_ticket_deleted_successfully'));
            ;
        }
        redirect(admin_url('flexstage/event_details/' . $event_id . '?key=' . $key));
    }

    public function remove_speaker($event_id, $speaker_id)
    {
        $key = $this->input->get('key');
        $this->load->model('flexstage/flexspeaker_model');

        $this->flexspeaker_model->remove_image($speaker_id);
        $conditions = [
            'id' => $speaker_id
        ];

        if ($this->flexspeaker_model->delete($conditions)) {
            set_alert('success', _l('flexstage_speaker_deleted_successfully'));
            ;
        }
        redirect(admin_url('flexstage/event_details/' . $event_id . '?key=' . $key));
    }

    public function change_event_status($event_id, $status)
    {
        if (has_permission('flexstage', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->load->model('flexstage/flexstage_model');

                $this->flexstage_model->change_status($event_id, $status);
            }
        }

    }
    
    public function change_auto_sync_attendees($event_id, $status)
    {
        if (has_permission('flexstage', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->load->model('flexstage/flexstage_model');

                $this->flexstage_model->change_auto_sync_attendees($event_id, $status);
            }
        }

    }

    public function mail_lists()
    {
        if (!has_permission('flexstage', '', 'view')) {
            access_denied('flexstage');
        }

        $this->load->model('flexstage/flexemaillist_model');
        $data['title'] = _l('flexstage_mail_lists');
        $data['email_lists'] = $this->flexemaillist_model->all();
        $data['email_lists'] = array_merge($data['email_lists'], flexstage_system_mail_lists());

        $this->load->view('admin/mail_lists/manage', $data);
    }

    /* Add or update mail list */
    public function mail_list($id = '')
    {
        $this->load->model('flexstage/flexemaillist_model');
        $post = $this->input->post();
        if ($post) {
            if ($id == '') {
                if (!has_permission('flexstage', '', 'create')) {
                    access_denied('flexstage');
                }
                $id = $this->flexemaillist_model->add_mail_list($post);
                if ($id) {
                    set_alert('success', _l('flexstage_saved_successfully'));
                    redirect(admin_url('flexstage/mail_list/' . $id));
                }
            } else {
                if (!has_permission('flexstage', '', 'edit')) {
                    access_denied('flexstage');
                }
                $success = $this->flexemaillist_model->update_mail_list($post, $id);
                if ($success) {
                    set_alert('success', _l('flexstage_saved_successfully'));
                }
                redirect(admin_url('flexstage/mail_list/' . $id));
            }
        }
        if ($id == '') {
            $title = _l('flexstage_add_new_mail_list');
        } else {
            $list = $this->flexemaillist_model->get_mail_lists($id);
            $data['list'] = $list;
            $data['custom_fields'] = $this->flexemaillist_model->get_list_custom_fields($list->listid);
            $title = _l('flexstage_edit') . ' ' . $list->name;
        }
        $data['title'] = $title;
        $this->load->view('admin/mail_lists/list', $data);
    }

    /* View mail list all added emails */
    public function mail_list_view($id = 0)
    {
        if (!has_permission('flexstage', '', 'view')) {
            access_denied('flexstage');
        }
        if (!$id) {
            redirect(admin_url('flexstage/mail_lists'));
        }

        $this->load->model('flexstage/flexemaillist_model');
        $data = [];
        $data['id'] = $id;
        if (is_numeric($id)) {
            $data['custom_fields'] = $this->flexemaillist_model->get_list_custom_fields($id);
        }
        // var_dump($data);
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('flexstage', 'admin/tables/mail_list_view'), [
                'id' => $id,
                'data' => $data,
            ]);
        }
        if ($id == 'staff' || $id == 'clients' || $id == 'leads') {
            $list = new stdClass();
            $title = _l('flexstage_clients_mail_lists');
            if ($id == 'clients') {
                if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') {
                    $this->load->model('gdpr_model');
                    $data['consent_purposes'] = $this->gdpr_model->get_consent_purposes();
                }

                $emails = $this->clients_model->get_contacts();
                $data['groups'] = $this->clients_model->get_groups();
            } elseif ($id == 'staff') {
                $title = _l('flexstage_staff_mail_lists');

                $emails = $this->staff_model->get();
            } elseif ($id == 'leads') {
                $title = _l('flexstage_leads_label');
                if (is_gdpr() && get_option('gdpr_enable_consent_for_leads') == '1') {
                    $this->load->model('gdpr_model');
                    $data['consent_purposes'] = $this->gdpr_model->get_consent_purposes();
                }
                $this->load->model('leads_model');

                $data['statuses'] = $this->leads_model->get_status();
                $data['sources'] = $this->leads_model->get_source();

                $emails = $this->leads_model->get('', ['lost' => 0]);
            }
            $list->emails = [];
            $i = 0;
            foreach ($emails as $email) {
                if (empty($email['email'])) {
                    continue;
                }
                if ($id == 'leads') {
                    $list->emails[$i]['dateadded'] = to_sql_date($email['dateadded'], true);
                } else {
                    $list->emails[$i]['dateadded'] = to_sql_date($email['datecreated']);
                }
                $list->emails[$i]['email'] = $email['email'];
                $i++;
            }
            $data['list'] = $list;
            $data['title'] = $title;
            $fixed_list = true;
        } else {
            $list = $this->flexemaillist_model->get_data_for_view_list($id);
            $data['title'] = $list->name;
            $data['list'] = $list;
            $fixed_list = false;
        }
        $data['fixedlist'] = $fixed_list;
        $this->load->view('admin/mail_lists/list_view', $data);
    }

    /* Delete mail list from database */
    public function delete_mail_list($id)
    {
        if (!has_permission('flexstage', '', 'delete')) {
            access_denied('flexstage');
        }
        $this->load->model('flexstage/flexemaillist_model');
        if (!$id) {
            redirect(admin_url('flexstage/mail_lists'));
        }
        $success = $this->flexemaillist_model->delete_mail_list($id);
        if ($success) {
            set_alert('success', _l('flexstage_deleted_successfully'));
        }
        redirect(admin_url('flexstage/mail_lists'));
    }

    /* Add single email to mail list / ajax*/
    public function add_email_to_list()
    {
        if (!has_permission('flexstage', '', 'create')) {
            echo json_encode([
                'success' => false,
                'error_message' => _l('access_denied'),
            ]);
            die();
        }
        if ($this->input->post()) {
            if ($this->input->is_ajax_request()) {
                $this->load->model('flexstage/flexemaillist_model');
                echo json_encode($this->flexemaillist_model->add_email_to_list($this->input->post()));
                die();
            }
        }
    }

    /* Remove single email from mail list / ajax */
    public function remove_email_from_mail_list($emailid)
    {
        if (!has_permission('flexstage', '', 'delete')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied'),
            ]);
            die();
        }
        if (!$emailid) {
            echo json_encode([
                'success' => false,
            ]);
            die();
        }
        $this->load->model('flexstage/flexemaillist_model');
        echo json_encode($this->flexemaillist_model->remove_email_from_mail_list($emailid));
        die();
    }

    /* Import .xls file with emails */
    public function import_emails()
    {
        if (!has_permission('surveys', '', 'create')) {
            access_denied('surveys');
        }

        $this->load->model('flexstage/flexemaillist_model');

        // Using composer
        // require_once(APPPATH . 'third_party/Excel_reader/php-excel-reader/excel_reader2.php');
        // require_once(APPPATH . 'third_party/Excel_reader/SpreadsheetReader.php');

        $filename = uniqid() . '_' . $_FILES['file_xls']['name'];
        $temp_url = TEMP_FOLDER . $filename;
        if (move_uploaded_file($_FILES['file_xls']['tmp_name'], $temp_url)) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($temp_url);
            } catch (Exception $e) {
                die('Error loading file "' . pathinfo($temp_url, PATHINFO_BASENAME) . '": ' . $e->getMessage());
            }
            $total_duplicate_emails = 0;
            $total_invalid_address = 0;
            $total_added_emails = 0;
            $mails_failed_to_insert = 0;
            $listid = $this->input->post('listid');

            foreach ($spreadsheet->getActiveSheet()->toArray() as $email) {
                if (isset($email[0]) && $email[0] !== '') {
                    $data['email'] = $email[0];
                    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $total_invalid_address++;

                        continue;
                    }
                    $data['listid'] = $listid;
                    if (count($email) > 1) {
                        $custom_fields = $this->flexemaillist_model->get_list_custom_fields($listid);
                        $total_custom_fields = count($custom_fields);
                        for ($i = 0; $i < $total_custom_fields; $i++) {
                            if ($email[$i + 1] !== '') {
                                $data['customfields'][$custom_fields[$i]['customfieldid']] = $email[$i + 1] ?? '';
                            }
                        }
                    }
                    $success = $this->flexemaillist_model->add_email_to_list($data);
                    if ($success['success'] == false && $success['duplicate'] == true) {
                        $total_duplicate_emails++;
                    } elseif ($success['success'] == false) {
                        $mails_failed_to_insert++;
                    } else {
                        $total_added_emails++;
                    }
                }
                if ($total_added_emails > 0 && $mails_failed_to_insert == 0) {
                    $_alert_type = 'success';
                } elseif ($total_added_emails == 0 && $mails_failed_to_insert > 0) {
                    $_alert_type = 'danger';
                } elseif ($total_added_emails > 0 && $mails_failed_to_insert > 0) {
                    $_alert_type = 'warning';
                } else {
                    $_alert_type = 'success';
                }
            }
            // Delete uploaded file
            unlink($temp_url);
            set_alert($_alert_type, _l('flexstage_mail_list_total_imported', $total_added_emails) . '<br />' . _l('flexstage_mail_list_total_duplicate', $total_duplicate_emails) . '<br />' . _l('flexstage_mail_list_total_failed_to_insert', $mails_failed_to_insert) . '<br />' . _l('flexstage_mail_list_total_invalid', $total_invalid_address));
        } else {
            set_alert('danger', _l('flexstage_error_uploading_file'));
        }
        redirect(admin_url('flexstage/mail_list_view/' . $listid));
    }

    protected function get_event_validation_rules()
    {
        $privacyList = implode(
            ',',
            array_map(
                function ($element) {
                    return $element['id'];
                },
                flexstage_event_privacy()
            )
        );
        $typeList = implode(
            ',',
            array_map(
                function ($element) {
                    return $element['id'];
                },
                flexstage_event_types()
            )
        );

        $end_date = $this->input->post('end_date') ?? '';

        return [
            [
                'field' => 'name',
                'label' => _l('flexstage_event_name'),
                'rules' => 'required'
            ],
            [
                'field' => 'category_id',
                'label' => _l('flexstage_event_category'),
                'rules' => 'required'
            ],
            [
                'field' => 'start_date',
                'label' => _l('flexstage_event_start_date'),
                'rules' => 'required',
            ],
            [
                'field' => 'end_date',
                'label' => _l('flexstage_event_end_date'),
                'rules' => 'required'
            ],
            [
                'field' => 'privacy',
                'label' => _l('flexstage_event_privacy'),
                'rules' => "in_list[$privacyList]"
            ],
            [
                'field' => 'type',
                'label' => _l('flexstage_event_type'),
                'rules' => "in_list[$typeList]"
            ],
            [
                'field' => 'event_link',
                'label' => _l('flexstage_event_link'),
                'rules' => "valid_url"
            ],
            [
                'field' => 'start_date',
                'label' => _l('flexstage_event_start_date'),
                'rules' => "callback_date_check[$end_date]"
            ],
        ];
    }

    protected function set_delete_alert($response, $lang_line)
    {
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('danger', _l('is_referenced'));
        } elseif ($response == true) {
            set_alert('success', _l($lang_line));
        } else {
            set_alert('warning', _l('problem_deleting'));
        }
    }

    public function date_check($start_date, $end_date)
    {
        if ($start_date > $end_date) {
            $this->form_validation->set_message('date_check', _l('flexstage_start_date_greater_than_end_date'));

            return false;
        }

        return true;
    }

    protected function get_ticket_validation_rules()
    {
        $rules = [
            [
                'field' => 'name',
                'label' => _l('flexstage_ticket_name'),
                'rules' => 'required|alpha_numeric_spaces'
            ],
            [
                'field' => 'quantity',
                'label' => _l('flexstage_ticket_quantity'),
                'rules' => 'required|greater_than_equal_to[1]'
            ],
            [
                'field' => 'min_buying_limit',
                'label' => _l('flexstage_ticket_min_buying_limit'),
                'rules' => 'greater_than_equal_to[1]'
            ]
        ];

        $post = $this->input->post();
        $min_buying_limit = $post['min_buying_limit'] ?? '';

        if ($min_buying_limit) {
            $rules[] = [
                'field' => 'max_buying_limit',
                'label' => _l('flexstage_ticket_max_buying_limit'),
                'rules' => 'greater_than_equal_to[' . $min_buying_limit . ']'
            ];
        }

        if (!array_key_exists('sales_start_date_now', $post)) {
            $rules[] = [
                'field' => 'sales_start_date',
                'label' => _l('flexstage_ticket_sales_start_date'),
                'rules' => 'required'
            ];
        }
        if (!array_key_exists('sales_end_date_event', $post)) {
            $rules[] = [
                'field' => 'sales_end_date',
                'label' => _l('flexstage_ticket_sales_end_date'),
                'rules' => 'required'
            ];
        }

        return $rules;
    }

    protected function get_speaker_validation_rules()
    {
        return [
            [
                'field' => 'name',
                'label' => _l('flexstage_name_label'),
                'rules' => 'required|alpha_numeric_spaces'
            ],
            [
                'field' => 'email',
                'label' => _l('flexstage_email_label'),
                'rules' => 'valid_email'
            ]
        ];
    }
}