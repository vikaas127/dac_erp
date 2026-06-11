<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Accounting_api_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('flutex_admin_api/reports_model');
    }

    public function accountingModuleActive()
    {
        if (!$this->db->table_exists(db_prefix().'acc_accounts')) {
            return false;
        }

        $this->load->library('app_modules');

        return $this->app_modules->is_active('accounting');
    }

    public function resolveDateRange($fromDate = null, $toDate = null)
    {
        if ($fromDate && $toDate) {
            return [$fromDate, $toDate];
        }

        $fy = $this->financialYearDates();

        return [
            $fromDate ?: $fy['from_date'],
            $toDate ?: $fy['to_date'],
        ];
    }

    public function financialYearDates()
    {
        $currentMonth = (int) date('n');
        $currentYear  = (int) date('Y');

        if ($currentMonth >= 4) {
            return [
                'from_date' => $currentYear.'-04-01',
                'to_date'   => ($currentYear + 1).'-03-31',
            ];
        }

        return [
            'from_date' => ($currentYear - 1).'-04-01',
            'to_date'   => $currentYear.'-03-31',
        ];
    }

    public function getProfitLoss($fromDate = null, $toDate = null)
    {
        [$from, $to] = $this->resolveDateRange($fromDate, $toDate);
        $filter      = [
            'from_date' => _d($from),
            'to_date'   => _d($to),
        ];

        $payload = [
            'from_date' => $from,
            'to_date'   => $to,
            'source'    => 'erp',
        ];

        if ($this->accountingModuleActive()) {
            $this->load->model('accounting/accounting_model');
            $raw = $this->accounting_model->get_data_profit_and_loss($filter);
            $transformed = $this->transformSectionReport($raw['data'] ?? []);
            $payload['source'] = 'accounting';
            $payload['sections'] = $transformed['sections'];
            $payload['income_total'] = $transformed['income_total'];
            $payload['expense_total'] = $transformed['expense_total'];
            $payload['net_profit'] = $transformed['income_total'] - $transformed['expense_total'];
        } else {
            $hub = $this->reports_model->build('profit_loss', [
                'from_date' => $from,
                'to_date'   => $to,
            ]);
            $payload['sections'] = $hub['sections'] ?? [];
            $payload['net_profit'] = $this->parseAmount($hub['summary']['value'] ?? '0');
        }

        $payload['hub'] = $this->reports_model->build('profit_loss', [
            'from_date' => $from,
            'to_date'   => $to,
        ]);

        return $payload;
    }

    public function getBalanceSheet($fromDate = null, $toDate = null)
    {
        [$from, $to] = $this->resolveDateRange($fromDate, $toDate);
        $filter = [
            'from_date' => _d($from),
            'to_date'   => _d($to),
        ];

        $payload = [
            'from_date' => $from,
            'to_date'   => $to,
            'source'    => 'erp',
            'sections'  => [],
        ];

        if ($this->accountingModuleActive()) {
            $this->load->model('accounting/accounting_model');
            $raw = $this->accounting_model->get_data_balance_sheet_summary($filter);
            $transformed = $this->transformSectionReport($raw['data'] ?? []);
            $payload['source'] = 'accounting';
            $payload['sections'] = $transformed['sections'];
            $payload['assets_total'] = $transformed['assets_total'];
            $payload['liabilities_total'] = $transformed['liabilities_total'];
        } else {
            $hub = $this->reports_model->build('balance_sheet', [
                'from_date' => $from,
                'to_date'   => $to,
            ]);
            $payload['sections'] = $hub['sections'] ?? [];
        }

        $payload['hub'] = $this->reports_model->build('balance_sheet', [
            'from_date' => $from,
            'to_date'   => $to,
        ]);

        return $payload;
    }

    public function getCashFlow($fromDate = null, $toDate = null)
    {
        [$from, $to] = $this->resolveDateRange($fromDate, $toDate);
        $filter = [
            'from_date' => _d($from),
            'to_date'   => _d($to),
        ];

        $payload = [
            'from_date' => $from,
            'to_date'   => $to,
            'source'    => 'erp',
            'sections'  => [],
        ];

        if ($this->accountingModuleActive()) {
            $this->load->model('accounting/accounting_model');
            $raw = $this->accounting_model->get_data_statement_of_cash_flows($filter);
            $transformed = $this->transformSectionReport($raw['data'] ?? []);
            $payload['source'] = 'accounting';
            $payload['sections'] = $transformed['sections'];
            $payload['net_cash_flow'] = $transformed['net_total'];
        } else {
            $hub = $this->reports_model->build('cash_flow', [
                'from_date' => $from,
                'to_date'   => $to,
            ]);
            $payload['sections'] = $hub['sections'] ?? [];
            $payload['net_cash_flow'] = $this->parseAmount($hub['summary']['value'] ?? '0');
        }

        $payload['hub'] = $this->reports_model->build('cash_flow', [
            'from_date' => $from,
            'to_date'   => $to,
        ]);

        return $payload;
    }

    public function getReceivables($aging = false, $fromDate = null, $toDate = null)
    {
        [$from, $to] = $this->resolveDateRange($fromDate, $toDate);
        $today = date('Y-m-d');

        $this->db->select('i.id, i.total, i.date, i.duedate, i.status, c.company, c.userid as client_id');
        $this->db->from(db_prefix().'invoices i');
        $this->db->join(db_prefix().'clients c', 'c.userid = i.clientid', 'left');
        $this->db->where_in('i.status', [1, 3, 4]);
        if ($fromDate || $toDate) {
            $this->db->where('i.date >=', $from);
            $this->db->where('i.date <=', $to);
        }
        $this->db->order_by('i.duedate', 'ASC');
        $invoices = $this->db->get()->result_array();

        $buckets = [
            'current'   => ['label' => 'Current', 'amount' => 0, 'count' => 0],
            '1_30'      => ['label' => '1-30 Days', 'amount' => 0, 'count' => 0],
            '31_60'     => ['label' => '31-60 Days', 'amount' => 0, 'count' => 0],
            '61_90'     => ['label' => '61-90 Days', 'amount' => 0, 'count' => 0],
            '90_plus'   => ['label' => '90+ Days', 'amount' => 0, 'count' => 0],
        ];

        $rows = [];
        $total = 0;
        $byCustomer = [];

        foreach ($invoices as $inv) {
            $amount = (float) $inv['total'];
            $total += $amount;
            $due = $inv['duedate'] ?: $inv['date'];
            $days = (int) floor((strtotime($today) - strtotime($due)) / 86400);
            $bucketKey = $this->agingBucketKey($days);
            $buckets[$bucketKey]['amount'] += $amount;
            $buckets[$bucketKey]['count']++;

            $cid = (int) $inv['client_id'];
            if (!isset($byCustomer[$cid])) {
                $byCustomer[$cid] = [
                    'customer' => $inv['company'] ?: 'Customer',
                    'amount'   => 0,
                    'invoices' => 0,
                    'oldest_days' => $days,
                ];
            }
            $byCustomer[$cid]['amount'] += $amount;
            $byCustomer[$cid]['invoices']++;
            $byCustomer[$cid]['oldest_days'] = max($byCustomer[$cid]['oldest_days'], $days);

            $rows[] = [
                'invoice_id'     => (int) $inv['id'],
                'invoice_number' => format_invoice_number($inv['id']),
                'customer'       => $inv['company'] ?: 'Customer',
                'date'           => $inv['date'],
                'due_date'       => $due,
                'amount'         => round($amount, 2),
                'days_overdue'   => max(0, $days),
                'aging_bucket'   => $buckets[$bucketKey]['label'],
                'status'         => (int) $inv['status'],
            ];
        }

        $customerRows = [];
        foreach ($byCustomer as $c) {
            $customerRows[] = [
                'customer'      => $c['customer'],
                'amount'        => round($c['amount'], 2),
                'invoice_count' => $c['invoices'],
                'days_overdue'  => max(0, $c['oldest_days']),
            ];
        }
        usort($customerRows, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        $payload = [
            'from_date' => $from,
            'to_date'   => $to,
            'total'     => round($total, 2),
            'customers' => $customerRows,
            'invoices'  => $rows,
            'summary'   => [
                'label' => 'Outstanding Receivables',
                'value' => number_format($total, 2, '.', ''),
            ],
        ];

        if ($aging) {
            $payload['aging'] = true;
            $payload['buckets'] = array_values(array_map(function ($b) {
                return [
                    'label'  => $b['label'],
                    'amount' => round($b['amount'], 2),
                    'count'  => $b['count'],
                ];
            }, $buckets));
        }

        $payload['hub'] = $this->reports_model->build('receivables', [
            'from_date' => $from,
            'to_date'   => $to,
        ]);

        return $payload;
    }

    public function getPayables($aging = false, $fromDate = null, $toDate = null)
    {
        [$from, $to] = $this->resolveDateRange($fromDate, $toDate);
        $today = date('Y-m-d');
        $rows = [];
        $total = 0;
        $buckets = [
            'current' => ['label' => 'Current', 'amount' => 0, 'count' => 0],
            '1_30'    => ['label' => '1-30 Days', 'amount' => 0, 'count' => 0],
            '31_60'   => ['label' => '31-60 Days', 'amount' => 0, 'count' => 0],
            '61_90'   => ['label' => '61-90 Days', 'amount' => 0, 'count' => 0],
            '90_plus' => ['label' => '90+ Days', 'amount' => 0, 'count' => 0],
        ];

        if ($this->db->table_exists(db_prefix().'pur_invoices')) {
            $this->db->select('pi.id, pi.invoice_number, pi.total, pi.invoice_date as date, pi.payment_status, v.company');
            $this->db->from(db_prefix().'pur_invoices pi');
            $this->db->join(db_prefix().'pur_vendor v', 'v.userid = pi.vendor', 'left');
            $this->db->where('pi.payment_status !=', 'paid');
            if ($fromDate || $toDate) {
                $this->db->where('pi.invoice_date >=', $from);
                $this->db->where('pi.invoice_date <=', $to);
            }
            $this->db->order_by('pi.invoice_date', 'DESC');
            foreach ($this->db->get()->result_array() as $r) {
                $amount = (float) $r['total'];
                $total += $amount;
                $days = (int) floor((strtotime($today) - strtotime($r['date'])) / 86400);
                $bucketKey = $this->agingBucketKey($days);
                $buckets[$bucketKey]['amount'] += $amount;
                $buckets[$bucketKey]['count']++;
                $rows[] = [
                    'id'       => (int) $r['id'],
                    'vendor'   => $r['company'] ?: 'Vendor',
                    'reference'=> $r['invoice_number'] ?: ('PI-'.$r['id']),
                    'date'     => $r['date'],
                    'amount'   => round($amount, 2),
                    'days'     => max(0, $days),
                    'aging_bucket' => $buckets[$bucketKey]['label'],
                    'status'   => $r['payment_status'],
                ];
            }
        } else {
            $this->db->select('e.id, e.amount, e.date, e.expense_name, e.category');
            $this->db->from(db_prefix().'expenses e');
            if ($fromDate || $toDate) {
                $this->db->where('e.date >=', $from);
                $this->db->where('e.date <=', $to);
            }
            $this->db->order_by('e.date', 'DESC');
            foreach ($this->db->get()->result_array() as $r) {
                $amount = (float) $r['amount'];
                $total += $amount;
                $days = (int) floor((strtotime($today) - strtotime($r['date'])) / 86400);
                $bucketKey = $this->agingBucketKey($days);
                $buckets[$bucketKey]['amount'] += $amount;
                $buckets[$bucketKey]['count']++;
                $rows[] = [
                    'id'       => (int) $r['id'],
                    'vendor'   => $r['expense_name'] ?: 'Expense',
                    'reference'=> 'EXP-'.$r['id'],
                    'date'     => $r['date'],
                    'amount'   => round($amount, 2),
                    'days'     => max(0, $days),
                    'aging_bucket' => $buckets[$bucketKey]['label'],
                    'status'   => 'unpaid',
                ];
            }
        }

        $payload = [
            'from_date' => $from,
            'to_date'   => $to,
            'total'     => round($total, 2),
            'items'     => $rows,
            'summary'   => [
                'label' => 'Outstanding Payables',
                'value' => number_format($total, 2, '.', ''),
            ],
        ];

        if ($aging) {
            $payload['aging'] = true;
            $payload['buckets'] = array_values(array_map(function ($b) {
                return [
                    'label'  => $b['label'],
                    'amount' => round($b['amount'], 2),
                    'count'  => $b['count'],
                ];
            }, $buckets));
        }

        $payload['hub'] = $this->reports_model->build('payables', [
            'from_date' => $from,
            'to_date'   => $to,
        ]);

        return $payload;
    }

    public function getLedgerReport($type, $fromDate = null, $toDate = null)
    {
        [$from, $to] = $this->resolveDateRange($fromDate, $toDate);
        $type = $type ?: 'general_ledger';

        $reportMap = [
            'general_ledger'   => 'ledger',
            'ledger'           => 'ledger',
            'trial_balance'    => 'trial_balance',
            'profit_and_loss'  => 'profit_loss',
            'profit_loss'      => 'profit_loss',
            'balance_sheet'    => 'balance_sheet',
            'cash_flow'        => 'cash_flow',
            'day_book'         => 'day_book',
        ];

        $hubId = $reportMap[$type] ?? 'ledger';
        $payload = [
            'type'      => $type,
            'from_date' => $from,
            'to_date'   => $to,
            'source'    => 'erp',
            'rows'      => [],
            'sections'  => [],
        ];

        if ($this->accountingModuleActive()) {
            $this->load->model('accounting/accounting_model');
            $filter = [
                'from_date' => _d($from),
                'to_date'   => _d($to),
            ];
            $method = 'get_data_'.$type;
            if (!method_exists($this->accounting_model, $method)) {
                $alt = [
                    'profit_loss' => 'get_data_profit_and_loss',
                    'ledger'      => 'get_data_general_ledger',
                ];
                $method = $alt[$type] ?? null;
            }
            if ($method && method_exists($this->accounting_model, $method)) {
                $raw = $this->accounting_model->{$method}($filter);
                $payload['source'] = 'accounting';
                $payload['raw'] = $raw;
                if (!empty($raw['data'])) {
                    $transformed = $this->transformSectionReport($raw['data']);
                    $payload['sections'] = $transformed['sections'];
                }
            }
        }

        $payload['hub'] = $this->reports_model->build($hubId, [
            'from_date' => $from,
            'to_date'   => $to,
        ]);

        return $payload;
    }

    public function getLedgerList(array $params = [])
    {
        $group  = trim($params['group'] ?? '');
        $search = trim($params['search'] ?? '');
        [$from, $to] = $this->resolveDateRange($params['from_date'] ?? null, $params['to_date'] ?? null);

        if ($this->accountingModuleActive()) {
            $this->load->model('accounting/accounting_model');
            $accounts = $this->accounting_model->get_accounts('', ['active' => 1]);
            $rows = [];
            foreach ($accounts as $acc) {
                if ($group !== '' && stripos($acc['account_type_name'] ?? '', $group) === false
                    && stripos($acc['detail_type_name'] ?? '', $group) === false) {
                    continue;
                }
                if ($search !== '' && stripos($acc['name'], $search) === false) {
                    continue;
                }

                $balance = $this->ledgerAccountBalance((int) $acc['id'], $from, $to);
                $rows[] = [
                    'id'     => (int) $acc['id'],
                    'name'   => $acc['name'],
                    'group'  => $acc['account_type_name'] ?: 'Account',
                    'city'   => '',
                    'mobile' => '',
                    'gst'    => $acc['number'] ?? '',
                    'amount' => number_format(abs($balance['amount']), 2, '.', ''),
                    'type'   => $balance['type'],
                ];
            }

            return $rows;
        }

        $this->db->select('c.userid as id, c.company as name, c.phonenumber as mobile, c.vat as gst, c.city');
        $this->db->from(db_prefix().'clients c');
        $this->db->where('c.active', 1);
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('c.company', $search);
            $this->db->or_like('c.city', $search);
            $this->db->group_end();
        }
        $this->db->limit(200);
        $rows = [];
        foreach ($this->db->get()->result_array() as $c) {
            $this->db->select_sum('total');
            $this->db->where('clientid', $c['id']);
            $this->db->where_in('status', [1, 3, 4]);
            $bal = (float) ($this->db->get(db_prefix().'invoices')->row()->total ?? 0);
            if ($group !== '' && stripos('Sundry Debtors', $group) === false) {
                continue;
            }
            $rows[] = [
                'id'     => (int) $c['id'],
                'name'   => $c['name'] ?: 'Customer',
                'group'  => 'Sundry Debtors',
                'city'   => $c['city'] ?? '',
                'mobile' => $c['mobile'] ?? '',
                'gst'    => $c['gst'] ?? '',
                'amount' => number_format($bal, 2, '.', ''),
                'type'   => $bal >= 0 ? 'Dr' : 'Cr',
            ];
        }

        return $rows;
    }

    public function getLedgerSummary()
    {
        $rows = $this->getLedgerList([]);
        $debit = 0;
        $credit = 0;
        foreach ($rows as $r) {
            $amt = (float) $r['amount'];
            if (($r['type'] ?? 'Dr') === 'Cr') {
                $credit += $amt;
            } else {
                $debit += $amt;
            }
        }

        return [
            'accounts'      => count($rows),
            'total_debit'   => round($debit, 2),
            'total_credit'  => round($credit, 2),
            'net_balance'   => round($debit - $credit, 2),
        ];
    }

    public function getLedgerDetails($id, $fromDate = null, $toDate = null)
    {
        [$from, $to] = $this->resolveDateRange($fromDate, $toDate);
        $id = (int) $id;

        if ($this->accountingModuleActive() && $this->db->table_exists(db_prefix().'acc_account_history')) {
            $account = $this->db->where('id', $id)->get(db_prefix().'acc_accounts')->row_array();
            if (!$account) {
                return null;
            }

            $this->db->where('account', $id);
            $this->db->where('date >=', $from);
            $this->db->where('date <=', $to);
            $this->db->order_by('date', 'ASC');
            $history = $this->db->get(db_prefix().'acc_account_history')->result_array();

            $entries = [];
            $balance = 0;
            foreach ($history as $h) {
                $debit  = (float) ($h['debit'] ?? 0);
                $credit = (float) ($h['credit'] ?? 0);
                $balance += $debit - $credit;
                $entries[] = [
                    'date'        => $h['date'],
                    'particulars' => $h['description'] ?? ($h['rel_type'].' #'.$h['rel_id']),
                    'debit'       => round($debit, 2),
                    'credit'      => round($credit, 2),
                    'balance'     => round($balance, 2),
                    'rel_type'    => $h['rel_type'] ?? null,
                    'rel_id'      => isset($h['rel_id']) ? (int) $h['rel_id'] : null,
                ];
            }

            return [
                'id'      => $id,
                'name'    => $account['name'] ?? 'Account',
                'group'   => 'Ledger',
                'from_date' => $from,
                'to_date'   => $to,
                'entries' => $entries,
                'closing_balance' => round($balance, 2),
            ];
        }

        $client = $this->db->where('userid', $id)->get(db_prefix().'clients')->row_array();
        if (!$client) {
            return null;
        }

        $this->db->select('id, date, total, status');
        $this->db->from(db_prefix().'invoices');
        $this->db->where('clientid', $id);
        $this->db->where('date >=', $from);
        $this->db->where('date <=', $to);
        $this->db->order_by('date', 'ASC');
        $entries = [];
        $balance = 0;
        foreach ($this->db->get()->result_array() as $inv) {
            $amt = (float) $inv['total'];
            $balance += $amt;
            $entries[] = [
                'date'        => $inv['date'],
                'particulars' => format_invoice_number($inv['id']),
                'debit'       => round($amt, 2),
                'credit'      => 0,
                'balance'     => round($balance, 2),
            ];
        }

        return [
            'id'      => $id,
            'name'    => $client['company'] ?: 'Customer',
            'group'   => 'Sundry Debtors',
            'from_date' => $from,
            'to_date'   => $to,
            'entries' => $entries,
            'closing_balance' => round($balance, 2),
        ];
    }

    public function createLedger(array $data)
    {
        if (!$this->accountingModuleActive()) {
            return ['success' => false, 'message' => 'Accounting module required to create ledger accounts'];
        }

        $this->load->model('accounting/accounting_model');
        $payload = [
            'name'                   => $data['name'] ?? '',
            'number'                 => $data['number'] ?? '',
            'account_type_id'        => (int) ($data['account_type_id'] ?? 16),
            'account_detail_type_id' => (int) ($data['account_detail_type_id'] ?? 14),
            'balance'                => $data['balance'] ?? 0,
            'balance_as_of'          => $data['balance_as_of'] ?? date('Y-m-d'),
            'description'            => $data['description'] ?? '',
        ];

        if ($payload['name'] === '') {
            return ['success' => false, 'message' => 'name is required'];
        }

        $id = $this->accounting_model->add_account($payload);

        return ['success' => $id > 0, 'id' => (int) $id];
    }

    public function updateLedger($id, array $data)
    {
        if (!$this->accountingModuleActive()) {
            return ['success' => false, 'message' => 'Accounting module required to update ledger accounts'];
        }

        $this->load->model('accounting/accounting_model');
        $id = (int) $id;
        $payload = array_filter([
            'name'                   => $data['name'] ?? null,
            'number'                 => $data['number'] ?? null,
            'account_type_id'        => isset($data['account_type_id']) ? (int) $data['account_type_id'] : null,
            'account_detail_type_id' => isset($data['account_detail_type_id']) ? (int) $data['account_detail_type_id'] : null,
            'description'            => $data['description'] ?? null,
        ], function ($v) {
            return $v !== null;
        });

        if (empty($payload)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $ok = $this->accounting_model->update_account($payload, $id);

        return ['success' => (bool) $ok, 'id' => $id];
    }

    public function deleteLedger($id)
    {
        if (!$this->accountingModuleActive()) {
            return ['success' => false, 'message' => 'Accounting module required to delete ledger accounts'];
        }

        $this->load->model('accounting/accounting_model');

        return ['success' => (bool) $this->accounting_model->delete_account((int) $id), 'id' => (int) $id];
    }

    private function ledgerAccountBalance($accountId, $from, $to)
    {
        if (!$this->db->table_exists(db_prefix().'acc_account_history')) {
            return ['amount' => 0, 'type' => 'Dr'];
        }

        $this->db->select('COALESCE(SUM(debit),0) as debit, COALESCE(SUM(credit),0) as credit', false);
        $this->db->where('account', $accountId);
        $this->db->where('date >=', $from);
        $this->db->where('date <=', $to);
        $row = $this->db->get(db_prefix().'acc_account_history')->row();
        $net = (float) ($row->debit ?? 0) - (float) ($row->credit ?? 0);

        return [
            'amount' => $net,
            'type'   => $net >= 0 ? 'Dr' : 'Cr',
        ];
    }

    private function agingBucketKey($days)
    {
        if ($days <= 0) {
            return 'current';
        }
        if ($days <= 30) {
            return '1_30';
        }
        if ($days <= 60) {
            return '31_60';
        }
        if ($days <= 90) {
            return '61_90';
        }

        return '90_plus';
    }

    private function transformSectionReport($data)
    {
        $sections = [];
        $incomeTotal = 0;
        $expenseTotal = 0;
        $assetsTotal = 0;
        $liabilitiesTotal = 0;
        $netTotal = 0;

        if (!is_array($data)) {
            return [
                'sections'          => [],
                'income_total'      => 0,
                'expense_total'     => 0,
                'assets_total'      => 0,
                'liabilities_total' => 0,
                'net_total'         => 0,
            ];
        }

        foreach ($data as $sectionKey => $items) {
            if (!is_array($items)) {
                continue;
            }
            $sectionRows = [];
            $sectionTotal = 0;
            foreach ($items as $item) {
                $amount = (float) ($item['amount'] ?? $item['this_year'] ?? 0);
                $sectionTotal += $amount;
                $sectionRows[] = [
                    'name'   => $item['name'] ?? '',
                    'amount' => round($amount, 2),
                ];
            }
            $title = ucwords(str_replace('_', ' ', (string) $sectionKey));
            $sections[] = [
                'title' => $title,
                'rows'  => $sectionRows,
                'total' => round($sectionTotal, 2),
            ];

            $key = strtolower((string) $sectionKey);
            if (strpos($key, 'income') !== false || strpos($key, 'revenue') !== false) {
                $incomeTotal += $sectionTotal;
            } elseif (strpos($key, 'expense') !== false || strpos($key, 'cost') !== false) {
                $expenseTotal += $sectionTotal;
            } elseif (strpos($key, 'asset') !== false) {
                $assetsTotal += $sectionTotal;
            } elseif (strpos($key, 'liabil') !== false || strpos($key, 'equity') !== false) {
                $liabilitiesTotal += $sectionTotal;
            }
            $netTotal += $sectionTotal;
        }

        return [
            'sections'           => $sections,
            'income_total'       => round($incomeTotal, 2),
            'expense_total'      => round($expenseTotal, 2),
            'assets_total'       => round($assetsTotal, 2),
            'liabilities_total'  => round($liabilitiesTotal, 2),
            'net_total'          => round($netTotal, 2),
        ];
    }

    private function parseAmount($value)
    {
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    }
}
