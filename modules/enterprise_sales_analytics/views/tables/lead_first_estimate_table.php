<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
// /* 🔥 DEBUG START */
// log_message('error', 'POST DATA: ' . print_r($_POST, true));
// log_message('error', 'GET DATA: ' . print_r($_GET, true));
// /* 🔥 DEBUG END */

/*
|------------------------------------------------------------------
| COLUMNS
|------------------------------------------------------------------
*/
$aColumns = [

    'l.id as lead_id',

    'l.dateadded as lead_added',
    'e.acceptance_date as estimate_accepted',

    'c.company',
    'l.name as contact_name',
    'l.phonenumber',
    'l.city as address',

    'e.total as order_amount',
    'e.id as estimate_id',
    'e.number as sales_order_number',
    'e.delivered_on',

    's.firstname as staff_firstname',
    's.lastname as staff_lastname',

    'ls.name as source_name'
];

$sIndexColumn = 'lead_id';
$sTable       = db_prefix().'leads l';

/*
|------------------------------------------------------------------
| JOIN (🔥 FIXED WITH SAFE ALIAS)
|------------------------------------------------------------------
*/
$join = [

    'LEFT JOIN '.db_prefix().'clients c ON c.leadid = l.id',

    // ✅ FIXED SUBQUERY (NO alias conflict)
  'INNER JOIN '.db_prefix().'estimates e 
    ON e.clientid = c.userid AND e.status = 4',

    'LEFT JOIN '.db_prefix().'leads_sources ls ON ls.id = l.source',
    'LEFT JOIN '.db_prefix().'staff s ON s.staffid = l.assigned',
];

/*
|------------------------------------------------------------------
| FILTER INPUTS
|------------------------------------------------------------------
*/
$where = [];

$staff          = $CI->input->post('staff');
$accepted_from  = $CI->input->post('accepted_from');
$accepted_to    = $CI->input->post('accepted_to');
$delivered_from = $CI->input->post('delivered_from');
$delivered_to   = $CI->input->post('delivered_to');

/*
|------------------------------------------------------------------
| APPLY FILTERS (🔥 CORRECT WAY)
|------------------------------------------------------------------
*/
if (!empty($staff)) {
    $where[] = 'AND l.assigned = ' . intval($staff);
}

if (!empty($accepted_from)) {
    $where[] = 'AND DATE(e.acceptance_date) >= "' . $accepted_from . '"';
}

if (!empty($accepted_to)) {
    $where[] = 'AND DATE(e.acceptance_date) <= "' . $accepted_to . '"';
}

if (!empty($delivered_from)) {
    $where[] = 'AND DATE(e.delivered_on) >= "' . $delivered_from . '"';
}

if (!empty($delivered_to)) {
    $where[] = 'AND DATE(e.delivered_on) <= "' . $delivered_to . '"';
}

/*
|------------------------------------------------------------------
| GROUP
|------------------------------------------------------------------
*/
$groupBy = '';

/*
|------------------------------------------------------------------
| DATATABLE INIT
|------------------------------------------------------------------
*/
$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    [],
    $groupBy
);

$output  = $result['output'];
$rResult = $result['rResult'];

/*
|------------------------------------------------------------------
| BUILD ROWS
|------------------------------------------------------------------
*/
foreach ($rResult as $aRow) {

    $row = [];

    $row[] = date('d-m-Y', strtotime($aRow['lead_added']));

    $row[] = !empty($aRow['estimate_accepted'])
        ? date('d-m-Y', strtotime($aRow['estimate_accepted']))
        : '-';

    $row[] = $aRow['company'] ?? '-';
    $row[] = $aRow['contact_name'] ?? '-';
    $row[] = $aRow['phonenumber'] ?? '-';
    $row[] = $aRow['address'] ?? '-';

    $row[] = (!empty($aRow['staff_firstname']))
        ? $aRow['staff_firstname'] . ' ' . $aRow['staff_lastname']
        : '-';

    $row[] = '₹' . number_format($aRow['order_amount'] ?? 0);

    $row[] = !empty($aRow['estimate_id'])
        ? format_estimate_number($aRow['estimate_id'])
        : '-';

    $row[] = !empty($aRow['delivered_on'])
        ? date('d-m-Y', strtotime($aRow['delivered_on']))
        : '-';

    $row[] = $aRow['source_name'] ?? '-';

    $output['aaData'][] = $row;
}

/*
|------------------------------------------------------------------
| TOTAL COUNT (MATCH FILTERS)
|------------------------------------------------------------------
*/
$CI->db->from(db_prefix().'leads l');

$CI->db->join(db_prefix().'clients c', 'c.leadid = l.id', 'left');

$CI->db->join(
    db_prefix().'estimates e',
    'e.clientid = c.userid AND e.status = 4',
    'inner'
);

/* APPLY SAME FILTERS */
if (!empty($staff)) {
    $CI->db->where('l.assigned', $staff);
}

if (!empty($accepted_from)) {
    $CI->db->where('DATE(e.acceptance_date) >=', $accepted_from);
}

if (!empty($accepted_to)) {
    $CI->db->where('DATE(e.acceptance_date) <=', $accepted_to);
}

if (!empty($delivered_from)) {
    $CI->db->where('DATE(e.delivered_on) >=', $delivered_from);
}

if (!empty($delivered_to)) {
    $CI->db->where('DATE(e.delivered_on) <=', $delivered_to);
}

$total = $CI->db->count_all_results();

$output['iTotalRecords']        = $total;
$output['iTotalDisplayRecords'] = $total;

/*
|------------------------------------------------------------------
| OUTPUT
|------------------------------------------------------------------
*/
header('Content-Type: application/json');
echo json_encode($output);
exit;