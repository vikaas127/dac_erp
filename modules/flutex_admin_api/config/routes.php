<?php

$method = $_SERVER['REQUEST_METHOD'];

// Authentication
$route['flutex_admin_api/auth/login'] = 'authentication/login';
$route['flutex_admin_api/auth/checkdomain'] = 'authentication/domain_check';
$route['flutex_admin_api/auth/app_version'] = 'authentication/app_version';
// POST: Staff Login Request     
// POST: Staff Login Request 
$route['flutex_admin_api/auth/logout']  = 'profile/logout';                         // POST: Staff Logout Request
$route['flutex_admin_api/auth/forgot-password'] = 'authentication/forgotPassword';         // POST: Request to Reset Staff Password

// Dashboard
$route['flutex_admin_api/dashboard'] = 'dashboard/dashboard';
$route['flutex_admin_api/clientdashboard']='dashboard/clientdashboard';
$route['flutex_admin_api/checkin'] = 'attendance/checkin';
$route['flutex_admin_api/checkout'] = 'attendance/checkout';
$route['flutex_admin_api/storelocation'] = 'attendance/storelocation';
$route['flutex_admin_api/storelocation44'] = 'attendance/storelocation44';
$route['flutex_admin_api/currentstatus'] = 'attendance/currentstatus';
$route['flutex_admin_api/planvisitstoday'] = 'attendance/planvisitstoday';
$route['flutex_admin_api/visitcheckin']  = 'attendance/visitcheckin';
$route['flutex_admin_api/dayactivity']  = 'attendance/dayactivity';
$route['flutex_admin_api/daysummary']  = 'attendance/daysummary';
$route['flutex_admin_api/daypath']  = 'attendance/daypath';
$route['flutex_admin_api/monthly_analytics']  = 'attendance/monthly_analytics';
$route['flutex_admin_api/visitcheckout'] = 'attendance/visitcheckout';
$route['flutex_admin_api/visit_report_summary'] = 'attendance/visit_report_summary';
$route['flutex_admin_api/visit_daily_logs'] = 'attendance/visit_daily_logs';
$route['dotonecrm_api/visit_report_summary']  = 'attendance/visit_report_summary';
$route['dotonecrm_api/visit_daily_logs']      = 'attendance/visit_daily_logs';
$route['flutex_admin_api/planvisit'] = 'attendance/planvisit';
$route['flutex_admin_api/monthly'] = 'attendance/monthly';
$route['flutex_admin_api/dayroute'] = 'attendance/dayroute';
$route['flutex_admin_api/allVisits'] = 'attendance/planvisits';
$route['flutex_admin_api/smart_planning'] = 'attendance/smart_planning';
$route['flutex_admin_api/miscellaneous/policy-pages'] = 'miscellaneous/privacy_policy';

// Accounting reports (short paths for mobile)
$route['flutex_admin_api/profit-loss']      = 'accounting/profit_loss';
$route['flutex_admin_api/balance-sheet']    = 'accounting/balance_sheet';
$route['flutex_admin_api/cash-flow']        = 'accounting/cash_flow';
$route['flutex_admin_api/receivables']      = 'accounting/receivables';
$route['flutex_admin_api/payables']        = 'accounting/payables';
$route['flutex_admin_api/ledger-report']   = 'accounting/ledger_report';
$route['flutex_admin_api/trial-balance']   = 'accounting/trial_balance';

// dotonecrm_api aliases (legacy mobile base path)
$route['dotonecrm_api/profit-loss']        = 'accounting/profit_loss';
$route['dotonecrm_api/balance-sheet']      = 'accounting/balance_sheet';
$route['dotonecrm_api/cash-flow']          = 'accounting/cash_flow';
$route['dotonecrm_api/receivables']        = 'accounting/receivables';
$route['dotonecrm_api/payables']           = 'accounting/payables';
$route['dotonecrm_api/ledger-report']      = 'accounting/ledger_report';
$route['dotonecrm_api/trial-balance']      = 'accounting/trial_balance';
$route['dotonecrm_api/reports/(:any)']     = 'reports/show/$1';
$route['dotonecrm_api/reports']            = 'reports/index';
$route['dotonecrm_api/vouchers']           = 'vouchers/index';
$route['dotonecrm_api/voucher-stats']      = 'vouchers/stats';
$route['dotonecrm_api/voucher-month-summary'] = 'vouchers/month_summary';

// Ledger module
$route['flutex_admin_api/ledger/list']             = 'ledger/list';
$route['flutex_admin_api/ledger/summary']          = 'ledger/summary';
$route['flutex_admin_api/ledger/details/(:num)']   = 'ledger/details/$1';
$route['flutex_admin_api/ledger/create']           = 'ledger/create';
$route['flutex_admin_api/ledger/update/(:num)']    = 'ledger/update/$1';
$route['flutex_admin_api/ledger/delete/(:num)']    = 'ledger/delete/$1';

$route['flutex_admin_api/permissions'] = 'attendance/permissions';
//mapview_get
$route['flutex_admin_api/mapview'] = 'attendance/mapview';
$route['flutex_admin_api/attendance/map'] = 'mapwebview/map';
$route['flutex_admin_api/attendance/map_data'] = 'mapwebview/map_data';
$route['flutex_admin_api/attendance/map_activity'] = 'mapwebview/map_activity';
$route['flutex_admin_api/fix_distance/id/(:num)'] = 'attendance/fix_distance/$1';
//allVisits
//storelocation_post
// GET: Request Dashboard Information
$route['flutex_admin_api/overview'] = 'dashboard/overview';                         // GET: Request System Overview Information
$route['flutex_admin_api/notifications'] = 'dashboard/notifications';               // GET: Request Notifications

// Profile
$route['flutex_admin_api/profile'] = 'profile/profile';                             // GET: Request Staff Information

// Projects
$route['flutex_admin_api/projects'] = 'projects/projects';                          // GET: List All Projects                   // POST: Add New Project
$route['flutex_admin_api/projects/id/(:num)']  = 'projects/projects/id/$1';         // GET: Request Project Information         // PUT: Update Project Information
$route['flutex_admin_api/projects/id/(:num)/group/(:any)']   = 'projects/projects/id/$1/group/$2'; // GET: Request Project Group Information
$route['flutex_admin_api/projects/search/(:any)']  = 'projects/search/search/$1';   // GET: Search Projects
$route['flutex_admin_api/projects/create'] = 'projects/create';  

 /* =========================
   DISTRIBUTION API ROUTES
========================= */

// GET: List all orders | POST: Create order
$route['flutex_admin_api/distribution/orders'] = 'distribution/orders';
$route['flutex_admin_api/distribution/partners'] = 'distribution/partners';

$route['flutex_admin_api/distribution/territories'] = 'distribution/territories';
$route['flutex_admin_api/distribution/sales_areas'] = 'distribution/sales_areas';
$route['flutex_admin_api/distribution/client_master_details']='distribution/client_master_details' ;
// GET: Single order details
$route['flutex_admin_api/distribution/order/(:num)'] = 'distribution/order/$1';


// GET: Orders of a specific client
$route['flutex_admin_api/distribution/client_orders/(:num)'] = 'distribution/client_orders/$1';

// GET: Order items
$route['flutex_admin_api/distribution/order_items/(:num)'] = 'distribution/order_items/$1';
$route['flutex_admin_api/distribution/pdf/(:num)'] = 'distribution/pdf/$1';
// PUT: Update order | DELETE: Delete order
$route['flutex_admin_api/distribution/orders/id/(:num)'] = 'distribution/orders/id/$1';

// Sales performance (mobile dashboard / NSH)
$route['flutex_admin_api/sales/targets']           = 'distribution/sales_targets';
$route['flutex_admin_api/sales/achievements']      = 'distribution/sales_achievements';
$route['flutex_admin_api/sales/dashboard']        = 'distribution/sales_dashboard';
$route['flutex_admin_api/sales/team']             = 'distribution/sales_team';
$route['flutex_admin_api/sales/weekly_summary']   = 'distribution/sales_weekly_summary';
$route['flutex_admin_api/sales/weekly_report']    = 'distribution/sales_weekly_report';
$route['flutex_admin_api/sales/weekly_reports']   = 'distribution/sales_weekly_reports';
$route['flutex_admin_api/sales/kras']             = 'distribution/sales_kras';
$route['flutex_admin_api/dsr/daily']              = 'attendance/dsr_daily';
$route['flutex_admin_api/dsr/save']               = 'attendance/dsr_save';
$route['flutex_admin_api/dsr/bulk']               = 'attendance/dsr_bulk';

// Reports hub (mobile)
$route['flutex_admin_api/reports/(:any)']         = 'reports/show/$1';
$route['flutex_admin_api/reports']                = 'reports/index';

                // GET: Request Project Create Data

// Tasks
$route['flutex_admin_api/tasks'] = 'tasks/tasks';                                   // GET: List All Tasks                      // POST: Add New Task
$route['flutex_admin_api/tasks/id/(:num)']  = 'tasks/tasks/id/$1';                  // GET: Request Task Information            // PUT: Update Task Information
$route['flutex_admin_api/tasks/search/(:any)']  = 'tasks/search/search/$1';         // GET: Search Tasks

// Customers
$route['flutex_admin_api/customers'] = 'customers/customers';                       // GET: List All Customers                  // POST: Add New Customer
$route['flutex_admin_api/customers/id/(:num)']  = 'customers/customers/id/$1';      // GET: Request Customer Information        // PUT: Update Customer Information
$route['flutex_admin_api/customers/search/(:any)']  = 'customers/search/search/$1'; // GET: Search Customers
$route['flutex_admin_api/contacts/id/(:num)']  = 'customers/contacts/id/$1';        // GET: List All Customer Contacts          // POST: Add New Customer Contact
$route['flutex_admin_api/contacts/id/(:num)/contact/(:num)']  = 'customers/contacts/id/$1/contact/$2';  // GET: Request Contact Information
// ===============================
// CUSTOMER VISIT HISTORY
// ===============================
$route['flutex_admin_api/customers/(:num)/visit_history']
    = 'customers/visit_history/$1';
// GET: Customer Visit History


// ===============================
// CUSTOMER FOLLOWUPS
// ===============================
$route['flutex_admin_api/customers/(:num)/followups']
    = 'customers/followups/$1';
    
// GET: Customer Followups


// ===============================
// CUSTOMER ORDERS
// ===============================
$route['flutex_admin_api/customers/(:num)/orders']
    = 'customers/orders/$1';
// GET: Customer Orders


// ===============================
// CUSTOMER PAYMENTS
// ===============================
$route['flutex_admin_api/customers/(:num)/payments']
    = 'customers/payments/$1';
// GET: Customer Payments
// ===============================
// ADD REMINDER
// ===============================
$route['flutex_admin_api/reminders']
    = 'customers/reminders';
// POST: Add Reminder


// ===============================
// CUSTOMER ACTIVITY TIMELINE
// ===============================
$route['flutex_admin_api/customers/(:num)/activity']
    = 'customers/activity/$1';
// GET: Unified Activity Timeline

// Leads
$route['flutex_admin_api/leads'] = 'leads/leads';                                   // GET: List All Leads                      // POST: Add New Leads
$route['flutex_admin_api/leads/id/(:num)']  = 'leads/leads/id/$1';                  // GET: Request Lead Information            // PUT: Update Lead Information
$route['flutex_admin_api/leads/search/(:any)']  = 'leads/search/search/$1';
$route['flutex_admin_api/convert'] = 'leads/convert';
$route['flutex_admin_api/checkinlead'] = 'leads/checkinlead';         // GET: Search Leads
// Contracts
$route['flutex_admin_api/contracts'] = 'contracts/contracts';                       // GET: List All Contracts                   // POST: Add New Contract
$route['flutex_admin_api/contracts/id/(:num)']  = 'contracts/contracts/id/$1';      // GET: Request Contract Information         // PUT: Update Contract Information
$route['flutex_admin_api/contracts/search/(:any)']  = 'contracts/search/search/$1'; // GET: Search Contracts

// Proposals
$route['flutex_admin_api/proposals'] = 'proposals/proposals';                       // GET: List All Proposals                   // POST: Add New Proposal
$route['flutex_admin_api/proposals/id/(:num)']  = 'proposals/proposals/id/$1';      // GET: Request Proposal Information         // PUT: Update Proposal Information
$route['flutex_admin_api/proposals/search/(:any)']  = 'proposals/search/search/$1'; // GET: Search Proposals
$route['flutex_admin_api/factory_visitors']
    = 'factory_visitor/index';

$route['flutex_admin_api/factory_visitors/create']
    = 'factory_visitor/create';

$route['flutex_admin_api/factory_visitors/create_pass'] = 'factory_visitor/create_pass';

    $route['flutex_admin_api/factory_visitor/employee_invite'] = 'factory_visitor/employee_invite';

    $route['flutex_admin_api/factory_visitors/my_invites']='factory_visitor/my_invites';
    

$route['flutex_admin_api/factory_visitors/id/(:num)']
    = 'factory_visitor/id/$1';

$route['flutex_admin_api/factory_visitors/search/(:any)']
    = 'factory_visitor/search/$1';

$route['flutex_admin_api/factory_visitors/delete']
    = 'factory_visitor/index_delete';
$route['flutex_admin_api/factory_visitors/approve']
    = 'factory_visitor/approve';

$route['flutex_admin_api/factory_visitors/reject']
    = 'factory_visitor/reject';

$route['flutex_admin_api/factory_visitors/checkin']
    = 'factory_visitor/checkin';

$route['flutex_admin_api/factory_visitors/checkout']
    = 'factory_visitor/checkout';
$route['flutex_admin_api/factory_visitors/blacklist']
    = 'factory_visitor/blacklist';

$route['flutex_admin_api/factory_visitors/remove_blacklist']
    = 'factory_visitor/remove_blacklist';

$route['flutex_admin_api/factory_visitors/blacklist_list']
    = 'factory_visitor/blacklist';

$route['flutex_admin_api/factory_visitors/blacklist_details/(:num)']
    = 'factory_visitor/blacklist_details/$1';
    $route['flutex_admin_api/factory_visitors/gates']
    = 'factory_visitor/gates';

$route['flutex_admin_api/factory_visitors/add_gate']
    = 'factory_visitor/add_gate';

$route['flutex_admin_api/factory_visitors/update_gate']
    = 'factory_visitor/update_gate';

$route['flutex_admin_api/factory_visitors/delete_gate']
    = 'factory_visitor/delete_gate';
    $route['flutex_admin_api/factory_visitors/shifts']
    = 'factory_visitor/shifts';

$route['flutex_admin_api/factory_visitors/add_shift']
    = 'factory_visitor/add_shift';

$route['flutex_admin_api/factory_visitors/update_shift']
    = 'factory_visitor/update_shift';

$route['flutex_admin_api/factory_visitors/delete_shift']
    = 'factory_visitor/delete_shift';
    $route['flutex_admin_api/factory_visitors/dashboard']
    = 'factory_visitor/dashboard';

$route['flutex_admin_api/factory_visitors/security_dashboard']
    = 'factory_visitor/security_dashboard';
    $route['flutex_admin_api/factory_visitors/verify']
    = 'factory_visitor/verify_pass';

$route['flutex_admin_api/factory_visitors/staff']
    = 'factory_visitor/staff';
// Estimates
$route['flutex_admin_api/estimates'] = 'estimates/estimates';
$route['flutex_admin_api/taxes'] = 'estimates/taxes';
$route['flutex_admin_api/estimates/pdf/(:num)'] = 'estimates/pdf/$1';
$route['flutex_admin_api/invoices/pdf/(:num)'] = 'invoices/pdf/$1';
$route['flutex_admin_api/proposals/pdf/(:num)'] = 'proposals/pdf/$1';
$route['flutex_admin_api/leaves/list']             = 'leaves/list';
$route['flutex_admin_api/leaves/detail/(:num)']    = 'leaves/detail/$1';
$route['flutex_admin_api/leaves/apply']            = 'leaves/apply';
$route['flutex_admin_api/leaves/balance']          = 'leaves/balance';
$route['flutex_admin_api/leaves/manager/action']   = 'leaves/manager_action';
$route['flutex_admin_api/leaves/categories']        = 'leaves/categories';

$route['flutex_admin_api/expense/categories'] = 'expenses/categories';
$route['flutex_admin_api/expense/list']            = 'expenses/list';
$route['flutex_admin_api/expense/detail/(:num)']  = 'expenses/detail/$1';
$route['flutex_admin_api/expense/apply']           = 'expenses/apply';
$route['flutex_admin_api/expense/update']          = 'expenses/update';
$route['flutex_admin_api/expense/delete']          = 'expenses/delete';
$route['flutex_admin_api/expense/manager/action']  = 'expenses/manager_action';
$route['flutex_admin_api/vouchers']                      = 'vouchers/index';
$route['flutex_admin_api/voucher-stats']                 = 'vouchers/stats';
$route['flutex_admin_api/voucher-month-summary']         = 'vouchers/month_summary';
$route['flutex_admin_api/vouchers/list']                 = 'vouchers/list';

$route['flutex_admin_api/vouchers/id/(:num)']            = 'vouchers/id/$1';

$route['flutex_admin_api/vouchers/details/(:num)']       = 'vouchers/details/$1';

$route['flutex_admin_api/vouchers/create']               = 'vouchers/create';

$route['flutex_admin_api/vouchers/update/(:num)']        = 'vouchers/update/$1';

$route['flutex_admin_api/vouchers/delete/(:num)']        = 'vouchers/delete/$1';

$route['flutex_admin_api/vouchers/stats']                = 'vouchers/stats';

$route['flutex_admin_api/vouchers/month-summary']        = 'vouchers/month_summary';

$route['flutex_admin_api/vouchers/ledger']               = 'vouchers/ledger';

$route['flutex_admin_api/vouchers/inventory']            = 'vouchers/inventory';

$route['flutex_admin_api/vouchers/balance-sheet']        = 'vouchers/balance_sheet';

$route['flutex_admin_api/vouchers/profit-loss']          = 'vouchers/profit_loss';

$route['flutex_admin_api/vouchers/trial-balance']        = 'vouchers/trial_balance';

// GET: List All Estimates                   // POST: Add New Estimate
$route['flutex_admin_api/estimates/id/(:num)']  = 'estimates/estimates/id/$1'; 

$route['flutex_admin_api/estimates/id/(:num)']  = 'estimates/estimates/id/$1'; 
     // GET: Request Estimate Information         // PUT: Update Estimate Information
$route['flutex_admin_api/estimates/search/(:any)']  = 'estimates/search/search/$1'; // GET: Search Estimates

// Invoices
$route['flutex_admin_api/invoices'] = 'invoices/invoices';                          // GET: List All Invoices                    // POST: Add New Invoice
$route['flutex_admin_api/invoices/id/(:num)']  = 'invoices/invoices/id/$1';         // GET: Request Invoice Information          // PUT: Update Invoice Information
$route['flutex_admin_api/invoices/search/(:any)']  = 'invoices/search/search/$1';   // GET: Search Invoices

// Tickets
$route['flutex_admin_api/tickets'] = 'tickets/tickets';                             // GET: List All Tickets                     // POST: Add New Ticket
$route['flutex_admin_api/tickets/id/(:num)']  = 'tickets/tickets/id/$1';            // GET: Request Ticket Information           // PUT: Update Ticket Information
$route['flutex_admin_api/tickets/search/(:any)']  = 'tickets/search/search/$1';     // GET: Search Tickets

// Items
$route['flutex_admin_api/items'] = 'items/items';   
$route['flutex_admin_api/ditems'] = 'items/ditems';
$route['flutex_admin_api/groups'] = 'items/groups';

$route['flutex_admin_api/sub-groups'] = 'items/sub_groups';

$route['flutex_admin_api/categories'] = 'items/categories';

$route['flutex_admin_api/units'] = 'items/units';

$route['flutex_admin_api/godowns'] = 'items/godowns';   // GET: List All Items                       // POST: Add New Item
$route['flutex_admin_api/items/id/(:num)']  = 'items/items/id/$1';                  // GET: Request Item Information             // PUT: Update Item Information
$route['flutex_admin_api/items/search/(:any)']  = 'items/search/search/$1';         // GET: Search Items

// Payments
$route['flutex_admin_api/payments'] = 'payments/payments';                          // GET: List All Payments                    // POST: Add New Payment
$route['flutex_admin_api/payments/id/(:num)']  = 'payments/payments/id/$1';         // GET: Request Payment Information          // PUT: Update Payment Information
$route['flutex_admin_api/payments/search/(:any)']  = 'payments/search/search/$1';   // GET: Search Payments

// Miscellaneous
$route['flutex_admin_api/miscellaneous/client_groups']        = 'miscellaneous/client_groups';        // GET: Request Client Groups
$route['flutex_admin_api/miscellaneous/payment_modes']        = 'miscellaneous/payment_modes';        // GET: Request Payment Modes
$route['flutex_admin_api/miscellaneous/expense_categories']   = 'miscellaneous/expense_categories';   // GET: Request Expense Categories
$route['flutex_admin_api/miscellaneous/tax_data']             = 'miscellaneous/tax_data';             // GET: Request Tax Data
$route['flutex_admin_api/miscellaneous/leads_sources']        = 'miscellaneous/leads_sources';        // GET: Request Leads Sources
$route['flutex_admin_api/miscellaneous/leads_statuses']       = 'miscellaneous/leads_statuses';       // GET: Request Leads Statuses
$route['flutex_admin_api/miscellaneous/proposal_statuses']    = 'miscellaneous/proposal_statuses';    // GET: Request Proposal Statuses
$route['flutex_admin_api/miscellaneous/ticket_departments']   = 'miscellaneous/ticket_departments';   // GET: Request Ticket Departments
$route['flutex_admin_api/miscellaneous/ticket_priorities']    = 'miscellaneous/ticket_priorities';    // GET: Request Ticket Priorities
$route['flutex_admin_api/miscellaneous/ticket_services']      = 'miscellaneous/ticket_services';      // GET: Request Ticket Services
$route['flutex_admin_api/miscellaneous/currencies']           = 'miscellaneous/currencies';           // GET: Request Currencies
$route['flutex_admin_api/miscellaneous/countries']            = 'miscellaneous/countries';  
$route['flutex_admin_api/miscellaneous/staff']                = 'miscellaneous/staff';    
$route['flutex_admin_api/tasks/update_status'] = 'tasks/update_status';
$route['flutex_admin_api/tasks/statuses'] = 'tasks/statuses';
$route['flutex_admin_api/hrms/holidays'] = 'hrms/holidays';
$route['flutex_admin_api/hrms/anniversaries'] = 'hrms/anniversaries';
$route['flutex_admin_api/hrms/birthdays'] = 'hrms/birthdays';
$route['flutex_admin_api/hrms/dashboard']      = 'hrms/dashboard';

$route['flutex_admin_api/hrms/employees']      = 'hrms/employees';
$route['flutex_admin_api/hrms/employee/(:num)'] = 'hrms/employee/$1';

$route['flutex_admin_api/hrms/employee']       = 'hrms/employee';
$route['flutex_admin_api/hrms/attendance_dashboard']
    = 'hrms/attendance_dashboard';

$route['flutex_admin_api/hrms/attendance']
    = 'hrms/attendance';

$route['flutex_admin_api/hrms/monthly_attendance']
    = 'hrms/monthly_attendance';

$route['flutex_admin_api/hrms/shifts']
    = 'hrms/shifts';

$route['flutex_admin_api/hrms/attendance_summary']
    = 'hrms/attendance_summary';
    $route['flutex_admin_api/hrms/my_attendance_dashboard']
    = 'hrms/my_attendance_dashboard';

$route['flutex_admin_api/hrms/my_attendance_logs']
    = 'hrms/my_attendance_logs';

$route['flutex_admin_api/hrms/my_monthly_attendance']
    = 'hrms/my_monthly_attendance';

$route['flutex_admin_api/hrms/my_attendance_calendar']
    = 'hrms/my_attendance_calendar';

$route['flutex_admin_api/hrms/my_shift']
    = 'hrms/my_shift';
$route['flutex_admin_api/hrms/my_payroll']
    = 'hrms/my_payroll';

$route['flutex_admin_api/hrms/my_salary_slips']
    = 'hrms/my_salary_slips';

$route['flutex_admin_api/hrms/my_salary_slip/(:num)']
    = 'hrms/my_salary_slip/$1';

$route['flutex_admin_api/hrms/my_salary_components/(:num)']
    = 'hrms/my_salary_components/$1';
      // GET: Request Staff
