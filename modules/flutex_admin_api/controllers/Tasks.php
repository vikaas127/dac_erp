<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';

use FlutexAdminApi\RestController;

class Tasks extends RestController
{
    protected $staffInfo;

   public function __construct()
{
    parent::__construct();

    register_language_files('flutex_admin_api');
    load_admin_language();

    $this->load->helper('flutex_admin_api');

    if (!isset(isAuthorized()['status'])) {
        $this->response(
            isAuthorized()['response'],
            isAuthorized()['response_code']
        );
    }

    $this->staffInfo = isAuthorized();

    /*
    |-------------------------------------------------
    | Task Permission Check
    |-------------------------------------------------
    |
    | Allow:
    | - view
    | - OR view_own
    |
    */

    
}

public function statuses_get()
{
    try {

        $this->load->model('tasks_model');

        $statuses = $this->tasks_model->get_statuses();

        $formatted = [];

        foreach ($statuses as $status) {

            $formatted[] = [
                'id'   => (string) $status['id'],
                'name' => $status['name'],
                'color' => $status['color'] ?? '',
            ];
        }

        $this->response([
            'status'  => true,
            'message' => 'Task statuses retrieved successfully',
            'data'    => $formatted,
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        log_message(
            'error',
            'TASK STATUS LIST ERROR => ' . $th->getMessage()
        );

        $this->response([
            'status'  => false,
            'message' => _l('something_went_wrong')
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}
public function update_status_post()
{
    try {

        

       

       

        $this->form_validation->set_rules(
            'task_id',
            'Task ID',
            'required|numeric'
        );

        $this->form_validation->set_rules(
            'status',
            'Status',
            'required|numeric'
        );

        if (!$this->form_validation->run()) {

            $this->response([
                'status'  => false,
                'message' => strip_tags(validation_errors()),
                'errors'  => $this->form_validation->error_array()
            ], RestController::HTTP_BAD_REQUEST);
        }

        $taskId = $this->input->post('task_id');
        $status = $this->input->post('status');

        /*
        |-------------------------------------------------
        | Check Task Exists
        |-------------------------------------------------
        */

        $this->load->model('tasks_model');

        $task = $this->tasks_model->get($taskId);

        if (!$task) {

            $this->response([
                'status'  => false,
                'message' => _l('invalid_task_id')
            ], RestController::HTTP_NOT_FOUND);
        }

        /*
        |-------------------------------------------------
        | Update Task Status
        |-------------------------------------------------
        */

        $update = $this->db
            ->where('id', $taskId)
            ->update(
                db_prefix() . 'tasks',
                [
                    'status' => $status,
                ]
            );

        if ($update) {

            $this->response([
                'status'  => true,
                'message' => 'Task status updated successfully'
            ], RestController::HTTP_OK);

        } else {

            $this->response([
                'status'  => false,
                'message' => 'Failed to update task status'
            ], RestController::HTTP_INTERNAL_ERROR);
        }

    } catch (\Throwable $th) {

        log_message(
            'error',
            'TASK STATUS UPDATE ERROR => ' . $th->getMessage()
        );

        $this->response([
            'status'  => false,
            'message' => _l('something_went_wrong')
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}
 public function tasks_get()
{
    // Validate params
    if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {

        $this->response([
            'status'  => false,
            'message' => _l('something_went_wrong')
        ], RestController::HTTP_INTERNAL_ERROR);
    }

    $taskID = $this->get('id');

    $this->load->model('tasks_model');

    $staffId = $this->staffInfo['data']->staff_id;

    /*
    |-------------------------------------------------
    | Check Permissions Properly
    |-------------------------------------------------
    */

    $hasViewPermission = has_permission('tasks', '', 'view');
    $hasViewOwn        = has_permission('tasks', '', 'view_own');

    /*
    |-------------------------------------------------
    | If no FULL VIEW permission,
    | show only own tasks
    |-------------------------------------------------
    */
$isAdmin = is_admin($staffId);
    if (!$isAdmin && !$hasViewPermission) {

        $this->db->group_start();

        // Created by user
        $this->db->where('addedfrom', $staffId);

        // Assigned tasks
        $this->db->or_where("
            id IN (
                SELECT taskid
                FROM " . db_prefix() . "task_assigned
                WHERE staffid = {$staffId}
            )
        ", null, false);

        // Followed tasks
        $this->db->or_where("
            id IN (
                SELECT taskid
                FROM " . db_prefix() . "task_followers
                WHERE staffid = {$staffId}
            )
        ", null, false);

        $this->db->group_end();
    }

    /*
    |-------------------------------------------------
    | Single Task
    |-------------------------------------------------
    */

    if (!empty($taskID)) {

        $this->db->where('id', $taskID);

        $taskExists = $this->db
            ->get(db_prefix() . 'tasks')
            ->row_array();

        if (!$taskExists) {

            $this->response([
                'status'  => false,
                'message' => _l('data_not_found')
            ], RestController::HTTP_NOT_FOUND);
        }

        $taskData = $this->tasks_model->get($taskID);
        /*
|--------------------------------------------------------------------------
| Followers
|--------------------------------------------------------------------------
*/

$this->db->select("
    tf.id,
    tf.staffid,
    s.firstname,
    s.lastname,
    CONCAT(
        s.firstname,
        ' ',
        s.lastname
    ) as full_name
");

$this->db->from(
    db_prefix() . 'task_followers as tf'
);

$this->db->join(
    db_prefix() . 'staff as s',
    's.staffid = tf.staffid',
    'left'
);

$this->db->where(
    'tf.taskid',
    $taskID
);

$followers = $this->db
    ->get()
    ->result_array();

$taskData->followers = $followers;

        $this->response([
            'status'  => true,
            'message' => _l('data_retrieved_successfully'),
            'data'    => $taskData
        ], RestController::HTTP_OK);
    }

    /*
    |-------------------------------------------------
    | Task Listing
    |-------------------------------------------------
    */

    $taskData = [];

    $tasks = $this->db
        ->get(db_prefix() . 'tasks')
        ->result_array();

    foreach ($tasks as $task) {

        $taskDetails = $this->tasks_model->get($task['id']);

        if ($taskDetails) {
            $taskData[] = (array) $taskDetails;
        }
    }

    $task_summary = $this->tasks_summary();

    $this->response([
        'status'   => true,
        'message'  => _l('data_retrieved_successfully'),
        'overview' => $task_summary,
        'data'     => $taskData
    ], RestController::HTTP_OK);
}
   
    public function tasks_summary()
{
    $tasks = [];

    $this->load->model('tasks_model');

    $tasks_statuses = $this->tasks_model->get_statuses();

    $totalTasks = total_rows(db_prefix() . 'tasks');

    foreach ($tasks_statuses as $status) {

        $where = ['status' => $status['id']];

        $count = total_rows(
            db_prefix() . 'tasks',
            $where
        );

        $tasks[] = [
            'id'      => (string)$status['id'],
            'status'  => $status['name'],
            'total'   => (string)$count,
            'percent' => $count == 0 || $totalTasks == 0
                ? '0'
                : (string)(($count / $totalTasks) * 100),
        ];
    }

    return $tasks;
}
    
    public function search_get()
    {
        try {
            
            if (!empty($this->get()) && !in_array('search', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
            }
            
            $keySearch = $this->get('search');
            
            if ($keySearch) {
                $this->db->select(db_prefix().'tasks.*');
                $this->db->from(db_prefix().'tasks');
                $this->db->like('name', $keySearch);
                $this->db->or_like(db_prefix().'tasks.id', $keySearch);
            }
            
            $taskData = $this->db->get()->result_array();
            
            if (!empty($taskData)) {
                $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $taskData], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function tasks_post()
    {
        if (staff_cant('create', 'tasks', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
        
        try {
            $this->form_validation->set_rules('name', 'Task Name', 'required|max_length[600]');
            $this->form_validation->set_rules('startdate', 'Task Start Date', 'required');
            $this->form_validation->set_rules('priority', 'Task Priority', 'required');
            
            if (!$this->form_validation->run()) {
                $this->response(['message' => strip_tags(validation_errors()),'error' => $this->form_validation->error_array()], RestController::HTTP_BAD_REQUEST);
            } else {
                $data = [
                    'name' => $this->input->post('name'),
                    'hourly_rate' => $this->input->post('hourly_rate') ?? '',
                    'milestone' => $this->input->post('milestone') ?? '',
                    'startdate' => $this->input->post('startdate'),
                    'duedate' => $this->input->post('duedate') ?? '',
                    'priority' => $this->input->post('priority'),
                    'is_public' => $this->input->post('is_public') ?? '',
                    'billable' => $this->input->post('billable') ?? '',
                    'repeat_every' => $this->input->post('repeat_every') ?? '',
                    'repeat_every_custom' => $this->input->post('repeat_every_custom') ?? '',
                    'repeat_type_custom' => $this->input->post('repeat_type_custom') ?? '',
                    'cycles' => $this->input->post('cycles') ?? '',
                    'rel_type' => $this->input->post('rel_type') ?? '',
                    'rel_id' => $this->input->post('rel_id') ?? '',
                    'tags' => $this->input->post('tags') ?? '',
                    'addedfrom' => $this->staffInfo['data']->staff_id,
                    'description' => $this->input->post('description') ?? '',
                    'assignees' => !empty($this->input->post('assignees'))
    ? explode(',', $this->input->post('assignees'))
    : [],

'followers' => !empty($this->input->post('followers'))
    ? explode(',', $this->input->post('followers'))
    : [],
                ];
                log_message('error', 'TASK API REQUEST DATA => ' . json_encode($data));

/**
 * OPTIONAL: LOG RAW POST
 */
log_message('error', 'RAW POST => ' . json_encode($_POST));
                $this->load->model('tasks_model');
                $success = $this->tasks_model->add($data);
                if ($success) {
                    $this->response(['message' => _l('task_added_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('task_add_failed')], RestController::HTTP_NOT_FOUND);
                }
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function tasks_put()
    {
        if (staff_cant('edit', 'tasks', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
        
        try {
            
            if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_BAD_REQUEST);
            }
            
            $taskID = $this->get('id');
            $this->load->model('tasks_model');
            $task = $this->tasks_model->get($taskID);
            
            if (is_object($task)) {
                $data = array();
                parse_str(file_get_contents('php://input'), $data);
                $success = $this->tasks_model->update($data, $taskID);
                if ($success) {
                    $this->response(['message' => _l('task_updated_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('task_update_failed')], RestController::HTTP_NOT_FOUND);
                }
            } else {
                $this->response(['message' => _l('invalid_task_id')], RestController::HTTP_NOT_FOUND);
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function tasks_delete()
    {
        if (staff_cant('delete', 'tasks', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
        
        $taskID = $this->get('id');
        
        $this->load->model('tasks_model');
        $task = $this->tasks_model->get($taskID);
        if (is_object($task)) {
            $success = $this->tasks_model->delete_task($taskID);
            if ($success) {
                $this->response(['message' => _l('task_deleted_successfully')], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('task_delete_failed')], RestController::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(['message' => _l('invalid_task_id')], RestController::HTTP_NOT_FOUND);
        }
    }
}