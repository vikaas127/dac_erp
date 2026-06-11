<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';

use FlutexAdminApi\RestController;

class Ledger extends RestController
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

    public function list_get()
    {
        try {
            $data = $this->accounting_api_model->getLedgerList([
                'group'     => $this->get('group'),
                'search'    => $this->get('search'),
                'from_date' => $this->get('from_date'),
                'to_date'   => $this->get('to_date'),
            ]);

            return $this->response([
                'status'  => true,
                'success' => true,
                'message' => 'Ledger list fetched successfully',
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to load ledger list',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function summary_get()
    {
        try {
            $data = $this->accounting_api_model->getLedgerSummary();

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to load ledger summary',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function details_get($id = 0)
    {
        try {
            $id = (int) ($id ?: $this->get('id'));
            if ($id <= 0) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Ledger id is required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $data = $this->accounting_api_model->getLedgerDetails(
                $id,
                $this->get('from_date'),
                $this->get('to_date')
            );

            if ($data === null) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Ledger account not found',
                ], RestController::HTTP_NOT_FOUND);
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to load ledger details',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function create_post()
    {
        try {
            $body = json_decode($this->input->raw_input_stream, true) ?: $this->post() ?: [];
            $result = $this->accounting_api_model->createLedger($body);

            if (!$result['success']) {
                return $this->response([
                    'status'  => false,
                    'message' => $result['message'] ?? 'Failed to create ledger',
                ], RestController::HTTP_BAD_REQUEST);
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $result,
            ], RestController::HTTP_CREATED);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to create ledger',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function update_post($id = 0)
    {
        try {
            $id = (int) ($id ?: $this->post('id'));
            if ($id <= 0) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Ledger id is required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $body = json_decode($this->input->raw_input_stream, true) ?: $this->post() ?: [];
            $result = $this->accounting_api_model->updateLedger($id, $body);

            if (!$result['success']) {
                return $this->response([
                    'status'  => false,
                    'message' => $result['message'] ?? 'Failed to update ledger',
                ], RestController::HTTP_BAD_REQUEST);
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $result,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to update ledger',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function delete_post($id = 0)
    {
        try {
            $id = (int) ($id ?: $this->post('id'));
            if ($id <= 0) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Ledger id is required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $result = $this->accounting_api_model->deleteLedger($id);

            if (!$result['success']) {
                return $this->response([
                    'status'  => false,
                    'message' => $result['message'] ?? 'Failed to delete ledger',
                ], RestController::HTTP_BAD_REQUEST);
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $result,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to delete ledger',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }
}
