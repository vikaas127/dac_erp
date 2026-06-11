<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__ . '/RestController.php';

use FlutexAdminApi\RestController;

class Customers extends RestController
{
    protected $staffInfo;

    public function __construct()
    {
        parent::__construct();
        register_language_files('flutex_admin_api');
        load_admin_language();

        $this->load->helper('flutex_admin_api');
        $this->load->helper('admin');
        $this->load->helper('distribution_channel/dc_schema');
        if (!function_exists('dc_ensure_mobile_api_schema')) {
            $schemaHelper = module_dir_path('distribution_channel', 'helpers/dc_schema_helper.php');
            if (is_file($schemaHelper)) {
                require_once $schemaHelper;
            }
        }
        try {
            dc_ensure_mobile_api_schema();
        } catch (\Throwable $e) {
            log_message('error', '[Customers][schema] '.$e->getMessage());
        }
        if (!isset(isAuthorized()['status'])) {
            $this->response(isAuthorized()['response'], isAuthorized()['response_code']);
        }

        $this->staffInfo = isAuthorized();
    }

    public function customers_get()
    {
        try {
            $customerID = $this->get('id');
            $staff_id   = $this->auth_staff_id();

            $this->load->model('clients_model');

            if (!empty($customerID)) {
                $customerData = $this->clients_model->get($customerID);
                if (!empty($customerData)) {
                    if (!$this->customer_in_permission_scope($staff_id, (int) $customerID)) {
                        $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);

                        return;
                    }
                    $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $customerData], RestController::HTTP_OK);
                }
                $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);

                return;
            }

            $filters = [
                'search' => trim((string) ($this->get('search') ?: '')),
            ];
            $limit  = min(500, max(1, (int) ($this->get('limit') ?: 100)));
            $page   = max(1, (int) ($this->get('page') ?: 1));
            $offset = ($page - 1) * $limit;

            $this->apply_customers_list_filters($staff_id, $filters);
            $total = (int) $this->db->count_all_results();

            $this->apply_customers_list_filters($staff_id, $filters);
            $this->db->select($this->customers_list_select(), false);
            $this->db->order_by('c.company', 'ASC');
            $this->db->limit($limit, $offset);
            $rows = $this->db->get()->result_array();

            $meta = [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'scope' => $this->staff_can_view_all_customers($staff_id) ? 'all' : 'own',
            ];

            $this->response([
                'message' => _l('data_retrieved_successfully'),
                'meta'    => $meta,
                'data'    => $rows,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', '[Customers][customers_get] '.$th->getMessage().' @ '.$th->getFile().':'.$th->getLine());
            $this->response(['status' => false, 'message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    private function auth_staff_id()
    {
        $data = $this->staffInfo['data'] ?? null;
        if (!$data) {
            return 0;
        }

        return (int) ($data->staff_id ?? $data->staffid ?? 0);
    }

    /**
     * Mobile customers API defaults to "own" scope.
     * Only admins or staff with global customers "view" see every customer.
     */
    private function staff_can_view_all_customers($staff_id)
    {
        $staff_id = (int) $staff_id;

        if ($staff_id <= 0) {
            return false;
        }

        if (function_exists('is_admin') && is_admin($staff_id)) {
            return true;
        }

        return has_permission('customers', $staff_id, 'view');
    }

    private function apply_customers_permission_scope($staff_id, $alias = 'c')
    {
        if ($this->staff_can_view_all_customers($staff_id)) {
            return;
        }

        $staff_id    = (int) $staff_id;
        $clientTable = db_prefix().'clients';

        $this->db->group_start();
        $this->db->where(
            $alias.'.userid IN (SELECT customer_id FROM '.db_prefix().'customer_admins WHERE staff_id='.$staff_id.')',
            null,
            false
        );

        if ($this->db->field_exists('addedfrom', $clientTable)) {
            $this->db->or_where($alias.'.addedfrom', $staff_id);
        }

        $this->db->group_end();
    }

    private function customer_in_permission_scope($staff_id, $client_id)
    {
        if ($this->staff_can_view_all_customers($staff_id)) {
            return true;
        }

        $staff_id  = (int) $staff_id;
        $client_id = (int) $client_id;

        if (total_rows(db_prefix().'customer_admins', [
            'customer_id' => $client_id,
            'staff_id'    => $staff_id,
        ]) > 0) {
            return true;
        }

        if ($this->db->field_exists('addedfrom', db_prefix().'clients')) {
            return total_rows(db_prefix().'clients', [
                'userid'    => $client_id,
                'addedfrom' => $staff_id,
            ]) > 0;
        }

        return false;
    }

    private function customers_list_select()
    {
        $hasTerritorySchema = dc_has_territory_schema();
        $select             = '
            c.userid,
            c.company,
            c.phonenumber,
            '.dc_client_column_select('c', 'city', 'billing_city', 'city').',
            '.dc_client_column_select('c', 'state', 'billing_state', 'state').',
            '.dc_client_address_select('c').'
        ';
        if ($this->db->field_exists('active', db_prefix().'clients')) {
            $select .= ', c.active';
        }
        $select .= '
        ';

        if ($hasTerritorySchema) {
            $select .= ',
            c.territory_id,
            c.sales_area_id,
            t.name AS territory_name,
            a.name AS sales_area_name';
        }

        if ($this->db->field_exists('parent_partner_id', db_prefix().'clients')) {
            $select .= ', c.parent_partner_id';
        }

        return $select;
    }

    private function apply_customers_list_filters($staff_id, array $filters)
    {
        $hasTerritorySchema = dc_has_territory_schema();

        $this->db->from(db_prefix().'clients c');
        if ($hasTerritorySchema) {
            $this->db->join(db_prefix().'territories t', 't.id = c.territory_id', 'left');
            $this->db->join(db_prefix().'sales_area a', 'a.id = c.sales_area_id', 'left');
        }
        if ($this->db->field_exists('active', db_prefix().'clients')) {
            $this->db->where('c.active', 1);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $clientTable = db_prefix().'clients';
            $cityColumn  = $this->db->field_exists('city', $clientTable) ? 'c.city' : 'c.billing_city';
            $stateColumn = $this->db->field_exists('state', $clientTable) ? 'c.state' : 'c.billing_state';
            $this->db->group_start();
            $this->db->like('c.company', $search);
            $this->db->or_like('c.phonenumber', $search);
            if ($this->db->field_exists(str_replace('c.', '', $cityColumn), $clientTable)) {
                $this->db->or_like($cityColumn, $search);
            }
            if ($this->db->field_exists(str_replace('c.', '', $stateColumn), $clientTable)) {
                $this->db->or_like($stateColumn, $search);
            }
            $this->db->group_end();
        }

        $this->apply_customers_permission_scope($staff_id, 'c');
    }
    

    public function customers_summary()
    {
        return [
            'customers_total' => total_rows(db_prefix() . 'clients'),
            'customers_active' => total_rows(db_prefix() . 'clients', 'active=1'),
            'customers_inactive' => total_rows(db_prefix() . 'clients', 'active=0'),
            'contacts_active' => total_rows(db_prefix() . 'contacts', 'active=1'),
            'contacts_inactive' => total_rows(db_prefix() . 'contacts', 'active=0'),
            'contacts_last_login' => total_rows(db_prefix() . 'contacts', 'last_login LIKE "' . date('Y-m-d') . '%"')
        ];
    }

    public function search_get()
    {
        try {

            if (!empty($this->get()) && !in_array('search', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_BAD_REQUEST);
            }

            $keySearch = $this->get('search');
            $staff_id  = (int) $this->staffInfo['data']->staff_id;
            $hasTerritorySchema = dc_has_territory_schema();

            $citySelect  = dc_client_column_select('c', 'city', 'billing_city', 'city');
            $stateSelect = dc_client_column_select('c', 'state', 'billing_state', 'state');
            $select      = '
                c.userid,
                c.company,
                c.phonenumber,
                '.$citySelect.',
                '.$stateSelect.'
            ';
            if ($this->db->field_exists('active', db_prefix().'clients')) {
                $select .= ', c.active';
            }
            if ($hasTerritorySchema) {
                $select .= ',
                c.territory_id,
                c.sales_area_id,
                t.name AS territory_name,
                a.name AS sales_area_name';
            }
            $this->db->select($select, false);
            $this->db->from(db_prefix().'clients c');
            if ($hasTerritorySchema) {
                $this->db->join(db_prefix().'territories t', 't.id = c.territory_id', 'left');
                $this->db->join(db_prefix().'sales_area a', 'a.id = c.sales_area_id', 'left');
            }
            if ($this->db->field_exists('active', db_prefix().'clients')) {
                $this->db->where('c.active', 1);
            }

            if ($keySearch) {
                $keySearch   = trim(urldecode($keySearch));
                $clientTable = db_prefix().'clients';
                $cityColumn  = $this->db->field_exists('city', $clientTable) ? 'c.city' : 'c.billing_city';
                $this->db->group_start();
                $this->db->like('c.company', $keySearch);
                $this->db->or_like('c.phonenumber', $keySearch);
                if ($this->db->field_exists(str_replace('c.', '', $cityColumn), $clientTable)) {
                    $this->db->or_like($cityColumn, $keySearch);
                }
                $this->db->group_end();
            }

            $this->apply_customers_permission_scope($staff_id, 'c');
            $this->db->order_by('c.company', 'ASC');
            $this->db->limit(100);
            $customerData = $this->db->get()->result_array();

            if (!empty($customerData)) {
                $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $customerData], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
            }
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function customers_post()
    {
        if (staff_cant('create', 'customers', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }

        try {
            $this->form_validation->set_rules('company', 'Company', 'required|max_length[600]');
            if (!$this->form_validation->run()) {
                $this->response(['message' => strip_tags(validation_errors()), 'error' => $this->form_validation->error_array()], RestController::HTTP_BAD_REQUEST);
            } else {
                $data = [
                    'company' => $this->input->post('company'),
                    'vat' => $this->input->post('vat') ?? '',
                    'addedfrom' => $this->staffInfo['data']->staff_id,
                    'phonenumber' => $this->input->post('phonenumber') ?? '',
                    'website' => $this->input->post('website') ?? '',
                    'default_currency' => $this->input->post('default_currency') ?? '',
                    'default_language' => $this->input->post('default_language') ?? '',
                    'address' => $this->input->post('address') ?? '',
                    'city' => $this->input->post('city') ?? '',
                    'state' => $this->input->post('state') ?? '',
                    'zip' => $this->input->post('zip') ?? '',
                    'country' => $this->input->post('country') ?? '',
                    'groups_in' => $this->input->post('groups_in'),
                    'billing_street' => $this->input->post('billing_street') ?? '',
                    'billing_city' => $this->input->post('billing_city') ?? '',
                    'billing_state' => $this->input->post('billing_state') ?? '',
                    'billing_zip' => $this->input->post('billing_zip') ?? '',
                    'billing_country' => $this->input->post('billing_country') ?? '',
                    'shipping_street' => $this->input->post('shipping_street') ?? '',
                    'shipping_city' => $this->input->post('shipping_city') ?? '',
                    'shipping_state' => $this->input->post('shipping_state') ?? '',
                    'shipping_zip' => $this->input->post('shipping_zip') ?? '',
                    'shipping_country' => $this->input->post('shipping_country') ?? '',
                    'territory_id'=> $this->input->post('territory_id') ??'',
                    'parent_partner_id'=>$this->input->post('parent_partner_id')??'',
                    'sales_area_id'=>$this->input->post('sales_area_id')??''

                ];

                $this->load->model('clients_model');
                $success = $this->clients_model->add($data);
                if ($success) {
                    $this->response(['message' => _l('customer_added_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('customer_add_failed')], RestController::HTTP_NOT_FOUND);
                }
            }
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    public function visit_history_get()
    {
        try {

            $customerID = $this->get('id');

            if (!$customerID) {
                $this->response(['message' => 'Customer ID required'], RestController::HTTP_BAD_REQUEST);
            }

            $this->db->where('client_id', $customerID);
            $this->db->order_by('visit_date', 'DESC');
            $visits = $this->db->get(db_prefix() . 'sales_visits')->result_array();

            $this->response([
                'message' => 'Visit history retrieved',
                'data'    => $visits
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            $this->response(['message' => 'Something went wrong'], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    public function followups_get($customerID = null)
    {
        try {

            if (!$customerID) {
                return $this->response([
                    'status' => false,
                    'message' => 'Customer ID required'
                ], RestController::HTTP_BAD_REQUEST);
            }

            $this->db->where('client_id', $customerID);
            //$this->db->order_by('next_followup_date', 'ASC');
            $followups = $this->db->get(db_prefix() . 'sales_visits')->result_array();

            $this->response([
                'message' => 'Followups retrieved',
                'data'    => $followups
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            $this->response(['message' => 'Something went wrong'], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    public function orders_get($customerID = null)
    {
        try {

            log_message('debug', '===== orders_get API Called =====');

            log_message('debug', 'Customer ID Received: ' . print_r($customerID, true));

            if (!$customerID) {
                log_message('error', 'Customer ID missing in orders_get');
                return $this->response(
                    ['message' => 'Customer ID required'],
                    RestController::HTTP_BAD_REQUEST
                );
            }

            $this->db->where('clientid', $customerID);
            $this->db->order_by('datecreated', 'DESC');

            $query = $this->db->get(db_prefix() . 'estimates');

            // Log last query
            log_message('debug', 'Last Query: ' . $this->db->last_query());

            $orders = $query->result_array();

            log_message('debug', 'Orders Count: ' . count($orders));
            log_message('debug', 'Orders Data: ' . print_r($orders, true));

            return $this->response([
                'message' => 'Orders retrieved',
                'data'    => $orders
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {

            log_message('error', 'orders_get Exception: ' . $th->getMessage());
            log_message('error', 'orders_get Line: ' . $th->getLine());
            log_message('error', 'orders_get File: ' . $th->getFile());

            return $this->response(
                ['message' => 'Something went wrong'],
                RestController::HTTP_INTERNAL_ERROR
            );
        }
    }

    public function payments_get($customerID = null)
    {
        try {

            // $customerID = $this->get('id');

            if (!$customerID) {
                $this->response(['message' => 'Customer ID required'], RestController::HTTP_BAD_REQUEST);
            }

            // $this->db->where('clientid', $customerID);
            $this->db->order_by('date', 'DESC');
            $payments = $this->db->get(db_prefix() . 'invoicepaymentrecords')->result_array();

            $this->response([
                'message' => 'Payments retrieved',
                'data'    => $payments
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            $this->response(['message' => 'Something went wrong'], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    public function activity_get($customerID = null)
    {
        try {

            // $customerID = $this->get('id');

            if (!$customerID) {
                $this->response(['message' => 'Customer ID required'], RestController::HTTP_BAD_REQUEST);
            }

            $timeline = [];

            // Visits
            $visits = $this->db
                ->where('client_id', $customerID)
                ->get(db_prefix() . 'sales_visits')
                ->result_array();

            foreach ($visits as $v) {
                $timeline[] = [
                    'type' => 'visit',
                    'date' => $v['visit_date'],
                    'description' => 'Visit - ' . $v['status']
                ];
            }

            // Orders
            $orders = $this->db
                ->where('clientid', $customerID)
                ->get(db_prefix() . 'estimates')
                ->result_array();

            foreach ($orders as $o) {
                $timeline[] = [
                    'type' => 'order',
                    'date' => $o['datecreated'],
                    'description' => 'Order #' . $o['id']
                ];
            }

            // Payments
            $payments = $this->db
                // ->where('clientid', $customerID)
                ->get(db_prefix() . 'invoicepaymentrecords')
                ->result_array();

            foreach ($payments as $p) {
                $timeline[] = [
                    'type' => 'payment',
                    'date' => $p['date'],
                    'description' => 'Payment ₹' . $p['amount']
                ];
            }

            // Sort by date DESC
            usort($timeline, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            $this->response([
                'message' => 'Activity retrieved',
                'data'    => $timeline
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            $this->response(['message' => 'Something went wrong'], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function reminders_get()
    {
        try {

            $rel_id   = $this->get('rel_id');
            $rel_type = $this->get('rel_type');

            if (!$rel_id || !$rel_type) {
                return $this->response(
                    ['message' => 'rel_id and rel_type required'],
                    RestController::HTTP_BAD_REQUEST
                );
            }

            $reminders = $this->db
              //  ->where('rel_id', $rel_id)
               // ->where('rel_type', $rel_type)
                ->order_by('date', 'DESC')
                ->get(db_prefix() . 'reminders')
                ->result_array();

            return $this->response([
                'message' => 'Reminders retrieved',
                'data'    => $reminders
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response(
                ['message' => 'Something went wrong'],
                RestController::HTTP_INTERNAL_ERROR
            );
        }
    }
    public function reminders_post()
    {
        try {

            $rel_id   = $this->post('rel_id');
            $rel_type = $this->post('rel_type');
            $description = $this->post('description');
            $date = $this->post('date');
            $staff_id = $this->staffInfo['data']->staff_id;

            if (!$rel_id || !$rel_type || !$description || !$date || !$staff_id) {
                return $this->response(
                    ['message' => 'Missing required fields'],
                    RestController::HTTP_BAD_REQUEST
                );
            }

            $insert = [
                'description'      => $description,
                'date'             => $date,
                'isnotified'       => 0,
                'rel_id'           => $rel_id,
                'rel_type'         => $rel_type,
                'staff'            => $this->staffInfo['data']->staff_id,
                'notify_by_email'  => 1,
                'creator'          => $staff_id
            ];

            $this->db->insert(db_prefix() . 'reminders', $insert);

            return $this->response([
                'message' => 'Reminder created successfully',
                'reminder_id' => $this->db->insert_id()
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response(
                ['message' => 'Something went wrong'],
                RestController::HTTP_INTERNAL_ERROR
            );
        }
    }

    public function customers_put()
    {
        if (staff_cant('edit', 'customers', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }

        try {

            if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_BAD_REQUEST);
            }

            $customerID = $this->get('id');
            $this->load->model('clients_model');
            $customer = $this->clients_model->get($customerID);

            if (is_object($customer)) {
                $data = array();
                parse_str(file_get_contents('php://input'), $data);
                $success = $this->clients_model->update($data, $customerID);
                if ($success) {
                    $this->response(['message' => _l('customer_updated_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('customer_update_failed')], RestController::HTTP_NOT_FOUND);
                }
            } else {
                $this->response(['message' => _l('invalid_customer_id')], RestController::HTTP_NOT_FOUND);
            }
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function customers_delete()
    {
        $customerID = $this->get('id');

        if (staff_cant('delete', 'customers', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }

        $this->load->model('Clients_model');
        $customer = $this->Clients_model->get($customerID);
        if (is_object($customer)) {
            $success = $this->Clients_model->delete($customerID);
            if ($success) {
                $this->response(['message' => _l('customer_deleted_successfully')], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('customer_delete_failed')], RestController::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(['message' => _l('invalid_customer_id')], RestController::HTTP_NOT_FOUND);
        }
    }
    public function reminder_count_get()
    {
        try {

            $staff_id = $this->get('staff_id');

            if (!$staff_id) {
                return $this->response(
                    ['message' => 'staff_id required'],
                    RestController::HTTP_BAD_REQUEST
                );
            }

            $count = $this->db
                ->where('staff', $staff_id)
                ->where('isnotified', 0)
                ->count_all_results(db_prefix() . 'reminders');

            return $this->response([
                'status'  => true,
                'count'   => $count
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {

            return $this->response([
                'status'  => false,
                'message' => 'Something went wrong'
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function contacts_get()
    {
        try {

            if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_BAD_REQUEST);
            }

            $customerID = $this->get('id');
            $contactID = $this->get('contact');

            $this->load->model('Clients_model');

            if (!empty($customerID) && empty($contactID)) {
                $data = $this->Clients_model->get_contacts($customerID);
                $contactData = [];
                foreach ($data as $contact) {
                    $contactData[] = array(
                        'id' => $contact['id'],
                        'userid' => $contact['userid'],
                        'is_primary' => $contact['is_primary'],
                        'firstname' => $contact['firstname'],
                        'lastname' => $contact['lastname'],
                        'email' => $contact['email'],
                        'phonenumber' => $contact['phonenumber'],
                        'title' => $contact['title'],
                        'active' => $contact['active'],
                        'profile_image' => contact_profile_image_url($contact['id'])
                    );
                }
            }

            if (!empty($customerID) && !empty($contactID)) {
                $data = $this->Clients_model->get_contact($contactID);
                $contactData = array(
                    'id' => $data->id,
                    'userid' => $data->userid,
                    'is_primary' => $data->is_primary,
                    'firstname' => $data->firstname,
                    'lastname' => $data->lastname,
                    'email' => $data->email,
                    'phonenumber' => $data->phonenumber,
                    'title' => $data->title,
                    'active' => $data->active,
                    'profile_image' => contact_profile_image_url($data->id)
                );
            }

            if (!empty($data)) {
                $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $contactData], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
            }
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function contacts_post()
    {
        if (staff_cant('create', 'customers', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }

        if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_BAD_REQUEST);
        }

        $customerID = $this->get('id');
        $this->load->model('Clients_model');
        $customer = $this->Clients_model->get($customerID);
        if (is_object($customer)) {
            try {
                $this->form_validation->set_rules('firstname', 'First Name', 'required|max_length[255]');
                $this->form_validation->set_rules('lastname', 'Last Name', 'required|max_length[255]');
                $this->form_validation->set_rules('email', 'Email', 'required|max_length[255]|is_unique[' . db_prefix() . 'contacts.email]', array('is_unique' => _l('email_already_exist')));
                $this->form_validation->set_rules('password', 'Password', 'required|max_length[255]');

                if (!$this->form_validation->run()) {
                    $this->response(['message' => strip_tags(validation_errors()), 'error' => $this->form_validation->error_array()], RestController::HTTP_BAD_REQUEST);
                } else {
                    $data = [
                        'firstname' => $this->input->post('firstname'),
                        'lastname' => $this->input->post('lastname'),
                        'email' => $this->input->post('email'),
                        'password' => $this->input->post('password'),
                        'phonenumber' => $this->input->post('phonenumber') ?? '',
                        'title' => $this->input->post('title') ?? '',
                    ];

                    $this->load->model('clients_model');
                    $success = $this->clients_model->add_contact($data, $customerID);
                    if ($success) {
                        $this->response(['message' => _l('contact_added_successfully')], RestController::HTTP_OK);
                    } else {
                        $this->response(['message' => _l('contact_add_failed')], RestController::HTTP_NOT_FOUND);
                    }
                }
            } catch (\Throwable $th) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
            }
        } else {
            $this->response(['message' => _l('invalid_customer_id')], RestController::HTTP_NOT_FOUND);
        }
    }

    public function contacts_delete()
    {
        $contactID = $this->get('id');

        if (staff_cant('delete', 'customers', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }

        $this->load->model('Clients_model');
        $contact = $this->Clients_model->get_contact($contactID);
        if (is_object($contact)) {
            $success = $this->Clients_model->delete_contact($contactID);
            if ($success) {
                $this->response(['message' => _l('contact_deleted_successfully')], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('contact_delete_failed')], RestController::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(['message' => _l('invalid_contact_id')], RestController::HTTP_NOT_FOUND);
        }
    }
}
