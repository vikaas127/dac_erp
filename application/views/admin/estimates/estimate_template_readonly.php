<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>


      

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
    <!-- Client Info -->
    <div class="col-md-6 mb-3">
        <label class="form-label"><?php echo _l('estimate_select_customer'); ?>:</label>
        <div class="form-control-static border rounded p-2 bg-light">
            <?php echo !empty($revision->client_data->company) ? e($revision->client_data->company) : '--'; ?>
        </div>
    </div>

    <!-- Project Info -->
    <div class="col-md-6 mb-3">
        <label class="form-label"><?php echo _l('project'); ?>:</label>
        <div class="form-control-static border rounded p-2 bg-light">
            <?php echo !empty($revision->project_id) ? e(get_project_name_by_id($revision->project_id)) : '--'; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Billing Info -->
    <div class="col-md-6 mb-3">
        <p class="fw-bold"><?php echo _l('invoice_bill_to'); ?></p>
        <div class="border rounded p-2 bg-light">
            <p><?php echo process_text_content_for_display($revision->billing_street ?? '--'); ?></p>
            <p><?php echo e($revision->billing_city ?? '--'); ?>, <?php echo e($revision->billing_state ?? '--'); ?></p>
            <p><?php echo e(get_country_short_name($revision->billing_country) ?? '--'); ?>, <?php echo e($revision->billing_zip ?? '--'); ?></p>
        </div>
    </div>

    <!-- Shipping Info -->
    <div class="col-md-6 mb-3">
        <p class="fw-bold"><?php echo _l('ship_to'); ?></p>
        <div class="border rounded p-2 bg-light">
            <p><?php echo process_text_content_for_display($revision->shipping_street ?? '--'); ?></p>
            <p><?php echo e($revision->shipping_city ?? '--'); ?>, <?php echo e($revision->shipping_state ?? '--'); ?></p>
            <p><?php echo e(get_country_short_name($revision->shipping_country) ?? '--'); ?>, <?php echo e($revision->shipping_zip ?? '--'); ?></p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Estimate Number -->
    <div class="col-md-6 mb-3">
        <label class="form-label"><?php echo _l('estimate_add_edit_number'); ?>:</label>
        <div class="form-control-static border rounded p-2 bg-light">
            <?php
            $prefix = $revision->prefix ?? get_option('estimate_prefix');
            $number = $revision->number ?? get_option('next_estimate_number');
            $format = $revision->number_format ?? get_option('estimate_number_format');
            $padded = str_pad($number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);

            if ($format == 2) {
                $prefix .= date('/Y', strtotime($revision->date ?? date('Y-m-d')));
            } elseif ($format == 3) {
                $prefix .= date('y', strtotime($revision->date ?? date('Y-m-d')));
            } elseif ($format == 4) {
                $prefix .= date('/m/Y', strtotime($revision->date ?? date('Y-m-d')));
            }

            echo $prefix . $padded;
            ?>
        </div>
    </div>

    <!-- Estimate & Expiry Dates -->
    <div class="col-md-3 mb-3">
        <label class="form-label"><?php echo _l('estimate_add_edit_date'); ?>:</label>
        <div class="form-control-static border rounded p-2 bg-light">
            <?php echo !empty($revision->date) ? _d($revision->date) : '--'; ?>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label"><?php echo _l('estimate_add_edit_expirydate'); ?>:</label>
        <div class="form-control-static border rounded p-2 bg-light">
            <?php echo !empty($revision->expirydate) ? _d($revision->expirydate) : '--'; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Production Assigned -->
    <div class="col-md-6 mb-3">
        <label class="form-label"><?php echo _l('production_assigned_to'); ?>:</label>
        <div class="form-control-static border rounded p-2 bg-light">
            <?php echo !empty($revision->production_assigned_to) ? e(get_staff_full_name($revision->production_assigned_to)) : '--'; ?>
        </div>
    </div>

    <!-- Production Type -->
    <div class="col-md-6 mb-3">
        <label class="form-label"><?php echo _l('production_type'); ?>:</label>
        <div class="form-control-static border rounded p-2 bg-light">
            <?php echo !empty($revision->production_type) ? ucfirst($revision->production_type) : '--'; ?>
        </div>
    </div>
</div>




                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <h5><?php echo _l('estimate_items'); ?></h5>
                                <div class="table-responsive">
                                    <table class="table items table-main-estimate-edit no-mtop">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th class="text-left"><?php echo _l('estimate_table_item_heading'); ?></th>
                                                <th class="text-left"><?php echo _l('description'); ?></th>
                                                <th class="text-left"><?php echo _l('qty'); ?></th>
                                                <th class="text-left"><?php echo _l('rate'); ?></th>
                                                <th class="text-left"><?php echo _l('tax'); ?></th>
                                                <th class="text-right"><?php echo _l('amount'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php

                                            $items = $this->db->where('revision_id', $revision->id)->get(db_prefix() . 'estimate_revision_items')->result();
                                            $item_number = 1;
                                            foreach ($items as $item) {
                                                $taxes = json_decode($item->taxname,true);
                                                $tax_string = '';

                                                if (!empty($taxes)) {
                                                    foreach ($taxes as $tax) {
                                                        // Optional: Split name and rate from 'IGST|18.00'
                                                        $tax_parts = explode('|', $tax['taxname']);
                                                        $tax_name = $tax_parts[0] ?? '';
                                                        $tax_rate = $tax['taxrate'] ?? '';
                                                        $tax_string .= $tax_name . ' (' . $tax_rate . '%)<br>';
                                                    }
                                                }

                                                $total = $item->rate * $item->qty;
                                                echo '<tr>'; 
                                                echo '<td>' . $item_number++ . '</td>';
                                                echo '<td>' . $item->description . '</td>';
                                                echo '<td>' . $item->long_description . '</td>';
                                                echo '<td>' . $item->qty . '</td>';
                                                echo '<td>' . app_format_number($item->rate) . '</td>';
                                                echo '<td>' . $tax_string . '</td>';
                                                echo '<td class="text-right">' . app_format_number($total) . '</td>';
                                                echo '</tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                  <?php $currency = get_currency($revision->currency); ?>
              <div class="col-md-6 col-md-offset-6">
                                    <table class="table text-right">
                                        <tbody>

                                                <tr>
                                                    <td><strong>Subtotal:</strong></td>
                                                    <td>
                                                        <?php echo isset($revision->subtotal) ? app_format_money($revision->subtotal, $currency->name) : '--'; ?>
                                                    </td>
                                                </tr>
<?php if (!empty($revision->discount_total) && $revision->discount_total > 0): ?>
<tr>
    <td>
        <strong>Discount
            <?php
            if (!empty($revision->discount_percent)) {
                echo ' (' . floatval($revision->discount_percent) . '%)';
            } else {
                echo ' (Fixed)';
            }
            ?>
        :</strong>
    </td>
    <td>
       - <?php echo app_format_money($revision->discount_total, $currency->name); ?>
    </td>
</tr>
<?php endif; ?>
<?php if (!empty($revision->adjustment) && $revision->adjustment > 0): ?>
<tr>
    <td><strong>Adjudstments:</strong></td>
    <td>
        <?php echo app_format_money($revision->adjustment, $currency->name); ?>
    </td>
</tr>
<?php endif; ?>

<?php if (!empty($revision->total_tax) && $revision->total_tax > 0): ?>
<tr>
    <td><strong>Tax:</strong></td>
    <td>
        <?php echo app_format_money($revision->total_tax, $currency->name); ?>
    </td>
</tr>
<?php endif; ?>

                                                <tr>

                                                    <td><strong>Total:</strong></td>
                                                    <td>
                                                        <?php echo isset($revision->total) ? app_format_money($revision->total, $currency->name) : '--'; ?>
                                                    </td>
                                                </tr>


                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <p class="bold"><?php echo _l('estimate_note'); ?>:</p>
                                <p><?php echo $revision->clientnote; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="bold"><?php echo _l('terms'); ?>:</p>
                                <p><?php echo $revision->terms; ?></p>
                            </div>
                        </div>

                    </div>
                </div>


