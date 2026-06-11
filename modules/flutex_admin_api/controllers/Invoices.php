<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';

use FlutexAdminApi\RestController;

class Invoices extends RestController
{
    protected $staffInfo;

   public function __construct()
{
    parent::__construct();

    register_language_files('flutex_admin_api');
    load_admin_language();

    $this->load->helper('flutex_admin_api');

    $auth = isAuthorized();

    if (!isset($auth['status'])) {
        return $this->response($auth['response'], $auth['response_code']);
    }

    // ✅ Detect user safely
    $this->staffInfo  = null;
    $this->clientInfo = null;

    if (isset($auth['data']->staff_id)) {
        $this->staffInfo = $auth;
    }

    if (isset($auth['data']->contact_id) || isset($auth['data']->userid)) {
        $this->clientInfo = $auth;
    }

    /*
    |--------------------------------------------------------------------------
    | 🔹 STAFF PERMISSION CHECK (ONLY FOR STAFF)
    |--------------------------------------------------------------------------
    */
    if ($this->staffInfo) {

        $staffID = $this->staffInfo['data']->staff_id ?? null;

        if ($staffID && staff_cant('view', 'invoices', $staffID) && !is_admin($staffID)) {
            return $this->response([
                'message' => _l('not_permission_to_perform_this_action')
            ], RestController::HTTP_FORBIDDEN);
        }
    }

    // ✅ CLIENT → skip staff_cant
}
    public function pdf_get($id)
{
    log_message('info', '================ INVOICE PDF START ================');
    log_message('info', '[Invoice][PDF] Method hit');
    log_message('info', '[Invoice][PDF] Invoice ID: ' . $id);

    if (!$id) {
        show_error('Invoice ID required', 400);
        return;
    }

    $staff_id = $this->staffInfo['data']->staff_id;
    log_message('info', '[Invoice][PDF] Staff ID: ' . $staff_id);

    // 🚫 Disable profiler & clean buffers
    $this->output->enable_profiler(false);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // 🔎 Load invoice
    $this->load->model('invoices_model');
    $invoice = $this->invoices_model->get($id);

    if (!$invoice) {
        log_message('error', '[Invoice][PDF] Invoice not found');
        show_error('Invoice not found', 404);
        return;
    }

    log_message('info', '[Invoice][PDF] Invoice loaded successfully');

    // ✅ Load PDF class
    require_once(APPPATH . 'libraries/pdf/Invoice_pdf.php');
    log_message('info', '[Invoice][PDF] Invoice_pdf class loaded');

    // ✅ Create PDF object
    $pdf = new Invoice_pdf($invoice);
    log_message('info', '[Invoice][PDF] Invoice_pdf instance created');

    // Build PDF
    $pdf->prepare();
    log_message('info', '[Invoice][PDF] PDF prepared');

    // ✅ Headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="invoice_'.$id.'.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    log_message('info', '[Invoice][PDF] Outputting PDF');

    // 🚨 Output PDF
    $pdf->output();

    log_message('info', '================ INVOICE PDF END ==================');
    exit;
}

   public function invoices_get()
{
    if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
        return $this->response([
            'message' => _l('something_went_wrong')
        ], RestController::HTTP_BAD_REQUEST);
    }

    $invoiceID = $this->get('id');

    $this->load->model('invoices_model');

    // ✅ Detect user
    $staff  = $this->staffInfo['data'] ?? null;
    $client = $this->clientInfo['data'] ?? null;

    if (!$staff && !$client) {
        return $this->response([
            'status' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔹 CLIENT FLOW
    |--------------------------------------------------------------------------
    */
    if ($client) {

        $clientID = $client->userid;

        // 🔸 SINGLE
        if (!empty($invoiceID)) {

            $invoice = $this->invoices_model->get($invoiceID);

            if (!$invoice || $invoice->clientid != $clientID) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $invoice->allowed_payment_modes = convertSerializeDataToObject($invoice->allowed_payment_modes);

            return $this->response([
                'status' => true,
                'data'   => $invoice
            ], 200);
        }

        // 🔸 LIST (ONLY CLIENT INVOICES)
        $invoices = $this->invoices_model->get('', [
            'clientid' => $clientID
        ]);

        return $this->response([
            'status' => true,
            'data'   => $invoices
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔹 STAFF FLOW
    |--------------------------------------------------------------------------
    */
    if ($staff) {

        $staffID = $staff->staff_id ?? null;

        if (!has_permission('invoices', '', 'view') &&
            !has_permission('invoices', '', 'view_own')  && !is_admin($staffID)) {

            return $this->response([
                'status'  => false,
                'message' => 'No permission'
            ], 403);
        }

        // 🔸 SINGLE
        if (!empty($invoiceID)) {

            $invoice = $this->invoices_model->get($invoiceID);

            if (!$invoice) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Not found'
                ], 404);
            }

            $invoice->allowed_payment_modes = convertSerializeDataToObject($invoice->allowed_payment_modes);

            return $this->response([
                'status' => true,
                'data'   => $invoice
            ], 200);
        }

        // 🔸 LIST (ALL)
        $invoices = $this->invoices_model->get();

        foreach ($invoices as $key => $invoice) {
            $invoices[$key]['client_name'] = get_client($invoice['clientid'])->company ?? '';
        }

        return $this->response([
            'status'   => true,
            'overview' => $this->invoices_summary(),
            'data'     => $invoices
        ], 200);
    }

    return $this->response([
        'status'  => false,
        'message' => 'Invalid user'
    ], 400);
}
    
    public function invoices_summary()
    {
        $staffID = $this->staffInfo['data']->staff_id;
        // Invoices Overview
        $invoices = [];
        $this->load->model('invoices_model');
        $invoice_statuses = $this->invoices_model->get_statuses();
        foreach ($invoice_statuses as $status) {
            $percent_data = get_invoices_percent_by_status_api($status,$staffID);
            array_push($invoices, [
                'status' => format_invoice_status($status, '', false),
                'total' => strval($percent_data['total_by_status']),
                'percent' => strval($percent_data['percent'])
            ]);
        }
        return $invoices;
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
            
            $this->load->model('invoices_model');
            
            $invoiceData = $this->invoices_model->get('', $where);
            
            if (!empty($invoiceData)) {
                $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $invoiceData], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function invoices_post()
    {
        if (staff_cant('create', 'invoices', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
        
        try {
    		$this->form_validation->set_rules('clientid', 'Customer Id', 'required|greater_than[0]');
            $this->form_validation->set_rules('number', 'Invoice number', 'required|max_length[255]');
            $this->form_validation->set_rules('date', 'Invoice date', 'required|max_length[255]');
            $this->form_validation->set_rules('currency', 'Currency', 'required|max_length[255]');
            $this->form_validation->set_rules('newitems[]', 'Items', 'required');
            $this->form_validation->set_rules('allowed_payment_modes[]', 'Allowed Payment Mode', 'required|max_length[255]');
            $this->form_validation->set_rules('billing_street', 'Billing Street', 'required|max_length[255]');
            $this->form_validation->set_rules('subtotal', 'Subtotal', 'required|decimal|greater_than[0]');
            $this->form_validation->set_rules('total', 'Total', 'required|decimal|greater_than[0]');
    		
            if (!$this->form_validation->run()) {
                $this->response(['message' => strip_tags(validation_errors()),'error' => $this->form_validation->error_array()], RestController::HTTP_BAD_REQUEST);
            } else {
                $data = $this->input->post();
                
                $this->load->model('invoices_model');
                $success = $this->invoices_model->add($data);
                if ($success) {
                    $this->response(['message' => _l('invoice_added_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('invoice_add_failed')], RestController::HTTP_NOT_FOUND);
                }
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function invoices_put()
    {
        if (staff_cant('edit', 'invoices', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
    
        try {
            
            if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_BAD_REQUEST);
            }
            
            $invoiceID = $this->get('id');
            $this->load->model('invoices_model');
            $invoice = $this->invoices_model->get($invoiceID);
            
            if (is_object($invoice)) {
                $data = array();
                parse_str(file_get_contents('php://input'), $data);
                $success = $this->invoices_model->update($data, $invoiceID);
                if ($success) {
                    $this->response(['message' => _l('invoice_updated_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('invoice_update_failed')], RestController::HTTP_NOT_FOUND);
                }
            } else {
                $this->response(['message' => _l('invalid_invoice_id')], RestController::HTTP_NOT_FOUND);
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function invoices_delete()
    {
        $invoiceID = $this->get('id');
        
        if (staff_cant('delete', 'invoices', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
        
        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($invoiceID);
        if (is_object($invoice)) {
            $output = $this->invoices_model->delete($invoiceID);
            if ($output === TRUE) {
                $this->response(['message' => _l('invoice_deleted_successfully')], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('invoice_delete_failed')], RestController::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(['message' => _l('invalid_invoice_id')], RestController::HTTP_NOT_FOUND);
        }
    }
}