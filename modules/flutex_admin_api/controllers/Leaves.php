<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__ . '/RestController.php';

use FlutexAdminApi\RestController;

class Leaves extends RestController
{
    protected $staffInfo;

    public function __construct()
    {
        parent::__construct();

        register_language_files('flutex_admin_api');
        load_admin_language();

        $this->load->helper('flutex_admin_api');
        $this->load->helper('admin');

        if (!isset(isAuthorized()['status'])) {
            $this->response(
                isAuthorized()['response'],
                isAuthorized()['response_code']
            );
        }

        $this->staffInfo = isAuthorized();

        $this->load->model('hrms/Leave_model', 'leave_model');
        $this->load->model('staff_model');
    }

    /*
    |--------------------------------------------------------------------------
    | GET MY LEAVES
    |--------------------------------------------------------------------------
    */

    public function list_get()
    {
        try {

            $employee_id = $this->staffInfo['data']->staff_id;
            $status      = $this->get('status');

            $this->db->select('l.*,t.leave_name');
            $this->db->from(db_prefix().'hrms_leave_applications l');
            $this->db->join(db_prefix().'hrms_leave_types t','t.id=l.leave_type_id','left');
            $this->db->where('l.employee_id',$employee_id);

            if ($status) {
                $this->db->where('l.status',$status);
            }

            $this->db->order_by('l.id','DESC');

            $leaves = $this->db->get()->result_array();

            $this->response([
                'status'=>true,
                'data'=>$leaves ?: []
            ],200);

        } catch (\Throwable $e) {

            log_message('error',$e->getMessage());

            $this->response([
                'status'=>false,
                'message'=>'Failed to fetch leaves'
            ],500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LEAVE DETAIL
    |--------------------------------------------------------------------------
    */

    public function detail_get($id)
    {
        $leave = $this->leave_model->get_leave_with_details($id);

        if (!$leave) {
            return $this->response([
                'status'=>false,
                'message'=>'Leave not found'
            ],404);
        }

        $this->response([
            'status'=>true,
            'data'=>$leave
        ],200);
    }

    /*
    |--------------------------------------------------------------------------
    | APPLY LEAVE
    |--------------------------------------------------------------------------
    */

    public function apply_post()
    {
        try {

            $employee_id = $this->staffInfo['data']->staff_id;

            $this->form_validation->set_rules('type_of_leave','Leave Type','required');
            $this->form_validation->set_rules('start_date','Start Date','required');
            $this->form_validation->set_rules('end_date','End Date','required');

            if (!$this->form_validation->run()) {

                return $this->response([
                    'status'=>false,
                    'message'=>strip_tags(validation_errors())
                ],400);
            }

            $start = $this->post('start_date');
            $end   = $this->post('end_date');

            $days = (strtotime($end) - strtotime($start)) / 86400 + 1;

            $data = [
                'employee_id'   => $employee_id,
                'leave_type_id' => $this->post('type_of_leave'),
                'from_date'     => $start,
                'to_date'       => $end,
                'total_days'    => $days,
                'reason'        => $this->post('reason'),
                'status'        => 'pending',
                'created_at'    => date('Y-m-d H:i:s')
            ];

            $this->db->insert(db_prefix().'hrms_leave_applications',$data);

            $leave_id = $this->db->insert_id();

            $this->response([
                'status'=>true,
                'message'=>'Leave applied successfully',
                'leave_id'=>$leave_id
            ],200);

        } catch (\Throwable $e) {

            log_message('error',$e->getMessage());

            $this->response([
                'status'=>false,
                'message'=>'Leave apply failed ' . $e->getMessage()
            ],500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LEAVE BALANCE
    |--------------------------------------------------------------------------
    */

    public function balance_get()
    {
        $employee_id = $this->staffInfo['data']->staff_id;

        $balance = $this->db
            ->select('b.*,t.leave_name')
            ->from(db_prefix().'hrms_leave_balances b')
            ->join(db_prefix().'hrms_leave_types t','t.id=b.leave_type_id','left')
            ->where('b.employee_id',$employee_id)
            ->get()
            ->result_array();

        $this->response([
            'status'=>true,
            'data'=>$balance ?: []
        ],200);
    }

    /*
    |--------------------------------------------------------------------------
    | MANAGER APPROVE / REJECT
    | POST flutex_admin_api/leaves/manager/action
    |--------------------------------------------------------------------------
    */

    public function manager_action_post()
    {
        try {
            $staffId = (int) $this->staffInfo['data']->staff_id;

            if (!is_admin($staffId) && !has_permission('hrms', $staffId, 'manage_leave')) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Access denied',
                ], RestController::HTTP_FORBIDDEN);
            }

            $body = json_decode($this->input->raw_input_stream, true);
            if (!is_array($body)) {
                $body = [];
            }

            $leaveId = (int) ($this->post('leave_id') ?: ($body['leave_id'] ?? 0));
            $action  = strtolower(trim((string) ($this->post('action') ?: ($body['action'] ?? ''))));
            $remark  = $this->post('remark') ?: ($body['remark'] ?? null);

            if ($leaveId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
                return $this->response([
                    'status'  => false,
                    'message' => 'leave_id and action (approve|reject) are required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $leave = $this->db
                ->where('id', $leaveId)
                ->get(db_prefix().'hrms_leave_applications')
                ->row_array();

            if (!$leave) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Leave not found',
                ], RestController::HTTP_NOT_FOUND);
            }

            if (!in_array($leave['status'], ['pending', 'submitted'], true)) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Leave is not pending approval',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $status = $action === 'approve' ? 'approved' : 'rejected';
            $result = $this->leave_model->update_status($leaveId, $status);

            if (!$result) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Failed to update leave',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $patch = [
                'approved_by' => $staffId,
                'approved_at' => date('Y-m-d H:i:s'),
            ];
            if ($remark !== null && $remark !== '' && $this->db->field_exists('manager_remark', db_prefix().'hrms_leave_applications')) {
                $patch['manager_remark'] = $remark;
            }
            if ($remark !== null && $remark !== '' && $this->db->field_exists('rejection_reason', db_prefix().'hrms_leave_applications')) {
                $patch['rejection_reason'] = $remark;
            }

            $this->db->where('id', $leaveId)->update(db_prefix().'hrms_leave_applications', $patch);

            return $this->response([
                'status'  => true,
                'success' => true,
                'message' => 'Leave '.$status,
                'data'    => [
                    'leave_id' => $leaveId,
                    'status'   => $status,
                ],
            ], RestController::HTTP_OK);
        } catch (\Throwable $e) {
            log_message('error', '[Leaves][manager_action] '.$e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Action failed',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LEAVE TYPES
    |--------------------------------------------------------------------------
    */

    public function categories_get()
    {
        $types = $this->db
            ->order_by('leave_name','ASC')
            ->get(db_prefix().'hrms_leave_types')
            ->result_array();

        $this->response([
            'status'=>true,
            'data'=>$types ?: []
        ],200);
    }

}