<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Get page dimensions
$dimensions = $pdf->getPageDimensions();

$info_left_column = pdf_logo_url();
// $org_info = format_organization_info();

pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);
$pdf->Ln(0);

// BOM Code and Date Centered
$info = '<div style="text-align:center;">
        <span style="font-weight:bold;font-size:27px;">Bill of Material</span><br />

            <b style="color:#4e4e4e;">' . html_escape($bill_of_material->bom_code) . '</b><br />
            Date: ' . _d($bill_of_material->date_created ?? date('Y-m-d')) . '
        </div>';
$pdf->writeHTML($info, true, false, false, false, '');
$pdf->Ln(5);

$pdf->SetFont($font_name, 'B', $font_size); 
$pdf->Cell(0, 0, 'Manufactured Product Details', 0, 1, 'L');
$pdf->Ln(2);
$pdf->SetFont($font_name, '', $font_size);

$details = '
<table cellpadding="10" cellspacing="0" width="100%" style="font-size:11px; border: 1px solid #000000; border-collapse: collapse;">
    <tbody>
        <tr>
            <td style="background-color:#002a46; color:#fff; border: 1px solid #000000;" width="35%"><strong>Product ID:</strong></td>
            <td style="border: 1px solid #000000;" width="65%">' . html_escape($bill_of_material->product_id) . '</td>
        </tr>
        <tr>
            <td style="background-color:#002a46; color:#fff; border: 1px solid #000000;"><strong>Product Name:</strong></td>
            <td style="border: 1px solid #000000;">' . new_html_entity_decode(mrp_get_product_name($bill_of_material->product_id) . $bill_of_material->product_variant_name) . '</td>
        </tr>
        <tr>
            <td style="background-color:#002a46; color:#fff; border: 1px solid #000000;"><strong>Routing ID:</strong></td>
            <td style="border: 1px solid #000000;">' . html_escape($bill_of_material->routing_id) . '</td>
        </tr>
        <tr>
            <td style="background-color:#002a46; color:#fff; border: 1px solid #000000;"><strong>Routing Name:</strong></td>
            <td style="border: 1px solid #000000;">' . new_html_entity_decode(mrp_get_routing_name($bill_of_material->routing_id)) . '</td>
        </tr>
        <tr>
            <td style="background-color:#002a46; color:#fff; border: 1px solid #000000;"><strong>BOM Type:</strong></td>
            <td style="border: 1px solid #000000;">' . ($bill_of_material->bom_type == 'manufacture_this_product' ? 'Manufacture This Product' : 'Kit') . '</td>
        </tr>
        <tr>
            <td style="background-color:#002a46; color:#fff; border: 1px solid #000000;"><strong>Consumption Type:</strong></td>
            <td style="border: 1px solid #000000;">' . ($bill_of_material->consumption == 'flexible' ? 'Flexible' : 'Strict') . '</td>
        </tr>
  


    </tbody>
</table>';


$pdf->writeHTML($details, true, false, false, false, '');


$pdf->Ln(4);


// Components Table

$pdf->SetFont($font_name, 'B', $font_size);
$pdf->Cell(0, 0, 'Component Details', 0, 1, 'L');
$pdf->Ln(2);
$pdf->SetFont($font_name, '', $font_size);

$component_table = '
<table width="100%" cellpadding="15" cellspacing="0" border="1" style="font-size:11px; border-collapse: collapse; ">
    <thead>
        <tr style="background-color:#002a46; color:#fff">
            <th width="5%">#</th>
            <th width="35%">Component Name</th>
            <th width="10%" align="right">Qty</th>
            <th width="10%">Unit</th>
            <th width="20%" align="right">Price</th>
            <th width="20%" align="right">Amount</th>
        </tr>
    </thead>
    <tbody>';

$total_amount = 0;
foreach ($components as $index => $c) {
    $amount = $c['price'] * $c['product_qty'];
    $total_amount += $amount;

    $component_table .= '
        <tr>
            <td width="5%" align="center" valign="top">' . ($index + 1) . '</td>
            <td width="35%" valign="top">' . html_escape($c['product_name']) . '</td>
            <td width="10%" align="right" valign="top">' . number_format($c['product_qty'], 2) . '</td>
            <td width="10%" valign="top">' . html_escape($c['product_unit']) . '</td>
            <td width="20%" align="right" valign="top">' . number_format($c['price'], 2) . '</td>
            <td width="20%" align="right" valign="top">' . number_format($amount, 2) . '</td>
        </tr>';
}

$component_table .= '
        <tr>
            <td colspan="5" align="right"><strong>Total Amount</strong></td>
            <td align="right"><strong>' . number_format($total_amount, 2) . '</strong></td>
        </tr>
    </tbody>
</table>';
$pdf->writeHTML($component_table, true, false, false, false, '');
$pdf->Ln(5);

// Scrap Details
if (!empty($scrap_details)) {
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, 'Scrap Details', 0, 1, 'L');
    $pdf->Ln(2);
    $pdf->SetFont($font_name, '', $font_size);

    $scrap_table = '
    <table width="100%" cellpadding="15" cellspacing="0" border="1" style="font-size:11px; border-collapse: collapse;">
        <thead>
            <tr style="background-color:#002a46; color:#fff">
                <th width="5%">#</th>
                <th width="35%">Product Name</th>
                <th width="10%" align="right">Qty</th>
                <th width="10%">Unit</th>
                <th width="20%" align="right">Rate</th>
                <th width="20%" align="right">Amount</th>
            </tr>
        </thead>
        <tbody>';

    $total_scrap_cost = 0;
    $i = 1;
    foreach ($scrap_details as $scrap) {
        $amount = $scrap['price'] * $scrap['scrap_qty'];
        $total_scrap_cost += $amount;

        $scrap_table .= '
            <tr>
                <td width="5%" align="center" valign="top">' . $i++ . '</td>
                <td width="35%" valign="top">' . html_escape($scrap['product_name']) . '</td>
                <td width="10%" align="right" valign="top">' . number_format($scrap['scrap_qty'], 2) . '</td>
                <td width="10%" valign="top">' . html_escape($scrap['unit_name']) . '</td>
                <td width="20%" align="right" valign="top">' . number_format($scrap['price'], 2) . '</td>
                <td width="20%" align="right" valign="top">' . number_format($amount, 2) . '</td>
            </tr>';
    }

    $scrap_table .= '
            <tr>
                <td colspan="5" align="right"><strong>Total Scrap Value</strong></td>
                <td align="right"><strong>' . number_format($total_scrap_cost, 2) . '</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>';
    $pdf->writeHTML($scrap_table, true, false, false, false, '');
    $pdf->Ln(5);
}

// Additional Charges
$pdf->SetFont($font_name, 'B', $font_size);
$pdf->Cell(0, 0, 'Additional Charges', 0, 1, 'L');
$pdf->Ln(2);
$pdf->SetFont($font_name, '', $font_size);

$charges_table = '
<table width="100%" cellpadding="15" cellspacing="0" border="1" style="font-size:11px; border-collapse: collapse;">
    <thead>
        <tr style="background-color:#002a46; color:#fff">
            <th width="5%">#</th>
            <th width="30%">Charge Type</th>
            <th width="20%" align="right">Amount</th>
            <th width="45%">Description</th>
        </tr>
    </thead>
    <tbody>';

$charges = [
    ['label' => 'Labour Charges', 'amount' => $labour_charges, 'desc' => $labour_charges_description],
    ['label' => 'Electricity Charges', 'amount' => $electricity_charges, 'desc' => $electricity_charges_description],
    ['label' => 'Machinery Charges', 'amount' => $machinery_charges, 'desc' => $machinery_charges_description],
    ['label' => 'Other Charges', 'amount' => $other_charges, 'desc' => $other_charges_description],
];

$total_charges = 0;
$i = 1;
foreach ($charges as $charge) {
    $charges_table .= '
        <tr>
            <td width="5%" align="center" valign="top">' . $i++ . '</td>
            <td width="30%" valign="top">' . html_escape($charge['label']) . '</td>
            <td width="20%" align="right" valign="top">' . number_format($charge['amount'], 2) . '</td>
            <td width="45%" valign="top">' . html_escape($charge['desc']) . '</td>
        </tr>';
    $total_charges += $charge['amount'];
}

$charges_table .= '
        <tr>
            <td colspan="2" align="right"><strong>Total Charges</strong></td>
            <td align="right"><strong>' . number_format($total_charges, 2) . '</strong></td>
            <td></td>
        </tr>
    </tbody>
</table>';
$pdf->writeHTML($charges_table, true, false, false, false, '');
$pdf->Ln(5);

$grand_total = $total_amount + $total_scrap_cost + $total_charges;

$total = '<table cellpadding="15" cellspacing="0" width="100%" style="font-size:11px; border: 1px solid #000000; border-collapse: collapse;">

   <tbody>
<tr>
    <td style="background-color:#002a46; color:#fff; border: 1px solid #000000;"><strong>Total Cost (Components + Scrap + Charges):</strong></td>
<td style="border: 1px solid #000000;"><strong>Rs ' . number_format($grand_total, 2) . ' per product</strong></td>
</tr>    </tbody> </table>';

$pdf->writeHTML($total, true, false, false, false, '');
$pdf->Ln(5);
// Optional Notes Section
if (!empty($bill_of_material->notes)) {
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, 'Notes', 0, 1, 'L');
    $pdf->Ln(2);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->writeHTMLCell('', '', '', '', nl2br(html_escape($bill_of_material->notes)), 0, 1, false, true, 'L', true);
}
