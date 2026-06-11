<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';

use FlutexAdminApi\RestController;

class Dashboard extends RestController
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
    }
    
    public function dashboard_get()
    {
        log_message('error', 'DASHBOARD API HIT');
        // DOTONE Logo
        $perfex_logo = get_option('company_logo');
        $perfex_logo_dark = get_option('company_logo_dark');
        
        // Staff Information
        $staffID = $this->staffInfo['data']->staff_id;
        log_message('error', 'Staff ID: ' . $staffID);
        $staff = $this->db->where('staffid', $staffID)->get(db_prefix() . 'staff')->row();
        log_message('error', 'Staff Data: ' . json_encode($staff));
        if (!$staff) {

            log_message('error', 'Staff Not Found');

            return $this->response([
                'message' => 'Staff not found'
            ], 404);
        }
        $staff_data = array(
            'id'=> $staff->staffid,
            'email' => $staff->email,
            'firstname' => $staff->firstname,
            'lastname' => $staff->lastname,
            'phonenumber' => $staff->phonenumber,
            'profile_image' => staff_profile_image_url($staff->staffid),
        );
        
        // Dashboard Overview Data << START >>
        $total_invoices = total_rows(db_prefix() . 'invoices', 'status NOT IN (5,6)' . (!staff_can('view', 'invoices', $staffID) ? ' AND ' . get_invoices_where_sql_for_staff($staffID) : ''));
        $total_invoices_awaiting_payment = total_rows(db_prefix() . 'invoices', 'status NOT IN (2,5,6)' . (!staff_can('view', 'invoices', $staffID) ? ' AND ' . get_invoices_where_sql_for_staff($staffID) : ''));
        $percent_total_invoices_awaiting_payment = ($total_invoices > 0 ? number_format(($total_invoices_awaiting_payment * 100) / $total_invoices, 2) : 0);

        $where = '';
        if (!is_admin()) {
            $where .= '(addedfrom = ' . $staffID . ' OR assigned = ' . $staffID . ')';
        }
        
        $total_leads = total_rows(db_prefix() . 'leads', ($where == '' ? 'junk=0' : $where .= ' AND junk =0'));
        if ($where == '') {
            $where .= 'status=1';
        } else {
            $where .= ' AND status =1';
        }
        $total_leads_converted = total_rows(db_prefix() . 'leads', $where);
        $percent_total_leads_converted = ($total_leads > 0 ? number_format(($total_leads_converted * 100) / $total_leads, 2) : 0);

        $_where = '';
        $project_status = get_project_status_by_id(2);
        if (!staff_can('view', 'projects', $staffID)) {
            $_where = 'id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . $staffID . ')';
        }
        $total_projects = total_rows(db_prefix() . 'projects', $_where);
        $where = ($_where == '' ? '' : $_where . ' AND ') . 'status = 2';
        $total_projects_in_progress = total_rows(db_prefix() . 'projects', $where);
        $percent_in_progress_projects = ($total_projects > 0 ? number_format(($total_projects_in_progress * 100) / $total_projects, 2) : 0);

        $_where = '';
        if (!staff_can('view', 'tasks', $staffID)) {
            $_where = db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . $staffID . ')';
        }
        $total_tasks = total_rows(db_prefix() . 'tasks', $_where);
        $where = ($_where == '' ? '' : $_where . ' AND ') . 'status != ' . Tasks_model::STATUS_COMPLETE;
        $total_not_finished_tasks = total_rows(db_prefix() . 'tasks', $where);
        $percent_not_finished_tasks = ($total_tasks > 0 ? number_format(($total_not_finished_tasks * 100) / $total_tasks, 2) : 0);
        // Dashboard Overview Data << END >>
        
        //Menu Items
      /* $menu_items = [

    // Admin can see everything
    'customers' => is_admin($staffID) ? true :
        (
            staff_can('view', 'customers', $staffID) ||
            have_assigned_customers() ||
            staff_can('create', 'customers', $staffID)
        ),

    'proposals' => is_admin($staffID) ? true :
        (
            staff_can('view', 'proposals', $staffID) ||
            staff_can('view_own', 'proposals', $staffID) ||
            (
                staff_has_assigned_proposals() &&
                get_option('allow_staff_view_proposals_assigned') == 1
            )
        ),

    'estimates' => is_admin($staffID) ? true :
        (
            staff_can('view', 'estimates', $staffID) ||
            staff_can('view_own', 'estimates', $staffID) ||
            (
                staff_has_assigned_estimates() &&
                get_option('allow_staff_view_estimates_assigned') == 1
            )
        ),

    'invoices' => is_admin($staffID) ? true :
        (
            staff_can('view', 'invoices', $staffID) ||
            staff_can('view_own', 'invoices', $staffID) ||
            (
                staff_has_assigned_invoices() &&
                get_option('allow_staff_view_invoices_assigned') == 1
            )
        ),

    'payments' => is_admin($staffID) ? true :
        (
            staff_can('view', 'payments', $staffID) ||
            staff_can('view_own', 'payments', $staffID) ||
            (
                get_option('allow_staff_view_invoices_assigned') == 1 &&
                staff_has_assigned_invoices()
            )
        ),

    'credit_notes' => is_admin($staffID) ? true :
        (
            staff_can('view', 'credit_notes', $staffID) ||
            staff_can('view_own', 'credit_notes', $staffID)
        ),

    'items' => is_admin($staffID) ? true :
        staff_can('view', 'items', $staffID),

    'subscriptions' => is_admin($staffID) ? true :
        (
            staff_can('view', 'subscriptions', $staffID) ||
            staff_can('view_own', 'subscriptions', $staffID)
        ),

    'expenses' => is_admin($staffID) ? true :
        (
            staff_can('view', 'expenses', $staffID) ||
            staff_can('view_own', 'expenses', $staffID)
        ),

    'contracts' => is_admin($staffID) ? true :
        (
            staff_can('view', 'contracts', $staffID) ||
            staff_can('view_own', 'contracts', $staffID)
        ),

    'projects' => is_admin($staffID) ? true :
        (
            staff_can('view', 'projects', $staffID) ||
            staff_can('view_own', 'projects', $staffID) ||
            total_rows(db_prefix() . 'project_members', [
                'staff_id' => $staffID
            ]) > 0
        ),

    'tasks' => is_admin($staffID) ? true :
        (
            staff_can('view', 'tasks', $staffID) ||
            total_rows(db_prefix() . 'task_assigned', [
                'staffid' => $staffID
            ]) > 0
        ),

    'tickets' => is_admin($staffID) ? true :
        (
            (!is_staff_member($staffID) &&
            get_option('access_tickets_to_none_staff_members') == 1) ||
            is_staff_member($staffID)
        ),

    'leads' => is_admin($staffID) ? true :
        is_staff_member($staffID),

    'staff' => is_admin($staffID) ? true :
        staff_can('view', 'staff', $staffID),
];*/
$menu_items = [

    // ================= MAIN MODULES =================
    'crm' => is_admin($staffID) ? true :
        (
            staff_can('view', 'customers', $staffID) ||
            staff_can('view', 'proposals', $staffID) ||
            is_staff_member($staffID)
        ),

    'sales' => is_admin($staffID) ? true :
        (
            staff_can('view', 'invoices', $staffID) ||
            staff_can('view', 'estimates', $staffID)
        ),

    'purchase' => is_admin($staffID) ? true :
        (
            staff_can('view', 'purchase_orders', $staffID) ||
            staff_can('view', 'purchase_items', $staffID)
        ),

    'inventory' => is_admin($staffID) ? true :
        (
            staff_can('view', 'warehouse_item', $staffID) ||
            staff_can('view', 'items', $staffID)
        ),

    'projects_service' => is_admin($staffID) ? true :
        (
            staff_can('view', 'projects', $staffID) ||
            staff_can('view', 'tasks', $staffID)
        ),

    'hr_payroll' => is_admin($staffID) ? true :
        (
            staff_can('view', 'attendance_management', $staffID) ||
            staff_can('view', 'leave_management', $staffID) ||
            staff_can('view_ess', 'hrms', $staffID)
        ),

    'gst_accounts' => is_admin($staffID) ? true :
        (
            staff_can('view', 'payments', $staffID) ||
            staff_can('view', 'expenses', $staffID)
        ),

    'manufacturing' => is_admin($staffID) ? true :
        (
            staff_can('view', 'manufacturing', $staffID)
        ),

    'administration' => is_admin($staffID),

    // ================= CRM =================
    'customers' => is_admin($staffID) ? true :
        (
            staff_can('view', 'customers', $staffID) ||
            have_assigned_customers() ||
            staff_can('create', 'customers', $staffID)
        ),

    'proposals' => is_admin($staffID) ? true :
        (
            staff_can('view', 'proposals', $staffID) ||
            staff_can('view_own', 'proposals', $staffID) ||
            (
                staff_has_assigned_proposals() &&
                get_option('allow_staff_view_proposals_assigned') == 1
            )
        ),

    'contracts' => is_admin($staffID) ? true :
        (
            staff_can('view', 'contracts', $staffID) ||
            staff_can('view_own', 'contracts', $staffID)
        ),

    'leads' => is_admin($staffID) ? true :
        is_staff_member($staffID),

    // ================= SALES =================
    'estimates' => is_admin($staffID) ? true :
        (
            staff_can('view', 'estimates', $staffID) ||
            staff_can('view_own', 'estimates', $staffID) ||
            (
                staff_has_assigned_estimates() &&
                get_option('allow_staff_view_estimates_assigned') == 1
            )
        ),

    'invoices' => is_admin($staffID) ? true :
        (
            staff_can('view', 'invoices', $staffID) ||
            staff_can('view_own', 'invoices', $staffID) ||
            (
                staff_has_assigned_invoices() &&
                get_option('allow_staff_view_invoices_assigned') == 1
            )
        ),

    'payments' => is_admin($staffID) ? true :
        (
            staff_can('view', 'payments', $staffID) ||
            staff_can('view_own', 'payments', $staffID) ||
            (
                get_option('allow_staff_view_invoices_assigned') == 1 &&
                staff_has_assigned_invoices()
            )
        ),

    'credit_notes' => is_admin($staffID) ? true :
        (
            staff_can('view', 'credit_notes', $staffID) ||
            staff_can('view_own', 'credit_notes', $staffID)
        ),

    'sales_return' => is_admin($staffID) ? true :
        (
            staff_can('view', 'invoices', $staffID)
        ),

    'delivery_challan' => is_admin($staffID) ? true :
        (
            staff_can('view', 'wh_internal_delivery_note', $staffID)
        ),

    'customer_outstanding' => is_admin($staffID) ? true :
        (
            staff_can('view', 'invoices', $staffID)
        ),

    // ================= PURCHASE =================
    'purchase_orders' => is_admin($staffID) ? true :
        (
            staff_can('view', 'purchase_orders', $staffID)
        ),

    'purchase_request' => is_admin($staffID) ? true :
        (
            staff_can('view', 'purchase_orders', $staffID)
        ),

    'vendors' => is_admin($staffID) ? true :
        (
            staff_can('view', 'purchase_items', $staffID)
        ),

    'grn' => is_admin($staffID) ? true :
        (
            staff_can('view', 'wh_receipt_return_order', $staffID)
        ),

    'purchase_bills' => is_admin($staffID) ? true :
        (
            staff_can('view', 'purchase_orders', $staffID)
        ),

    'debit_note' => is_admin($staffID) ? true :
        (
            staff_can('view', 'purchase_orders', $staffID)
        ),

    'purchase_return' => is_admin($staffID) ? true :
        (
            staff_can('view', 'wh_receipt_return_order', $staffID)
        ),

    'vendor_payments' => is_admin($staffID) ? true :
        (
            staff_can('view', 'payments', $staffID)
        ),

    // ================= INVENTORY =================
    'items' => is_admin($staffID) ? true :
        staff_can('view', 'items', $staffID),

    'warehouse' => is_admin($staffID) ? true :
        (
            staff_can('view', 'warehouse_item', $staffID)
        ),

    'godown' => is_admin($staffID) ? true :
        (
            staff_can('view', 'warehouse_item', $staffID)
        ),

    'delivery_voucher' => is_admin($staffID) ? true :
        (
            staff_can('view', 'wh_internal_delivery_note', $staffID)
        ),

    'receiving_voucher' => is_admin($staffID) ? true :
        (
            staff_can('view', 'wh_receipt_return_order', $staffID)
        ),

    'packing_list' => is_admin($staffID) ? true :
        (
            staff_can('view', 'wh_packing_list', $staffID)
        ),

    'stock_transfer' => is_admin($staffID) ? true :
        (
            staff_can('view', 'warehouse_item', $staffID)
        ),

    'warehouse_reports' => is_admin($staffID) ? true :
        (
            staff_can('view', 'wh_report', $staffID)
        ),

    'warehouse_history' => is_admin($staffID) ? true :
        (
            staff_can('view', 'wh_warehouse_history', $staffID)
        ),

    'warehouse_settings' => is_admin($staffID) ? true :
        (
            staff_can('view', 'wh_setting', $staffID)
        ),

    // ================= PROJECTS =================
    'projects' => is_admin($staffID) ? true :
        (
            staff_can('view', 'projects', $staffID) ||
            staff_can('view_own', 'projects', $staffID) ||
            total_rows(db_prefix() . 'project_members', [
                'staff_id' => $staffID
            ]) > 0
        ),

    'tasks' => is_admin($staffID) ? true :
        (
            staff_can('view', 'tasks', $staffID) ||
            total_rows(db_prefix() . 'task_assigned', [
                'staffid' => $staffID
            ]) > 0
        ),

    'tickets' => is_admin($staffID) ? true :
        (
            (!is_staff_member($staffID) &&
            get_option('access_tickets_to_none_staff_members') == 1) ||
            is_staff_member($staffID)
        ),

    'plan_visits' => is_admin($staffID) ? true :
        (
            staff_can('view', 'route_management', $staffID)
        ),

    // ================= HRMS =================
    'employees' => is_admin($staffID) ? true :
        (
            staff_can('view_ess', 'hrms', $staffID)
        ),

    'attendance' => is_admin($staffID) ? true :
        (
            staff_can('view', 'attendance_management', $staffID)
        ),

    'leaves' => is_admin($staffID) ? true :
        (
            staff_can('view', 'leave_management', $staffID)
        ),

    'payroll' => is_admin($staffID) ? true :
        (
            staff_can('view_ess', 'hrms', $staffID)
        ),

    'salary_slip' => is_admin($staffID) ? true :
        (
            staff_can('view_ess', 'hrms', $staffID)
        ),

    'advance_salary' => is_admin($staffID) ? true :
        (
            staff_can('view_ess', 'hrms', $staffID)
        ),

    'shift_management' => is_admin($staffID) ? true :
        (
            staff_can('view', 'table_shiftwork_management', $staffID)
        ),

    'departments' => is_admin($staffID) ? true :
        (
            staff_can('view_ess', 'hrms', $staffID)
        ),

    'designations' => is_admin($staffID) ? true :
        (
            staff_can('view_ess', 'hrms', $staffID)
        ),

    'expenses' => is_admin($staffID) ? true :
        (
            staff_can('view', 'expenses', $staffID) ||
            staff_can('view_own', 'expenses', $staffID)
        ),

    // ================= GST =================
    'ledger' => is_admin($staffID) ? true :
        (
            staff_can('view', 'payments', $staffID)
        ),

    'cash_bank' => is_admin($staffID) ? true :
        (
            staff_can('view', 'payments', $staffID)
        ),

    'journal_entries' => is_admin($staffID) ? true :
        (
            staff_can('view', 'payments', $staffID)
        ),

    'profit_loss' => is_admin($staffID) ? true :
        (
            staff_can('view', 'report_management', $staffID)
        ),

    'balance_sheet' => is_admin($staffID) ? true :
        (
            staff_can('view', 'report_management', $staffID)
        ),

    // ================= MANUFACTURING =================
    'production_orders' => is_admin($staffID) ? true :
        (
            staff_can('view', 'manufacturing_orders', $staffID)
        ),

    'bom' => is_admin($staffID) ? true :
        (
            staff_can('view', 'bill_of_material', $staffID)
        ),

    'job_work' => is_admin($staffID) ? true :
        (
            staff_can('view', 'work_order', $staffID)
        ),

    'machines' => is_admin($staffID) ? true :
        (
            staff_can('view', 'work_centers', $staffID)
        ),

    'maintenance' => is_admin($staffID) ? true :
        (
            staff_can('view', 'work_centers', $staffID)
        ),
         'workorders' => is_admin($staffID) ? true :
        (
            staff_can('view', 'work_order', $staffID)
        ),
          'routing' => is_admin($staffID) ? true :
        (
            staff_can('view', 'routing', $staffID)

        ),
        

    // ================= ADMIN =================
    'reports' => is_admin($staffID) ? true :
        (
            staff_can('view', 'report_management', $staffID)
        ),

    'alerts' => is_admin($staffID),

    'approvals' => is_admin($staffID),

    'settings' => is_admin($staffID),

    'staff' => is_admin($staffID) ? true :
        staff_can('view', 'staff', $staffID),
];
        log_message('error', 'Before Dashboard Response');
        $this->response([
            'message' => _l('data_retrieved_successfully'),
            'overview' => [
                'perfex_logo' => ($perfex_logo != '' ? base_url('uploads/company/' . $perfex_logo) : ''),
                'perfex_logo_dark' => ($perfex_logo_dark != '' ? base_url('uploads/company/' . $perfex_logo_dark) : ''),
                'total_invoices' => strval($total_invoices),
                'invoices_awaiting_payment_total' => strval($total_invoices_awaiting_payment),
                'invoices_awaiting_payment_percent' => strval($percent_total_invoices_awaiting_payment),
                'total_leads' => strval($total_leads),
                'leads_converted_total' => strval($total_leads_converted),
                'leads_converted_percent' => strval($percent_total_leads_converted),
                'total_projects' => strval($total_projects),
                'projects_in_progress_total' => strval($total_projects_in_progress),
                'projects_in_progress_percent' => strval($percent_in_progress_projects),
                'total_tasks' => strval($total_tasks),
                'tasks_not_finished_total' => strval($total_not_finished_tasks),
                'tasks_not_finished_percent' => strval($percent_not_finished_tasks)
            ],
            'data' => [
                'invoices'  => $this->invoices_summary(),
                'estimates' => $this->estimates_summary(),
                'proposals' => $this->proposals_summary(),
                'projects'  => $this->projects_summary(),
                'tasks'     => $this->tasks_summary(),
                'customers' => $this->customers_summary(),
                'leads'     => $this->leads_summary(),
                'tickets'   => $this->tickets_summary(),
            ],
            'staff' => $staff_data,
            'menu_items' => $menu_items
        ], RestController::HTTP_OK);
    }
public function clientdashboard1_get()
{
    log_message('error', '--- CLIENT DASHBOARD API CALLED ---');

    // 🔐 Get logged-in user
    $auth = isAuthorized();
    log_message('error', 'Auth Response: ' . json_encode($auth));

    if (!isset($auth['status']) || !$auth['status']) {
        log_message('error', 'Authorization failed');
        $this->response($auth['response'], $auth['response_code']);
    }

    $user = $auth['data'];

    log_message('error', 'User Type: ' . $user->type);

    // ✅ Ensure it's client
    if ($user->type != 'client') {
        log_message('error', 'Unauthorized access - Not a client');
        $this->response([
            'status' => false,
            'message' => 'Unauthorized (Client only)'
        ], RestController::HTTP_UNAUTHORIZED);
    }

    $client_id = $user->client_id;
    log_message('error', 'Client ID: ' . $client_id);

    // =============================
    // 👤 CLIENT INFO
    // =============================
    $client = $this->db
        ->where('userid', $client_id)
        ->get(db_prefix() . 'clients')
        ->row();

    log_message('error', 'Client Data: ' . json_encode($client));

    if (!$client) {
        log_message('error', 'Client not found in DB');
    }

    $contact = $this->db
        ->where('userid', $client_id)
        ->get(db_prefix() . 'contacts')
        ->row();

    log_message('error', 'Contact Data: ' . json_encode($contact));

    // =============================
    // 📊 OVERVIEW
    // =============================
    $total_orders = total_rows(db_prefix().'estimates', 'clientid='.$client_id);
    $total_invoices = total_rows(db_prefix().'invoices', 'clientid='.$client_id);

    $pending_invoices = total_rows(
        db_prefix().'invoices',
        'clientid='.$client_id.' AND status NOT IN (2,5)'
    );

    log_message('error', 'Overview: Orders='.$total_orders.' Invoices='.$total_invoices.' Pending='.$pending_invoices);

    // =============================
    // 📦 RECENT SALES ORDERS
    // =============================
    $this->db->where('clientid', $client_id);
    $this->db->order_by('id', 'DESC');
    $this->db->limit(5);
    $orders = $this->db->get(db_prefix().'estimates')->result_array();

    log_message('error', 'Orders Count: ' . count($orders));

    // =============================
    // 💰 RECENT INVOICES
    // =============================
    $this->db->where('clientid', $client_id);
    $this->db->order_by('id', 'DESC');
    $this->db->limit(5);
    $invoices = $this->db->get(db_prefix().'invoices')->result_array();

    log_message('error', 'Invoices Count: ' . count($invoices));

    // =============================
    // 🚀 RESPONSE
    // =============================
    $response = [
        'status' => true,
        'message' => 'Client dashboard data',
        'overview' => [
            'total_orders' => strval($total_orders),
            'total_invoices' => strval($total_invoices),
            'pending_invoices' => strval($pending_invoices),
        ],
        'data' => [
            'orders' => $orders,
            'invoices' => $invoices,
        ],
        'client' => [
            'id' => $client->userid ?? '',
            'company' => $client->company ?? '',
            'email' => $contact->email ?? '',
        ]
    ];

    log_message('error', 'Final Response: ' . json_encode($response));

    $this->response($response, RestController::HTTP_OK);
}
public function clientdashboard_get()
{
    log_message('error', '--- CLIENT DASHBOARD API CALLED ---');

    // =============================
    // 🔐 AUTH
    // =============================
    $auth = isAuthorized();

    if (!isset($auth['status']) || !$auth['status']) {
        $this->response($auth['response'], $auth['response_code']);
    }

    $user = $auth['data'];

    if ($user->type != 'client') {
        $this->response([
            'status' => false,
            'message' => 'Unauthorized (Client only)'
        ], RestController::HTTP_UNAUTHORIZED);
    }

    $client_id = $user->client_id;

    // =============================
    // 🏢 COMPANY LOGO
    // =============================
    $logo = get_option('company_logo');
    $logo_dark = get_option('company_logo_dark');

    $logo_url = $logo ? base_url('uploads/company/' . $logo) : '';
    $logo_dark_url = $logo_dark ? base_url('uploads/company/' . $logo_dark) : '';

    // =============================
    // 👤 CLIENT + GROUP + PARENT
    // =============================
    $this->db->select("
        c.userid,
        c.company,
        ct.email,

        GROUP_CONCAT(DISTINCT g.name) as groups,
        GROUP_CONCAT(DISTINCT g.id) as group_ids,

        parent_client.userid as parent_id,
        parent_client.company as parent_company,

        GROUP_CONCAT(DISTINCT parent_g.name) as parent_groups,
        GROUP_CONCAT(DISTINCT parent_g.id) as parent_group_ids
    ");

    $this->db->from(db_prefix().'clients as c');

    // Primary contact
    $this->db->join(
        db_prefix().'contacts as ct',
        'ct.userid = c.userid AND ct.is_primary = 1',
        'left'
    );

    // Client groups
    $this->db->join(
        db_prefix().'customer_groups as cg',
        'cg.customer_id = c.userid',
        'left'
    );

    $this->db->join(
        db_prefix().'customers_groups as g',
        'g.id = cg.groupid',
        'left'
    );

    // Parent client
    $this->db->join(
        db_prefix().'clients as parent_client',
        'parent_client.userid = c.parent_partner_id',
        'left'
    );

    // Parent groups
    $this->db->join(
        db_prefix().'customer_groups as parent_cg',
        'parent_cg.customer_id = parent_client.userid',
        'left'
    );

    $this->db->join(
        db_prefix().'customers_groups as parent_g',
        'parent_g.id = parent_cg.groupid',
        'left'
    );

    $this->db->where('c.userid', $client_id);
    $this->db->group_by('c.userid');

    $client = $this->db->get()->row();

    if (!$client) {
        $this->response([
            'status' => false,
            'message' => 'Client not found'
        ], RestController::HTTP_NOT_FOUND);
    }

    // =============================
    // 📊 OVERVIEW
    // =============================
    $total_orders = total_rows(db_prefix().'estimates', 'clientid='.$client_id);
    $total_invoices = total_rows(db_prefix().'invoices', 'clientid='.$client_id);

    $pending_invoices = total_rows(
        db_prefix().'invoices',
        'clientid='.$client_id.' AND status NOT IN (2,5)'
    );

    // =============================
    // 📦 RECENT ORDERS
    // =============================
    $this->db->where('clientid', $client_id);
    $this->db->order_by('id', 'DESC');
    $this->db->limit(5);
    $orders_raw = $this->db->get(db_prefix().'estimates')->result_array();

    $orders = [];
    foreach ($orders_raw as $o) {
        $orders[] = [
            'id' => $o['id'],
            'order_no' => $o['prefix'] . $o['number'],
            'date' => $o['date'],
            'total' => $o['total'],
            'status' => $o['status'],
            'status_label' => $this->order_status($o['status']),
        ];
    }

    // =============================
    // 💰 RECENT INVOICES
    // =============================
    $this->db->where('clientid', $client_id);
    $this->db->order_by('id', 'DESC');
    $this->db->limit(5);
    $invoices_raw = $this->db->get(db_prefix().'invoices')->result_array();

    $invoices = [];
    foreach ($invoices_raw as $i) {
        $invoices[] = [
            'id' => $i['id'],
            'invoice_no' => $i['prefix'] . $i['number'],
            'date' => $i['date'],
            'due_date' => $i['duedate'],
            'total' => $i['total'],
            'status' => $i['status'],
            'status_label' => $this->invoice_status($i['status']),
        ];
    }

    // =============================
    // 🚀 RESPONSE
    // =============================
    $response = [
        'status' => true,
        'message' => 'Client dashboard data',

        'overview' => [
            'perfex_logo' => $logo_url,
            'perfex_logo_dark' => $logo_dark_url,

            'total_orders' => strval($total_orders),
            'total_invoices' => strval($total_invoices),
            'pending_invoices' => strval($pending_invoices),
        ],

        'data' => [
            'orders' => $orders,
            'invoices' => $invoices,
        ],

        'client' => [
            'id' => $client->userid ?? '',
            'company' => $client->company ?? '',
            'email' => $client->email ?? '',

            'groups' => $client->groups ?? '',
            'group_ids' => $client->group_ids ?? '',

            'parent' => [
                'id' => $client->parent_id ?? '',
                'company' => $client->parent_company ?? '',
                'groups' => $client->parent_groups ?? '',
                'group_ids' => $client->parent_group_ids ?? '',
            ]
        ]
    ];

    log_message('error', 'Client Dashboard Response: ' . json_encode($response));

    $this->response($response, RestController::HTTP_OK);
}
private function order_status($status)
{
    switch ($status) {
        case 1: return "Draft";
        case 2: return "Sent";
        case 3: return "Accepted";
        case 4: return "Declined";
        default: return "Unknown";
    }
}

private function invoice_status($status)
{
    switch ($status) {
        case 1: return "Unpaid";
        case 2: return "Paid";
        case 3: return "Partially Paid";
        case 4: return "Overdue";
        case 5: return "Cancelled";
        case 6: return "Draft";
        default: return "Unknown";
    }
}
public function overview_get()
    {
        // DOTONE Logo / Name
        $perfex_logo = get_option('company_logo');
        $perfex_logo_dark = get_option('company_logo_dark');
        $perfex_company_name = get_option('companyname');
        
        $this->response([
            'message' => _l('data_retrieved_successfully'),
            'data' => [
                'perfex_logo' => ($perfex_logo != '' ? base_url('uploads/company/' . $perfex_logo) : ''),
                'perfex_logo_dark' => ($perfex_logo_dark != '' ? base_url('uploads/company/' . $perfex_logo_dark) : ''),
                'perfex_company_name' => $perfex_company_name,
            ],
        ], RestController::HTTP_OK);
    }
    
    public function notifications_get($read = false)
    {
        $read     = $read == false ? 0 : 1;
        $total    = 15;
        $staff_id = $this->staffInfo['data']->staff_id;

        $sql = 'SELECT COUNT(*) as total FROM ' . db_prefix() . 'notifications WHERE isread=' . $read . ' AND touserid=' . $staff_id;
        $sql .= ' UNION ALL ';
        $sql .= 'SELECT COUNT(*) as total FROM ' . db_prefix() . 'notifications WHERE isread_inline=' . $read . ' AND touserid=' . $staff_id;

        $res = $this->db->query($sql)->result();

        $total_unread        = $res[0]->total;
        $total_unread_inline = $res[1]->total;

        if ($total_unread > $total) {
            $total = ($total_unread - $total) + $total;
        } elseif ($total_unread_inline > $total) {
            $total = ($total_unread_inline - $total) + $total;
        }

        // In case user is not marking the notifications are read this process may be long because the script will always fetch the total from the not read notifications.
        // In this case we are limiting to 30
        $total = $total > 30 ? 30 : $total;

        $this->db->where('touserid', $staff_id);
        $this->db->limit($total);
        $this->db->order_by('date', 'desc');

        $notifications = $this->db->get(db_prefix() . 'notifications')->result_array();
        
        $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $notifications], RestController::HTTP_OK);
    }
    
    public function invoices_summary()
    {
        $staffID = $this->staffInfo['data']->staff_id;
        if (staff_can('view', 'invoices', $staffID)) {
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

        return null;
    }

    public function estimates_summary()
    {
        $staffID = $this->staffInfo['data']->staff_id;
        if (staff_can('view', 'estimates', $staffID)) {
            // Estimates Overview
            $estimates = [];
            $this->load->model('estimates_model');
            $estimate_statuses = $this->estimates_model->get_statuses();
    
            array_splice($estimate_statuses, 1, 0, 'not_sent');
            foreach ($estimate_statuses as $status) {
                $percent_data = get_estimates_percent_by_status_api($status,$staffID);
                array_push($estimates, [
                    'status' => format_estimate_status($status, '', false),
                    'total' => strval($percent_data['total_by_status']),
                    'percent' => strval($percent_data['percent'])
                ]);
            }
            return $estimates;
        }

        return null;
    }

    public function proposals_summary()
    {
        $staffID = $this->staffInfo['data']->staff_id;
        if (staff_can('view', 'proposals', $staffID)) {
            // Proposals Overview
            $proposals = [];
            $this->load->model('proposals_model');
            $proposal_statuses = $this->proposals_model->get_statuses();
            
            foreach ($proposal_statuses as $status) {
                $percent_data = get_proposals_percent_by_status_api($status,$staffID);
                array_push($proposals, [
                    'status' => format_proposal_status($status, '', false),
                    'total' => strval($percent_data['total_by_status']),
                    'percent' => strval($percent_data['percent'])
                ]);
            }
            return $proposals;
        }

        return null;
    }

    public function projects_summary()
    {
        $staffID = $this->staffInfo['data']->staff_id;
        if (staff_can('view', 'projects', $staffID)) {
            // Projects Overview
            $projects = [];
            $this->load->model('projects_model');
            $project_statuses = $this->projects_model->get_project_statuses();
    
            $_where = '';
            $staffID = $this->staffInfo['data']->staff_id;
            if (staff_cant('view', 'projects', $staffID)) {
                $_where = 'id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . $staffID . ')';
            }
    
            foreach ($project_statuses as $key => $status) {
                $where = ($_where == '' ? '' : $_where . ' AND ') . 'status = ' . $status['id'];
                array_push($projects, [
                    'status' => $status['name'],
                    'total' => strval(total_rows(db_prefix() . 'projects', $where)),
                    'percent' => total_rows(db_prefix() . 'projects', $where) == 0 ? '0' : strval(total_rows(db_prefix() . 'projects', $where) / total_rows(db_prefix() . 'projects') * 100)
                ]);
            }
            return $projects;
        }

        return null;
    }

    public function tasks_summary()
    {
        $staffID = $this->staffInfo['data']->staff_id;
        if (staff_can('view', 'tasks', $staffID)) {
            // Tasks Overview
            $tasks = [];
            $this->load->model('tasks_model');
            $tasks_statuses = $this->tasks_model->get_statuses();
    
            $_where = '';
            $staffID = $this->staffInfo['data']->staff_id;
            if (staff_cant('view', 'tasks', $staffID)) {
                $_where = db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . $staffID . ')';
            }
    
            foreach ($tasks_statuses as $key => $status) {
                $where = ($_where == '' ? '' : $_where . ' AND ') . 'status = ' . $status['id'];
                array_push($tasks, [
                    'status' => $status['name'],
                    'total' => strval(total_rows(db_prefix() . 'tasks', $where)),
                    'percent' => total_rows(db_prefix() . 'tasks', $where) == 0 ? '0' : strval(total_rows(db_prefix() . 'tasks', $where) / total_rows(db_prefix() . 'tasks') * 100)
                ]);
            }
            return $tasks;
        }

        return null;
    }

    public function customers_summary()
    {
        $staffID = $this->staffInfo['data']->staff_id;
        if (staff_can('view', 'customers', $staffID) || have_assigned_customers()) {
            $where_summary = '';
            $staffID = $this->staffInfo['data']->staff_id;
            if (staff_cant('view', 'customers', $staffID)) {
                $where_summary = ' AND userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . $staffID . ')';
            }
            return [
                'customers_total' => strval(total_rows(db_prefix() . 'clients', ($where_summary != '' ? substr($where_summary, 5) : ''))),
                'customers_active' => strval(total_rows(db_prefix() . 'clients', 'active=1' . $where_summary)),
                'customers_inactive' => strval(total_rows(db_prefix() . 'clients', 'active=0' . $where_summary)),
                'contacts_active' => strval(total_rows(db_prefix() . 'contacts', 'active=1' . $where_summary)),
                'contacts_inactive' => strval(total_rows(db_prefix() . 'contacts', 'active=0' . $where_summary)),
                'contacts_last_login' => strval(total_rows(db_prefix() . 'contacts', 'last_login LIKE "' . date('Y-m-d') . '%"' . $where_summary))
            ];
        }

        return null;
    }

    public function leads_summary()
    {
        $staffID = $this->staffInfo['data']->staff_id;
        if (staff_can('view', 'leads', $staffID)) {
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

        return null;
    }

    public function tickets_summary()
    {
        // Tickets Overview
        $tickets = [];
        $this->load->model('tickets_model');
        $tickets_statuses = $this->tickets_model->get_ticket_status();

        foreach ($tickets_statuses as $key => $status) {
            $where = 'status = ' . $status['ticketstatusid'];
            array_push($tickets, [
                'status' => $status['name'],
                'total' => strval(total_rows(db_prefix() . 'tickets', $where)),
                'percent' => total_rows(db_prefix() . 'tickets', $where) == 0 ? '0' : strval(total_rows(db_prefix() . 'tickets', $where) / total_rows(db_prefix() . 'tickets') * 100)
            ]);
        }

        return $tickets;
    }
}