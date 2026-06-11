<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__ . '/RestController.php';

use FlutexAdminApi\RestController;

class Estimates extends RestController
{
    protected $staffInfo;







    public function __construct()
    {
        parent::__construct();

        register_language_files('flutex_admin_api');
        load_admin_language();

        $this->load->helper('flutex_admin_api');
        $this->load->model('estimates_model');

        /*
        |--------------------------------------------------------------------------
        | AUTHORIZATION
        |--------------------------------------------------------------------------
        */
        $auth = isAuthorized();

        if (
            !isset($auth['status']) ||
            !$auth['status']
        ) {

            return $this->response(
                $auth['response'],
                $auth['response_code']
            );
        }

        $user = $auth['data'];

        log_message('error', 'AUTH USER: ' . json_encode($user));

        /*
        |--------------------------------------------------------------------------
        | DEFAULT
        |--------------------------------------------------------------------------
        */
        $this->staffInfo  = null;
        $this->clientInfo = null;

        /*
        |--------------------------------------------------------------------------
        | DETECT STAFF
        |--------------------------------------------------------------------------
        */
        if (isset($user->staffid)) {

            // Normalize fields
            $user->staff_id = (int) $user->staffid;
            $user->type     = 'staff';

            $this->staffInfo = [
                'status' => true,
                'data'   => $user
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | DETECT CLIENT
        |--------------------------------------------------------------------------
        */
        elseif (
            isset($user->userid) ||
            isset($user->contact_id)
        ) {

            $user->client_id = (int) ($user->userid ?? 0);
            $user->type      = 'client';

            $this->clientInfo = [
                'status' => true,
                'data'   => $user
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET ESTIMATES
    |--------------------------------------------------------------------------
    */
    public function estimates_get()
    {
        log_message('error', '--- ESTIMATES API CALLED ---');

        $estimateID = $this->get('id');

        /*
        |--------------------------------------------------------------------------
        | STAFF FLOW
        |--------------------------------------------------------------------------
        */
        if ($this->staffInfo) {

            $user = $this->staffInfo['data'];

            $staffID = (int) $user->staff_id;

            log_message('error', 'FLOW: STAFF | ID: ' . $staffID);

            $canView    = staff_can('view', 'estimates', $staffID);
            $canViewOwn = staff_can('view_own', 'estimates', $staffID);

            /*
            |--------------------------------------------------------------------------
            | PERMISSION CHECK
            |--------------------------------------------------------------------------
            */
            if (!$canView && !$canViewOwn) {

                return $this->response([
                    'status'  => false,
                    'message' => 'No permission'
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | SINGLE ESTIMATE
            |--------------------------------------------------------------------------
            */
            if (!empty($estimateID)) {

                $estimate = $this->estimates_model->get($estimateID);

                if (!$estimate) {

                    return $this->response([
                        'status'  => false,
                        'message' => 'Estimate not found'
                    ], 404);
                }

                /*
                |--------------------------------------------------------------------------
                | OWN ACCESS CHECK
                |--------------------------------------------------------------------------
                */
                if (
                    !$canView &&
                    $canViewOwn &&
                    $estimate->addedfrom != $staffID &&
                    $estimate->sale_agent != $staffID
                ) {

                    return $this->response([
                        'status'  => false,
                        'message' => 'Access denied'
                    ], 403);
                }

                return $this->response([
                    'status' => true,
                    'data'   => $estimate
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | LIST ESTIMATES
            |--------------------------------------------------------------------------
            */
            if ($canView) {

                $estimates = $this->estimates_model->get();
                $this->db->select('
    '.db_prefix().'estimates.*,
    '.db_prefix().'clients.company as client_name,
     '.db_prefix().'currencies.symbol,
        '.db_prefix().'currencies.name as currency_name,
        '.db_prefix().'currencies.decimal_separator,
        '.db_prefix().'currencies.thousand_separator,
        '.db_prefix().'currencies.placement
');
 $this->db->join(
        db_prefix().'currencies',
        db_prefix().'currencies.id = '.db_prefix().'estimates.currency',
        'left'
    );

$this->db->from(db_prefix() . 'estimates');

$this->db->join(
    db_prefix().'clients',
    db_prefix().'clients.userid = '.db_prefix().'estimates.clientid',
    'left'
);

$estimates = $this->db->get()->result();

            } else {

    $this->db->select('
        '.db_prefix().'estimates.*,
        '.db_prefix().'clients.company as client_name,
         '.db_prefix().'currencies.symbol,
        '.db_prefix().'currencies.name as currency_name,
        '.db_prefix().'currencies.decimal_separator,
        '.db_prefix().'currencies.thousand_separator,
        '.db_prefix().'currencies.placement
    ');
 $this->db->join(
        db_prefix().'currencies',
        db_prefix().'currencies.id = '.db_prefix().'estimates.currency',
        'left'
    );

    $this->db->from(db_prefix() . 'estimates');

    $this->db->join(
        db_prefix().'clients',
        db_prefix().'clients.userid = '.db_prefix().'estimates.clientid',
        'left'
    );

    $this->db->group_start();
    $this->db->where(db_prefix().'estimates.addedfrom', $staffID);
    $this->db->or_where(db_prefix().'estimates.sale_agent', $staffID);
    $this->db->group_end();

    $estimates = $this->db->get()->result();
}

            return $this->response([
                'status' => true,
                'data'   => $estimates
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | CLIENT FLOW
        |--------------------------------------------------------------------------
        */
        elseif ($this->clientInfo) {

            $user = $this->clientInfo['data'];

            $clientID = (int) $user->client_id;

            log_message('error', 'FLOW: CLIENT | ID: ' . $clientID);

            /*
            |--------------------------------------------------------------------------
            | SINGLE ESTIMATE
            |--------------------------------------------------------------------------
            */
            if (!empty($estimateID)) {

                $estimate = $this->estimates_model->get($estimateID);

                if (
                    !$estimate ||
                    $estimate->clientid != $clientID
                ) {

                    return $this->response([
                        'status'  => false,
                        'message' => 'Access denied'
                    ], 403);
                }

                return $this->response([
                    'status' => true,
                    'data'   => $estimate
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | LIST CLIENT ESTIMATES
            |--------------------------------------------------------------------------
            */
            $estimates = $this->estimates_model->get('', [
                'clientid' => $clientID
            ]);

            return $this->response([
                'status' => true,
                'data'   => $estimates
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | INVALID USER TYPE
        |--------------------------------------------------------------------------
        */
        return $this->response([
            'status'  => false,
            'message' => 'Invalid user type'
        ], 400);
    }



public function estimateaction_post()
{
    $this->load->model('estimates_model');
    $this->load->model('invoices_model');

    $estimateID = $this->post('estimate_id');
    $action     = $this->post('estimate_action'); // 4 = accept, 3 = decline

    // ✅ Validate
    if (!$estimateID || !in_array($action, [3, 4])) {
        return $this->response([
            'status'  => false,
            'message' => 'Invalid request'
        ], 400);
    }

    // ✅ Client auth only
    $client = $this->clientInfo['data'] ?? null;

    if (!$client) {
        return $this->response([
            'status'  => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $clientID = $client->userid;

    // ✅ Get estimate
    $estimate = $this->estimates_model->get($estimateID);

    if (!$estimate || $estimate->clientid != $clientID) {
        return $this->response([
            'status'  => false,
            'message' => 'Access denied'
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔹 CORE LOGIC (USE YOUR FUNCTION)
    |--------------------------------------------------------------------------
    */
    $result = $this->estimates_model->mark_action_status($action, $estimateID, true);

    if (!$result) {
        return $this->response([
            'status'  => false,
            'message' => 'Failed to process estimate'
        ], 500);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔹 RESPONSE HANDLING
    |--------------------------------------------------------------------------
    */

    // ✅ ACCEPTED
    if ($action == 4) {

        // Auto invoiced
        if (is_array($result) && $result['invoiced'] == true) {

            $invoice = $this->invoices_model->get($result['invoiceid']);

            return $this->response([
                'status'      => true,
                'message'     => 'Estimate accepted & converted to invoice',
                'invoiced'    => true,
                'invoice_id'  => $invoice->id,
                'invoice_url' => site_url('invoice/' . $invoice->id . '/' . $invoice->hash)
            ], 200);
        }

        // Accepted only
        return $this->response([
            'status'   => true,
            'message'  => 'Estimate accepted successfully',
            'invoiced' => false
        ], 200);
    }

    // ✅ DECLINED
    if ($action == 3) {
        return $this->response([
            'status'  => true,
            'message' => 'Estimate declined successfully'
        ], 200);
    }

    return $this->response([
        'status'  => false,
        'message' => 'Unknown error'
    ], 500);
}
    public function taxes_get()
{
    try {

        $this->db->from(db_prefix() . 'tbltaxes');
        $this->db->order_by('taxrate', 'ASC');

        $taxes = $this->db->get()->result_array();

        $this->response([
            'message' => _l('data_retrieved_successfully'),
            'data'    => $taxes
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        $this->response([
            'message' => $th->getMessage(),
            'file'    => $th->getFile(),
            'line'    => $th->getLine(),
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}


public function estimates1_get()
{
    log_message('error', '==== AUTH DEBUG START ====');
    log_message('error', 'clientInfo: ' . json_encode($this->clientInfo));
    log_message('error', 'staffInfo: ' . json_encode($this->staffInfo));
    log_message('error', '==== AUTH DEBUG END ====');

    $this->load->model('estimates_model');

    $estimateID = $this->get('id');

    // ✅ Detect auth separately (DON'T MERGE)
    $staff  = $this->staffInfo['data'] ?? null;
    $client = $this->clientInfo['data'] ?? null;

    if (!$staff && !$client) {
        return $this->response([
            'status'  => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔹 CLIENT FLOW
    |--------------------------------------------------------------------------
    */
    if ($client) {

        // ✅ Correct client id
        $clientID = $client->userid;

        log_message('error', 'FLOW: CLIENT | ID: ' . $clientID);

        /*
        | 🔸 SINGLE ESTIMATE
        */
        if (!empty($estimateID)) {

            $estimate = $this->estimates_model->get($estimateID);

            if (!$estimate || $estimate->clientid != $clientID) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Access denied'
                ], 403);
            }

            return $this->response([
                'status' => true,
                'data'   => $estimate
            ], 200);
        }

        /*
        | 🔸 LIST (ONLY CLIENT DATA)
        */
        $estimates = $this->estimates_model->get('', [
            'clientid' => $clientID
        ]);

        return $this->response([
            'status' => true,
            'data'   => $estimates
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔹 STAFF FLOW
    |--------------------------------------------------------------------------
    */
    if ($staff) {

        $staffID = $staff->staff_id ?? null;

        log_message('error', 'FLOW: STAFF | ID: ' . $staffID);

        // ✅ Permission check
        if (!has_permission('estimates', '', 'view') &&
            !has_permission('estimates', '', 'view_own')) {

            return $this->response([
                'status'  => false,
                'message' => 'No permission'
            ], 403);
        }

        /*
        | 🔸 SINGLE ESTIMATE
        */
        if (!empty($estimateID)) {

            $estimate = $this->estimates_model->get($estimateID);

            if (!$estimate) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Not found'
                ], 404);
            }

            // ✅ view_own restriction
            if (!has_permission('estimates', '', 'view')) {
                if (
                    $estimate->addedfrom != $staffID &&
                    $estimate->sale_agent != $staffID
                ) {
                    return $this->response([
                        'status'  => false,
                        'message' => 'Access denied'
                    ], 403);
                }
            }

            return $this->response([
                'status' => true,
                'data'   => $estimate
            ], 200);
        }

        /*
        | 🔸 LIST ALL (or restrict if needed)
        */
        $estimates = $this->estimates_model->get();

        return $this->response([
            'status' => true,
            'data'   => $estimates
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔹 FALLBACK
    |--------------------------------------------------------------------------
    */
    return $this->response([
        'status'  => false,
        'message' => 'Invalid user'
    ], 400);
}
public function estimates11_get()
{
   
         $isAdmin = is_admin();
   
   if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
      $estimateID = $this->get('id');
   
    if ($isAdmin &&
        !has_permission('estimates', '', 'view') && !has_permission('estimates', '', 'view_own')) {
        $this->response(
            ['message' => _l('access_denied')],
            RestController::HTTP_FORBIDDEN
        );
        return;
    }

     $this->load->model('estimates_model');
     $estimate = $this->estimates_model->get($estimateID);

  
    if (empty($estimate)) {
        $this->response(
            ['message' => _l('data_not_found')],
            RestController::HTTP_NOT_FOUND
        );
        return;
    }

    $staffID = $this->staffInfo['data']->staff_id;

   
    if ($isAdmin &&
        !has_permission('estimates', '', 'view') &&
        has_permission('estimates', '', 'view_own')
    ) {
        if (
            $estimate->addedfrom != $staffID &&
            $estimate->sale_agent != $staffID
        ) {
            $this->response(
                ['message' => _l('access_denied')],
                RestController::HTTP_FORBIDDEN
            );
            return;
        }
    }

   
    $response = [
        'message' => _l('data_retrieved_successfully'),
        'data'    => $estimate,
        'overview'=> $this->estimates_summary(),
    ];

    $this->response($response, RestController::HTTP_OK);
}

    public function estimates1_summary()
    {
        $staffID = $this->staffInfo['data']->staff_id;
       
        $estimates = [];
        $this->load->model('estimates_model');
        $estimate_statuses = $this->estimates_model->get_statuses();

        array_splice($estimate_statuses, 1, 0, 'not_sent');
        foreach ($estimate_statuses as $status) {
            $percent_data = get_estimates_percent_by_status_api($status, $staffID);
            array_push($estimates, [
                'status' => format_estimate_status($status, '', false),
                'total' => strval($percent_data['total_by_status']),
                'percent' => strval($percent_data['percent'])
            ]);
        }
        return $estimates;
    }
public function estimates_summary()
{ $isAdmin = is_admin();
    $staffID = $this->staffInfo['data']->staff_id;

    if ($isAdmin &&
        !has_permission('estimates', '', 'view') &&
        !has_permission('estimates', '', 'view_own')
    ) {
        return [];
    }

    $this->load->model('estimates_model');
    $estimate_statuses = $this->estimates_model->get_statuses();

    array_splice($estimate_statuses, 1, 0, 'not_sent');

    $estimates = [];

    foreach ($estimate_statuses as $status) {

        if (has_permission('estimates', '', 'view')) {
            // All estimates
            $percent_data = get_estimates_percent_by_status_api($status);
        } else {
            // Only own estimates
            $percent_data = get_estimates_percent_by_status_api($status, $staffID);
        }

        $estimates[] = [
            'status'  => format_estimate_status($status, '', false),
            'total'   => (string) $percent_data['total_by_status'],
            'percent' => (string) $percent_data['percent'],
        ];
    }

    return $estimates;
}

    public function search_get()
    {
        try {

            if (!empty($this->get()) && !in_array('search', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
            }

            $keySearch = $this->get('search');

            $where = '';

            if ($keySearch) {
                $keySearch = trim(urldecode($keySearch));
                $keySearch = $this->db->escape_like_str($keySearch);
                $where .= '(number LIKE "' . $keySearch . '" OR clientnote LIKE "%' . $keySearch . '%" ESCAPE \'!\' OR adminnote LIKE "%' . $keySearch . '%" ESCAPE \'!\')';
            }

            $this->load->model('estimates_model');

            $estimateData = $this->estimates_model->get('', $where);

            if (!empty($estimateData)) {
                $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $estimateData], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
            }
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function estimates_post()
    {
        if (staff_cant('create', 'estimates', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }

        try {
            $this->form_validation->set_rules('clientid', 'Customer', 'required|numeric|greater_than[0]');
            $this->form_validation->set_rules('date', 'Estimate date', 'required|max_length[255]');
            $this->form_validation->set_rules('currency', 'Currency', 'required|numeric|greater_than[0]');
            $this->form_validation->set_rules('status', 'Status', 'required|numeric|greater_than[0]');
            $this->form_validation->set_rules('newitems[]', 'Items', 'required');
            $this->form_validation->set_rules('subtotal', 'Sub Total', 'required|decimal|greater_than[0]');
            $this->form_validation->set_rules('total', 'Total', 'required|decimal|greater_than[0]');




            if (!$this->form_validation->run()) {
                $this->response(['message' => strip_tags(validation_errors()), 'error' => $this->form_validation->error_array()], RestController::HTTP_BAD_REQUEST);
            } else {
                $data = $this->input->post();
                $data['shipping_charges']          = $data['shipping_charges'] ?? 0.00;
                $data['shipping_charges_tax_type'] = $data['shipping_charges_tax_type'] ?? 'after_tax';
                $data['packing_charges']           = $data['packing_charges'] ?? 0.00;
                $data['packing_charges_tax_type']  = $data['packing_charges_tax_type'] ?? 'after_tax';
                $data['other_charges']             = $data['other_charges'] ?? 0.00;
                $data['other_charges_tax_type']    = $data['other_charges_tax_type'] ?? 'after_tax';
                $data['adjustment']             = $data['adjustment'] ?? 0.00;
                $data['sale_agent']                = $this->staffInfo['data']->staff_id ?? 0;
                $this->load->model('estimates_model');
                $success = $this->estimates_model->add($data);
                if ($success) {
                    $this->response(['message' => _l('estimate_added_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('estimate_add_failed')], RestController::HTTP_NOT_FOUND);
                }
            }
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function estimates_put()
    {
        if (staff_cant('edit', 'estimates', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }

        try {

            if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_BAD_REQUEST);
            }

            $estimateID = $this->get('id');
            $this->load->model('estimates_model');
            $estimate = $this->estimates_model->get($estimateID);

            if (is_object($estimate)) {
                $data = array();
                parse_str(file_get_contents('php://input'), $data);
                $success = $this->estimates_model->update($data, $estimateID);
                if ($success) {
                    $this->response(['message' => _l('estimate_updated_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('estimate_update_failed')], RestController::HTTP_NOT_FOUND);
                }
            } else {
                $this->response(['message' => _l('invalid_estimate_id')], RestController::HTTP_NOT_FOUND);
            }
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }

public function pdf_get($id)
{
    log_message('info', '================ ESTIMATE PDF START ================');
    log_message('info', '[Estimate][PDF] Method hit');
    log_message('info', '[Estimate][PDF] Estimate ID: ' . $id);

    if (!$id) {
        show_error('Estimate ID required', 400);
        return;
    }

    $staff_id = $this->staffInfo['data']->staff_id;
    log_message('info', '[Estimate][PDF] Staff ID: ' . $staff_id);

    // 🚫 Disable profiler & clean buffers
    $this->output->enable_profiler(false);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // 🔎 Load estimate
    $this->load->model('estimates_model');
    $estimate = $this->estimates_model->get($id);

    if (!$estimate) {
        log_message('error', '[Estimate][PDF] Estimate not found');
        show_error('Estimate not found', 404);
        return;
    }

    log_message('info', '[Estimate][PDF] Estimate loaded successfully');

    // ✅ IMPORTANT: Include the class file manually
    require_once(APPPATH . 'libraries/pdf/Estimate_pdf.php');

    log_message('info', '[Estimate][PDF] Estimate_pdf class loaded');

    // ✅ Create object WITH required constructor params
    $pdf = new Estimate_pdf($estimate);

    log_message('info', '[Estimate][PDF] Estimate_pdf instance created');

    // Build PDF
    $pdf->prepare();

    log_message('info', '[Estimate][PDF] PDF prepared');

    // ✅ Headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="estimate_'.$id.'.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    log_message('info', '[Estimate][PDF] Outputting PDF');

    // 🚨 Output PDF
    $pdf->output();

    log_message('info', '================ ESTIMATE PDF END ==================');
    exit;
}



    public function estimates_delete()
    {
        $estimateID = $this->get('id');

        if (staff_cant('delete', 'estimates', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }

        $this->load->model('estimates_model');
        $estimate = $this->estimates_model->get($estimateID);
        if (is_object($estimate)) {
            $success = $this->estimates_model->delete($estimateID);
            if ($success) {
                $this->response(['message' => _l('estimate_deleted_successfully')], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('estimate_delete_failed')], RestController::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(['message' => _l('invalid_estimate_id')], RestController::HTTP_NOT_FOUND);
        }
    }
}
