<?php
defined('BASEPATH') or exit('No direct script access allowed');
include_once(APPPATH . 'libraries/App_items_table.php');
$CI =& get_instance();

$logo_type = get_option('performance_invoice_logo_type');
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
// $formatted_estimate_id = 'EST-' . str_pad(($estimate->id ?? 0), 6, '0', STR_PAD_LEFT);
$prefix = $estimate->prefix ?? 'EST';
$number = $estimate->number ?? 0;

$formatted_estimate_id = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);

$org_info     = trim(format_organization_info());
$client = $estimate->client;

$primary_contact_id = get_primary_contact_user_id($estimate->clientid);
$contact = $CI->clients_model->get_contact($primary_contact_id);
$client_company  = $client->company ?? '';
$client_name     = trim(($contact->firstname ?? '').' '.($contact->lastname ?? ''));
$client_email    = $contact->email ?? '';
$client_phone    = $client->phonenumber ?? '';
$client_gst      = $client->vat ?? 'N/A';
// Billing address
$billing_address = $estimate->billing_street ?? '';
$billing_city    = $estimate->billing_city ?? '';
$billing_state   = $estimate->billing_state ?? '';
$billing_zip     = $estimate->billing_zip ?? '';
// Shipping address
$shipping_address = $estimate->shipping_street ?? '';
$shipping_city    = $estimate->shipping_city ?? '';
$shipping_state   = $estimate->shipping_state ?? '';
$shipping_zip     = $estimate->shipping_zip ?? '';
// === ESTIMATE INFO ===
$estimate_date  = trim(isset($estimate->date) ? _d($estimate->date) : '');
$expiry_date    = trim(isset($estimate->expirydate) ? _d($estimate->expirydate) : '');
$reference_no   = trim($estimate->reference_no ?? '');
$currency       = trim($estimate->currency_name ?? '');
$document_title = strtoupper($estimate->type ?? 'Proforma Invoice');
// === STYLES ===
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
  padding: 4px;
  vertical-align: top;
}
.table th {
  font-weight: bold;
  font-size: 11px;
  background-color: #F8F8F8;
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
.title-box {
  font-size: 16px;
  font-weight: bold;
  text-transform: uppercase;
  text-align: center;
  padding: 6px 0;
}
.page-continuation {
  page-break-before: always;
  margin-top: 0;
}
.items-table-header {
  display: table-header-group;
  page-break-inside: avoid;
}
.summary-table {
  margin-top: 10px;
}


</style>';
$header_html = '
<!-- === HEADER === -->
<table width="100%" cellpadding="0.5" cellspacing="0.5"
       style="border:'.($is_letterhead ? '1.5px solid #000' : 'none').';
              border-collapse:collapse;
              margin: 0; padding: 0;">
  <tr>
    <td style="'.$logo_align.'
               padding: 10px 10px 10px 10px; margin:10px; line-height:0;">
        '.$pdf_logo_url.'
    </td>
  </tr>
</table>
<!-- === DYNAMIC TITLE BOX === -->
<table width="100%" cellpadding="6" cellspacing="0"
       style="border:1.5px solid #000; border-collapse:collapse; margin-bottom:5px;">
  <tr>
    <td class="title-box">
      ' . $document_title . '
    </td>
  </tr>
</table>';
$html .= $header_html;
$html .= '
<table width="100%" cellpadding="2" cellspacing="0" class="table">
  <tr>
    <td rowspan="2" width="50%">
      ' . $org_info . '
    </td>
    <td colspan="2" width="50%">
      <b>Proforma Details:</b><br>
      PI No: ' . $formatted_estimate_id . '<br>
      Proforma Date: ' . $estimate_date . '<br>
      Expiry Date: ' . $expiry_date . '<br>
  
    Reference #: ' .$reference_no.'<br>
      Sale Agent: ' . (
    !empty($estimate->sale_agent)
        ? get_staff_full_name($estimate->sale_agent)
        : '-'
) . '<br>
    </td>
  </tr>
  <tr></tr>
  <tr>
   <td width="50%">
  <b>Buyer (Bill To)</b><br>
  ' . (!empty($client_company) ? '<b>'.$client_company.'</b><br>' : '') .
  (!empty($billing_address) ? $billing_address.'<br>' : '') .
  (
    !empty($billing_city) || !empty($billing_state) || !empty($billing_zip)
      ? trim(
          (!empty($billing_city) ? $billing_city : '') .
          (!empty($billing_state) ? ', '.$billing_state : '') .
          (!empty($billing_zip) ? ' - '.$billing_zip : '')
        ) . '<br>'
      : ''
  ) .
  (!empty($client_gst) ? 'GSTIN/UIN: '.$client_gst.'<br>' : '') .
  (!empty($client_name) ? 'Name: '.$client_name.'<br>' : '') .
  (!empty($client_phone) ? 'MOB: '.$client_phone.'<br>' : '') .
  (!empty($client_email) ? 'Email: '.$client_email : '') . '
</td>

   <td width="50%">
  <b>Consignee (Ship To):</b><br>
  ' . (!empty($client_company) ? '<b>'.$client_company.'</b><br>' : '') .
  (!empty($shipping_address) ? $shipping_address.'<br>' : '') .
  (
    !empty($shipping_city) || !empty($shipping_state) || !empty($shipping_zip)
      ? trim(
          (!empty($shipping_city) ? $shipping_city : '') .
          (!empty($shipping_state) ? ', '.$shipping_state : '') .
          (!empty($shipping_zip) ? ' - '.$shipping_zip : '')
        ) . '<br>'
      : ''
  ) .
  (!empty($client_gst) ? 'GSTIN/UIN: '.$client_gst.'<br>' : '') .
  (!empty($client_name) ? 'Name: '.$client_name.'<br>' : '') .
  (!empty($client_phone) ? 'MOB: '.$client_phone.'<br>' : '') .
  (!empty($client_email) ? 'Email: '.$client_email : '') . '
</td>

  </tr>
</table>';
// === SINGLE CONTINUOUS ITEMS TABLE ===
$html .= '
<table width="100%" cellpadding="2" cellspacing="0" class="table">
<thead class="items-table-header">
<tr class="bold">
  <th width="5%" class="text-center">S.No</th>
  <th width="29%" class="text-center">Items</th>
  <th width="10%" class="text-center">HSN/SAC</th>
  <th width="10%" class="text-center">Tax</th>
  <th width="8%" class="text-center">Qty</th>
  <th width="10%" class="text-center">Unit</th>
  <th width="8%" class="text-right">Rate</th>
  <th width="10%" class="text-center">Discount</th>
  <th width="10%" class="text-right">Amount</th>
</tr>
</thead>
<tbody>
';

$counter = 1;
$item_taxes = $CI->db
    ->select('itemid, taxrate, taxname')
    ->from(db_prefix() . 'item_tax')
    ->where('rel_id', $estimate->id)
    ->where('rel_type', 'estimate')
    ->get()
    ->result_array();
$tax_map = [];
foreach ($item_taxes as $t) {
    $tax_map[$t['itemid']][] = [
        'taxrate' => $t['taxrate'],
        'taxname' => $t['taxname']
    ];
}

foreach ($estimate->items as $item) {
    $qty = $item['qty'];
    $rate = $item['rate'];
    $total_tax_percent = 0;
    if (isset($tax_map[$item['id']])) {
        foreach ($tax_map[$item['id']] as $tax) {
            $total_tax_percent += (float)$tax['taxrate'];
        }
    }
    $tax_display = $total_tax_percent > 0 ? number_format($total_tax_percent, 2).'%' : '—';
    $amount = $qty * $rate;
$line_total = $qty * $rate;
$discount_percent = (float)($item['item_discount_percent'] ?? 0);
$discount_amount  = 0;
if ($discount_percent > 0) {
    $discount_amount = ($line_total * $discount_percent) / 100;
}
$discount_display = $discount_percent > 0
    ? number_format($discount_percent, 2) . '% (' . app_format_money($discount_amount, $currency) . ')'
    : '-';
$final_amount = $line_total - $discount_amount;
$html .= '
<tr>
  <td width="5%"  class="text-center">' . $counter++ . '</td>

  <td width="29%">
    ' . $item['description'] . '
    <br><span class="small">' . ($item['long_description'] ?? '') . '</span>
  </td>

  <td width="10%" class="text-center">' . ($item['hsn_code'] ?? 'N/A') . '</td>
  <td width="10%" class="text-center">' . $tax_display . '</td>
  <td width="8%"  class="text-center">' . $qty . '</td>
  <td width="10%" class="text-center">' . ($item['unit'] ?? 'N/A') . '</td>
  <td width="8%"  class="text-right">' . app_format_money($rate, $currency) . '</td>
  <td width="10%"  class="text-center">' . $discount_display . '</td>
  <td width="10%" class="text-right">' . app_format_money($final_amount, $currency) . '</td>
</tr>
';
}
// 🔹 ADD 5 EMPTY ROWS
// $empty_rows = 5;
$total_visible_rows = 3; 
$item_count = count($estimate->items);


$empty_rows = max(0, $total_visible_rows - $item_count);

for ($i = 0; $i < $empty_rows; $i++) {
    $html .= '
    <tr>
      <td class="text-center">&nbsp;</td>
      <td>&nbsp;</td>
      <td class="text-center">&nbsp;</td>
      <td class="text-center">&nbsp;</td>
      <td class="text-center">&nbsp;</td>
      <td class="text-center">&nbsp;</td>
      <td class="text-right">&nbsp;</td>
      <td class="text-center">&nbsp;</td>
      <td class="text-right">&nbsp;</td>
    </tr>';
}

$html .= '
  </tbody>
</table>';

$html .= '
<table width="100%" cellpadding="2" cellspacing="0" class="table summary-table">
  <tr>
    <td colspan="8" class="text-right bold">Sub Total</td>
    <td class="text-right">' . app_format_money($estimate->subtotal, $currency) . '</td>
  </tr>';
if ((float)$estimate->discount_total != 0) {
    $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Discount</td>
    <td class="text-right">-' . app_format_money($estimate->discount_total, $currency) . '</td>
  </tr>';
}
if ((float)$estimate->local_forwarding_charges > 0) {
    $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Other Charges</td>
    <td class="text-right">' . app_format_money($estimate->local_forwarding_charges, $currency) . '</td>
  </tr>';
}
if ((float)$estimate->freight_charges > 0) {
    $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Shipping Charges</td>
    <td class="text-right">' . app_format_money($estimate->freight_charges, $currency) . '</td>
  </tr>';
}
if ((float)$estimate->packing_charges > 0) {
    $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Packing Charges</td>
    <td class="text-right">' . app_format_money($estimate->packing_charges, $currency) . '</td>
  </tr>';
}


$taxes = [];
$total_tax_before_discount = 0;

foreach ($estimate->items as $item) {

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
    !empty($estimate->discount_total) &&
    !empty($estimate->subtotal) &&
    $estimate->discount_type === 'before_tax'
) {
    $discount_ratio = $estimate->discount_total / $estimate->subtotal;
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
        <td colspan="8" class="text-right bold">'
        . $tax['taxname'] . ' (' . number_format($tax['taxrate'], 2) . '%)
        </td>
        <td colspan="8" class="text-right">'
        . app_format_money($tax['amount'], $currency) .
        '</td>
    </tr>';
}
$html .= '
<tr>
    <td colspan="8" class="text-right bold">Total Tax</td>
    <td colspan="2"  class="text-right">'
    . app_format_money($total_tax, $currency) .
    '</td>
</tr>';
if ((float)$estimate->adjustment != 0) {
    $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Adjustment</td>
    <td class="text-right">' . app_format_money($estimate->adjustment, $currency) . '</td>
  </tr>';
}
$html .= '
  <tr class="bold">
    <td colspan="8" class="text-right">Total</td>
    <td class="text-right">' . app_format_money($estimate->total, $currency) . '</td>
  </tr>
</table>';
$html .= '
<!-- AMOUNT IN WORDS -->
<table width="100%" cellpadding="4" cellspacing="0" class="table" style="margin-top:5px;">
  <tr>
    <td width="70%">
      <b>Amount Chargeable (in words):</b>
      ' . ucwords($CI->numberword->convert($estimate->total, $currency)) . '
    </td>
    <td width="30%" class="text-right">
      <b>E. & O.E</b>
    </td>
  </tr>
</table>';


$noteText  = trim($estimate->clientnote ?: '');
$termsText = trim($estimate->terms ?: '');
function clean_multiline_text($text)
{
    $text = nl2br(trim($text));
    
    $text = preg_replace('/(<br\s*\/?>\s*){2,}/i', '<br>', $text);
    return $text;
}
$noteText  = clean_multiline_text($noteText);
$termsText = clean_multiline_text($termsText);
$html .= '
<div style="margin-top:10px;">
  <table width="100%" cellpadding="2" cellspacing="0" class="table">
    <tr>
      <td style="padding:8px; vertical-align:top;">
        <b>Note:</b><br>' . $noteText . '<br><br>
        <b>Terms & Conditions:</b><br>' . $termsText . '
      </td>
    </tr>
  </table>
</div>';
$html = preg_replace('/\s+</', '<', $html);
$html = preg_replace('/>\s+/', '>', $html);
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);
$pdf->writeHTML(
    '<div style="text-align:center; font-size:8px;">
        This is a Computer Generated Proforma Invoice
     </div>',
    false, false, false, false, ''
);
    // echo "<pre>";
    // print_r($estimate);
    // echo "</pre>";
    // exit;
?>