<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__ . '/RestController.php';

use FlutexAdminApi\RestController;

class Factory_visitor extends RestController
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

        $this->load->model('factory_visitor/Factory_visitor_model', 'visitor');
        $this->load->model('staff_model');
    }

    /* =====================================================
       👨‍💼 STAFF LIST
    ====================================================== */
    public function staff_get()
    {
        $staffRaw = $this->staff_model->get();

        $staff = [];

        foreach ($staffRaw as $row) {

            if ($row['active'] != 1) continue;

            $staff[] = [
                'id'        => $row['staffid'],
                'email'     => $row['email'],
                'full_name' => trim($row['firstname'] . ' ' . $row['lastname']),
            ];
        }

        $this->response([
            'status'  => true,
            'message' => 'Staff list retrieved successfully',
            'data'    => $staff
        ], RestController::HTTP_OK);
    }

    /* =====================================================
       📋 VISITOR LIST
    ====================================================== */
    public function index_get()
    {
        $visitors = $this->visitor->get();

        $this->response([
            'message' => 'Data retrieved successfully',
            'data'    => $visitors
        ], RestController::HTTP_OK);
    }
    public function my_invites_get()
    {
        $staff_id = $this->staffInfo['data']->staff_id;

        if (!$staff_id) {
            return $this->response([
                'message' => 'staff_id is required'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $visitors = $this->db
            ->where('host_staff_id', $staff_id)
            ->order_by('id', 'DESC')
            ->get(db_prefix() . 'factory_visitors')
            ->result_array();

        $this->response([
            'message' => 'My invites retrieved successfully',
            'data'    => $visitors
        ], RestController::HTTP_OK);
    }
    /* =====================================================
       👁 VISITOR DETAILS
    ====================================================== */
    public function id_get($id = '')
    {
        $visitor = $this->visitor->get($id);

        if (!$visitor) {
            return $this->response([
                'status'  => false,
                'message' => 'Visitor not found'
            ], 404);
        }

        $history = $this->visitor->get_history($visitor, $id);
        $logs    = $this->visitor->get_activity_logs($id);

        return $this->response([
            'status'  => true,
            'message' => 'Visitor details retrieved',
            'data'    => [
                'visitor' => $visitor,
                'history' => $history,
                'logs'    => $logs,
            ]
        ], 200);
    }

    /* =====================================================
       🔎 SEARCH VISITOR
    ====================================================== */
    public function search_get($key = '')
    {
        if (!$key) {
            $this->response(['message' => 'Search key required'], 400);
        }

        $result = $this->visitor->search($key);

        $this->response([
            'message' => 'Search result',
            'data'    => $result
        ], 200);
    }

    /* =====================================================
       📊 GATE DASHBOARD
    ====================================================== */
    public function dashboard_get()
    {
        $filters = [
            'report_type' => $this->input->get('report_type'),
            'guard_id'    => $this->input->get('guard_id'),
            'gate_id'     => $this->input->get('gate_id'),
        ];

        $data = $this->visitor->get_dashboard_report($filters);

        echo json_encode([
            'status' => true,
            'data'   => $data
        ]);
    }

    /* =====================================================
       🚫 BLACKLIST VISITOR
    ====================================================== */
    public function blacklist_post()
    {
        $visitor_id = $this->post('visitor_id');

        if (!$visitor_id) {
            $this->response(['message' => 'Visitor ID required'], 400);
        }

        $visitor = $this->visitor->get($visitor_id);

        if (!$visitor) {
            $this->response(['message' => 'Visitor not found'], 404);
        }

        $this->visitor->add_to_blacklist($visitor);

        $this->response([
            'message' => 'Visitor blacklisted successfully'
        ], 200);
    }

    public function remove_blacklist_post()
    {
        $blacklist_id = $this->post('blacklist_id');

        $removed = $this->visitor->remove_blacklist($blacklist_id);

        if (!$removed) {
            $this->response(['message' => 'Blacklist record not found'], 404);
        }

        $this->response([
            'message' => 'Visitor removed from blacklist'
        ], 200);
    }

    /* =====================================================
       ❌ DELETE VISITOR
    ====================================================== */
    public function index_delete()
    {
        $id = $this->get('id');

        if (!$id) {
            $this->response(['message' => 'Visitor ID required'], 400);
        }

        $this->visitor->delete($id);

        $this->response([
            'message' => 'Visitor deleted successfully'
        ], 200);
    }

    /* =====================================================
       🚪 CREATE PASS
    ====================================================== */
    public function create_pass_post()
    {
        try {

            log_message('info', 'Create Pass API called by staff ID: ' . $this->staffInfo['data']->staff_id);

            $this->form_validation->set_rules('visitor_name', 'Visitor Name', 'required');
            $this->form_validation->set_rules('mobile', 'Mobile', 'required');
            $this->form_validation->set_rules('host_staff_id', 'Host Staff', 'required');

            if (!$this->form_validation->run()) {

                log_message('error', 'Create Pass Validation Failed: ' . validation_errors());

                return $this->response([
                    'status'  => false,
                    'message' => strip_tags(validation_errors())
                ], 400);
            }

            $host_id = $this->post('host_staff_id');
            $host = $this->staff_model->get($host_id);

            if (!$host) {

                log_message('error', 'Invalid Host Staff ID: ' . $host_id);

                return $this->response([
                    'status'  => false,
                    'message' => 'Invalid Host Staff'
                ], 400);
            }

            $guard_id = $this->staffInfo['data']->staff_id;
            $shift    = $this->visitor->get_guard_active_shift($guard_id);

            if (!$shift) {

                log_message('error', 'No active shift found for guard ID: ' . $guard_id);

                return $this->response([
                    'status'  => false,
                    'message' => 'No active shift found'
                ], 400);
            }

            $pass_number = 'GP-' . date('YmdHis') . rand(10, 99);

            $data = [
                'pass_number'   => $pass_number,
                'visitor_name'  => $this->post('visitor_name'),
                'mobile'        => $this->post('mobile'),
                'company'       => $this->post('company'),
                'purpose'       => $this->post('purpose'),
                'visitor_type'  => $this->post('visitor_type') ?? 'guest',
                'host_staff_id' => $host_id,
                'status'        => 'approved',
                'approval_time' => date('Y-m-d H:i:s'),
                'checkin_time'  => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'gate_guard_id' => $guard_id,
                'addedfrom'     => $guard_id,
                'gate_id'       => $shift->gate_id,
            ];

            $insertID = $this->visitor->add($data);

            if (!$insertID) {

                log_message('error', 'Visitor insert failed for mobile: ' . $this->post('mobile'));

                return $this->response([
                    'status'  => false,
                    'message' => 'Failed to create pass'
                ], 500);
            }

            // 🔐 Insert Audit Log (Gate Log Table)
            $this->db->insert(db_prefix() . 'factory_gate_logs', [
                'visitor_id' => $insertID,
                'gate_id'    => $shift->gate_id,
                'guard_id'   => $guard_id,
                'action'     => 'create_pass',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            log_message('info', 'Gate Pass Created: ' . $pass_number . ' by Guard ID: ' . $guard_id);

            return $this->response([
                'status'      => true,
                'message'     => 'Gate pass created successfully',
                'pass_number' => $pass_number,
                'visitor_id'  => $insertID
            ], 200);
        } catch (Exception $e) {

            log_message('error', 'Create Pass Exception: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }
    public function employee_invite_post()
    {
        try {

            $staff_id = $this->staffInfo['data']->staff_id;

            log_message('info', 'Employee Invite API called by staff ID: ' . $staff_id);

            $this->form_validation->set_rules('visitor_name', 'Visitor Name', 'required');
            $this->form_validation->set_rules('mobile', 'Mobile', 'required');
            $this->form_validation->set_rules('valid_from', 'Valid From', 'required');
            $this->form_validation->set_rules('valid_to', 'Valid To', 'required');
            if (!$this->form_validation->run()) {

                return $this->response([
                    'status'  => false,
                    'message' => strip_tags(validation_errors())
                ], 400);
            }

            // 🔹 Generate Pass Number
            $pass_number = 'INV-' . date('YmdHis') . rand(10, 99);

            // 🔹 Generate Secure Share Token
            $share_token = bin2hex(random_bytes(16));

            $data = [
                'pass_number'   => $pass_number,
                'visitor_name'  => $this->post('visitor_name'),
                'mobile'        => $this->post('mobile'),
                'company'       => $this->post('company'),
                'purpose'       => $this->post('purpose'),
                'visitor_type'  => $this->post('visitor_type') ?? 'guest',
                'host_staff_id' => $staff_id,
                'status'        => 'approved', // ✅ Auto Approved
                'approval_time' => date('Y-m-d H:i:s'),
                'checkin_time'  => null,
                'created_at'    => date('Y-m-d H:i:s'),
                'visit_from' => $this->post('valid_from'),
                'visit_to'  => $this->post('valid_to'),
                'addedfrom'     => $staff_id,
                'gate_guard_id' => null,
                'gate_id'       => null,
                'share_token'   => $share_token
            ];

            $insertID = $this->visitor->add($data);

            if (!$insertID) {

                return $this->response([
                    'status'  => false,
                    'message' => 'Failed to create invite'
                ], 500);
            }

            // 🔹 Generate Share URL
            $share_url = base_url('visitor/pass/' . $share_token);

            return $this->response([
                'status'      => true,
                'message'     => 'Visitor invite created successfully',
                'pass_number' => $pass_number,
                'visitor_id'  => $insertID,
                'share_url'   => $share_url,
                'qr_data'     => $share_url // Flutter will generate QR from this
            ], 200);
        } catch (Exception $e) {

            log_message('error', 'Employee Invite Exception: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }
    public function employee_invite11_post()
    {
        try {

            $staff_id = $this->staffInfo['data']->staff_id;

            log_message('info', 'Employee Invite API called by staff ID: ' . $staff_id);

            $this->form_validation->set_rules('visitor_name', 'Visitor Name', 'required');
            $this->form_validation->set_rules('mobile', 'Mobile', 'required');

            if (!$this->form_validation->run()) {

                log_message('error', 'Employee Invite Validation Failed: ' . validation_errors());

                return $this->response([
                    'status'  => false,
                    'message' => strip_tags(validation_errors())
                ], 400);
            }

            // Generate Pass Number
            $pass_number = 'INV-' . date('YmdHis') . rand(10, 99);

            $data = [
                'pass_number'   => $pass_number,
                'visitor_name'  => $this->post('visitor_name'),
                'mobile'        => $this->post('mobile'),
                'company'       => $this->post('company'),
                'purpose'       => $this->post('purpose'),
                'visitor_type'  => $this->post('visitor_type') ?? 'guest',
                'host_staff_id' => $staff_id, // Employee becomes host
                'status'        => 'pending', // Important difference
                'created_at'    => date('Y-m-d H:i:s'),
                'addedfrom'     => $staff_id,
                'gate_guard_id' => null,
                'gate_id'       => null
            ];

            $insertID = $this->visitor->add($data);

            if (!$insertID) {

                log_message('error', 'Employee Invite insert failed');

                return $this->response([
                    'status'  => false,
                    'message' => 'Failed to create invite'
                ], 500);
            }

            log_message('info', 'Employee Invite Created: ' . $pass_number);

            return $this->response([
                'status'      => true,
                'message'     => 'Visitor invite created successfully',
                'pass_number' => $pass_number,
                'visitor_id'  => $insertID
            ], 200);
        } catch (Exception $e) {

            log_message('error', 'Employee Invite Exception: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }

    /* =====================================================
       🔍 VERIFY PASS
    ====================================================== */
    public function verify_pass_get()
    {
        $pass_number = $this->get('pass_number');

        if (!$pass_number) {
            $this->response(['message' => 'Pass number required'], 400);
        }

        $visitor = $this->visitor->get_by_pass($pass_number);

        if (!$visitor) {
            $this->response(['message' => 'Invalid Pass'], 404);
        }

        if ($visitor->status === 'blacklisted') {
            $this->response(['message' => 'Visitor Blacklisted'], 403);
        }

        if ($visitor->status === 'checked_out') {
            $this->response(['message' => 'Visitor Already Checked Out'], 400);
        }

        $this->visitor->log_action(
            $visitor->id,
            $visitor->gate_id,
            $this->staffInfo['data']->staff_id,
            'verify'
        );

        $this->response([
            'message' => 'Pass Valid',
            'data'    => $visitor
        ], 200);
    }

    /* =====================================================
       🚪 CHECKOUT
    ====================================================== */
    public function checkout_post()
    {
        $visitor_id = $this->post('visitor_id');

        if (!$visitor_id) {
            $this->response(['message' => 'Visitor ID required'], 400);
        }

        $this->visitor->checkout($visitor_id);

        $this->response([
            'message' => 'Visitor checked out successfully'
        ], 200);
    }
    /* ===========================
   🛡 FULL SECURITY DASHBOARD
=========================== */
    public function get_security_dashboard_full()
    {
        $guard_id = $this->staffInfo['data']->staff_id; // logged in staff

        $data = $this->factory_visitor_model
            ->get_security_dashboard_full($guard_id);

        echo json_encode([
            'status' => true,
            'data'   => $data
        ]);
    }
    /* ===========================
   🚫 BLACKLIST LIST
=========================== */
    public function blacklist_get()
    {
        $this->response([
            'data' => $this->visitor->get_blacklist()
        ], 200);
    }

    /* ===========================
   👁 BLACKLIST DETAILS
=========================== */
    public function blacklist_details_get($id)
    {
        $record = $this->visitor->get_blacklist_record($id);

        if (!$record) {
            $this->response(['message' => 'Not found'], 404);
        }

        $this->response(['data' => $record], 200);
    }
    /* ===========================
   ⏱ SHIFTS LIST
=========================== */
    public function shifts_get()
    {
        $this->response([
            'data' => $this->visitor->get_shifts()
        ], 200);
    }

    /* ===========================
   ➕ ADD SHIFT
=========================== */
    public function add_shift_post()
    {
        $start = $this->post('shift_start');
        $end   = $this->post('shift_end');
        $gate  = $this->post('gate_id');

        if ($this->visitor->is_shift_overlap($gate, $start, $end)) {
            $this->response(['message' => 'Shift overlap detected'], 400);
        }

        $this->visitor->add_shift($this->post());

        $this->response(['message' => 'Shift added'], 200);
    }

    /* ===========================
   ✏ UPDATE SHIFT
=========================== */
    public function update_shift_post()
    {
        $this->visitor->update_shift(
            $this->post('shift_id'),
            $this->post()
        );

        $this->response(['message' => 'Shift updated'], 200);
    }

    /* ===========================
   ❌ DELETE SHIFT
=========================== */
    public function delete_shift_post()
    {
        $this->visitor->delete_shift($this->post('shift_id'));
        $this->response(['message' => 'Shift deleted'], 200);
    }
    /* ===========================
   🚪 GATES LIST
=========================== */
    public function gates_get()
    {
        $this->response([
            'data' => $this->visitor->get_gates()
        ], 200);
    }

    /* ===========================
   ➕ ADD GATE
=========================== */
    public function add_gate_post()
    {
        $this->visitor->add_gate($this->post());
        $this->response(['message' => 'Gate added'], 200);
    }

    /* ===========================
   ✏ UPDATE GATE
=========================== */
    public function update_gate_post()
    {
        $this->visitor->update_gate(
            $this->post('gate_id'),
            $this->post()
        );

        $this->response(['message' => 'Gate updated'], 200);
    }

    /* ===========================
   ❌ DELETE GATE
=========================== */
    public function delete_gate_post()
    {
        $this->visitor->delete_gate($this->post('gate_id'));
        $this->response(['message' => 'Gate deleted'], 200);
    }
}
