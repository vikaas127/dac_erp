<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
init_head();
$currency = get_base_currency()->name;

// Retrieve filters from session if available, else use empty array
$filters = $this->session->userdata('customer_export_filters') ?: [];
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-lg-6 col-md-8 col-sm-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <?php echo _l('customer_filter_title'); ?>
                        </h4>
                        <hr class="hr-panel-heading" />
                        <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
                        <?php $this->load->view('authentication/includes/alerts'); ?>

                        <?php echo form_open(admin_url('promo_codes/clients_export/export'), ['id' => 'customer-export-form']); ?>
                        <div class="accordion" id="filterAccordion">

                            <?php hooks()->do_action('promo_code_client_export_form_start', $filters); ?>

                            <!-- Registration Filters -->
                            <div class="card">
                                <div class="card-header tw-bg-neutral-100 tw-rounded" id="headingRegistration">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link tw-w-full tw-text-left tw-p-2" type="button"
                                            data-toggle="collapse" data-target="#collapseRegistration"
                                            aria-expanded="true" aria-controls="collapseRegistration">
                                            <?php echo _l('customer_filter_registration_filters'); ?>
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseRegistration" class="collapse in" aria-labelledby="headingRegistration"
                                    data-parent="#filterAccordion">
                                    <div class="card-body tw-ml-4 tw-py-2">
                                        <div class="form-group">
                                            <label
                                                for="new_customers_days"><?php echo _l('customer_filter_new_customers_days'); ?></label>
                                            <input type="number" class="form-control" id="new_customers_days"
                                                name="new_customers_days"
                                                placeholder="<?php echo _l('customer_filter_new_customers_days_placeholder'); ?>"
                                                value="<?php echo isset($filters['new_customers_days']) ? htmlspecialchars($filters['new_customers_days']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label
                                                for="registered_after"><?php echo _l('customer_filter_registered_after'); ?></label>
                                            <input type="date" class="form-control" id="registered_after"
                                                name="registered_after"
                                                value="<?php echo isset($filters['registered_after']) ? htmlspecialchars($filters['registered_after']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label
                                                for="registered_before"><?php echo _l('customer_filter_registered_before'); ?></label>
                                            <input type="date" class="form-control" id="registered_before"
                                                name="registered_before"
                                                value="<?php echo isset($filters['registered_before']) ? htmlspecialchars($filters['registered_before']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Login Activity -->
                            <div class="card">
                                <div class="card-header tw-bg-neutral-100 tw-rounded" id="headingLogin">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link tw-w-full tw-text-left tw-p-2 collapsed"
                                            type="button" data-toggle="collapse" data-target="#collapseLogin"
                                            aria-expanded="false" aria-controls="collapseLogin">
                                            <?php echo _l('customer_filter_login_activity'); ?>
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseLogin" class="collapse" aria-labelledby="headingLogin"
                                    data-parent="#filterAccordion">
                                    <div class="card-body tw-ml-4 tw-py-2">
                                        <div class="form-group">
                                            <label
                                                for="not_logged_in_since"><?php echo _l('customer_filter_not_logged_in_since'); ?></label>
                                            <input type="number" class="form-control" id="not_logged_in_since"
                                                name="not_logged_in_since"
                                                placeholder="<?php echo _l('customer_filter_not_logged_in_since_placeholder'); ?>"
                                                value="<?php echo isset($filters['not_logged_in_since']) ? htmlspecialchars($filters['not_logged_in_since']) : ''; ?>">
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="never_logged_in" name="never_logged_in"
                                                <?php echo isset($filters['never_logged_in']) && $filters['never_logged_in'] ? 'checked' : ''; ?>>
                                            <label
                                                for="never_logged_in"><?php echo _l('customer_filter_never_logged_in'); ?></label>
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="logged_in_once" name="logged_in_once"
                                                <?php echo isset($filters['logged_in_once']) && $filters['logged_in_once'] ? 'checked' : ''; ?>>
                                            <label
                                                for="logged_in_once"><?php echo _l('customer_filter_logged_in_once'); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Status -->
                            <div class="card">
                                <div class="card-header tw-bg-neutral-100 tw-rounded" id="headingProfile">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link tw-w-full tw-text-left tw-p-2 collapsed"
                                            type="button" data-toggle="collapse" data-target="#collapseProfile"
                                            aria-expanded="false" aria-controls="collapseProfile">
                                            <?php echo _l('customer_filter_profile_status'); ?>
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseProfile" class="collapse" aria-labelledby="headingProfile"
                                    data-parent="#filterAccordion">
                                    <div class="card-body tw-ml-4 tw-py-2">
                                        <div class="checkbox">
                                            <input type="checkbox" id="incomplete_profiles" name="incomplete_profiles"
                                                <?php echo isset($filters['incomplete_profiles']) && $filters['incomplete_profiles'] ? 'checked' : ''; ?>>
                                            <label
                                                for="incomplete_profiles"><?php echo _l('customer_filter_incomplete_profiles'); ?></label>
                                        </div>
                                        <hr />
                                        <div class="radio">
                                            <input type="radio" id="registration_confirmed_active"
                                                name="registration_confirmed" value="1"
                                                <?php echo isset($filters['registration_confirmed']) && $filters['registration_confirmed'] == '1' ? 'checked' : ''; ?>>
                                            <label
                                                for="registration_confirmed_active"><?php echo _l('customer_filter_registration_confirmed_active'); ?></label>
                                        </div>
                                        <div class="radio">
                                            <input type="radio" id="registration_confirmed_inactive"
                                                name="registration_confirmed" value="0"
                                                <?php echo isset($filters['registration_confirmed']) && $filters['registration_confirmed'] == '0' ? 'checked' : ''; ?>>
                                            <label
                                                for="registration_confirmed_inactive"><?php echo _l('customer_filter_registration_confirmed_inactive'); ?></label>
                                        </div>
                                        <hr />
                                        <div class="radio">
                                            <input type="radio" id="profile_status_active" name="profile_status"
                                                value="1"
                                                <?php echo isset($filters['profile_status']) && $filters['profile_status'] == '1' ? 'checked' : ''; ?>>
                                            <label
                                                for="profile_status_active"><?php echo _l('customer_filter_profile_status_active'); ?></label>
                                        </div>
                                        <div class="radio">
                                            <input type="radio" id="profile_status_inactive" name="profile_status"
                                                value="0"
                                                <?php echo isset($filters['profile_status']) && $filters['profile_status'] == '0' ? 'checked' : ''; ?>>
                                            <label
                                                for="profile_status_inactive"><?php echo _l('customer_filter_profile_status_inactive'); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Status -->
                            <div class="card">
                                <div class="card-header tw-bg-neutral-100 tw-rounded" id="headingPayment">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link tw-w-full tw-text-left tw-p-2 collapsed"
                                            type="button" data-toggle="collapse" data-target="#collapsePayment"
                                            aria-expanded="false" aria-controls="collapsePayment">
                                            <?php echo _l('customer_filter_payment_status'); ?>
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapsePayment" class="collapse" aria-labelledby="headingPayment"
                                    data-parent="#filterAccordion">
                                    <div class="card-body tw-ml-4 tw-py-2">
                                        <div class="checkbox">
                                            <input type="checkbox" id="no_payment" name="no_payment"
                                                <?php echo isset($filters['no_payment']) && $filters['no_payment'] ? 'checked' : ''; ?>>
                                            <label
                                                for="no_payment"><?php echo _l('customer_filter_no_payment'); ?></label>
                                        </div>
                                        <div class="form-group">
                                            <label
                                                for="payments_over"><?php echo _l('customer_filter_payments_over', $currency); ?></label>
                                            <input type="number" step="0.01" class="form-control" id="payments_over"
                                                name="payments_over"
                                                placeholder="<?php echo _l('customer_filter_payments_over_placeholder'); ?>"
                                                value="<?php echo isset($filters['payments_over']) ? htmlspecialchars($filters['payments_over']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label
                                                for="payments_under"><?php echo _l('customer_filter_payments_under', $currency); ?></label>
                                            <input type="number" step="0.01" class="form-control" id="payments_under"
                                                name="payments_under"
                                                placeholder="<?php echo _l('customer_filter_payments_under_placeholder'); ?>"
                                                value="<?php echo isset($filters['payments_under']) ? htmlspecialchars($filters['payments_under']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label
                                                for="outstanding_balance_over"><?php echo _l('customer_filter_outstanding_balance_over', $currency); ?></label>
                                            <input type="number" step="0.01" class="form-control"
                                                id="outstanding_balance_over" name="outstanding_balance_over"
                                                placeholder="<?php echo _l('customer_filter_outstanding_balance_over_placeholder'); ?>"
                                                value="<?php echo isset($filters['outstanding_balance_over']) ? htmlspecialchars($filters['outstanding_balance_over']) : ''; ?>">
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="has_partial_payments" name="has_partial_payments"
                                                <?php echo isset($filters['has_partial_payments']) && $filters['has_partial_payments'] ? 'checked' : ''; ?>>
                                            <label
                                                for="has_partial_payments"><?php echo _l('customer_filter_has_partial_payments'); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Document Status -->
                            <div class="card">
                                <div class="card-header tw-bg-neutral-100 tw-rounded" id="headingDocument">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link tw-w-full tw-text-left tw-p-2 collapsed"
                                            type="button" data-toggle="collapse" data-target="#collapseDocument"
                                            aria-expanded="false" aria-controls="collapseDocument">
                                            <?php echo _l('customer_filter_document_status'); ?>
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseDocument" class="collapse" aria-labelledby="headingDocument"
                                    data-parent="#filterAccordion">
                                    <div class="card-body tw-ml-4 tw-py-2">
                                        <div class="checkbox">
                                            <input type="checkbox" id="without_documents" name="without_documents"
                                                <?php echo isset($filters['without_documents']) && $filters['without_documents'] ? 'checked' : ''; ?>>
                                            <label
                                                for="without_documents"><?php echo _l('customer_filter_without_documents'); ?></label>
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="overdue_invoices" name="overdue_invoices"
                                                <?php echo isset($filters['overdue_invoices']) && $filters['overdue_invoices'] ? 'checked' : ''; ?>>
                                            <label
                                                for="overdue_invoices"><?php echo _l('customer_filter_overdue_invoices'); ?></label>
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="unpaid_invoices" name="unpaid_invoices"
                                                <?php echo isset($filters['unpaid_invoices']) && $filters['unpaid_invoices'] ? 'checked' : ''; ?>>
                                            <label
                                                for="unpaid_invoices"><?php echo _l('customer_filter_unpaid_invoices'); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Subscriptions -->
                            <div class="card">
                                <div class="card-header tw-bg-neutral-100 tw-rounded" id="headingSubscriptions">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link tw-w-full tw-text-left tw-p-2 collapsed"
                                            type="button" data-toggle="collapse" data-target="#collapseSubscriptions"
                                            aria-expanded="false" aria-controls="collapseSubscriptions">
                                            <?php echo _l('customer_filter_subscriptions'); ?>
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseSubscriptions" class="collapse" aria-labelledby="headingSubscriptions"
                                    data-parent="#filterAccordion">
                                    <div class="card-body tw-ml-4 tw-py-2">
                                        <div class="checkbox">
                                            <input type="checkbox" id="overdue_or_cancelled_subscriptions"
                                                name="overdue_or_cancelled_subscriptions"
                                                <?php echo isset($filters['overdue_or_cancelled_subscriptions']) && $filters['overdue_or_cancelled_subscriptions'] ? 'checked' : ''; ?>>
                                            <label
                                                for="overdue_or_cancelled_subscriptions"><?php echo _l('customer_filter_overdue_or_cancelled_subscriptions'); ?></label>
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="active_subscriptions_only"
                                                name="active_subscriptions_only"
                                                <?php echo isset($filters['active_subscriptions_only']) && $filters['active_subscriptions_only'] ? 'checked' : ''; ?>>
                                            <label
                                                for="active_subscriptions_only"><?php echo _l('customer_filter_active_subscriptions_only'); ?></label>
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="multiple_active_subscriptions"
                                                name="multiple_active_subscriptions"
                                                <?php echo isset($filters['multiple_active_subscriptions']) && $filters['multiple_active_subscriptions'] ? 'checked' : ''; ?>>
                                            <label
                                                for="multiple_active_subscriptions"><?php echo _l('customer_filter_multiple_active_subscriptions'); ?></label>
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="free_trial_subscriptions"
                                                name="free_trial_subscriptions"
                                                <?php echo isset($filters['free_trial_subscriptions']) && $filters['free_trial_subscriptions'] ? 'checked' : ''; ?>>
                                            <label
                                                for="free_trial_subscriptions"><?php echo _l('customer_filter_free_trial_subscriptions'); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Estimates -->
                            <div class="card">
                                <div class="card-header tw-bg-neutral-100 tw-rounded" id="headingEstimates">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link tw-w-full tw-text-left tw-p-2 collapsed"
                                            type="button" data-toggle="collapse" data-target="#collapseEstimates"
                                            aria-expanded="false" aria-controls="collapseEstimates">
                                            <?php echo _l('customer_filter_estimates'); ?>
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseEstimates" class="collapse" aria-labelledby="headingEstimates"
                                    data-parent="#filterAccordion">
                                    <div class="card-body tw-ml-4 tw-py-2">
                                        <div class="checkbox">
                                            <input type="checkbox" id="last_estimate_rejected"
                                                name="last_estimate_rejected"
                                                <?php echo isset($filters['last_estimate_rejected']) && $filters['last_estimate_rejected'] ? 'checked' : ''; ?>>
                                            <label
                                                for="last_estimate_rejected"><?php echo _l('customer_filter_last_estimate_rejected'); ?></label>
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="expired_estimates" name="expired_estimates"
                                                <?php echo isset($filters['expired_estimates']) && $filters['expired_estimates'] ? 'checked' : ''; ?>>
                                            <label
                                                for="expired_estimates"><?php echo _l('customer_filter_expired_estimates'); ?></label>
                                        </div>
                                        <div class="checkbox">
                                            <input type="checkbox" id="accepted_estimates_no_invoices"
                                                name="accepted_estimates_no_invoices"
                                                <?php echo isset($filters['accepted_estimates_no_invoices']) && $filters['accepted_estimates_no_invoices'] ? 'checked' : ''; ?>>
                                            <label
                                                for="accepted_estimates_no_invoices"><?php echo _l('customer_filter_accepted_estimates_no_invoices'); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tickets -->
                            <div class="card">
                                <div class="card-header tw-bg-neutral-100 tw-rounded" id="headingTickets">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link tw-w-full tw-text-left tw-p-2 collapsed"
                                            type="button" data-toggle="collapse" data-target="#collapseTickets"
                                            aria-expanded="false" aria-controls="collapseTickets">
                                            <?php echo _l('customer_filter_tickets'); ?>
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapseTickets" class="collapse" aria-labelledby="headingTickets"
                                    data-parent="#filterAccordion">
                                    <div class="card-body tw-ml-4 tw-py-2">
                                        <div class="checkbox">
                                            <input type="checkbox" id="no_tickets" name="no_tickets"
                                                <?php echo isset($filters['no_tickets']) && $filters['no_tickets'] ? 'checked' : ''; ?>>
                                            <label
                                                for="no_tickets"><?php echo _l('customer_filter_no_tickets'); ?></label>
                                        </div>
                                        <?php
                                        // Pre-select tickets_in_status from session if available
                                        $selected = isset($filters['tickets_in_status']) ? $filters['tickets_in_status'] : '';
                                        echo render_select('tickets_in_status', get_instance()->tickets_model->get_ticket_status(), ['ticketstatusid', 'name'], 'customer_filter_tickets_in_status', $selected, ['id' => 'tickets_in_status', 'placeholder' => _l('customer_filter_tickets_in_status_placeholder')]);
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <?php hooks()->do_action('promo_code_client_export_form_end', $filters); ?>
                        </div>

                        <div class="text-right mtop20">
                            <button type="button" id="reset-filters"
                                class="btn btn-default"><?php echo _l('customer_filter_reset_filters'); ?></button>
                            <button type="submit"
                                class="btn btn-primary"><?php echo _l('customer_filter_export_customers'); ?></button>
                        </div>
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
$(document).ready(function() {
    // Reset filters and clear session
    $('#reset-filters').on('click', function() {
        // Reset form fields
        $('#customer-export-form')[0].reset();
        // Clear session filters via AJAX
        $.post('<?php echo admin_url('promo_codes/clients_export/reset_filters'); ?>');
    });
});
</script>
</body>

</html>