<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('flexstage_ticket') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;">' . $ticket['name'] . '</b><br/>';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . $ticketsale['reference_code'] . '</b>';


$info_right_column .= ' - <a style="color:#84c529;text-decoration:none;text-transform:uppercase;" href="' . flexstage_get_client_event_url($event['slug']) . '"><1b>' . $event['name'] . '</1b></a>';


// Add logo
$info_left_column .= pdf_logo_url();


// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

$qr_code = flexstage_get_qr_image($ticketsale['reference_code']);

// Purchased by
$ticket_info = '<b>' . _l('flexstage_purchased_by') . ':</b>';
$ticket_info .= '<div style="color:#424242;">';
$ticket_info .= $ticketorder['attendee_name'];
$ticket_info .= '</div>';

$ticket_info .= '<br /><b>' . _l('flexstage_event_start_date') . ':</b> ' . flexstage_format_date($event['start_date']) . '<br />';
$ticket_info .= '<br /><b>' . _l('flexstage_event_end_date') . ':</b> ' . flexstage_format_date($event['end_date']) . '<br />';
$ticket_info .= '<br /><b>' . _l('flexstage_event_location') . ':</b> ' . flexstage_format_venue($event['type'], $event['event_link'], $event['location']) . '<br />';

$left_info = $qr_code;
$right_info = $ticket_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);
