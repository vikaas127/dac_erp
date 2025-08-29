<?php

defined('BASEPATH') or exit('No direct script access allowed');

log_message('info', 'Initializing BOM Scrap DataTable...');

$prefix = db_prefix();

$aColumns = [
    "{$prefix}bom_scrap.ScrapID as ScrapID",
    "{$prefix}bom_scrap.product_id as product_id",
    "{$prefix}bom_scrap.item_type as item_type",
    "{$prefix}bom_scrap.unit_id as unit_id",
    "{$prefix}bom_scrap.scrap_type as scrap_type",
    "{$prefix}bom_scrap.estimated_quantity as estimated_quantity",
  
   
    "{$prefix}bom_scrap.reason as reason",
    "{$prefix}bom_scrap.bill_of_material_id as bill_of_material_id",
    "{$prefix}bom_scrap.routing_id as routing_id",
    "{$prefix}bom_scrap.operation_id as operation_id",
    "{$prefix}bom_scrap.bill_of_material_product_id as bill_of_material_product_id"
];

$sIndexColumn = 'ScrapID';
$sTable = "{$prefix}bom_scrap";

$where = [];

// ✅ Only use BOM ID filter
$bill_of_material_id = $this->ci->input->post('bill_of_material_id');
log_message('debug', 'Input Filter: bill_of_material_id = ' . $bill_of_material_id);

if ($bill_of_material_id) {
    $where[] = "AND {$prefix}bom_scrap.bill_of_material_id = \"{$bill_of_material_id}\"";
}

// 🛑 Do NOT use other filters
/*
$bill_of_material_product_id = $this->ci->input->post('bill_of_material_product_id');
$routing_id = $this->ci->input->post('bill_of_material_routing_id');
*/

$join = [
    "LEFT JOIN {$prefix}items ON {$prefix}items.id = {$prefix}bom_scrap.product_id",
    "LEFT JOIN {$prefix}ware_unit_type ON {$prefix}ware_unit_type.unit_type_id = {$prefix}bom_scrap.unit_id"
];



$fields = [
    "{$prefix}bom_scrap.ScrapID",
    "{$prefix}bom_scrap.product_id",
    "{$prefix}bom_scrap.item_type",
    "{$prefix}bom_scrap.unit_id",
    "{$prefix}bom_scrap.scrap_type",
    "{$prefix}bom_scrap.estimated_quantity",
   
    "{$prefix}bom_scrap.reason",
    "{$prefix}bom_scrap.bill_of_material_id",
    "{$prefix}bom_scrap.routing_id",
    "{$prefix}bom_scrap.operation_id",
    "{$prefix}bom_scrap.bill_of_material_product_id"
];

log_message('info', 'Executing data_tables_init with BOM ID only...');
log_message('debug', 'WHERE clause: ' . json_encode($where));

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $fields);

$output = $result['output'];
$rResult = $result['rResult'];

log_message('info', 'Scrap Records Fetched: ' . count($rResult));

foreach ($rResult as $aRow) {
    $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';
    $row = [];

    $product_name = mrp_get_product_name($aRow['product_id']);
    $product_name .= '<div class="row-options">';

    if ((has_permission('manufacturing', '', 'edit') && has_permission('bill_of_material', '', 'edit')) || is_admin()) {
     /*  $product_name .= ' <a href="#" onclick="open_scrap_modal('
    . htmlspecialchars($bill_of_material_id, ENT_QUOTES) . ', '
    . htmlspecialchars($aRow['product_id'], ENT_QUOTES) . ', '
    . htmlspecialchars($aRow['bill_of_material_product_id'], ENT_QUOTES) . ', '
    . htmlspecialchars($aRow['routing_id'], ENT_QUOTES) . '); return false;">'
    . _l('edit') . '</a>';*/

    }

    if ((has_permission('manufacturing', '', 'delete') && has_permission('bill_of_material', '', 'delete')) || is_admin()) {
        $product_name .= ' | <a href="' 
            . admin_url('manufacturing/delete_bom_scrap/' . $aRow['ScrapID']) 
            . '/' . $bill_of_material_id . '" class="text-danger _delete">' . _l('delete') . '</a>';
    }

    $product_name .= '</div>';
    $row[] = $product_name;


		

   
    $row[] = mrp_get_unit_name($aRow['unit_id']);
    $row[] = ucfirst($aRow['scrap_type']);
    $row[] = $aRow['estimated_quantity'];


    $row[] = $aRow['reason'];
   
    $row[] = mrp_get_routing_detail_name($aRow['operation_id']);

    $output['aaData'][] = $row;
}

log_message('info', 'BOM Scrap DataTable rendering complete.');
