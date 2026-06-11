<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';

use FlutexAdminApi\RestController;

class Leads extends RestController
{
    protected $staffInfo;

    public function __construct()
    {
        parent::__construct();
        register_language_files('flutex_admin_api');
        load_admin_language();
        
        $this->load->helper('flutex_admin_api');
        if (!isset(isAuthorized()['status'])) {
            $this->response(isAuthorized()['response'], isAuthorized()['response_code']);
        }

        $this->staffInfo = isAuthorized();

      /*  if (staff_cant('view', 'leads', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }*/
    }
    
    public function leads_get()
    {
        if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
        
        $leadID = $this->get('id');
        
        $this->load->model('leads_model');
        
        $leadData = $this->leads_model->get($leadID);
        
        if (!empty($leadData) && !empty($leadID)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $leadData], RestController::HTTP_OK);
        }
        
        $leads_summary = $this->leads_summary();
        
        if (!empty($leadData)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'overview' => $leads_summary, 'data' => $leadData], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }
public function checkin_post()
{
    $lead_id   = (int) $this->post('lead_id');
    $type      = $this->post('type'); // checkin | checkout
    $latitude  = $this->post('latitude');
    $longitude = $this->post('longitude');
    $accuracy  = $this->post('accuracy_m');
    $address   = $this->post('address');

    if (!$lead_id || !in_array($type, ['checkin', 'checkout'])) {
        $this->response(['message' => 'Invalid input'], RestController::HTTP_BAD_REQUEST);
    }

    // Prevent wrong order
    $last = $this->db
        ->where('lead_id', $lead_id)
        ->where('staff_id', $this->staffInfo['data']->staff_id)
        ->order_by('id', 'DESC')
        ->limit(1)
        ->get(db_prefix() . 'lead_checkins')
        ->row();

    if ($type === 'checkin' && $last && $last->type === 'checkin') {
        $this->response(['message' => 'Already checked in'], RestController::HTTP_BAD_REQUEST);
    }

    if ($type === 'checkout' && (!$last || $last->type !== 'checkin')) {
        $this->response(['message' => 'Checkout not allowed without check-in'], RestController::HTTP_BAD_REQUEST);
    }

    if (empty($address) && $latitude && $longitude) {
        $this->load->model('timesheets/timesheets_model');
        $address = $this->timesheets_model->getAddressFromLatLong($latitude, $longitude);
    }

    $data = [
        'lead_id'    => $lead_id,
        'staff_id'   => $this->staffInfo['data']->staff_id,
        'type'       => $type,
        'latitude'   => $latitude ?: null,
        'longitude'  => $longitude ?: null,
        'accuracy_m' => $accuracy ?: null,
        'address'    => $address,
        'ip_address' => $this->input->ip_address(),
        'user_agent' => $this->input->user_agent(),
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $this->db->insert(db_prefix() . 'lead_checkins', $data);

    $this->response([
        'message' => ucfirst($type) . ' saved successfully',
        'data'    => $data,
    ], RestController::HTTP_OK);
}
public function convert_post()
{
    $lead_id = (int) $this->post('lead_id');

    if (!$lead_id) {
        $this->response(['message' => 'Lead ID required'], RestController::HTTP_BAD_REQUEST);
    }

    $this->load->model(['leads_model', 'clients_model']);

    $lead = $this->leads_model->get($lead_id);
    if (!$lead) {
        $this->response(['message' => 'Lead not found'], RestController::HTTP_NOT_FOUND);
    }

    // Prevent duplicate conversion
    if (!empty($lead->date_converted)) {
        $this->response(['message' => 'Lead already converted'], RestController::HTTP_BAD_REQUEST);
    }

    $this->db->trans_start();

    $customerData = [
        'company'         => $lead->company ?: $lead->name,
        'phonenumber'     => $lead->phonenumber,
        'country'         => $lead->country,
        'city'            => $lead->city,
        'state'           => $lead->state,
        'zip'             => $lead->zip,
        'address'         => $lead->address,
        'billing_street'  => $lead->address,
        'billing_city'    => $lead->city,
        'billing_state'   => $lead->state,
        'billing_zip'     => $lead->zip,
        'billing_country' => $lead->country,
        'leadid'          => $lead_id,
        'is_primary'      => 1,
    ];

    $customer_id = $this->clients_model->add($customerData, true);

    if (!$customer_id) {
        $this->db->trans_rollback();
        $this->response(['message' => 'Customer creation failed'], RestController::HTTP_INTERNAL_ERROR);
    }

    $default_status = $this->leads_model->get_status('', ['isdefault' => 1])[0]['id'];

    $this->db->where('id', $lead_id);
    $this->db->update(db_prefix() . 'leads', [
        'status'         => $default_status,
        'date_converted' => date('Y-m-d H:i:s'),
        'junk'           => 0,
        'lost'           => 0,
    ]);

    $this->leads_model->log_lead_activity(
        $lead_id,
        'not_lead_activity_converted',
        false,
        serialize([get_staff_full_name()])
    );

    log_activity("Lead converted via API [LeadID: {$lead_id}, CustomerID: {$customer_id}]");

    hooks()->do_action('lead_converted_to_customer', [
        'lead_id'     => $lead_id,
        'customer_id' => $customer_id,
    ]);

    $this->db->trans_complete();

    $this->response([
        'message'     => 'Lead converted successfully',
        'lead_id'     => $lead_id,
        'customer_id' => $customer_id,
    ], RestController::HTTP_OK);
}

    public function leads_summary()
    {
        // Leads Overview
        $leads = [];
        $this->load->model('leads_model');
        $leads_statuses = $this->leads_model->get_status();

        foreach ($leads_statuses as $key => $status) {
            $where = 'status = ' . $status['id'];
            array_push($leads, [
                'status' => $status['name'],
                'total' => strval(total_rows(db_prefix() . 'leads', $where)),
                'percent' => total_rows(db_prefix() . 'leads', $where) == 0 ? '0' : strval(total_rows(db_prefix() . 'leads', $where) / total_rows(db_prefix() . 'leads') * 100)
            ]);
        }
        return $leads;
    }
    
    public function search_get()
    {
            
            if (!empty($this->get()) && !in_array('search', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
            }
            
            $keySearch = $this->get('search');
            
            $where = '';
            
            if ($keySearch) {
                $keySearch = trim(urldecode($keySearch));
                $keySearch = $this->db->escape_like_str($keySearch);
                $where .= '(leads.name LIKE "%' . $keySearch . '%" OR title LIKE "%' . $keySearch . '%" OR company LIKE "%' . $keySearch . '%"
                    OR zip LIKE "%' . $keySearch . '%" OR city LIKE "%' . $keySearch . '%" OR state LIKE "%' . $keySearch . '%" OR leads.address LIKE "%' . $keySearch . '%"
                    OR leads.email LIKE "%' . $keySearch . '%" OR leads.phonenumber LIKE "%' . $keySearch . '%")';
            }
            
            $this->load->model('leads_model');
            
            $leadData = $this->leads_model->get('', $where);
            
            if (!empty($leadData)) {
                $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $leadData], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
            }
    }
    
    public function leads_post()
    {
        try {
            
            $this->form_validation->set_rules('name', 'Lead Name', 'required|max_length[600]');
            $this->form_validation->set_rules('source', 'Source', 'required');
            $this->form_validation->set_rules('status', 'Status', 'required');
            
            if (!$this->form_validation->run()) {
                $this->response(['message' => strip_tags(validation_errors()),'error' => $this->form_validation->error_array()], RestController::HTTP_BAD_REQUEST);
            } else {
                $data = [
                    'name' => $this->input->post('name'),
                     'addedfrom' => $this->staffInfo['data']->staff_id,
                    'source' => $this->input->post('source'),
                    'status' => $this->input->post('status'),
                    'assigned' => $this->input->post('assigned'),
                    'lead_value' => $this->input->post('lead_value'),
                    'tags' => $this->input->post('tags')??'',
                    'title' => $this->input->post('title')??'',
                    'email' => $this->input->post('email')??'',
                    'website' => $this->input->post('website')??'',
                    'phonenumber' => $this->input->post('phonenumber')??'',
                    'company' => $this->input->post('company')??'',
                    'address' => $this->input->post('address')??'',
                    'city' => $this->input->post('city')??'',
                    'zip' => $this->input->post('zip')??'',
                    'state' => $this->input->post('state')??'',
                    'default_language' => $this->input->post('default_language')??'',
                    'description' => $this->input->post('description')??'',
                    'is_public' => $this->input->post('is_public')??''
                ];
                
                $this->load->model('leads_model');
                 $insert_id  = $this->leads_model->add($data);
                if ($insert_id ) {
            
                    $this->response(['message' => _l('lead_added_successfully'),  'data'    => [
                    'id' => $insert_id
                ]], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('lead_add_failed')], RestController::HTTP_NOT_FOUND);
                }
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function leads_put()
    {
        //try {
            
            if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
                $this->response(['message' => _l('hhhh')], RestController::HTTP_BAD_REQUEST);
            }
            
            $leadID = $this->get('id');
            $this->load->model('leads_model');
            $lead = $this->leads_model->get($leadID);
            
            if (is_object($lead)) {
                $data = array();
                parse_str(file_get_contents('php://input'), $data);
                $success = $this->leads_model->update($data, $leadID);
                if ($success) {
                    $this->response(['message' => _l('lead_updated_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('lead_update_failed')], RestController::HTTP_NOT_FOUND);
                }
            } else {
                $this->response(['message' => _l('invalid_lead_id')], RestController::HTTP_NOT_FOUND);
            }
            
        //} catch (\Throwable $th) {
        //    $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        //}
    }
    
    public function leads_delete()
    {
        
        $leadID = $this->get('id');
        
        if (staff_cant('delete', 'leads', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
        
        $this->load->model('leads_model');
        $lead = $this->leads_model->get($leadID);
        if (is_object($lead)) {
            $output = $this->leads_model->delete($leadID);
            if ($output === TRUE) {
                $this->response(['message' => _l('lead_deleted_successfully')], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('lead_delete_failed')], RestController::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(['message' => _l('invalid_lead_id')], RestController::HTTP_NOT_FOUND);
        }
    }
}