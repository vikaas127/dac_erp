<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Vouchers extends App_Controller
{
    private $staffId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('flutex_admin_api');
        $auth = isAuthorized();
        if (!isset($auth['status'])) {
            header('Content-Type: application/json');
            http_response_code($auth['response_code'] ?? 401);
            echo json_encode($auth['response']);
            exit;
        }

        $this->staffId = (int) ($auth['data']->staff_id ?? 0);
        $this->load->model('flutex_admin_api/vouchers_api_model');

        /// =====================================================
        /// EXISTING PERFEX MODELS
        /// =====================================================

        $this->load->model('estimates_model');

        $this->load->model('invoices_model');

        $this->load->model('credit_notes_model');

        $this->load->model('expenses_model');

        $this->load->model('payments_model');
    }

    /// =========================================================
    /// INDEX
    /// =========================================================
/// =========================================================
/// FINANCIAL YEAR
/// =========================================================

private function getFinancialYearDates()
{
    $currentMonth = date('n');

    $currentYear = date('Y');

    if ($currentMonth >= 4) {

        $fromDate = $currentYear . '-04-01';

        $toDate = ($currentYear + 1) . '-03-31';
    }

    else {

        $fromDate = ($currentYear - 1) . '-04-01';

        $toDate = $currentYear . '-03-31';
    }

    return [

        'from_date' => $fromDate,

        'to_date' => $toDate,
    ];
}
    public function index()
    {
        try {
$financialYear = $this->getFinancialYearDates();

$fromDate = $this->input->get('from_date');

$toDate = $this->input->get('to_date');

/// =====================================================
/// DEFAULT CURRENT FINANCIAL YEAR
/// =====================================================

if (empty($fromDate)) {

    $fromDate = $financialYear['from_date'];
}

if (empty($toDate)) {

    $toDate = $financialYear['to_date'];
}

log_message(
    'error',
    'FROM DATE => ' . $fromDate
);

log_message(
    'error',
    'TO DATE => ' . $toDate
);
            $type = $this->input->get('type');

            log_message(
                'error',
                'Voucher Type => ' . $type
            );

            $data = [];

            switch ($type)
            {
                case 'Estimate':
                 $this->db->where(
    'date >=',
    $fromDate
);

$this->db->where(
    'date <=',
    $toDate
);
                    $data = $this->estimates_model
                        ->get();

                    break;

                case 'Sales':

                    $this->db->where(
    'date >=',
    $fromDate
);

$this->db->where(
    'date <=',
    $toDate
);

$data = $this->invoices_model->get();

                    break;

                case 'Credit Note':

                    $data = $this->credit_notes_model
                        ->get();

                    break;

               case 'Payment':

    $this->db->select('

        ' . db_prefix() . 'invoicepaymentrecords.id,

        ' . db_prefix() . 'invoicepaymentrecords.amount,

        ' . db_prefix() . 'invoicepaymentrecords.date,

        ' . db_prefix() . 'invoicepaymentrecords.transactionid,

        ' . db_prefix() . 'invoices.number,

        ' . db_prefix() . 'invoices.prefix,

        ' . db_prefix() . 'clients.company
    ');

    $this->db->from(
        db_prefix() . 'invoicepaymentrecords'
    );

    $this->db->join(

        db_prefix() . 'invoices',

        db_prefix() . 'invoices.id =
        ' . db_prefix() . 'invoicepaymentrecords.invoiceid',

        'left'
    );

    $this->db->join(

        db_prefix() . 'clients',

        db_prefix() . 'clients.userid =
        ' . db_prefix() . 'invoices.clientid',

        'left'
    );

    $this->db->order_by(

        db_prefix() . 'invoicepaymentrecords.id',

        'DESC'
    );

    $data = $this->db
        ->get()
        ->result_array();

    break;

                    break;

                case 'Expense':

                    $data = $this->expenses_model
                        ->get();

                    break;
case 'PO':

    log_message(
        'error',
        'Loading Purchase Orders'
    );

    $this->db->select('

        ' . db_prefix() . 'pur_orders.*,

        ' . db_prefix() . 'pur_vendor.company as vendor_name
    ');

    $this->db->from(
        db_prefix() . 'pur_orders'
    );

    $this->db->join(

        db_prefix() . 'pur_vendor',

        db_prefix() . 'pur_vendor.userid =
        ' . db_prefix() . 'pur_orders.vendor',

        'left'
    );

    $this->db->order_by(

        db_prefix() . 'pur_orders.id',

        'DESC'
    );

    $data = $this->db
        ->get()
        ->result_array();

    break;
                default:

                    $data = [];
            }

            echo json_encode([

                'status' => true,

                'type' => $type,

                'count' => count($data),

                'data' => $data
            ]);
        }

        catch (Throwable $e) {

            log_message(

                'error',

                'VOUCHERS API ERROR => '
                . $e->getMessage()
            );

            echo json_encode([

                'status' => false,

                'message' => $e->getMessage()
            ]);
        }
    }

    /// =========================================================
    /// STATS
    /// =========================================================

 public function stats()
{
    try {

        /// =====================================================
        /// FINANCIAL YEAR
        /// =====================================================

        $financialYear =
            $this->getFinancialYearDates();

        $fromDate =
            $financialYear['from_date'];

        $toDate =
            $financialYear['to_date'];

        /// =====================================================
        /// SALES TOTAL
        /// =====================================================

        $this->db->select_sum('total');

        $this->db->where(
            'date >=',
            $fromDate
        );

        $this->db->where(
            'date <=',
            $toDate
        );

        $sales = $this->db
            ->get(db_prefix() . 'invoices')
            ->row();

        $salesAmount =
            (float) ($sales->total ?? 0);

        /// =====================================================
        /// PAYMENT TOTAL
        /// =====================================================

        $this->db->select_sum('amount');

        $this->db->where(
            'date >=',
            $fromDate
        );

        $this->db->where(
            'date <=',
            $toDate
        );

        $payment = $this->db
            ->get(db_prefix() . 'invoicepaymentrecords')
            ->row();

        $paymentAmount =
            (float) ($payment->amount ?? 0);

        /// =====================================================
        /// EXPENSE TOTAL
        /// =====================================================

        $this->db->select_sum('amount');

        $this->db->where(
            'date >=',
            $fromDate
        );

        $this->db->where(
            'date <=',
            $toDate
        );

        $expense = $this->db
            ->get(db_prefix() . 'expenses')
            ->row();

        $expenseAmount =
            (float) ($expense->amount ?? 0);

        /// =====================================================
        /// GROSS
        /// =====================================================

        $grossAmount =
            $salesAmount - $expenseAmount;

        log_message(
            'error',
            'SALES => ' . $salesAmount
        );

        log_message(
            'error',
            'PAYMENT => ' . $paymentAmount
        );

        log_message(
            'error',
            'EXPENSE => ' . $expenseAmount
        );

        log_message(
            'error',
            'GROSS => ' . $grossAmount
        );

        echo json_encode([

            'status' => true,

            'financial_year' => [

                'from_date' => $fromDate,

                'to_date' => $toDate,
            ],

            'gross' => round(
                $grossAmount,
                2
            ),

            'sales' => round(
                $salesAmount,
                2
            ),

            'purchase' => 0,

            'receipt' => 0,

            'payment' => round(
                $paymentAmount,
                2
            ),

            'expense' => round(
                $expenseAmount,
                2
            ),
        ]);
    }

    catch (Throwable $e) {

        log_message(
            'error',
            'STATS ERROR => ' .
            $e->getMessage()
        );

        log_message(
            'error',
            'TRACE => ' .
            $e->getTraceAsString()
        );

        echo json_encode([

            'status' => false,

            'message' => $e->getMessage()
        ]);
    }
}
    /// =========================================================
    /// MONTH SUMMARY
    /// =========================================================

    public function month_summary()
{
    try {

        $response = [];

        for ($i = 4; $i <= 12; $i++)
        {
            $month =
                date('M', mktime(0, 0, 0, $i, 1));

            $this->db->select_sum('total');

            $this->db->where(
                'MONTH(date)',
                $i
            );

            $invoice = $this->db
                ->get(db_prefix() . 'invoices')
                ->row();

            $response[] = [

                'month' => $month,

                'amount' =>
                    number_format(
                        $invoice->total ?? 0,
                        2
                    )
            ];
        }

        for ($i = 1; $i <= 3; $i++)
        {
            $month =
                date('M', mktime(0, 0, 0, $i, 1));

            $this->db->select_sum('total');

            $this->db->where(
                'MONTH(date)',
                $i
            );

            $invoice = $this->db
                ->get(db_prefix() . 'invoices')
                ->row();

            $response[] = [

                'month' => $month,

                'amount' =>
                    number_format(
                        $invoice->total ?? 0,
                        2
                    )
            ];
        }

        echo json_encode([

            'status' => true,

            'data' => $response
        ]);
    }

    catch (Throwable $e) {

        echo json_encode([

            'status' => false,

            'message' => $e->getMessage()
        ]);
    }
}

    /// =========================================================
    /// LIST / LEDGER / INVENTORY / CRUD
    /// =========================================================

    public function list()
    {
        $this->index();
    }

    public function ledger()
    {
        try {
            [$from, $to] = $this->vouchers_api_model->resolveDates(
                $this->input->get('from_date'),
                $this->input->get('to_date')
            );

            $data = $this->vouchers_api_model->fetchLedger($from, $to);

            echo json_encode([
                'status'    => true,
                'success'   => true,
                'from_date' => $from,
                'to_date'   => $to,
                'type'      => 'Journal',
                'count'     => count($data),
                'data'      => $data,
            ]);
        } catch (Throwable $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function inventory()
    {
        try {
            [$from, $to] = $this->vouchers_api_model->resolveDates(
                $this->input->get('from_date'),
                $this->input->get('to_date')
            );
            $data = $this->vouchers_api_model->fetchInventory($from, $to);

            echo json_encode([
                'status'    => true,
                'success'   => true,
                'from_date' => $from,
                'to_date'   => $to,
                'count'     => count($data),
                'data'      => $data,
            ]);
        } catch (Throwable $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function id($id = 0)
    {
        $this->respondVoucher((int) $id, false);
    }

    public function details($id = 0)
    {
        $this->respondVoucher((int) $id, true);
    }

    public function create()
    {
        try {
            $body = json_decode($this->input->raw_input_stream, true) ?: $this->input->post() ?: [];
            $type = $body['type'] ?? $this->input->get('type') ?? 'Journal';

            if (strcasecmp($type, 'Journal') !== 0) {
                echo json_encode([
                    'status'  => false,
                    'message' => 'Only Journal vouchers can be created via API. Use native modules for Sales/Expense.',
                ]);

                return;
            }

            $result = $this->vouchers_api_model->createJournal($body, $this->staffId);
            echo json_encode(array_merge(['status' => $result['success']], $result));
        } catch (Throwable $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update($id = 0)
    {
        try {
            $body = json_decode($this->input->raw_input_stream, true) ?: $this->input->post() ?: [];
            $type = $body['type'] ?? $this->input->get('type') ?? 'Journal';

            if (strcasecmp($type, 'Journal') !== 0) {
                echo json_encode([
                    'status'  => false,
                    'message' => 'Only Journal vouchers can be updated via API.',
                ]);

                return;
            }

            $result = $this->vouchers_api_model->updateJournal((int) $id, $body);
            echo json_encode(array_merge(['status' => $result['success']], $result));
        } catch (Throwable $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delete($id = 0)
    {
        try {
            $type = $this->input->get('type') ?: 'Journal';
            if (strcasecmp($type, 'Journal') !== 0) {
                echo json_encode([
                    'status'  => false,
                    'message' => 'Only Journal vouchers can be deleted via API.',
                ]);

                return;
            }

            $result = $this->vouchers_api_model->deleteJournal((int) $id);
            echo json_encode(array_merge(['status' => $result['success']], $result));
        } catch (Throwable $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    private function respondVoucher($id, $withDetails)
    {
        try {
            $type = $this->input->get('type');
            if (!$type || $id <= 0) {
                echo json_encode([
                    'status'  => false,
                    'message' => 'Query param type and voucher id are required',
                ]);

                return;
            }

            if ($withDetails) {
                $data = $this->vouchers_api_model->fetchDetails($type, $id);
            } else {
                $data = $this->vouchers_api_model->fetchByType($type, '1970-01-01', '2099-12-31', $id);
            }

            if ($data === null || $data === []) {
                echo json_encode(['status' => false, 'message' => 'Voucher not found']);

                return;
            }

            echo json_encode([
                'status'  => true,
                'success' => true,
                'type'    => $type,
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /// =========================================================
    /// PROFIT & LOSS / BALANCE SHEET / TRIAL BALANCE
    /// =========================================================

    public function profit_loss()
    {
        $this->respondAccountingReport('getProfitLoss');
    }

    public function balance_sheet()
    {
        $this->respondAccountingReport('getBalanceSheet');
    }

    public function trial_balance()
    {
        $this->load->model('flutex_admin_api/accounting_api_model');
        $this->load->model('flutex_admin_api/reports_model');

        try {
            [$from, $to] = $this->accounting_api_model->resolveDateRange(
                $this->input->get('from_date'),
                $this->input->get('to_date')
            );

            $hub = $this->reports_model->build('trial_balance', [
                'from_date' => $from,
                'to_date'   => $to,
            ]);

            echo json_encode([
                'status'    => true,
                'from_date' => $from,
                'to_date'   => $to,
                'data'      => $hub,
            ]);
        } catch (Throwable $e) {
            echo json_encode([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function respondAccountingReport($method)
    {
        $this->load->model('flutex_admin_api/accounting_api_model');

        try {
            $data = $this->accounting_api_model->{$method}(
                $this->input->get('from_date'),
                $this->input->get('to_date')
            );

            echo json_encode([
                'status' => true,
                'data'   => $data,
            ]);
        } catch (Throwable $e) {
            echo json_encode([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}