<?php
include_once(APPPATH . 'libraries/App_items_table.php');
defined('BASEPATH') or exit('No direct script access allowed');
$formatted_invoice_id = 'INV-' . str_pad(($invoice->id ?? 0), 6, '0', STR_PAD_LEFT);
$org_info     = trim(format_organization_info());
$client_id = $invoice->clientid;
$client = $CI->clients_model->get($client_id);
$client_company = $client->company ?? '';
$client_gst     = $client->vat ?? '';
$client_phone   = $client->phonenumber ?? '';
$client_address = $invoice->billing_street ?? '';
$client_city    = $invoice->billing_city ?? '';
$client_state   = $invoice->billing_state ?? '';
$client_zip     = $invoice->billing_zip ?? '';
$billing_address = $invoice->billing_street ?? '';
$billing_city    = $invoice->billing_city ?? '';
$billing_state   = $invoice->billing_state ?? '';
$billing_zip     = $invoice->billing_zip ?? '';
$shipping_address = $invoice->shipping_street ?? '';
$shipping_city    = $invoice->shipping_city ?? '';
$shipping_state   = $invoice->shipping_state ?? '';
$shipping_zip     = $invoice->shipping_zip ?? '';
$primary_contact_id = get_primary_contact_user_id($client_id);
$contact = $CI->clients_model->get_contact($primary_contact_id);
$client_name  = trim(($contact->firstname ?? '') . ' ' . ($contact->lastname ?? ''));
$client_email = $contact->email ?? '';
$invoice_date   = trim(isset($invoice->date) ? _d($invoice->date) : '');
$payment_terms  = trim($invoice->payment_terms ?? '');
$reference_no   = trim($invoice->reference_no ?? '');
$transport_name     = trim($invoice->transport_name ?? '');
$delivery_type     = trim(get_delivery_type_name($invoice->delivery_type_id) ?? '-');
$currency       = trim($invoice->currency_name ?? '');
$destination    = trim($client_city . ' (' . $client_state . ')');
$document_title = strtoupper($invoice->type ?? 'Tax Invoices');
$subtotal        = $invoice->subtotal;
$total_tax       = $invoice->total_tax;
$grand_total     = $invoice->total;
$discount_total = $invoice->discount_total;
$adjustment     = $invoice->adjustment;
$invoice_date_display = !empty($invoice->date) ? _d($invoice->date) : '—';
$due_date_display     = !empty($invoice->duedate) ? _d($invoice->duedate) : '—';
$sale_agent_name      = !empty($invoice->sale_agent)
    ? get_staff_full_name($invoice->sale_agent)
    : '—';
$grand_total     = $invoice->total;
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
  border: 0.5px solid #000;
  padding: 5px 6px;
  vertical-align: top;
}

.table th {
  font-weight: bold;
  font-size: 11px;
  background-color: #f8f8f8;
}

.bold { font-weight: bold; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.small { font-size: 9px; }

h3 {
  text-align: center;
  margin: 3px 0;
  font-size: 13px;
  text-transform: uppercase;
  font-weight: bold;
}

.amount-words {
  font-size: 12px;
  font-weight: bold;
  line-height: 1.5;
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
<!-- === HEADER === -->

<!-- === TITLE BOX === -->
<table width="100%" cellpadding="6" cellspacing="0"
       style="border:0.5px solid #000; border-collapse:collapse; margin-bottom:8px;">
  <tr>
    <td class="title-box">' . $document_title . '</td>
  </tr>
</table>
<!-- MAIN TABLE -->
<table width="100%" cellpadding="2" cellspacing="0" class="table">
  <tr>

  <td rowspan="2" width="50%">
  <table width="100%" cellpadding="0" cellspacing="4" style="border:none;">
    <tr>
      <td width="30%" style="border:none; vertical-align:top;">
        ' . pdf_logo_url() . '
      </td>
      <td width="70%" style="border:none; vertical-align:top; padding-left:8px;">
        ' . $org_info . '
      </td>
    </tr>
  </table>
</td>

      
    <td width="25%"><b>Invoice No:</b><br>' . $formatted_invoice_id . '</td>
    <td width="25%">
  <b>Invoice Date:</b>
  ' . $invoice_date_display . '<br>

  <b>Due Date:</b>
  ' . $due_date_display . '<br>

  <b>Sale Agent:</b>
  ' . $sale_agent_name . '
</td>
  </tr>
  <tr>
    <td><b>Mode/Terms of Payment:</b><br>' . $payment_terms . '</td>
    <td><b>Reference No. & Date:</b><br>' . $reference_no . '</td>
  </tr>
  <tr>
<td rowspan="2" width="50%">
  <b>Consignee (Ship To)</b><br>
  <b>' . $client_company . '</b><br>
  ' . $shipping_address . '<br>
  ' . $shipping_city . '' . $shipping_state . '' . $shipping_zip . '<br>
  GSTIN/UIN: ' . $client_gst . '<br>
  Name: ' . $client_name . '<br>
  MOB: ' . $client_phone . '<br>
  Email: ' . $client_email . '
</td>
<td>
  <b>Dispatched Through:</b><br>
</td>
<td>
  <b>Destination:</b><br>' . $destination . '
</td>
</tr>
<tr>
  <td>
    <b>Bill of Lading/LR-RR No.:</b><br>
  </td>
  <td>
    <b>Buyer\'s Order No.:</b><br>
  </td>
</tr>
  <tr >
    <td rowspan="2"  width="50%">
      <b>Buyer (Bill To)</b><br>
      <b>' . $client_company . '</b><br>
      ' . $billing_address . '<br>' . $billing_city . '' . $billing_state . '' . $client_zip . '<br>
      GSTIN/UIN: ' . $client_gst . '<br>
       Name: ' . $client_name . '<br>
      MOB: ' . $client_phone . '<br>
      Email: ' . $client_email . '
    </td>
   <td>
  <b>Delivery Type.:</b><br>
  ' . $delivery_type . '
</td>

<td>
  <b>Delivery Note:</b><br>
</td>
  </tr>
  <tr>
  <td>
     <b>Motor Vehicle No.:</b><br>
  ' . $transport_name . '
  </td>
  <td>
  
  </td>
</tr>
</table>
<!-- === ITEMS TABLE === -->
<table width="100%" cellpadding="2" cellspacing="0" class="table">
  <tr class="bold">
    <th width="5%">S.No</th>
    <th width="29%" class="text-center">Items</th>
    <th width="10%">HSN/SAC</th>
    <th width="10%">Tax</th>
    <th width="6%">Qty</th>
    <th width="10%">Per</th>
    <th width="10%">Rate</th>
    <th width="10%">Discount%</th>
    <th width="10%">Amount</th>
  </tr>';
$counter = 1;

$item_taxes = $CI->db
    ->select('itemid, taxrate, taxname')
    ->from(db_prefix().'item_tax')
    ->where('rel_id', $invoice->id)
    ->where('rel_type', 'invoice')
    ->get()
    ->result_array();

$tax_map = [];
foreach ($item_taxes as $t) {
    $tax_map[$t['itemid']][] = [
        'taxrate' => $t['taxrate'],
        'taxname' => $t['taxname']
    ];
}


foreach ($invoice->items as $item) {
  $hsn_sac = '—';

if (!empty($item['hsn_code'])) {
    $hsn_sac = $item['hsn_code'];
} elseif (!empty($item['sac_code'])) {
    $hsn_sac = $item['sac_code'];
}

$discount_percent = $item['item_discount_percent'] ?? 0;

$line_amount = $item['qty'] * $item['rate'];
$discount_amount = ($line_amount * $discount_percent) / 100;
$discount_display = $discount_percent > 0
    ? number_format($discount_percent, 2) . '% (' .
      app_format_money($discount_amount, $currency) . ')'
    : '—';

$final_amount = $line_amount - $discount_amount;

$total_tax_percent = 0;

if (isset($tax_map[$item['id']])) {
    foreach ($tax_map[$item['id']] as $tax) {
        $total_tax_percent += (float)$tax['taxrate'];
    }
}

$tax_display = $total_tax_percent > 0
    ? number_format($total_tax_percent, 2).'%' 
    : '—';


    $html .= '
    <tr>
        <td class="text-center">'.$counter++.'</td>
        <td>'.$item['description'].'</td>
        <td class="text-center">'.$hsn_sac.'</td>
       <td class="text-center">'.$tax_display.'</td>
        <td class="text-center">'.$item['qty'].'</td>
        <td class="text-center">'.($item['unit'] ?? 'N/A').'</td>
        <td class="text-right">'.app_format_money($item['rate'], $currency).'</td>
        <td class="text-center">'.$discount_display.'</td>
      <td class="text-right">'.app_format_money($final_amount, $currency).'</td>

    </tr>';
}



$min_rows = 8;
$item_count = count($invoice->items);

if ($item_count < $min_rows) {
    $extra_rows = $min_rows - $item_count;
    for ($i = 0; $i < $extra_rows; $i++) {
        $html .= '
        <tr>
            <td class="text-center">&nbsp;</td>
            <td>&nbsp;</td>
            <td class="text-center">&nbsp;</td>
            <td class="text-center">&nbsp;</td>
            <td class="text-center">&nbsp;</td>
            <td class="text-right">&nbsp;</td>
            <td class="text-center">&nbsp;</td>
            <td class="text-center">&nbsp;</td>
            <td class="text-right">&nbsp;</td>
        </tr>';
    }
}


$html .= '
</table>

<!-- TOTALS -->
<table width="100%" cellpadding="2" cellspacing="0" class="table">

<tr>
  <td colspan="8" class="text-right bold">Sub Total</td>
  <td class="text-right">'.app_format_money($subtotal, $currency).'</td>
</tr>';

if ($discount_total > 0) {
  $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Discount</td>
    <td class="text-right">-'.app_format_money($discount_total, $currency).'</td>
  </tr>';
}
 
foreach ($invoice->items as $item) {

    $line_amount = $item['qty'] * $item['rate'];

    $discount = 0;
    if (
        isset($invoice->discount_type) &&
        $invoice->discount_type === 'before_tax'
    ) {
        if (!empty($item['discount'])) {
            $discount = ($line_amount * $item['discount']) / 100;
        }
    }

    $taxable = $line_amount - $discount;

    if (empty($tax_map[$item['id']])) {
        continue;
    }

    foreach ($tax_map[$item['id']] as $tax) {

        $tax_name = $tax['taxname'].'|'.$tax['taxrate'];
        $tax_amount = ($taxable * $tax['taxrate']) / 100;

        if (!isset($taxes[$tax_name])) {
            $taxes[$tax_name] = [
                'tax_name' => $tax_name,
                'total'    => []
            ];
        }

        $taxes[$tax_name]['total'][] = $tax_amount;
    }
}
$total_tax = 0;

foreach ($taxes as $key => $tax) {

    $tax_total = array_sum($tax['total']);

    if (
        isset($invoice->discount_percent) &&
        $invoice->discount_percent != 0 &&
        isset($invoice->discount_type) &&
        $invoice->discount_type == 'before_tax'
    ) {
        $tax_discount = ($tax_total * $invoice->discount_percent) / 100;
        $tax_total -= $tax_discount;

    } elseif (
        isset($invoice->discount_total) &&
        $invoice->discount_total != 0 &&
        isset($invoice->discount_type) &&
        $invoice->discount_type == 'before_tax'
    ) {
        $t = ($invoice->discount_total / $invoice->subtotal) * 100;
        $tax_total -= ($tax_total * $t / 100);
    }

    $tax_name_array = explode('|', $tax['tax_name']);

    $taxes[$key]['total_tax'] = $tax_total;
    $taxes[$key]['taxname']   = $tax_name_array[0];
    $taxes[$key]['taxrate']   = $tax_name_array[1];

    $total_tax += $tax_total;
}

foreach ($taxes as $tax) {

    if ($tax['total_tax'] <= 0) {
        continue;
    }

    $html .= '
    <tr>
        <td colspan="8" class="text-right bold">
            ' . $tax['taxname'] . ' (' . number_format($tax['taxrate'], 2) . '%)
        </td>
        <td class="text-right">
            ' . app_format_money($tax['total_tax'], $currency) . '
        </td>
    </tr>';
}


$html .= '
<tr>
    <td colspan="8" class="text-right bold">Total Tax</td>
    <td class="text-right">'.app_format_money($total_tax, $currency).'</td>
</tr>';

if ((float)$invoice->local_forwarding_charges > 0) {
    $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Shipping Charges</td>
    <td class="text-right">' . app_format_money($invoice->local_forwarding_charges, $currency) . '</td>
  </tr>';
}
if ((float)$invoice->freight_charges > 0) {
    $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Packing Charges</td>
    <td class="text-right">' . app_format_money($invoice->freight_charges, $currency) . '</td>
  </tr>';
}
if ((float)$invoice->packing_charges > 0) {
    $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Other Charges</td>
    <td class="text-right">' . app_format_money($invoice->packing_charges, $currency) . '</td>
  </tr>';
}

if ($adjustment != 0) {
  $html .= '
  <tr>
    <td colspan="8" class="text-right bold">Adjustment</td>
    <td class="text-right">'.app_format_money($adjustment, $currency).'</td>
  </tr>';
}
$total_paid = (float) $CI->db
    ->select_sum('amount')
    ->where('invoiceid', $invoice->id)
    ->get(db_prefix().'invoicepaymentrecords')
    ->row()
    ->amount;

$total_paid = $total_paid ?: 0;

$total       = (float) $invoice->total;
$amount_due  = max(0, $total - $total_paid);

if ($amount_due > 0) {
$html .= '
<tr class="bold">
    <td colspan="8" class="text-right">Amount Due</td>
    <td class="text-right">' . app_format_money($amount_due, $currency) . '</td>
</tr>';
}


$html .= '
<tr class="bold">
  <td colspan="8" class="text-right">Total</td>
  <td class="text-right">'.app_format_money($grand_total, $currency).'</td>
</tr>
</table>

<!-- AMOUNT IN WORDS -->
<table width="100%" cellpadding="4" cellspacing="0" class="table" style="margin-top:5px;">
  <tr>
    <td width="70%"><b>Amount Chargeable (in words):</b> ' . ucwords($CI->numberword->convert($grand_total, $currency)) .  '</td>
    <td width="30%" class="text-right"><b>E. & O.E</b></td>
  </tr>
</table>';


if ($total_tax > 0) {

    $html .= '
    <table width="100%" cellpadding="2" cellspacing="0" class="table" style="margin-top:6px;">
        <tr class="bold">
            <th>Taxable Amount</th>';

  
    foreach ($taxes as $tax) {
        if ($tax['total_tax'] <= 0) continue;

        $html .= '
            <th>' . $tax['taxname'] . ' (' . number_format($tax['taxrate'], 2) . '%)</th>';
    }

    $html .= '
            <th>Total Tax</th>
        </tr>';


    $html .= '
        <tr>
            <td class="text-right">'
                . app_format_money(($subtotal - $discount_total), $currency) .
            '</td>';

    foreach ($taxes as $tax) {
        if ($tax['total_tax'] <= 0) continue;

        $html .= '
            <td class="text-right">'
                . app_format_money($tax['total_tax'], $currency) .
            '</td>';
    }

    $html .= '
            <td class="text-right">'
                . app_format_money($total_tax, $currency) .
            '</td>
        </tr>
    </table>';
}

$html .= '
<!-- NOTE & TERMS -->
<table width="100%" cellpadding="3" cellspacing="0" class="table" style="margin-top:5px;">
  <tr>
    <td>
      <b>Note:</b><br>
      '.(!empty($invoice->clientnote) ? nl2br($invoice->clientnote) : '—').'
      <br><br>
      <b>Terms & Conditions:</b><br>
      '.(!empty($invoice->terms) ? nl2br($invoice->terms) : '—').'
    </td>
  </tr>
</table>

<div class="footer-note">
  This is a Computer Generated Invoice
</div>
';
$html = preg_replace('/\s+</', '<', $html);
$html = preg_replace('/>\s+/', '>', $html);
$pdf->writeHTML($html, true, false, true, false, '');
?>
