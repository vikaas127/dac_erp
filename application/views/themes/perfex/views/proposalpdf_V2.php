<?php
defined('BASEPATH') or exit('No direct script access allowed');
include_once(APPPATH . 'libraries/App_items_table.php');

$CI =& get_instance();

// === BASIC INFO ===


$logo_type = get_option('quotation_logo_type'); 

$logo_width = 120;
$logo_align = 'text-align:left; padding: 10px 10px 10px 10px;';

switch ($logo_type) {

    case 'dark':
        $logo_file  = get_option('company_logo_dark');
        $logo_width = 120;
        $logo_align = 'text-align:left; padding: 10px 10px 10px 10px;';
        break;

    case 'letterhead':
        $logo_file  = get_option('letterhead');
        $logo_width = 1200;
        $logo_align = 'text-align:center;';
        break;

    default: 
        $logo_file  = get_option('company_logo');
        $logo_width = 120;
        $logo_align = 'text-align:left; padding: 10px 10px 10px 10px;';
        break;
}

$logo_path = FCPATH . 'uploads/company/' . $logo_file;


if (file_exists($logo_path) && !empty($logo_file)) {
    $pdf_logo_url = '<img src="' . $logo_path . '" width="' . $logo_width . 'px" style="height:40mm; display:block; margin:0; padding:0; border:none;">';

} else {
    $pdf_logo_url = pdf_logo_url(); 
}

$header_border = ($logo_type === 'letterhead') ? '1.5px solid #000' : 'none';
$is_letterhead = ($logo_type === 'letterhead');


$proposal_id = $proposal->id ?? 0;
$formatted_proposal_id = 'PRO-' . str_pad($proposal_id, 6, '0', STR_PAD_LEFT);
$org_info = trim(format_organization_info());

// CLIENT ID from proposal
$client_id = $proposal->rel_id;

// Get full client info
$client = $CI->clients_model->get($client_id);

// Company & GST
$client_company = $client->company ?? '';
$client_gst     = $client->vat ?? '';

// Address from client (not proposal)
$client_address = $client->address ?? '';
$client_city    = $client->city ?? '';
$client_state   = $client->state ?? '';
$client_zip     = $client->zip ?? '';

// Phone
$client_phone = $client->phonenumber ?? '';

// Primary contact (name + email)
$primary_contact_id = get_primary_contact_user_id($client_id);
$contact = $CI->clients_model->get_contact($primary_contact_id);

$client_name  = trim(($contact->firstname ?? '').' '.($contact->lastname ?? ''));
$client_email = $contact->email ?? '';


// === PROPOSAL INFO ===
$proposal_date  = !empty($proposal->date) ? _d($proposal->date) : '';
$expiry_date    = !empty($proposal->open_till) ? _d($proposal->open_till) : '';
$currency       = $proposal->currency_name ?? '';
$document_title = strtoupper($proposal->type ?? 'Proposal');

$sub_total        = (float) $proposal->subtotal;
$discount_total   = (float) $proposal->discount_total;
$local_forwarding_charges  = (float) ($proposal->local_forwarding_charges ?? 0);
$freight_charges = (float) ($proposal->freight_charges ?? 0);
$packing_charges    = (float) ($proposal->packing_charges ?? 0);
$total_tax        = (float) $proposal->total_tax;
$grand_total      = (float) $proposal->total;
$adjustment = (float) ($proposal->adjustment ?? 0);



// === HTML CONTENT START ===
$html = '
<style>
body {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 10px;
  color: #000;
  margin: 8px;
}

.table {
  border-collapse: collapse;
  width: 100%;
  table-layout: fixed;
  word-wrap: break-word;
}

.table td, .table th {
  border: 1.5px solid #000;
  padding: 5px 6px;
  vertical-align: top;
}

.table th {
  font-weight: bold;
  font-size: 11px;
  background-color: #f8f8f8;
}

.bold {
  font-weight: bold;
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.small {
  font-size: 9px;
}

h3 {
  text-align: center;
  margin: 3px 0;
  font-size: 13px;
  text-transform: uppercase;
  font-weight: bold;
}

.amount-words {
  font-size: 10px;
}

.footer-note {
  text-align: center;
  font-size: 8px;
  margin-top: 6px;
}

.title-box {
  font-size: 16px;
  font-weight: bold;
  text-transform: uppercase;
  text-align: center;
  padding: 6px 0;
}
</style>

<!-- Header --> 
<table width="100%" cellpadding="0.5" cellspacing="0.5"
       style="border:'.($is_letterhead ? '1.5px solid #000' : 'none').';
              border-collapse:collapse;
              margin:0; padding:0;">
  <tr>
    <td style="'.$logo_align.'
               padding: 10px 10px 10px 10px; margin:0; line-height:0;">
        '.$pdf_logo_url.'
    </td>
  </tr>
</table>

<!-- === PROPOSAL TITLE === -->
<table width="100%" cellpadding="6" cellspacing="0"
       style="border:1.5px solid #000; border-collapse:collapse; margin-bottom:8px;">

  <tr>
    <td class="title-box">
      ' . $document_title . '
    </td>
  </tr>
</table>


<!-- === ORG & CLIENT INFO === -->
<table width="100%" cellpadding="4" cellspacing="0" class="table">
  <tr>
    <td rowspan="2" width="50%">
      ' . $org_info . '
    </td>
    <td colspan="2" width="50%">
      <b>Proposal Details:</b><br>
      Proposal No: ' . $formatted_proposal_id . '<br>
      Proposal Date: ' . $proposal_date . '<br>
      Expiry Date: ' . $expiry_date . '<br>
      ';

if ($proposal->delivery_type_id && get_option('show_delivery_type_on_proposals') == 1) {
    $html .= _l('delivery_type') . ': ' . get_delivery_type_name($proposal->delivery_type) . '<br>';
}

if ($proposal->transport_name && get_option('show_transport_name_on_proposals') == 1) {
    $html .= _l('transport_name') . ': ' . $proposal->transport_name . '<br>';
}

if ($proposal->payment_terms && get_option('show_payment_terms_on_proposals') == 1) {
    $html .= _l('payment_terms') . ': ' . $proposal->payment_terms . '<br>';
}

$html .= '</td>

  </tr>

  <tr></tr>

  <tr>
<td width="50%">
  <b>Client (Bill To):</b><br>
  ' . (!empty($client_company) ? '<b>'.$client_company.'</b><br>' : '') .
  (!empty($client_address) ? $client_address.'<br>' : '') .
  (
    !empty($client_city) || !empty($client_state) || !empty($client_zip)
      ? trim(
          (!empty($client_city) ? $client_city : '') .
          (!empty($client_state) ? ', '.$client_state : '') .
          (!empty($client_zip) ? ' - '.$client_zip : '')
        ) . '<br>'
      : ''
  ) .
  (!empty($client_gst) ? 'GSTIN/UIN: '.$client_gst.'<br>' : '') .
  (!empty($client_name) ? 'Name: '.$client_name.'<br>' : '') .
  (!empty($client_phone) ? 'MOB: '.$client_phone.'<br>' : '') .
  (!empty($client_email) ? 'Email: '.$client_email : '') . '
</td>



   <td colspan="2" width="50%">
  <b>Consignee (Ship To):</b><br>
  <b>' . ($proposal->proposal_to ?? 'N/A') . '</b><br>'
  . (!empty($proposal->address) ? $proposal->address . '<br>' : '') .
  (!empty($proposal->city) || !empty($proposal->state) || !empty($proposal->zip)
      ? trim(($proposal->city ?? '') . ', ' . ($proposal->state ?? '') . ' - ' . ($proposal->zip ?? '')) . '<br>'
      : '') .
  (!empty($proposal->country) ? get_country_short_name($proposal->country) . '<br>' : '') .
  (!empty($proposal->phone) ? 'Phone: ' . $proposal->phone . '<br>' : '') .
  (!empty($proposal->email) ? 'Email: ' . $proposal->email : '') . '
</td>

  </tr>
</table>

<!-- === ITEMS TABLE === -->
<table width="100%" cellpadding="4" cellspacing="0" class="table">
<tr class="bold">
  <th width="5%">S.No</th>
  <th width="30%">Description</th>
  <th width="8%">Qty</th>
  <th width="10%">Unit</th>
  <th width="12%">Rate</th>
  <th width="15%">Discount %</th>
  <th width="10%">Tax</th>
  <th width="10%">Amount</th>
</tr>
';

$item_taxes = $CI->db
    ->select('itemid, taxrate, taxname')
    ->from(db_prefix().'item_tax')
    ->where('rel_id', $proposal->id)
    ->where('rel_type', 'proposal')
    ->get()
    ->result_array();

$tax_map = [];
foreach ($item_taxes as $t) {
    $tax_map[$t['itemid']][] = [
        'taxrate' => $t['taxrate'],
        'taxname' => $t['taxname']
    ];
}
$counter = 1;

foreach ($proposal->items as $item) {

    $qty  = (float)$item['qty'];
    $rate = (float)$item['rate'];

    // 🔹 Line amount
    $line_amount = $qty * $rate;

    // 🔹 Discount (same as invoice)
    $discount_percent = (float)($item['item_discount_percent'] ?? 0);
    $discount_amount  = ($line_amount * $discount_percent) / 100;
    $final_amount     = $line_amount - $discount_amount;

    // 🔹 Tax display (same as invoice)
    $total_tax_percent = 0;
    if (!empty($tax_map[$item['id']])) {
        foreach ($tax_map[$item['id']] as $tax) {
            $total_tax_percent += (float)$tax['taxrate'];
        }
    }

    $tax_display = $total_tax_percent > 0
        ? number_format($total_tax_percent, 2) . '%'
        : '—';

    $discount_display = $discount_percent > 0
        ? number_format($discount_percent, 2) . '% (' .
          app_format_money($discount_amount, $currency) . ')'
        : '—';

    $html .= '
    <tr>
      <td class="text-center">'.$counter++.'</td>
      <td>'.$item['description'].'<br><span class="small">'.$item['long_description'].'</span></td>
      <td class="text-center">'.$qty.'</td>
      <td class="text-center">'.($item['unit'] ?? '-').'</td>
      <td class="text-right">'.app_format_money($rate, $currency).'</td>
      <td class="text-center">'.$discount_display.'</td>
      <td class="text-center">'.$tax_display.'</td>
      <td class="text-right">'.app_format_money($final_amount, $currency).'</td>
    </tr>';
}


// Add empty rows if less than 8 items
$min_rows = 5;
$item_count = !empty($proposal->items) ? count($proposal->items) : 0;

if ($item_count < $min_rows) {
    $extra_rows = $min_rows - $item_count;
    for ($i = 0; $i < $extra_rows; $i++) {
        $html .= '
        <tr>
            <td class="text-center">&nbsp;</td>
            <td>&nbsp;</td>
            <td class="text-center">&nbsp;</td>
            <td class="text-right">&nbsp;</td>
            <td class="text-right">&nbsp;</td>
            <td class="text-center">&nbsp;</td>
            <td class="text-right">&nbsp;</td>
            <td class="text-right">&nbsp;</td>
        </tr>';
    }
}

$html .= '
<tr>
  <td colspan="6" class="text-right bold">Sub Total</td>
  <td colspan="2"  class="text-right">' . app_format_money($sub_total, $currency) . '</td>
</tr>';


if ($discount_total > 0) {
  $html .= '
  <tr>
    <td colspan="6" class="text-right bold">Discount</td>
    <td colspan="2"  class="text-right">-' . app_format_money($discount_total, $currency) . '</td>
  </tr>';
}

$taxes = [];
$total_tax_before_discount = 0;

foreach ($proposal->items as $item) {

    $line_amount = $item['qty'] * $item['rate'];

    if (empty($tax_map[$item['id']])) continue;

    foreach ($tax_map[$item['id']] as $tax) {

        $key = trim($tax['taxname']) . '|' . number_format($tax['taxrate'], 2);
        $tax_amount = ($line_amount * $tax['taxrate']) / 100;

        if (!isset($taxes[$key])) {
            $taxes[$key] = [
                'taxname' => $tax['taxname'],
                'taxrate' => (float)$tax['taxrate'],
                'amount'  => 0
            ];
        }

        $taxes[$key]['amount'] += $tax_amount;
        $total_tax_before_discount += $tax_amount;
    }
}
$discount_ratio = 0;

if (
    !empty($proposal->discount_total) &&
    !empty($proposal->subtotal) &&
    $proposal->discount_type === 'before_tax'
) {
    $discount_ratio = $proposal->discount_total / $proposal->subtotal;
}

$total_tax = 0;

foreach ($taxes as &$tax) {

    if ($discount_ratio > 0) {
        $tax['amount'] -= ($tax['amount'] * $discount_ratio);
    }

    $total_tax += $tax['amount'];
}
unset($tax);
foreach ($taxes as $tax) {

    if ($tax['amount'] <= 0) continue;

    $html .= '
    <tr>
        <td colspan="6" class="text-right bold">'
        . $tax['taxname'] . ' (' . number_format($tax['taxrate'], 2) . '%)
        </td>
        <td colspan="6" class="text-right">'
        . app_format_money($tax['amount'], $currency) .
        '</td>
    </tr>';
}
$html .= '
<tr>
    <td colspan="6" class="text-right bold">Total Tax</td>
    <td colspan="2"  class="text-right">'
    . app_format_money($total_tax, $currency) .
    '</td>
</tr>';


if ($local_forwarding_charges > 0) {
  $html .= '
  <tr>
    <td colspan="6" class="text-right bold">Packing Charges</td>
    <td colspan="2"  class="text-right">' . app_format_money($local_forwarding_charges, $currency) . '</td>
  </tr>';
}

if ($freight_charges > 0) {
  $html .= '
  <tr>
    <td colspan="6" class="text-right bold">Shipping Charges</td>
    <td colspan="2"  class="text-right">' . app_format_money($freight_charges, $currency) . '</td>
  </tr>';
}

if ($packing_charges > 0) {
  $html .= '
  <tr>
    <td colspan="6" class="text-right bold">Other Charges</td>
    <td colspan="2"  class="text-right">' . app_format_money($packing_charges, $currency) . '</td>
  </tr>';
}
if ($adjustment != 0) {
  $html .= '
  <tr>
    <td colspan="6" class="text-right bold">Adjustment</td>
    <td colspan="2"  class="text-right">'
    . ($adjustment > 0 ? app_format_money($adjustment, $currency)
                       : '-' . app_format_money(abs($adjustment), $currency)) .
    '</td>
  </tr>';
}

$html .= '
<tr class="bold">
  <td colspan="6" class="text-right">Total</td>
  <td colspan="2" class="text-right">' . app_format_money($grand_total, $currency) . '</td>
</tr>';
$html .= '
<!-- AMOUNT IN WORDS -->
  <tr  width="100%" cellpadding="4" cellspacing="0" class="table" style="margin-top:5px;">
    <td width="70%"><b>Amount Chargeable (in words):</b> ' .ucwords($CI->numberword->convert($grand_total, $currency)) .  '</td>
    <td width="30%" class="text-right"><b>E. & O.E</b></td>
  </tr>

  <div class="footer-note">
  This is a Computer Generated Proposal
</div>
</table>';

//  $html .= '
// <!-- === FOOTER SECTION === -->
// <table width="100%" cellpadding="6" cellspacing="0" class="table" style="margin-top:10px;">
//   <tr>
//     <td width="100%" valign="top">
//       <b>Terms & Conditions:</b><br>' .
//       (!empty($proposal->content) ? nl2br($proposal->content) : "") . '<br><br>
//     </td>
//   </tr>
// </table>

// <div class="footer-note">
//   This is a Computer Generated Proposal
// </div>
// ';

// Clean whitespace
$html = preg_replace('/\s+</', '<', $html);
$html = preg_replace('/>\s+/', '>', $html);

// Generate PDF
$pdf->writeHTML($html, true, false, true, false, '');

// echo '<pre>';
// print_r($proposal->taxes);
// exit;

?>
