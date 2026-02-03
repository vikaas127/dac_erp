<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = ['name', 'default_discount', 'default_profit_margin', 'override_allowed', 'quantity_discount_allowed','additional_discount_allowed'];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'customers_groups';

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], ['id']);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Name column with modal trigger
    $row[] = '<a href="#" data-toggle="modal" data-target="#customer_group_modal" 
                data-id="' . $aRow['id'] . '" 
                data-name="' . e($aRow['name']) . '"
                data-discount="' . $aRow['default_discount'] . '"
                data-margin="' . $aRow['default_profit_margin'] . '"
                data-override="' . $aRow['override_allowed'] . '"
                data-quantity_discount_allowed="' . $aRow['quantity_discount_allowed'] . '"
                data-additional_discount_allowed="' . $aRow['additional_discount_allowed'] . '"
              >' . e($aRow['name']) . '</a>';

    // Default discount
    $row[] = $aRow['default_discount'] . '%';

    // Default profit margin
    $row[] = $aRow['default_profit_margin'] . '%';

    // Override allowed
    $row[] = $aRow['override_allowed'] ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>';

    // Quantity discount allowed
    $row[] = $aRow['quantity_discount_allowed'] ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>';
     $row[] = $aRow['additional_discount_allowed'] ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>';

    // Options column
    $options = '<div class="tw-flex tw-items-center tw-space-x-3">';
    $options .= '<a href="#" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700" 
                    data-toggle="modal" data-target="#customer_group_modal" 
                    data-id="' . $aRow['id'] . '" 
                    data-name="' . e($aRow['name']) . '"
                    data-discount="' . $aRow['default_discount'] . '"
                    data-margin="' . $aRow['default_profit_margin'] . '"
                    data-override="' . $aRow['override_allowed'] . '"
                    data-quantity_discount_allowed="' . $aRow['quantity_discount_allowed'] . '"    data-additional_discount_allowed="' . $aRow['additional_discount_allowed'] . '">
                    <i class="fa-regular fa-pen-to-square fa-lg"></i>
                </a>';

    $options .= '<a href="' . admin_url('clients/delete_group/' . $aRow['id']) . '" 
                    class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                    <i class="fa-regular fa-trash-can fa-lg"></i>
                </a>';
    $options .= '</div>';

    $row[] = $options;

    $output['aaData'][] = $row;
}
