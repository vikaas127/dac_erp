<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Reports_model extends App_Model
{
    private $catalog = [];

    public function __construct()
    {
        parent::__construct();
        $this->catalog = $this->defineCatalog();
    }

    public function getCatalog()
    {
        return array_values($this->catalog);
    }

    public function getDefinition($reportId)
    {
        return $this->catalog[$reportId] ?? null;
    }

    public function build($reportId, array $params = [])
    {
        $def = $this->getDefinition($reportId);
        if (!$def) {
            return null;
        }

        [$from, $to, $dateLabel] = $this->resolveDateRange($params);

        $base = [
            'id'         => $reportId,
            'title'      => $def['title'],
            'layout'     => $def['layout'],
            'date_label' => $dateLabel,
            'filters'    => $def['filters'],
            'columns'    => $def['columns'],
            'rows'       => [],
            'metrics'    => [],
            'sections'   => [],
            'summary'    => null,
            'from_date'  => $from,
            'to_date'    => $to,
        ];

        $filter = $params['filter'] ?? 'All';

        switch ($reportId) {
            case 'sales_register':
            case 'invoices':
            case 'pending_invoices':
                return array_merge($base, $this->buildInvoicesReport($from, $to, $filter, $reportId));
            case 'quotations':
                return array_merge($base, $this->buildEstimatesReport($from, $to, $filter));
            case 'purchase_register':
                return array_merge($base, $this->buildPurchaseReport($from, $to, $filter));
            case 'receivables':
            case 'outstanding':
                return array_merge($base, $this->buildReceivablesReport($from, $to));
            case 'payables':
                return array_merge($base, $this->buildPayablesReport($from, $to));
            case 'payment_report':
                return array_merge($base, $this->buildPaymentsReport($from, $to, 'out'));
            case 'receipt_report':
                return array_merge($base, $this->buildPaymentsReport($from, $to, 'in'));
            case 'expense_report':
                return array_merge($base, $this->buildExpensesReport($from, $to));
            case 'profit_loss':
                return array_merge($base, $this->buildProfitLossReport($from, $to));
            case 'balance_sheet':
                return array_merge($base, $this->buildBalanceSheetReport($from, $to));
            case 'trial_balance':
                return array_merge($base, $this->buildTrialBalanceReport($from, $to));
            case 'cash_flow':
                return array_merge($base, $this->buildCashFlowReport($from, $to));
            case 'sales_overview':
                return array_merge($base, $this->buildSalesOverviewReport($from, $to));
            case 'avg_bill_value':
                return array_merge($base, $this->buildAvgBillReport($from, $to));
            case 'top_customer':
                return array_merge($base, $this->buildTopCustomerReport($from, $to));
            case 'repeat_customer':
                return array_merge($base, $this->buildRepeatCustomerReport($from, $to));
            case 'bill_wise':
                return array_merge($base, $this->buildBillWiseReport($from, $to));
            case 'item_wise':
                return array_merge($base, $this->buildItemWiseReport($from, $to));
            case 'day_book':
                return array_merge($base, $this->buildDayBookReport($from, $to));
            case 'ledger':
                return array_merge($base, $this->buildLedgerReport($from, $to));
            case 'bank_report':
                return array_merge($base, $this->buildBankReport($from, $to));
            case 'stock_summary':
            case 'godown_stock':
            case 'item_wise_stock':
            case 'category_wise':
                return array_merge($base, $this->buildStockReport($from, $to, $reportId));
            case 'low_stock':
            case 'negative_stock':
                return array_merge($base, $this->buildLowStockReport($reportId));
            case 'gstr_1':
            case 'gstr_3b':
            case 'gst_summary':
            case 'tax_liability':
                return array_merge($base, $this->buildGstReport($from, $to, $reportId));
            case 'attendance':
                return array_merge($base, $this->buildAttendanceReport($from, $to, (int) ($params['staff_id'] ?? 0)));
            case 'employee_report':
            case 'salary_report':
            case 'leave_report':
                return array_merge($base, $this->buildHrReport($from, $to, $reportId));
            default:
                return $base;
        }
    }

    private function defineCatalog()
    {
        $items = [
            ['id' => 'sales_register', 'title' => 'Sales Register', 'tab' => 'Sales', 'layout' => 'list', 'filters' => ['All', 'Paid', 'Unpaid'], 'columns' => ['Invoice', 'Customer', 'Amount']],
            ['id' => 'purchase_register', 'title' => 'Purchase Register', 'tab' => 'Sales', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['PO', 'Vendor', 'Amount']],
            ['id' => 'invoices', 'title' => 'Invoices', 'tab' => 'Sales', 'layout' => 'list', 'filters' => ['All', 'Paid', 'Unpaid', 'Overdue'], 'columns' => ['Invoice', 'Amount']],
            ['id' => 'quotations', 'title' => 'Quotations', 'tab' => 'Sales', 'layout' => 'list', 'filters' => ['All', 'Draft', 'Sent'], 'columns' => ['Quote', 'Customer', 'Amount']],
            ['id' => 'receivables', 'title' => 'Receivables', 'tab' => 'Sales', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Customer', 'Due', 'Amount']],
            ['id' => 'payables', 'title' => 'Payables', 'tab' => 'Sales', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Vendor', 'Amount']],
            ['id' => 'day_book', 'title' => 'Day Book', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Date', 'Particulars', 'Amount']],
            ['id' => 'ledger', 'title' => 'Ledger Report', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Account', 'Balance']],
            ['id' => 'top_customer', 'title' => 'Top Customer', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Customer', 'Revenue']],
            ['id' => 'profit_loss', 'title' => 'Profit & Loss', 'tab' => 'Accounting', 'layout' => 'sections', 'filters' => ['All'], 'columns' => ['Account', 'Amount']],
            ['id' => 'balance_sheet', 'title' => 'Balance Sheet', 'tab' => 'Accounting', 'layout' => 'sections', 'filters' => ['All'], 'columns' => ['Account', 'Amount']],
            ['id' => 'sales_overview', 'title' => 'Sales Overview', 'tab' => 'Accounting', 'layout' => 'metrics', 'filters' => ['All'], 'columns' => []],
            ['id' => 'avg_bill_value', 'title' => 'Avg Bill Value', 'tab' => 'Accounting', 'layout' => 'metrics', 'filters' => ['All'], 'columns' => []],
            ['id' => 'repeat_customer', 'title' => 'Repeat Customer', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Customer', 'Orders']],
            ['id' => 'bill_wise', 'title' => 'Bill Wise', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Bill', 'Customer', 'Amount']],
            ['id' => 'item_wise', 'title' => 'Item Wise', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Item', 'Qty', 'Amount']],
            ['id' => 'pending_invoices', 'title' => 'Pending Invoices', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All', 'Unpaid'], 'columns' => ['Invoice', 'Due', 'Amount']],
            ['id' => 'trial_balance', 'title' => 'Trial Balance', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Account', 'Debit', 'Credit']],
            ['id' => 'cash_flow', 'title' => 'Cash Flow', 'tab' => 'Accounting', 'layout' => 'sections', 'filters' => ['All'], 'columns' => ['Activity', 'Amount']],
            ['id' => 'bank_report', 'title' => 'Bank Report', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Date', 'Particulars', 'Amount']],
            ['id' => 'expense_report', 'title' => 'Expense Report', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Expense', 'Category', 'Amount']],
            ['id' => 'receipt_report', 'title' => 'Receipt Report', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Receipt', 'Customer', 'Amount']],
            ['id' => 'payment_report', 'title' => 'Payment Report', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Payment', 'Amount']],
            ['id' => 'outstanding', 'title' => 'Outstanding', 'tab' => 'Accounting', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Customer', 'Outstanding']],
            ['id' => 'stock_summary', 'title' => 'Stock Summary', 'tab' => 'Inventory', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Item', 'Qty', 'Value']],
            ['id' => 'godown_stock', 'title' => 'Godown Stock', 'tab' => 'Inventory', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Warehouse', 'Item', 'Qty']],
            ['id' => 'negative_stock', 'title' => 'Negative Stock', 'tab' => 'Inventory', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Item', 'Qty']],
            ['id' => 'low_stock', 'title' => 'Low Stock', 'tab' => 'Inventory', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Item', 'Qty']],
            ['id' => 'item_wise_stock', 'title' => 'Item Wise', 'tab' => 'Inventory', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Item', 'Qty']],
            ['id' => 'category_wise', 'title' => 'Category Wise', 'tab' => 'Inventory', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Category', 'Qty']],
            ['id' => 'gstr_1', 'title' => 'GSTR-1', 'tab' => 'GST', 'layout' => 'sections', 'filters' => ['All'], 'columns' => ['Type', 'Taxable', 'Tax']],
            ['id' => 'gstr_3b', 'title' => 'GSTR-3B', 'tab' => 'GST', 'layout' => 'sections', 'filters' => ['All'], 'columns' => ['Head', 'Amount']],
            ['id' => 'gst_summary', 'title' => 'GST Summary', 'tab' => 'GST', 'layout' => 'metrics', 'filters' => ['All'], 'columns' => []],
            ['id' => 'tax_liability', 'title' => 'Tax Liability', 'tab' => 'GST', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Period', 'Liability']],
            ['id' => 'attendance', 'title' => 'Attendance', 'tab' => 'HR', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Staff', 'Date', 'Status']],
            ['id' => 'employee_report', 'title' => 'Employee Report', 'tab' => 'HR', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Employee', 'Department']],
            ['id' => 'salary_report', 'title' => 'Salary Report', 'tab' => 'HR', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Employee', 'Net Pay']],
            ['id' => 'leave_report', 'title' => 'Leave Report', 'tab' => 'HR', 'layout' => 'list', 'filters' => ['All'], 'columns' => ['Employee', 'Leave', 'Days']],
        ];

        $map = [];
        foreach ($items as $item) {
            $map[$item['id']] = $item;
        }

        return $map;
    }

    public function resolveDateRange(array $params)
    {
        $from   = $params['from_date'] ?? null;
        $to     = $params['to_date'] ?? null;
        $period = $params['period'] ?? 'month';

        if ($from && $to) {
            $label = date('M Y', strtotime($from));
            if (date('Y-m', strtotime($from)) !== date('Y-m', strtotime($to))) {
                $label = date('d M Y', strtotime($from)).' - '.date('d M Y', strtotime($to));
            }

            return [$from, $to, $label];
        }

        $today = date('Y-m-d');
        switch ($period) {
            case 'year':
                $from = date('Y-01-01');
                $to   = date('Y-12-31');
                $label = date('Y');
                break;
            case 'quarter':
                $m = (int) date('n');
                $qStart = (int) (floor(($m - 1) / 3) * 3 + 1);
                $from = date('Y-'.str_pad((string) $qStart, 2, '0', STR_PAD_LEFT).'-01');
                $to   = date('Y-m-t', strtotime($from.' +2 months'));
                $label = 'Q'.(int) ceil($m / 3).' '.date('Y');
                break;
            case 'fy':
                $m = (int) date('n');
                $y = (int) date('Y');
                if ($m >= 4) {
                    $from = $y.'-04-01';
                    $to   = ($y + 1).'-03-31';
                } else {
                    $from = ($y - 1).'-04-01';
                    $to   = $y.'-03-31';
                }
                $label = 'FY '.substr((string) $from, 2, 2).'-'.substr((string) $to, 2, 2);
                break;
            case 'month':
            default:
                $from  = date('Y-m-01');
                $to    = date('Y-m-t');
                $label = date('F Y');
                break;
        }

        return [$from, $to, $label];
    }

    private function fmt($amount)
    {
        return number_format((float) $amount, 2);
    }

    private function invoiceStatusBadge($status)
    {
        $map = ['1' => 'Unpaid', '2' => 'Paid', '3' => 'Partially', '4' => 'Overdue', '5' => 'Cancelled', '6' => 'Draft'];
        return $map[(string) $status] ?? 'Unknown';
    }

    private function buildInvoicesReport($from, $to, $filter, $mode)
    {
        $this->db->select('i.id, i.number, i.date, i.total, i.status, c.company');
        $this->db->from(db_prefix().'invoices i');
        $this->db->join(db_prefix().'clients c', 'c.userid = i.clientid', 'left');
        $this->db->where('i.date >=', $from);
        $this->db->where('i.date <=', $to);
        if ($mode === 'pending_invoices') {
            $this->db->where_in('i.status', [1, 3, 4]);
        }
        if ($filter === 'Paid') {
            $this->db->where('i.status', 2);
        } elseif ($filter === 'Unpaid') {
            $this->db->where_in('i.status', [1, 3, 4]);
        } elseif ($filter === 'Overdue') {
            $this->db->where('i.status', 4);
        }
        $this->db->order_by('i.date', 'DESC');
        $this->db->limit(200);
        $rows = [];
        $total = 0;
        foreach ($this->db->get()->result_array() as $r) {
            $badge = $this->invoiceStatusBadge($r['status']);
            $total += (float) $r['total'];
            $rows[] = [
                'title'    => format_invoice_number($r['id']),
                'subtitle' => $r['company'] ?: '—',
                'amount'   => $this->fmt($r['total']),
                'badge'    => $badge,
                'filter'   => $badge,
                'icon'     => 'invoice',
                'color'    => $r['status'] == 2 ? '#16a34a' : '#dc2626',
                'meta'     => ['id' => (int) $r['id'], 'date' => $r['date']],
            ];
        }

        return [
            'rows'    => $rows,
            'summary' => ['label' => 'Total', 'value' => $this->fmt($total), 'color' => '#111827'],
        ];
    }

    private function buildEstimatesReport($from, $to, $filter)
    {
        $this->db->select('e.id, e.number, e.date, e.total, e.status, c.company');
        $this->db->from(db_prefix().'estimates e');
        $this->db->join(db_prefix().'clients c', 'c.userid = e.clientid', 'left');
        $this->db->where('e.date >=', $from);
        $this->db->where('e.date <=', $to);
        if ($filter === 'Draft') {
            $this->db->where('e.status', 1);
        } elseif ($filter === 'Sent') {
            $this->db->where('e.status', 2);
        }
        $this->db->order_by('e.date', 'DESC');
        $this->db->limit(200);
        $rows = [];
        $total = 0;
        foreach ($this->db->get()->result_array() as $r) {
            $total += (float) $r['total'];
            $rows[] = [
                'title'    => format_estimate_number($r['id']),
                'subtitle' => $r['company'] ?: '—',
                'amount'   => $this->fmt($r['total']),
                'badge'    => 'Quote',
                'filter'   => 'All',
                'icon'     => 'quotation',
                'color'    => '#2563eb',
                'meta'     => ['id' => (int) $r['id'], 'date' => $r['date']],
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Total', 'value' => $this->fmt($total), 'color' => '#111827']];
    }

    private function buildPurchaseReport($from, $to, $filter)
    {
        if (!$this->db->table_exists(db_prefix().'pur_orders')) {
            return ['rows' => [], 'summary' => ['label' => 'Total', 'value' => '0.00', 'color' => '#6b7280']];
        }
        $this->db->select('po.id, po.pur_order_number, po.order_date, po.total, v.company');
        $this->db->from(db_prefix().'pur_orders po');
        $this->db->join(db_prefix().'pur_vendor v', 'v.userid = po.vendor', 'left');
        $this->db->where('po.order_date >=', $from);
        $this->db->where('po.order_date <=', $to);
        $this->db->order_by('po.order_date', 'DESC');
        $this->db->limit(200);
        $rows = [];
        $total = 0;
        foreach ($this->db->get()->result_array() as $r) {
            $total += (float) $r['total'];
            $rows[] = [
                'title'    => $r['pur_order_number'] ?: ('PO-'.$r['id']),
                'subtitle' => $r['company'] ?: 'Vendor',
                'amount'   => $this->fmt($r['total']),
                'badge'    => 'Purchase',
                'filter'   => $filter,
                'icon'     => 'purchase',
                'color'    => '#7c3aed',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Total', 'value' => $this->fmt($total), 'color' => '#111827']];
    }

    private function buildReceivablesReport($from, $to)
    {
        $this->db->select('i.id, i.total, i.date, c.company, i.status');
        $this->db->from(db_prefix().'invoices i');
        $this->db->join(db_prefix().'clients c', 'c.userid = i.clientid', 'left');
        $this->db->where_in('i.status', [1, 3, 4]);
        $this->db->where('i.date >=', $from);
        $this->db->where('i.date <=', $to);
        $this->db->order_by('i.date', 'DESC');
        $this->db->limit(200);
        $rows = [];
        $total = 0;
        foreach ($this->db->get()->result_array() as $r) {
            $total += (float) $r['total'];
            $rows[] = [
                'title'    => $r['company'] ?: 'Customer',
                'subtitle' => format_invoice_number($r['id']).' · '.$r['date'],
                'amount'   => $this->fmt($r['total']),
                'badge'    => $this->invoiceStatusBadge($r['status']),
                'filter'   => 'All',
                'icon'     => 'receivable',
                'color'    => '#ea580c',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Outstanding', 'value' => $this->fmt($total), 'color' => '#dc2626']];
    }

    private function buildPayablesReport($from, $to)
    {
        $this->db->select('e.id, e.amount, e.date, e.category, e.expense_name');
        $this->db->from(db_prefix().'expenses e');
        $this->db->where('e.date >=', $from);
        $this->db->where('e.date <=', $to);
        $this->db->order_by('e.date', 'DESC');
        $this->db->limit(200);
        $rows = [];
        $total = 0;
        foreach ($this->db->get()->result_array() as $r) {
            $total += (float) $r['amount'];
            $rows[] = [
                'title'    => $r['expense_name'] ?: ('Expense #'.$r['id']),
                'subtitle' => $r['category'] ?: 'Expense',
                'amount'   => $this->fmt($r['amount']),
                'badge'    => 'Payable',
                'filter'   => 'All',
                'icon'     => 'payable',
                'color'    => '#b91c1c',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Total', 'value' => $this->fmt($total), 'color' => '#111827']];
    }

    private function buildPaymentsReport($from, $to, $mode)
    {
        $this->db->select('p.id, p.amount, p.date, p.invoiceid, c.company');
        $this->db->from(db_prefix().'invoicepaymentrecords p');
        $this->db->join(db_prefix().'invoices i', 'i.id = p.invoiceid', 'left');
        $this->db->join(db_prefix().'clients c', 'c.userid = i.clientid', 'left');
        $this->db->where('p.date >=', $from);
        $this->db->where('p.date <=', $to);
        $this->db->order_by('p.date', 'DESC');
        $this->db->limit(200);
        $rows = [];
        $total = 0;
        foreach ($this->db->get()->result_array() as $r) {
            $total += (float) $r['amount'];
            $rows[] = [
                'title'    => ($mode === 'in' ? 'Receipt #' : 'Payment #').$r['id'],
                'subtitle' => ($r['company'] ?: 'Customer').' · '.($r['invoiceid'] ? format_invoice_number($r['invoiceid']) : ''),
                'amount'   => $this->fmt($r['amount']),
                'badge'    => $mode === 'in' ? 'Receipt' : 'Payment',
                'filter'   => 'All',
                'icon'     => $mode === 'in' ? 'receipt' : 'payment',
                'color'    => '#059669',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Total', 'value' => $this->fmt($total), 'color' => '#111827']];
    }

    private function buildExpensesReport($from, $to)
    {
        return $this->buildPayablesReport($from, $to);
    }

    private function buildProfitLossReport($from, $to)
    {
        if ($this->accountingModuleActive()) {
            $this->load->model('accounting/accounting_model');
            $raw = $this->accounting_model->get_data_profit_and_loss($this->accountingFilter($from, $to));

            return $this->hubFromAccountingSections($raw['data'] ?? [], 'profit_loss');
        }

        $sales = (float) ($this->db->select_sum('total')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'invoices')->row()->total ?? 0);
        $expense = (float) ($this->db->select_sum('amount')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'expenses')->row()->amount ?? 0);
        $net = $sales - $expense;

        return [
            'layout'   => 'sections',
            'source'   => 'erp',
            'sections' => [
                [
                    'title' => 'Income',
                    'rows'  => [['title' => 'Sales', 'subtitle' => 'Invoices', 'amount' => $this->fmt($sales), 'color' => '#16a34a']],
                ],
                [
                    'title' => 'Expenses',
                    'rows'  => [['title' => 'Operating Expenses', 'subtitle' => 'Expenses', 'amount' => $this->fmt($expense), 'color' => '#dc2626']],
                ],
            ],
            'metrics' => [
                ['label' => 'Net Profit', 'value' => $this->fmt($net), 'color' => $net >= 0 ? '#16a34a' : '#dc2626'],
            ],
            'summary' => ['label' => 'Net Profit', 'value' => $this->fmt($net), 'color' => $net >= 0 ? '#16a34a' : '#dc2626'],
        ];
    }

    private function buildBalanceSheetReport($from, $to)
    {
        if ($this->accountingModuleActive()) {
            $this->load->model('accounting/accounting_model');
            $raw = $this->accounting_model->get_data_balance_sheet_summary($this->accountingFilter($from, $to));

            return $this->hubFromAccountingSections($raw['data'] ?? [], 'balance_sheet');
        }

        $receivables = (float) ($this->db->select_sum('total')->where_in('status', [1, 3, 4])->get(db_prefix().'invoices')->row()->total ?? 0);
        $payments = (float) ($this->db->select_sum('amount')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'invoicepaymentrecords')->row()->amount ?? 0);
        $expenses = (float) ($this->db->select_sum('amount')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'expenses')->row()->amount ?? 0);

        return [
            'layout'   => 'sections',
            'source'   => 'erp',
            'sections' => [
                ['title' => 'Assets', 'rows' => [
                    ['title' => 'Receivables', 'amount' => $this->fmt($receivables), 'color' => '#2563eb'],
                    ['title' => 'Cash (Receipts period)', 'amount' => $this->fmt($payments), 'color' => '#2563eb'],
                ]],
                ['title' => 'Liabilities', 'rows' => [
                    ['title' => 'Expenses (period)', 'amount' => $this->fmt($expenses), 'color' => '#dc2626'],
                ]],
            ],
            'summary' => ['label' => 'Assets (Receivables)', 'value' => $this->fmt($receivables), 'color' => '#111827'],
        ];
    }

    private function buildTrialBalanceReport($from, $to)
    {
        if ($this->accountingModuleActive()) {
            $this->load->model('accounting/accounting_model');
            $raw = $this->accounting_model->get_data_trial_balance($this->accountingFilter($from, $to));

            return $this->hubFromAccountingTrialBalance($raw['data'] ?? []);
        }

        $sales = (float) ($this->db->select_sum('total')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'invoices')->row()->total ?? 0);
        $expense = (float) ($this->db->select_sum('amount')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'expenses')->row()->amount ?? 0);
        $receipts = (float) ($this->db->select_sum('amount')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'invoicepaymentrecords')->row()->amount ?? 0);

        return [
            'source' => 'erp',
            'rows' => [
                ['title' => 'Sales', 'subtitle' => 'Credit', 'amount' => $this->fmt($sales), 'badge' => 'Cr', 'color' => '#16a34a'],
                ['title' => 'Receipts', 'subtitle' => 'Debit', 'amount' => $this->fmt($receipts), 'badge' => 'Dr', 'color' => '#2563eb'],
                ['title' => 'Expenses', 'subtitle' => 'Debit', 'amount' => $this->fmt($expense), 'badge' => 'Dr', 'color' => '#dc2626'],
            ],
            'summary' => ['label' => 'Sales (Cr)', 'value' => $this->fmt($sales), 'color' => '#111827'],
        ];
    }

    private function buildCashFlowReport($from, $to)
    {
        if ($this->accountingModuleActive()) {
            $this->load->model('accounting/accounting_model');
            $raw = $this->accounting_model->get_data_statement_of_cash_flows($this->accountingFilter($from, $to));

            return $this->hubFromAccountingSections($raw['data'] ?? [], 'cash_flow');
        }

        $receipts = (float) ($this->db->select_sum('amount')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'invoicepaymentrecords')->row()->amount ?? 0);
        $expense = (float) ($this->db->select_sum('amount')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'expenses')->row()->amount ?? 0);
        $net = $receipts - $expense;

        return [
            'layout'   => 'sections',
            'source'   => 'erp',
            'sections' => [
                ['title' => 'Operating', 'rows' => [
                    ['title' => 'Customer Receipts', 'amount' => $this->fmt($receipts), 'color' => '#16a34a'],
                    ['title' => 'Expenses Paid', 'amount' => $this->fmt($expense), 'color' => '#dc2626'],
                ]],
            ],
            'summary' => ['label' => 'Net Cash Flow', 'value' => $this->fmt($net), 'color' => $net >= 0 ? '#16a34a' : '#dc2626'],
        ];
    }

    private function buildSalesOverviewReport($from, $to)
    {
        $sales = (float) ($this->db->select_sum('total')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'invoices')->row()->total ?? 0);
        $count = (int) total_rows(db_prefix().'invoices', 'date >= "'.$from.'" AND date <= "'.$to.'"');
        $paid  = (int) total_rows(db_prefix().'invoices', 'date >= "'.$from.'" AND date <= "'.$to.'" AND status = 2');

        return [
            'layout'  => 'metrics',
            'metrics' => [
                ['label' => 'Total Sales', 'value' => $this->fmt($sales), 'color' => '#2563eb'],
                ['label' => 'Invoices', 'value' => (string) $count, 'color' => '#111827'],
                ['label' => 'Paid', 'value' => (string) $paid, 'color' => '#16a34a'],
            ],
            'summary' => ['label' => 'Total Sales', 'value' => $this->fmt($sales), 'color' => '#2563eb'],
        ];
    }

    private function buildAvgBillReport($from, $to)
    {
        $sales = (float) ($this->db->select_sum('total')->where('date >=', $from)->where('date <=', $to)->get(db_prefix().'invoices')->row()->total ?? 0);
        $count = max(1, (int) total_rows(db_prefix().'invoices', 'date >= "'.$from.'" AND date <= "'.$to.'"'));
        $avg   = $sales / $count;

        return [
            'layout'  => 'metrics',
            'metrics' => [
                ['label' => 'Avg Bill Value', 'value' => $this->fmt($avg), 'color' => '#7c3aed'],
                ['label' => 'Invoice Count', 'value' => (string) $count, 'color' => '#111827'],
            ],
            'summary' => ['label' => 'Avg Bill', 'value' => $this->fmt($avg), 'color' => '#7c3aed'],
        ];
    }

    private function buildTopCustomerReport($from, $to)
    {
        $this->db->select('c.company, SUM(i.total) as revenue, COUNT(i.id) as cnt', false);
        $this->db->from(db_prefix().'invoices i');
        $this->db->join(db_prefix().'clients c', 'c.userid = i.clientid', 'left');
        $this->db->where('i.date >=', $from);
        $this->db->where('i.date <=', $to);
        $this->db->group_by('i.clientid');
        $this->db->order_by('revenue', 'DESC');
        $this->db->limit(25);
        $rows = [];
        foreach ($this->db->get()->result_array() as $r) {
            $rows[] = [
                'title'    => $r['company'] ?: 'Customer',
                'subtitle' => $r['cnt'].' invoices',
                'amount'   => $this->fmt($r['revenue']),
                'icon'     => 'customer',
                'color'    => '#2563eb',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Top Customers', 'value' => (string) count($rows), 'color' => '#111827']];
    }

    private function buildRepeatCustomerReport($from, $to)
    {
        $this->db->select('c.company, COUNT(i.id) as cnt, SUM(i.total) as revenue', false);
        $this->db->from(db_prefix().'invoices i');
        $this->db->join(db_prefix().'clients c', 'c.userid = i.clientid', 'left');
        $this->db->where('i.date >=', $from);
        $this->db->where('i.date <=', $to);
        $this->db->group_by('i.clientid');
        $this->db->having('cnt >', 1);
        $this->db->order_by('cnt', 'DESC');
        $this->db->limit(50);
        $rows = [];
        foreach ($this->db->get()->result_array() as $r) {
            $rows[] = [
                'title'    => $r['company'] ?: 'Customer',
                'subtitle' => 'Repeat buyer',
                'amount'   => (string) $r['cnt'],
                'badge'    => $r['cnt'].' orders',
                'color'    => '#059669',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Repeat Customers', 'value' => (string) count($rows), 'color' => '#059669']];
    }

    private function buildBillWiseReport($from, $to)
    {
        return $this->buildInvoicesReport($from, $to, 'All', 'invoices');
    }

    private function buildItemWiseReport($from, $to)
    {
        $this->db->select('it.description, SUM(it.qty) as qty, SUM(it.qty * it.rate) as amount', false);
        $this->db->from(db_prefix().'itemable it');
        $this->db->join(db_prefix().'invoices i', 'i.id = it.rel_id AND it.rel_type = "invoice"', 'inner');
        $this->db->where('i.date >=', $from);
        $this->db->where('i.date <=', $to);
        $this->db->group_by('it.description');
        $this->db->order_by('amount', 'DESC');
        $this->db->limit(100);
        $rows = [];
        $total = 0;
        foreach ($this->db->get()->result_array() as $r) {
            $total += (float) $r['amount'];
            $rows[] = [
                'title'    => $r['description'] ?: 'Item',
                'subtitle' => 'Qty '.$this->fmt($r['qty']),
                'amount'   => $this->fmt($r['amount']),
                'color'    => '#4f46e5',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Total', 'value' => $this->fmt($total), 'color' => '#111827']];
    }

    private function buildDayBookReport($from, $to)
    {
        $rows = [];
        $this->db->select('date, amount, invoiceid');
        $this->db->from(db_prefix().'invoicepaymentrecords');
        $this->db->where('date >=', $from);
        $this->db->where('date <=', $to);
        $this->db->order_by('date', 'DESC');
        $this->db->limit(100);
        foreach ($this->db->get()->result_array() as $r) {
            $rows[] = [
                'title'    => $r['date'],
                'subtitle' => 'Receipt · '.($r['invoiceid'] ? format_invoice_number($r['invoiceid']) : ''),
                'amount'   => $this->fmt($r['amount']),
                'badge'    => 'Receipt',
                'color'    => '#16a34a',
            ];
        }
        $this->db->select('date, amount, expense_name');
        $this->db->from(db_prefix().'expenses');
        $this->db->where('date >=', $from);
        $this->db->where('date <=', $to);
        $this->db->order_by('date', 'DESC');
        $this->db->limit(100);
        foreach ($this->db->get()->result_array() as $r) {
            $rows[] = [
                'title'    => $r['date'],
                'subtitle' => 'Expense · '.($r['expense_name'] ?: ''),
                'amount'   => $this->fmt($r['amount']),
                'badge'    => 'Expense',
                'color'    => '#dc2626',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Entries', 'value' => (string) count($rows), 'color' => '#111827']];
    }

    private function buildLedgerReport($from, $to)
    {
        $rows = [];
        $this->db->select('company, userid');
        $this->db->from(db_prefix().'clients');
        $this->db->where('active', 1);
        $this->db->limit(100);
        foreach ($this->db->get()->result_array() as $c) {
            $this->db->select_sum('total');
            $this->db->where('clientid', $c['userid']);
            $this->db->where_in('status', [1, 3, 4]);
            $bal = (float) ($this->db->get(db_prefix().'invoices')->row()->total ?? 0);
            $rows[] = [
                'title'    => $c['company'],
                'subtitle' => 'Sundry Debtor',
                'amount'   => $this->fmt($bal),
                'badge'    => $bal > 0 ? 'Dr' : '—',
                'color'    => '#2563eb',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Accounts', 'value' => (string) count($rows), 'color' => '#111827']];
    }

    private function buildBankReport($from, $to)
    {
        return $this->buildPaymentsReport($from, $to, 'in');
    }

    private function buildStockReport($from, $to, $mode)
    {
        if (!$this->db->table_exists(db_prefix().'inventory_manage')) {
            return ['rows' => [], 'summary' => ['label' => 'Stock', 'value' => '0', 'color' => '#6b7280']];
        }

        if ($mode === 'godown_stock') {
            $this->db->select('w.warehouse_name, i.description, SUM(im.inventory_number) as qty', false);
            $this->db->from(db_prefix().'inventory_manage im');
            $this->db->join(db_prefix().'items i', 'i.id = im.commodity_id', 'left');
            $this->db->join(db_prefix().'warehouse w', 'w.warehouse_id = im.warehouse_id', 'left');
            $this->db->group_by('im.warehouse_id, im.commodity_id');
            $this->db->order_by('w.warehouse_name', 'ASC');
            $this->db->limit(200);
        } elseif ($mode === 'category_wise') {
            $this->db->select('g.name as category, SUM(im.inventory_number) as qty', false);
            $this->db->from(db_prefix().'inventory_manage im');
            $this->db->join(db_prefix().'items i', 'i.id = im.commodity_id', 'left');
            $this->db->join(db_prefix().'items_groups g', 'g.id = i.group_id', 'left');
            $this->db->group_by('i.group_id');
            $this->db->limit(100);
        } else {
            $this->db->select('i.description, SUM(im.inventory_number) as qty, i.rate', false);
            $this->db->from(db_prefix().'inventory_manage im');
            $this->db->join(db_prefix().'items i', 'i.id = im.commodity_id', 'left');
            $this->db->group_by('im.commodity_id');
            $this->db->order_by('qty', 'DESC');
            $this->db->limit(200);
        }

        $rows = [];
        foreach ($this->db->get()->result_array() as $r) {
            $qty = (float) ($r['qty'] ?? 0);
            $val = isset($r['rate']) ? $qty * (float) $r['rate'] : $qty;
            $rows[] = [
                'title'    => $r['description'] ?? $r['warehouse_name'] ?? $r['category'] ?? 'Item',
                'subtitle' => isset($r['warehouse_name']) ? ($r['warehouse_name']) : 'Stock',
                'amount'   => $this->fmt($qty),
                'badge'    => isset($r['rate']) ? '₹'.$this->fmt($val) : null,
                'color'    => '#0d9488',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Items', 'value' => (string) count($rows), 'color' => '#0d9488']];
    }

    private function buildLowStockReport($mode)
    {
        if (!$this->db->table_exists(db_prefix().'inventory_manage')) {
            return ['rows' => [], 'summary' => ['label' => 'Items', 'value' => '0', 'color' => '#6b7280']];
        }

        $this->db->select('i.description, SUM(im.inventory_number) as qty, i.minimum_stock', false);
        $this->db->from(db_prefix().'inventory_manage im');
        $this->db->join(db_prefix().'items i', 'i.id = im.commodity_id', 'left');
        $this->db->group_by('im.commodity_id');
        if ($mode === 'negative_stock') {
            $this->db->having('qty <', 0);
        } else {
            $this->db->having('qty <= COALESCE(i.minimum_stock, 5)', null, false);
        }
        $this->db->limit(200);
        $rows = [];
        foreach ($this->db->get()->result_array() as $r) {
            $rows[] = [
                'title'    => $r['description'] ?: 'Item',
                'subtitle' => 'Min '.($r['minimum_stock'] ?? 5),
                'amount'   => $this->fmt($r['qty']),
                'badge'    => $mode === 'negative_stock' ? 'Negative' : 'Low',
                'color'    => '#dc2626',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Alert Items', 'value' => (string) count($rows), 'color' => '#dc2626']];
    }

    private function buildGstReport($from, $to, $mode)
    {
        $this->db->select_sum('total');
        $this->db->where('date >=', $from);
        $this->db->where('date <=', $to);
        $taxable = (float) ($this->db->get(db_prefix().'invoices')->row()->total ?? 0);
        $gstEst  = round($taxable * 0.18, 2);

        if ($mode === 'gst_summary') {
            return [
                'layout'  => 'metrics',
                'metrics' => [
                    ['label' => 'Taxable Turnover', 'value' => $this->fmt($taxable), 'color' => '#2563eb'],
                    ['label' => 'GST (est. 18%)', 'value' => $this->fmt($gstEst), 'color' => '#7c3aed'],
                ],
                'summary' => ['label' => 'GST Liability (est.)', 'value' => $this->fmt($gstEst), 'color' => '#7c3aed'],
            ];
        }

        return [
            'layout'   => 'sections',
            'sections' => [
                ['title' => 'Outward Supplies', 'rows' => [
                    ['title' => 'Taxable Value', 'amount' => $this->fmt($taxable), 'color' => '#2563eb'],
                    ['title' => 'GST (estimated)', 'amount' => $this->fmt($gstEst), 'color' => '#7c3aed'],
                ]],
            ],
            'summary' => ['label' => 'Tax Liability (est.)', 'value' => $this->fmt($gstEst), 'color' => '#7c3aed'],
        ];
    }

    private function buildAttendanceReport($from, $to, $staffId)
    {
        $table = db_prefix().'timesheets_timesheet';
        if (!$this->db->table_exists($table)) {
            $table = db_prefix().'check_in_out';
        }
        if (!$this->db->table_exists($table)) {
            return ['rows' => [], 'summary' => ['label' => 'Records', 'value' => '0', 'color' => '#6b7280']];
        }

        $this->db->select('s.firstname, s.lastname, t.date_work, t.type_check');
        $this->db->from($table.' t');
        $this->db->join(db_prefix().'staff s', 's.staffid = t.staff_id', 'left');
        if ($this->db->field_exists('date_work', $table)) {
            $this->db->where('t.date_work >=', $from);
            $this->db->where('t.date_work <=', $to);
        }
        if ($staffId) {
            $this->db->where('t.staff_id', $staffId);
        }
        $this->db->order_by('t.date_work', 'DESC');
        $this->db->limit(200);
        $rows = [];
        foreach ($this->db->get()->result_array() as $r) {
            $name = trim(($r['firstname'] ?? '').' '.($r['lastname'] ?? ''));
            $rows[] = [
                'title'    => $name ?: 'Staff',
                'subtitle' => $r['date_work'] ?? '',
                'badge'    => !empty($r['type_check']) ? 'Present' : '—',
                'amount'   => '',
                'color'    => '#2563eb',
            ];
        }

        return ['rows' => $rows, 'summary' => ['label' => 'Records', 'value' => (string) count($rows), 'color' => '#2563eb']];
    }

    private function buildHrReport($from, $to, $mode)
    {
        $this->db->select('s.staffid, s.firstname, s.lastname, s.email');
        $this->db->from(db_prefix().'staff s');
        $this->db->where('s.active', 1);
        $this->db->limit(100);
        $rows = [];
        foreach ($this->db->get()->result_array() as $r) {
            $name = trim(($r['firstname'] ?? '').' '.($r['lastname'] ?? ''));
            $rows[] = [
                'title'    => $name,
                'subtitle' => $r['email'] ?? '',
                'amount'   => $mode === 'salary_report' ? '—' : '',
                'badge'    => ucfirst(str_replace('_report', '', $mode)),
                'color'    => '#6366f1',
            ];
        }

        return [
            'rows'    => $rows,
            'summary' => [
                'label' => 'Employees',
                'value' => (string) count($rows),
                'color' => '#6366f1',
            ],
        ];
    }

    private function accountingModuleActive()
    {
        if (!$this->db->table_exists(db_prefix().'acc_accounts')) {
            return false;
        }

        $this->load->library('app_modules');

        return $this->app_modules->is_active('accounting');
    }

    private function accountingFilter($from, $to)
    {
        return [
            'from_date' => _d($from),
            'to_date'   => _d($to),
        ];
    }

    private function hubFromAccountingSections(array $data, $reportType)
    {
        $sections = [];
        $incomeTotal = 0;
        $expenseTotal = 0;
        $assetsTotal = 0;
        $liabilitiesTotal = 0;
        $operatingTotal = 0;

        foreach ($data as $sectionKey => $items) {
            if (!is_array($items)) {
                continue;
            }

            $rows = [];
            $sectionTotal = 0;
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $amount = (float) ($item['amount'] ?? $item['this_year'] ?? 0);
                if ($amount == 0.0 && empty($item['name'])) {
                    continue;
                }
                $sectionTotal += $amount;
                $rows[] = [
                    'title'    => $item['name'] ?? 'Account',
                    'subtitle' => ucwords(str_replace('_', ' ', (string) $sectionKey)),
                    'amount'   => $this->fmt($amount),
                    'color'    => $amount >= 0 ? '#2563eb' : '#dc2626',
                ];
            }

            if (empty($rows)) {
                continue;
            }

            $sections[] = [
                'title' => ucwords(str_replace('_', ' ', (string) $sectionKey)),
                'rows'  => $rows,
            ];

            $key = strtolower((string) $sectionKey);
            if (strpos($key, 'income') !== false || strpos($key, 'revenue') !== false) {
                $incomeTotal += $sectionTotal;
            } elseif (strpos($key, 'expense') !== false || strpos($key, 'cost') !== false) {
                $expenseTotal += $sectionTotal;
            } elseif (strpos($key, 'asset') !== false || strpos($key, 'cash') !== false || strpos($key, 'receivable') !== false) {
                $assetsTotal += $sectionTotal;
            } elseif (strpos($key, 'liabil') !== false || strpos($key, 'equity') !== false || strpos($key, 'payable') !== false) {
                $liabilitiesTotal += $sectionTotal;
            } else {
                $operatingTotal += $sectionTotal;
            }
        }

        $payload = [
            'layout'  => 'sections',
            'source'  => 'accounting',
            'sections'=> $sections,
        ];

        if ($reportType === 'profit_loss') {
            $net = $incomeTotal - $expenseTotal;
            $payload['metrics'] = [
                ['label' => 'Net Profit', 'value' => $this->fmt($net), 'color' => $net >= 0 ? '#16a34a' : '#dc2626'],
            ];
            $payload['summary'] = [
                'label' => 'Net Profit',
                'value' => $this->fmt($net),
                'color' => $net >= 0 ? '#16a34a' : '#dc2626',
            ];
        } elseif ($reportType === 'balance_sheet') {
            $payload['summary'] = [
                'label' => 'Total Assets',
                'value' => $this->fmt($assetsTotal),
                'color' => '#2563eb',
            ];
        } else {
            $net = $operatingTotal !== 0.0 ? $operatingTotal : ($incomeTotal - $expenseTotal);
            $payload['summary'] = [
                'label' => 'Net Cash Flow',
                'value' => $this->fmt($net),
                'color' => $net >= 0 ? '#16a34a' : '#dc2626',
            ];
        }

        return $payload;
    }

    private function hubFromAccountingTrialBalance(array $data)
    {
        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($data as $sectionKey => $items) {
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $debit  = (float) ($item['debit'] ?? 0);
                $credit = (float) ($item['credit'] ?? 0);
                if ($debit == 0.0 && $credit == 0.0) {
                    continue;
                }
                $totalDebit += $debit;
                $totalCredit += $credit;
                $rows[] = [
                    'title'    => $item['name'] ?? 'Account',
                    'subtitle' => ucwords(str_replace('_', ' ', (string) $sectionKey)),
                    'amount'   => $this->fmt($debit > 0 ? $debit : $credit),
                    'badge'    => $debit > 0 ? 'Dr' : 'Cr',
                    'color'    => $debit > 0 ? '#2563eb' : '#16a34a',
                ];
            }
        }

        return [
            'source'  => 'accounting',
            'rows'    => array_slice($rows, 0, 200),
            'summary' => [
                'label' => 'Debit Total',
                'value' => $this->fmt($totalDebit),
                'color' => '#111827',
            ],
            'metrics' => [
                ['label' => 'Debit', 'value' => $this->fmt($totalDebit), 'color' => '#2563eb'],
                ['label' => 'Credit', 'value' => $this->fmt($totalCredit), 'color' => '#16a34a'],
            ],
        ];
    }
}
