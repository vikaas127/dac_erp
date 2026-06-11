<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';

use FlutexAdminApi\RestController;

class Accounting extends RestController
{
    protected $staffInfo;

    public function __construct()
    {
        parent::__construct();

        register_language_files('flutex_admin_api');
        load_admin_language();
        $this->load->helper('flutex_admin_api');
        $this->load->helper('admin');

        $auth = isAuthorized();
        if (!isset($auth['status'])) {
            $this->response($auth['response'], $auth['response_code']);
        }

        $this->staffInfo = $auth;
        $this->load->model('flutex_admin_api/accounting_api_model');
    }

    public function profit_loss_get()
    {
        try {
            $data = $this->accounting_api_model->getProfitLoss(
                $this->get('from_date'),
                $this->get('to_date')
            );

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', '[Accounting][profit_loss] '.$th->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Failed to load profit & loss',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function balance_sheet_get()
    {
        try {
            $data = $this->accounting_api_model->getBalanceSheet(
                $this->get('from_date'),
                $this->get('to_date')
            );

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', '[Accounting][balance_sheet] '.$th->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Failed to load balance sheet',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function cash_flow_get()
    {
        try {
            $data = $this->accounting_api_model->getCashFlow(
                $this->get('from_date'),
                $this->get('to_date')
            );

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', '[Accounting][cash_flow] '.$th->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Failed to load cash flow',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function receivables_get()
    {
        try {
            $aging = $this->get('aging');
            $agingEnabled = $aging === null || $aging === '' || filter_var($aging, FILTER_VALIDATE_BOOLEAN);

            $data = $this->accounting_api_model->getReceivables(
                $agingEnabled,
                $this->get('from_date'),
                $this->get('to_date')
            );

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', '[Accounting][receivables] '.$th->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Failed to load receivables',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function payables_get()
    {
        try {
            $aging = $this->get('aging');
            $agingEnabled = $aging === null || $aging === '' || filter_var($aging, FILTER_VALIDATE_BOOLEAN);

            $data = $this->accounting_api_model->getPayables(
                $agingEnabled,
                $this->get('from_date'),
                $this->get('to_date')
            );

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', '[Accounting][payables] '.$th->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Failed to load payables',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function ledger_report_get()
    {
        try {
            $data = $this->accounting_api_model->getLedgerReport(
                $this->get('type'),
                $this->get('from_date'),
                $this->get('to_date')
            );

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', '[Accounting][ledger_report] '.$th->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Failed to load ledger report',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function trial_balance_get()
    {
        try {
            $this->load->model('flutex_admin_api/reports_model');
            [$from, $to] = $this->accounting_api_model->resolveDateRange(
                $this->get('from_date'),
                $this->get('to_date')
            );

            $hub = $this->reports_model->build('trial_balance', [
                'from_date' => $from,
                'to_date'   => $to,
            ]);

            return $this->response([
                'status'    => true,
                'success'   => true,
                'from_date' => $from,
                'to_date'   => $to,
                'data'      => $hub,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to load trial balance',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }
}
