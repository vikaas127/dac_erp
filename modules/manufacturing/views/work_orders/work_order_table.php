<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
	'id',
	'operation_name',
	'date_planned_start',
	'work_center_id',
	'manufacturing_order_id',
	'product_id',
	'qty_production',
	'unit_id',
	'status',
	'contact_id'

];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'mrp_work_orders';

$where = [];
$join[] = 'LEFT JOIN '.db_prefix().'clients ON '.db_prefix().'mrp_work_orders.contact_id = '.db_prefix().'clients.userid';

$manufacturing_order_filter = $this->ci->input->post('manufacturing_order_filter');
$products_filter = $this->ci->input->post('products_filter');
$status_filter = $this->ci->input->post('status_filter');
$customer_filter = $this->ci->input->post('customer_filter');
$work_order_filter = $this->ci->input->post('work_order_filter');


if (isset($customer_filter)) {
    $where_customer_ft = '';
    foreach ($customer_filter as $contact_id) {
        if ($contact_id != '') {
            if ($where_customer_ft == '') {
                $where_customer_ft .= 'AND (' . db_prefix() . 'mrp_work_orders.contact_id = "' . $contact_id . '"';
            } else {
                $where_customer_ft .= ' OR ' . db_prefix() . 'mrp_work_orders.contact_id = "' . $contact_id . '"';
            }
        }
    }
    if ($where_customer_ft != '') {
        $where_customer_ft .= ')';
        array_push($where, $where_customer_ft);
    }
}
if (isset($work_order_filter)) {
    $where_work_order_filter = '';
    foreach ($work_order_filter as $operation_name) {
        if ($operation_name != '') {
            if ($where_work_order_filter == '') {
                $where_work_order_filter .= 'AND (' . db_prefix() . 'mrp_work_orders.operation_name = "' . $operation_name . '"';
            } else {
                $where_work_order_filter .= ' OR ' . db_prefix() . 'mrp_work_orders.operation_name = "' . $operation_name . '"';
            }
        }
    }
    if ($where_work_order_filter != '') {
        $where_work_order_filter .= ')';
        array_push($where, $where_work_order_filter);
    }
}


if (isset($manufacturing_order_filter)) {
	$where_manufacturing_order_ft = '';
	foreach ($manufacturing_order_filter as $manufacturing_order) {
		if ($manufacturing_order != '') {
			if ($where_manufacturing_order_ft == '') {
				$where_manufacturing_order_ft .= 'AND ('.db_prefix().'mrp_work_orders.manufacturing_order_id = "' . $manufacturing_order . '"';
			} else {
				$where_manufacturing_order_ft .= ' or '.db_prefix().'mrp_work_orders.manufacturing_order_id = "' . $manufacturing_order . '"';
			}
		}
	}
	if ($where_manufacturing_order_ft != '') {
		$where_manufacturing_order_ft .= ')';
		array_push($where, $where_manufacturing_order_ft);
	}
}

if (isset($products_filter)) {
	$where_products_ft = '';
	foreach ($products_filter as $product_id) {
		if ($product_id != '') {
			if ($where_products_ft == '') {
				$where_products_ft .= 'AND ('.db_prefix().'mrp_work_orders.product_id = "' . $product_id . '"';
			} else {
				$where_products_ft .= ' or '.db_prefix().'mrp_work_orders.product_id = "' . $product_id . '"';
			}
		}
	}
	if ($where_products_ft != '') {
		$where_products_ft .= ')';
		array_push($where, $where_products_ft);
	}
}

if (isset($status_filter)) {
	$where_status_ft = '';
	foreach ($status_filter as $status) {
		if ($status != '') {
			if ($where_status_ft == '') {
				$where_status_ft .= 'AND ('.db_prefix().'mrp_work_orders.status = "' . $status . '"';
			} else {
				$where_status_ft .= ' or '.db_prefix().'mrp_work_orders.status = "' . $status . '"';
			}
		}
	}
	if ($where_status_ft != '') {
		$where_status_ft .= ')';
		array_push($where, $where_status_ft);
	}
}
// Get logged-in staff ID
$logged_in_staff_id = get_staff_user_id(); 

// Check user permissions
$has_view_permission = has_permission('work_order', '', 'view'); // Can view all work orders
$has_view_own_permission = has_permission('work_order', '', 'view_own'); // Can only view assigned work orders

// Log permission details
log_message('info', 'User ID: ' . $logged_in_staff_id . ' | View Permission: ' . ($has_view_permission ? 'Yes' : 'No') . ' | View Own Permission: ' . ($has_view_own_permission ? 'Yes' : 'No'));

// Apply filter based on permissions
if ($has_view_own_permission && !$has_view_permission) {
    // Only fetch work orders where the logged-in user is the assigned operator
   $where_condition = 'AND ' . db_prefix() . 'mrp_work_orders.id IN (
    SELECT ' . db_prefix() . 'mrp_work_orders.id 
    FROM ' . db_prefix() . 'mrp_work_orders
    JOIN ' . db_prefix() . 'mrp_routing_details 
    ON ' . db_prefix() . 'mrp_work_orders.routing_detail_id = ' . db_prefix() . 'mrp_routing_details.id
    WHERE ' . db_prefix() . 'mrp_routing_details.staff_id = ' . $logged_in_staff_id . '
)';

    
    // Log applied SQL filter
    log_message('info', 'Applying View Own Permission Filter: ' . $where_condition);
    
    // Add to the query
    $where[] = $where_condition;
} else {
    // Log if user has global view access
    log_message('info', 'User has global view access to all work orders.');
}




$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id']);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
	$row = [];

	for ($i = 0; $i < count($aColumns); $i++) {

		if($aColumns[$i] == 'id') {
			$_data = $aRow['id'];

		}elseif ($aColumns[$i] == 'operation_name'   ) {
			$code = '<a href="' . admin_url('manufacturing/view_work_order/' . $aRow['id'].'/'.$aRow['manufacturing_order_id']) . '">' . $aRow['operation_name'] . '</a>';
			$code .= '<div class="row-options">';

			$code .= '<a href="' . admin_url('manufacturing/view_work_order/' . $aRow['id']).'/'.$aRow['manufacturing_order_id'] . '" >' . _l('view') . '</a>';

			$code .= '</div>';

			$_data = $code;


		}elseif($aColumns[$i] == 'date_planned_start'){
			$_data = _dt($aRow['date_planned_start']);
		}elseif($aColumns[$i] == 'work_center_id'){
			$_data =  get_work_center_name($aRow['work_center_id']);

		}elseif($aColumns[$i] == 'manufacturing_order_id'){

			$_data =  mrp_get_manufacturing_code($aRow['manufacturing_order_id']);

		}elseif($aColumns[$i] == 'product_id'){

			$_data =  mrp_get_product_name($aRow['product_id']);

		}elseif($aColumns[$i] == 'qty_production'){
			$_data =  app_format_money($aRow['qty_production'],'');

		}elseif($aColumns[$i] == 'unit_id'){

			$_data =  mrp_get_unit_name($aRow['unit_id']);

		}elseif($aColumns[$i] == 'status'){

			$_data = ' <span class="label label-'.$aRow['status'].'" > '._l($aRow['status']).' </span>';
			

		}elseif ($aColumns[$i] == 'contact_id') {  
			$_data = get_relation_values(get_relation_data('customer', $aRow['contact_id']), 'customer')['name'];}




		$row[] = $_data;
	}

	$output['aaData'][] = $row;
}

