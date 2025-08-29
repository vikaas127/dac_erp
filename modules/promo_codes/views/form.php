<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
init_head();
$metadata = $promo_code->metadata ?? [];
$condition = $metadata['condition'] ?? [];
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <?php echo isset($promo_code) ? _l('promo_codes_edit_heading') : _l('promo_codes_create_heading'); ?>
                        </h4>
                        <hr class="hr-panel-heading" />
                        <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
                        <?php $this->load->view('authentication/includes/alerts'); ?>

                        <?php echo form_open(admin_url('promo_codes/' . (isset($promo_code) ? 'edit/' . $promo_code->id : 'create')), ['id' => 'promo_codes_form']); ?>

                        <?php
                        echo render_input(
                            'code',
                            'promo_codes_code',
                            isset($promo_code) ? $promo_code->code : '',
                            'text',
                            ['required' => true, 'minlength' => 3]
                        );

                        echo render_select(
                            'type',
                            [
                                ['id' => 'fixed', 'name' => _l('promo_codes_fixed_amount')],
                                ['id' => 'percentage', 'name' => _l('promo_codes_percentage')],
                            ],
                            ['id', 'name'],
                            'promo_codes_type',
                            isset($promo_code) ? $promo_code->type : ''
                        );

                        echo render_input(
                            'amount',
                            'promo_codes_value',
                            isset($promo_code) ? $promo_code->amount : '',
                            'number',
                            ['step' => '0.01', 'required' => true]
                        );

                        echo render_input(
                            'usage_limit',
                            'promo_codes_usage_limit',
                            isset($promo_code) ? $promo_code->usage_limit : '',
                            'number',
                            ['title' => _l('promo_codes_usage_limit_info'), 'data-toggle' => 'tooltip'],
                        );

                        echo render_input(
                            'start_date',
                            'promo_codes_start_date',
                            isset($promo_code) ? $promo_code->start_date : '',
                            'date',
                            ['required' => true]
                        );

                        echo render_input(
                            'end_date',
                            'promo_codes_end_date',
                            isset($promo_code) ? $promo_code->end_date : '',
                            'date',
                            ['required' => true]
                        );
                        ?>


                        <div class="form-group select-placeholder">
                            <label for="metadata[tax_relation]"
                                class="control-label"><?php echo _l('promo_codes_tax_relation'); ?></label>
                            <select name="metadata[tax_relation]" class="selectpicker" data-width="100%"
                                data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                <option value="before_tax" <?php
                                                            if (isset($metadata['tax_relation'])) {
                                                                if ($metadata['tax_relation'] == 'before_tax') {
                                                                    echo 'selected';
                                                                }
                                                            } ?>><?php echo _l('discount_type_before_tax'); ?></option>
                                <option value="after_tax" <?php if (isset($metadata['tax_relation'])) {
                                                                if ($metadata['tax_relation'] == 'after_tax') {
                                                                    echo 'selected';
                                                                }
                                                            } ?>><?php echo _l('discount_type_after_tax'); ?></option>
                            </select>
                        </div>
                        <div class="tw-mt-4 tw-mb-4">
                            <hr />
                        </div>
                        <?php

                        echo render_input(
                            'metadata[condition][min_total]',
                            'promo_codes_min_total',
                            isset($promo_code) && isset($condition['min_total']) ? $condition['min_total'] : '',
                            'number',
                            [
                                'step' => '0.01',
                                'data-toggle' => 'tooltip',
                                'title' => _l('promo_codes_optional_hint') // e.g., "Leave blank if not applicable"
                            ]
                        );

                        echo render_input(
                            'metadata[condition][max_uses_per_customer]',
                            'promo_codes_max_uses_per_customer',
                            isset($promo_code) && isset($condition['max_uses_per_customer']) ? $condition['max_uses_per_customer'] : '',
                            'number',
                            [
                                'step' => '1',
                                'data-toggle' => 'tooltip',
                                'title' => _l('promo_codes_optional_hint') // e.g., "Leave blank if not applicable"
                            ]
                        );
                        ?>

                        <div class="form-group">
                            <label for="new_customers_only">
                                <input type="checkbox" name="metadata[condition][new_customers_only]" value="1"
                                    <?php echo (isset($promo_code) && !empty($condition['new_customers_only'])) ? 'checked' : ''; ?>>
                                <?php echo _l('promo_codes_new_customers_only'); ?>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="new_customers_only">
                                <input type="checkbox" name="metadata[condition][sales_without_discount_only]" value="1"
                                    <?php echo (isset($promo_code) && !empty($condition['sales_without_discount_only'])) ? 'checked' : ''; ?>>
                                <?php echo _l('promo_codes_sales_without_discount_only'); ?>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="new_customers_only">
                                <input type="checkbox" name="metadata[condition][sales_without_applied_code_only]"
                                    value="1"
                                    <?php echo (isset($promo_code) && !empty($condition['sales_without_applied_code_only'])) ? 'checked' : ''; ?>>
                                <?php echo _l('promo_codes_sales_without_applied_code_only'); ?>
                            </label>
                        </div>

                        <?php
                        echo render_select(
                            'metadata[condition][applicable_to][]',
                            $sales_object_dropdown_options,
                            ['id', 'name'],
                            'promo_codes_applicable_to',
                            isset($promo_code) && isset($condition['applicable_to']) ? $condition['applicable_to'] : '',
                            ['multiple' => true],
                            [],
                            '',
                            'selectpicker',
                            false
                        ); ?>
                        <div id="subscription_coupon_options" class="tw-mt-4" style="display:none;">
                            <h5><?php echo _l('promo_codes_subscription_coupon_options'); ?></h5>
                            <hr />
                            <?php
                            echo render_select(
                                'metadata[stripe_coupon][duration]',
                                [
                                    ['id' => 'forever', 'name' => _l('promo_codes_duration_forever') . ' - ' . _l('promo_codes_duration_forever_desc')],
                                    ['id' => 'once', 'name' => _l('promo_codes_duration_once') . ' - ' . _l('promo_codes_duration_once_desc')],
                                ],
                                ['id', 'name'],
                                'promo_codes_duration',
                                $metadata['stripe_coupon']['duration'] ?? ''
                            );
                            ?>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?php echo isset($promo_code) ? _l('promo_codes_edit_button') : _l('promo_codes_create_button'); ?>
                        </button>
                        <?php form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
'use strict';
$(function() {
    function toggleStripeCouponOptions() {
        const selected = $('select[name="metadata[condition][applicable_to][]"]').val() || [];
        if (selected.includes('subscription')) {
            $('#subscription_coupon_options').slideDown();
        } else {
            $('#subscription_coupon_options').slideUp();
        }
    }

    $('select[name="metadata[condition][applicable_to][]"]').on('change', toggleStripeCouponOptions);

    // Call on page load in case of existing value
    toggleStripeCouponOptions();
});
</script>
</body>

</html>