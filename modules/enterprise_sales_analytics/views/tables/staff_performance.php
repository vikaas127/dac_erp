<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$staff          = $CI->input->post('staff');
$accepted_from  = $CI->input->post('accepted_from');
$accepted_to    = $CI->input->post('accepted_to');

/*
|--------------------------------------------------------------------------
| STATUSES
|--------------------------------------------------------------------------
*/
$statuses = $CI->db
    ->order_by('statusorder','ASC')
    ->get(db_prefix().'leads_status')
    ->result_array();

/*
|--------------------------------------------------------------------------
| COLUMNS (NO DUPLICATION ISSUE)
|--------------------------------------------------------------------------
*/
$aColumns = [
    db_prefix().'staff.staffid',
    db_prefix().'staff.firstname',
    db_prefix().'staff.lastname',

    // ✅ FIXED: no duplicate leads
    'COUNT(DISTINCT l.id) as leads'
];

/*
|--------------------------------------------------------------------------
| DYNAMIC STATUS COUNTS
|--------------------------------------------------------------------------
*/
foreach ($statuses as $status) {
    $aColumns[] = 'COUNT(DISTINCT CASE 
        WHEN l.status='.$status['id'].' 
        THEN l.id 
    END) as status_'.$status['id'];
}

/*
|--------------------------------------------------------------------------
| WON (converted leads → clients)
|--------------------------------------------------------------------------
*/
$aColumns[] = 'COUNT(DISTINCT CASE 
    WHEN e.status = 4 
    '.(!empty($accepted_from) ? ' AND DATE(e.acceptance_date) >= "'.$accepted_from.'"' : '').'
    '.(!empty($accepted_to) ? ' AND DATE(e.acceptance_date) <= "'.$accepted_to.'"' : '').'
    THEN l.id 
END) as won';

$aColumns[] = 'SUM(CASE 
    WHEN e.status = 4 
    '.(!empty($accepted_from) ? ' AND DATE(e.acceptance_date) >= "'.$accepted_from.'"' : '').'
    '.(!empty($accepted_to) ? ' AND DATE(e.acceptance_date) <= "'.$accepted_to.'"' : '').'
    THEN e.total 
    ELSE 0 
END) as revenue';

$sIndexColumn = 'staffid';
$sTable       = db_prefix().'staff';

/*
|--------------------------------------------------------------------------
| JOINS
|--------------------------------------------------------------------------
*/
$join = [
    'LEFT JOIN '.db_prefix().'leads l 
        ON l.assigned = '.db_prefix().'staff.staffid',

    'LEFT JOIN '.db_prefix().'clients 
        ON '.db_prefix().'clients.leadid = l.id',

    'LEFT JOIN '.db_prefix().'estimates e 
    ON e.clientid = '.db_prefix().'clients.userid'
];

$where = [];

if (!empty($staff)) {
    $where[] = 'AND '.db_prefix().'staff.staffid = ' . intval($staff);
}
// if (!empty($accepted_from)) {
//     $where[] = 'DATE(e.acceptance_date) >= "'.$accepted_from.'"';
// }

// if (!empty($accepted_to)) {
//     $where[] = 'DATE(e.acceptance_date) <= "'.$accepted_to.'"';
// }
/*
|--------------------------------------------------------------------------
| GROUP BY (CRITICAL)
|--------------------------------------------------------------------------
*/
$groupBy = 'GROUP BY '.db_prefix().'staff.staffid';

/*
|--------------------------------------------------------------------------
| DATATABLE INIT
|--------------------------------------------------------------------------
*/
$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    [
        db_prefix().'staff.firstname',
        db_prefix().'staff.lastname'
    ],
    $groupBy
);

$output  = $result['output'];
$rResult = $result['rResult'];

/*
|--------------------------------------------------------------------------
| BUILD ROWS
|--------------------------------------------------------------------------
*/
foreach ($rResult as $aRow) {

    $row = [];

    // ✅ STAFF NAME FIXED
    $row[] = ($aRow['firstname'] ?? '') . ' ' . ($aRow['lastname'] ?? '');

    // Leads
    $row[] = '<strong>'.($aRow['leads'] ?? 0).'</strong>';

    // Statuses
    foreach ($statuses as $status) {
        $row[] = (string)($aRow['status_'.$status['id']] ?? 0);
    }

    // Won
    $row[] = '<span class="text-success">'.($aRow['won'] ?? 0).'</span>';

    // Revenue
    $row[] = '₹'.number_format($aRow['revenue'] ?? 0);

    $output['aaData'][] = $row;
}

/*
|--------------------------------------------------------------------------
| FIX TOTAL COUNT (NO JOIN INFLATION)
|--------------------------------------------------------------------------
*/
$total_staff = $CI->db
    ->count_all(db_prefix().'staff');

$output['iTotalRecords'] = $total_staff;
$output['iTotalDisplayRecords'] = $total_staff;

/*
|--------------------------------------------------------------------------
| FINAL OUTPUT
|--------------------------------------------------------------------------
*/
header('Content-Type: application/json');
echo json_encode($output);
exit;