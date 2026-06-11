<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__ . '/RestController.php';

use FlutexAdminApi\RestController;

class Expenses extends RestController
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

        $this->load->model('expenses_model');
    }

    // ------------------------------------------------
    // GET EXPENSE LIST
    // GET /expense/list?status=pending
    // ------------------------------------------------
 public function list_get()
{
    try {
        $status  = $this->get('status'); // pending | approved | rejected | null
        $staffId = $this->staffInfo['data']->staff_id;

        // 🔹 Log API hit
        log_message('info', 'EXPENSE LIST API CALLED | staff_id=' . $staffId . ' | status=' . ($status ?? 'ALL'));

        // Always restrict to logged-in staff
       $where = [db_prefix() . 'expenses.addedfrom' => $staffId];


        // Apply status filter
        if ($status === 'approved') {
            $where['approved'] = 1;
            $where['voided']   = 0;

        } elseif ($status === 'rejected') {
            $where['voided']   = 1;

        } elseif ($status === 'pending') {
            $where['approved'] = 0;
            $where['voided']   = 0;
        }

        // 🔹 Log final WHERE condition
        log_message('debug', 'EXPENSE LIST WHERE: ' . json_encode($where));

        $expenses = $this->expenses_model->get('', $where);

        // 🔹 Log result count
        log_message(
            'info',
            'EXPENSE LIST RESULT | staff_id=' . $staffId . ' | count=' . count($expenses)
        );

        $this->response([
            'status' => true,
            'data'   => $expenses ?: []
        ], RestController::HTTP_OK);

    } catch (\Throwable $e) {

        // 🔴 Log exception with trace
        log_message('error', 'EXPENSE LIST ERROR: ' . $e->getMessage());
        log_message('error', $e->getTraceAsString());

        $this->response([
            'status'  => false,
            'message' => 'Something went wrong'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}



    // ------------------------------------------------
    // GET EXPENSE DETAIL
    // GET /expense/detail/{id}
    // ------------------------------------------------
    public function detail_get($id)
    {
        $expense = $this->expenses_model->get($id);

        if (!$expense) {
            $this->response([
                'status' => false,
                'message' => 'Expense not found'
            ], RestController::HTTP_NOT_FOUND);
        }

        $this->response([
            'status' => true,
            'data'   => $expense
        ], RestController::HTTP_OK);
    }

    // ------------------------------------------------
    // ADD EXPENSE
    // POST /expense/apply
    // ------------------------------------------------
    public function apply1_post()
    {
        if (staff_cant('create', 'expenses', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => 'Access denied'], RestController::HTTP_FORBIDDEN);
        }

        $this->form_validation->set_rules('category', 'Category', 'required|numeric');
        $this->form_validation->set_rules('amount', 'Amount', 'required|decimal');
        $this->form_validation->set_rules('date', 'Date', 'required');

        if (!$this->form_validation->run()) {
            $this->response([
                'message' => strip_tags(validation_errors())
            ], RestController::HTTP_BAD_REQUEST);
        }

        $data = $this->input->post();
        $data['addedfrom'] = $this->staffInfo['data']->staff_id;
        $data['status']    = 'pending';

        $id = $this->expenses_model->add($data);

        if ($id) {
            $this->response([
                'status' => true,
                'message' => 'Expense added successfully',
                'expense_id' => $id
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Failed to add expense'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
public function apply_post()
{
    // Permission check
    if (staff_cant('create', 'expenses', staff_id: $this->staffInfo['data']->staff_id)) {
        $this->response(
            ['message' => 'Access denied'],
            RestController::HTTP_FORBIDDEN
        );
    }

    // Validation
    $this->form_validation->set_rules('category', 'Category', 'required|numeric');
    $this->form_validation->set_rules('amount', 'Amount', 'required|decimal');
    $this->form_validation->set_rules('date', 'Date', 'required');

    if (!$this->form_validation->run()) {
        $this->response([
            'status'  => false,
            'message' => strip_tags(validation_errors())
        ], RestController::HTTP_BAD_REQUEST);
    }

    // Collect data
    $data = $this->input->post();
unset($data['addedfrom']);

$data['addedfrom'] = $this->staffInfo['data']->staff_id;
$data['clientid']  = !empty($data['clientid']) ? (int)$data['clientid'] : 0;
$data['currency']  = get_base_currency()->id;
$data['dateadded'] = date('Y-m-d H:i:s');
$data['approved']  = 0;
$data['voided']    = 0;
    /* -------------------------------------------------
       FORCE STAFF CONTEXT (WHO RAISED EXPENSE)
       ------------------------------------------------- */
    $data['addedfrom'] = $this->staffInfo['data']->staff_id; // 👈 who applied
                                   // internal expense
   
    $data['dateadded'] = date('Y-m-d H:i:s');

    /* -------------------------------------------------
       FORCE PENDING STATUS
       ------------------------------------------------- */
    $data['approved'] = 0;
    $data['voided']   = 0;

    // Create expense
    $expenseId = $this->expenses_model->add($data);

    if ($expenseId) {
        $this->response([
            'status'      => true,
            'message'     => 'Expense request submitted successfully',
            'expense_id'  => $expenseId,
            'applied_by'  => $this->staffInfo['data']->staff_id
        ], RestController::HTTP_OK);
    }

    $this->response([
        'status'  => false,
        'message' => 'Failed to add expense'
    ], RestController::HTTP_INTERNAL_ERROR);
}

    // ------------------------------------------------
    // UPDATE EXPENSE
    // PUT /expense/update?id=1
    // ------------------------------------------------
    public function update_put()
    {
        $id = $this->get('id');

        if (!$id) {
            $this->response(['message' => 'Expense ID required'], RestController::HTTP_BAD_REQUEST);
        }

        parse_str(file_get_contents("php://input"), $data);

        if ($this->expenses_model->update($data, $id)) {
            $this->response([
                'status' => true,
                'message' => 'Expense updated'
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Update failed'
        ], RestController::HTTP_BAD_REQUEST);
    }

    // ------------------------------------------------
    // DELETE EXPENSE
    // DELETE /expense/delete?id=1
    // ------------------------------------------------
    public function delete_delete()
    {
        $id = $this->get('id');

        if (!$id) {
            $this->response(['message' => 'Expense ID required'], RestController::HTTP_BAD_REQUEST);
        }

        $result = $this->expenses_model->delete($id);

        if ($result === true) {
            $this->response([
                'status' => true,
                'message' => 'Expense deleted'
            ], RestController::HTTP_OK);
        }

        $this->response([
            'status' => false,
            'message' => 'Delete failed'
        ], RestController::HTTP_BAD_REQUEST);
    }

    // ------------------------------------------------
    // APPROVE / REJECT EXPENSE (Manager)
    // POST /expense/manager/action
    // ------------------------------------------------
    public function manager_action_post()
{
    if (staff_cant('approve', 'expenses', $this->staffInfo['data']->staff_id)) {
        $this->response(
            ['message' => 'Access denied'],
            RestController::HTTP_FORBIDDEN
        );
    }

    $expenseId = $this->post('expense_id');
    $action    = $this->post('action'); // approve | reject
    $remark    = $this->post('remark');

    if (!$expenseId || !in_array($action, ['approve', 'reject'])) {
        $this->response([
            'status'  => false,
            'message' => 'Invalid request'
        ], RestController::HTTP_BAD_REQUEST);
    }

    // ---------------- ACTION MAP ----------------
    if ($action === 'approve') {
        $update = [
            'approved'     => 1,
            'voided'       => 0,
            'status'       => 1, // approved
            'approved_by'  => $this->staffInfo['data']->staff_id,
            'approved_on'  => date('Y-m-d H:i:s'),
            'adminnote'    => $remark,
            'reason_for_void' => NULL,
        ];
    } else { // reject
        $update = [
            'approved'        => 0,
            'voided'          => 1,
            'status'          => 2, // rejected
            'approved_by'     => $this->staffInfo['data']->staff_id,
            'approved_on'     => date('Y-m-d H:i:s'),
            'reason_for_void' => $remark,
            'adminnote'       => $remark,
        ];
    }

    $this->db->where('id', $expenseId);
    $this->db->update(db_prefix() . 'expenses', $update);

    $this->response([
        'status'  => true,
        'message' => 'Expense ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully'
    ], RestController::HTTP_OK);
}

    // ------------------------------------------------
// GET EXPENSE CATEGORIES
// GET /expense/categories
// ------------------------------------------------
public function categories_get()
{
    try {

        // Permission check (optional but recommended)
        if (staff_cant('view', 'expenses', $this->staffInfo['data']->staff_id)
            && staff_cant('view_own', 'expenses', $this->staffInfo['data']->staff_id)) {

            $this->response([
                'status'  => false,
                'message' => _l('not_permission_to_perform_this_action')
            ], RestController::HTTP_FORBIDDEN);
        }

        $categories = $this->expenses_model->get_category();

        $this->response([
            'status' => true,
            'data'   => $categories ?: []
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {
        $this->response([
            'status'  => false,
            'message' => _l('something_went_wrong')
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

}
