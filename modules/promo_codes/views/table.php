<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$aColumns = [
    'code',
    'type',
    'amount',
    'usage_limit',
    'start_date',
    'status',
    'id', // for actions
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'promo_codes';

$join = [];
$additionalSelect = ['end_date', '(SELECT COUNT(*) FROM ' . db_prefix() . 'promo_codes_usage WHERE promo_code_id=' . db_prefix() . 'promo_codes.id) as total_usage'];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], $additionalSelect);

$output  = $result['output'];
$rResult = $result['rResult'];
$currency = get_base_currency();

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = $aRow['code'];
    $row[] = ucfirst($aRow['type']);
    $row[] =  $aRow['type'] === 'percentage' ? $aRow['amount'] . '%' : app_format_money($aRow['amount'], $currency);
    $row[] = $aRow['usage_limit'] . ' (' . _l('promo_codes_used') . ': ' . $aRow['total_usage'] . ')';
    $row[] = _d($aRow['start_date']) . ' - ' . _d($aRow['end_date']);

    // Status badge
    $statusLabel = $aRow['status'] === 'active'
        ? '<span class="label label-success">' . _l('promo_codes_active') . '</span>'
        : '<span class="label label-default">' . _l('promo_codes_inactive') . '</span>';
    $row[] = $statusLabel;

    // Action Icons
    $viewLink    = admin_url('promo_codes/view/' . $aRow['id']);
    $editLink    = admin_url('promo_codes/edit/' . $aRow['id']);
    $deleteLink  = admin_url('promo_codes/delete/' . $aRow['id']);
    $toggleLink  = admin_url('promo_codes/toggle_status/' . $aRow['id']);
    $toggleTitle = $aRow['status'] === 'active' ? _l('promo_codes_deactivate') : _l('promo_codes_activate');
    $toggleIcon  = $aRow['status'] === 'active' ? 'fa fa-ban text-warning' : 'fa fa-check text-success';

    $actions  = '<div class="tw-flex tw-items-center tw-space-x-3">';
    $actions .= '<a data-toggle="tooltip" data-original-title="' . $toggleTitle . '" href="' . $toggleLink . '" class="tw-text-yellow-600 hover:tw-text-yellow-800"><i class="' . $toggleIcon . '"></i></a>';
    $actions .= '<a data-toggle="tooltip" data-original-title="' . _l('view') . '" href="' . $viewLink . '" class="tw-text-neutral-500 hover:tw-text-neutral-700"><i class="fa fa-eye"></i></a>';
    $actions .= '<a data-toggle="tooltip" data-original-title="' . _l('edit') . '" href="' . $editLink . '" class="tw-text-blue-500 hover:tw-text-blue-700"><i class="fa fa-edit"></i></a>';
    $actions .= '<a data-toggle="tooltip" data-original-title="' . _l('delete') . '" href="' . $deleteLink . '" class="_delete text-danger tw-text-danger-500 hover:tw-text-red-700"><i class="fa fa-trash"></i></a>';
    $actions .= '</div>';

    $row[] = $actions;

    $output['aaData'][] = $row;
}