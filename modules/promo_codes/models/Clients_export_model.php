<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Clients_export_model extends App_Model
{
    /**
     * Fetches customers based on filter conditions.
     *
     * Uses subqueries to apply filters, avoiding complex joins and GROUP BY issues.
     * Selects customer records with primary contact fields and company details.
     *
     * @param array $filters Filter conditions (e.g., new_customers_days, overdue_invoices)
     * @return array Customer records
     */
    public function get_customers_with_conditions($filters)
    {
        $table = db_prefix() . 'clients';
        $fields = [
            'DISTINCT(c.userid)', 'cc.email', 'cc.phonenumber as contact_phonenumber', 'cc.firstname', 'cc.lastname'
        ];

        $fields = implode(',', $fields) . ',';
        $fields .= implode(',', prefixed_table_fields_array($table)) . ',' . get_sql_select_client_company();
        $fields = str_replace($table . '.', 'c.', $fields);

        $this->db->select($fields);
        $this->db->from(db_prefix() . 'clients AS c');
        $this->db->join(db_prefix() . 'countries co', 'co.country_id = c.country', 'left');
        $this->db->join(db_prefix() . 'contacts cc', 'cc.userid = c.userid AND cc.is_primary = 1', 'left');

        // Apply filters using subqueries
        $this->apply_registration_filters($filters);
        $this->apply_login_filters($filters);
        $this->apply_profile_filters($filters);
        $this->apply_payment_filters($filters);
        $this->apply_document_filters($filters);
        $this->apply_subscription_filters($filters);
        $this->apply_estimate_filters($filters);
        $this->apply_ticket_filters($filters);

        hooks()->do_action('promo_code_client_export_query', $filters);

        return $this->db->get()->result_array();
    }

    /**
     * Applies registration date filters.
     *
     * Filters by new customers, registered after, or before a date.
     *
     * @param array $filters Filters (new_customers_days, registered_after, registered_before)
     * @return void
     */
    private function apply_registration_filters($filters)
    {
        if (!empty($filters['new_customers_days']) && is_numeric($filters['new_customers_days'])) {
            $this->db->where('c.datecreated >=', date('Y-m-d', strtotime('-' . (int)$filters['new_customers_days'] . ' days')));
        }

        if (!empty($filters['registered_after'])) {
            $this->db->where('c.datecreated >', $this->db->escape_str($filters['registered_after']));
        }

        if (!empty($filters['registered_before'])) {
            $this->db->where('c.datecreated <', $this->db->escape_str($filters['registered_before']));
        }
    }

    /**
     * Applies login activity filters.
     *
     * Filters by no login since days, never logged in, or logged in once.
     *
     * @param array $filters Filters (not_logged_in_since, never_logged_in, logged_in_once)
     * @return void
     */
    private function apply_login_filters($filters)
    {
        if (!empty($filters['not_logged_in_since']) && is_numeric($filters['not_logged_in_since'])) {
            $this->db->where('(cc.last_login IS NULL OR cc.last_login <= DATE_SUB(NOW(), INTERVAL ' . (int)$filters['not_logged_in_since'] . ' DAY))');
        }

        if (!empty($filters['never_logged_in'])) {
            $this->db->where('cc.last_login IS NULL');
        }

        if (!empty($filters['logged_in_once'])) {
            $this->db->where('cc.last_login IS NOT NULL');
        }
    }

    /**
     * Applies profile status filters.
     *
     * Filters customers with incomplete profiles (missing email, phone, or company).
     *
     * @param array $filters Filters (incomplete_profiles, registration_confirmed, profile_status)
     * @return void
     */
    private function apply_profile_filters($filters)
    {
        if (!empty($filters['incomplete_profiles'])) {
            $this->db->group_start()
                ->where('c.email IS NULL')
                ->or_where('c.email', '')
                ->or_where('c.phone IS NULL')
                ->or_where('c.phone', '')
                ->or_where('c.company IS NULL')
                ->or_where('c.company', '')
                ->group_end();
        }

        if (isset($filters['registration_confirmed'])) {
            $this->db->where('c.registration_confirmed', (int)$filters['registration_confirmed']);
        }

        if (isset($filters['profile_status'])) {
            $this->db->where('c.active', (int)$filters['profile_status']);
        }
    }

    /**
     * Applies payment-related filters using subqueries.
     *
     * Filters by payment records, total payments, outstanding balances, or partial payments.
     *
     * @param array $filters Filters (no_payment, payments_over, payments_under, outstanding_balance_over, has_partial_payments)
     * @return void
     */
    private function apply_payment_filters($filters)
    {
        if (!empty($filters['no_payment'])) {
            $this->db->where('NOT EXISTS (SELECT 1 FROM ' . db_prefix() . 'invoices i1 
                JOIN ' . db_prefix() . 'invoicepaymentrecords ipr1 ON ipr1.invoiceid = i1.id 
                WHERE i1.clientid = c.userid)', NULL, FALSE);
        }

        if (!empty($filters['payments_over']) && is_numeric($filters['payments_over'])) {
            $this->db->where('(SELECT SUM(ipr2.amount) FROM ' . db_prefix() . 'invoices i2 
                JOIN ' . db_prefix() . 'invoicepaymentrecords ipr2 ON ipr2.invoiceid = i2.id 
                WHERE i2.clientid = c.userid) >', (float)$filters['payments_over'], NULL, FALSE);
        }

        if (!empty($filters['payments_under']) && is_numeric($filters['payments_under'])) {
            $this->db->where('(SELECT COALESCE(SUM(ipr3.amount), 0) FROM ' . db_prefix() . 'invoices i3 
                JOIN ' . db_prefix() . 'invoicepaymentrecords ipr3 ON ipr3.invoiceid = i3.id 
                WHERE i3.clientid = c.userid) <', (float)$filters['payments_under'], NULL, FALSE);
        }

        if (!empty($filters['outstanding_balance_over']) && is_numeric($filters['outstanding_balance_over'])) {
            $this->db->where('(SELECT SUM(i4.total - COALESCE(SUM(ipr4.amount), 0)) 
                FROM ' . db_prefix() . 'invoices i4 
                LEFT JOIN ' . db_prefix() . 'invoicepaymentrecords ipr4 ON ipr4.invoiceid = i4.id 
                WHERE i4.clientid = c.userid AND i4.status NOT IN (2,5)
                GROUP BY i4.clientid) >', (float)$filters['outstanding_balance_over'], NULL, FALSE);
        }

        if (!empty($filters['has_partial_payments'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'invoices i5 
                WHERE i5.clientid = c.userid AND i5.status = 3)', NULL, FALSE);
        }
    }

    /**
     * Applies sales documents filters using subqueries.
     *
     * Filters by absence of documents, overdue invoices, or unpaid invoices.
     *
     * @param array $filters Filters (without_documents, overdue_invoices, unpaid_invoices, overdue_unpaid_invoices)
     * @return void
     */
    private function apply_document_filters($filters)
    {
        if (!empty($filters['without_documents'])) {
            $sales_objects = $this->promo_codes_service->getSupportedSalesObjects();
            if (!empty($sales_objects)) {
                $this->db->group_start();
                foreach ($sales_objects as $key => $sales_object_type) {
                    $alias = 'sot' . $key;
                    $where = ' WHERE ' . $alias . '.clientid = c.userid';
                    if ($sales_object_type === 'proposal') {
                        $where = ' WHERE ' . $alias . '.rel_id = c.userid AND ' . $alias . '.rel_type = "customer"';
                    }
                    $table = $this->promo_codes_service->getSalesObjectTable($sales_object_type);
                    $this->db->where('NOT EXISTS (SELECT 1 FROM ' . $table . ' ' . $alias . $where . ')', NULL, FALSE);
                }
                $this->db->group_end();
            }
        }

        if (!empty($filters['overdue_invoices'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'invoices i6 
                WHERE i6.clientid = c.userid AND i6.status = 4)', NULL, FALSE);
        }

        if (!empty($filters['unpaid_invoices'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'invoices i7 
                WHERE i7.clientid = c.userid AND i7.status = 1)', NULL, FALSE);
        }

        if (!empty($filters['overdue_unpaid_invoices'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'invoices i8 
                WHERE i8.clientid = c.userid AND i8.status IN (2,3))', NULL, FALSE);
        }
    }

    /**
     * Applies subscription-specific filters using subqueries.
     *
     * Filters by overdue, cancelled, active, multiple active, or trial subscriptions.
     *
     * @param array $filters Filters (overdue_or_cancelled_subscriptions, active_subscriptions_only, multiple_active_subscriptions, free_trial_subscriptions)
     * @return void
     */
    private function apply_subscription_filters($filters)
    {
        if (!empty($filters['overdue_or_cancelled_subscriptions'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'subscriptions s1 
                WHERE s1.clientid = c.userid AND s1.status IN ("overdue", "canceled"))', NULL, FALSE);
        }

        if (!empty($filters['active_subscriptions_only'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'subscriptions s2 
                WHERE s2.clientid = c.userid AND s2.status = "active")', NULL, FALSE);
        }

        if (!empty($filters['multiple_active_subscriptions'])) {
            $this->db->where('(SELECT COUNT(s3.id) FROM ' . db_prefix() . 'subscriptions s3 
                WHERE s3.clientid = c.userid AND s3.status = "active") > 1', NULL, FALSE);
        }

        if (!empty($filters['free_trial_subscriptions'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'subscriptions s4 
                WHERE s4.clientid = c.userid AND s4.status = "trialing")', NULL, FALSE);
        }
    }

    /**
     * Applies estimate-related filters using subqueries.
     *
     * Filters by rejected, expired, or accepted estimates without invoices.
     *
     * @param array $filters Filters (last_estimate_rejected, expired_estimates, accepted_estimates_no_invoices)
     * @return void
     */
    private function apply_estimate_filters($filters)
    {
        if (!empty($filters['last_estimate_rejected'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'estimates e1 
                WHERE e1.clientid = c.userid AND e1.status = 3 
                AND e1.id = (SELECT e2.id FROM ' . db_prefix() . 'estimates e2 
                    WHERE e2.clientid = c.userid ORDER BY e2.datecreated DESC LIMIT 1))', NULL, FALSE);
        }

        if (!empty($filters['expired_estimates'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'estimates e3 
                WHERE e3.clientid = c.userid AND e3.status = "expired")', NULL, FALSE);
        }

        if (!empty($filters['accepted_estimates_no_invoices'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'estimates e4 
                WHERE e4.clientid = c.userid AND e4.status = "accepted" 
                AND NOT EXISTS (SELECT 1 FROM ' . db_prefix() . 'invoices i9 
                    WHERE i9.estimate_id = e4.id))', NULL, FALSE);
        }
    }

    /**
     * Applies ticket-specific filters using subqueries.
     *
     * Filters by absence of tickets or tickets in a specific status.
     *
     * @param array $filters Filters (no_tickets, tickets_in_status)
     * @return void
     */
    private function apply_ticket_filters($filters)
    {
        if (!empty($filters['no_tickets'])) {
            $this->db->where('NOT EXISTS (SELECT 1 FROM ' . db_prefix() . 'tickets t1 
                WHERE t1.userid = c.userid)', NULL, FALSE);
        }

        if (!empty($filters['tickets_in_status'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'tickets t2 
                WHERE t2.userid = c.userid AND t2.status = ' . $this->db->escape($filters['tickets_in_status']) . ')', NULL, FALSE);
        }
    }
}