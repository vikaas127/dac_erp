<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';
require_once __DIR__.'/../vendor/autoload.php';
use flutexAdminApi\RestController;

class Profile extends RestController
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
            $this->response(isAuthorized()['response'], isAuthorized()['response_code']);
        }

        $this->staffInfo = isAuthorized();
        $this->load->model('distribution_channel/sales_assignment_model');
    }
     
    public function profile_get()
    {
        $staffID = (int) $this->staffInfo['data']->staff_id;
        $staff   = $this->db->where('staffid', $staffID)->get(db_prefix() . 'staff')->row();

        $assignment = $this->sales_assignment_model->get_mobile_profile($staffID);

        $staff_data = [
            'id'            => (int) $staff->staffid,
            'firstname'     => $staff->firstname ?? '',
            'lastname'      => $staff->lastname ?? '',
            'email'         => $staff->email,
            'profile_image' => staff_profile_image_url($staff->staffid),
            'sales_assignment' => [
                'has_assignment'  => (bool) $assignment['has_assignment'],
                'can_fill_dsr'    => (bool) $assignment['can_fill_dsr'],
                'position'        => $assignment['position'],
                'manager_id'      => $assignment['manager_id'],
                'manager_name'    => $assignment['manager_name'],
                'regions_label'   => $assignment['regions_label'],
                'territory_ids'   => $assignment['territory_ids'],
                'sales_area_ids'  => $assignment['sales_area_ids'],
                'territories'     => $assignment['territories'],
                'sales_areas'     => $assignment['sales_areas'],
                'customer_scope_mode' => $assignment['customer_scope_mode'],
                'scope_customers' => (bool) $assignment['scope_customers'],
            ],
        ];

        $this->response([
            'message' => _l('data_retrieved_successfully'),
            'data'    => $staff_data,
        ], RestController::HTTP_OK);
    }
     
    public function logout_get()
    {
        $this->db->update(db_prefix() . 'staff', ['flutex_api_key' => NULL], ['staffid' => $this->staffInfo['data']->staff_id]);
        $this->response(['message' => _l('logged_out_successfully')], RestController::HTTP_OK);
    }
}