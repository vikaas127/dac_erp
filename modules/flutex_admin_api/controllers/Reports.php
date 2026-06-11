<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';

use FlutexAdminApi\RestController;

class Reports extends RestController
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
        $this->load->model('flutex_admin_api/reports_model');
    }

    /**
     * GET flutex_admin_api/reports — catalog of all report tiles.
     */
    public function index_get()
    {
        try {
            $tab = $this->get('tab');
            $catalog = $this->reports_model->getCatalog();

            if ($tab) {
                $catalog = array_values(array_filter($catalog, function ($item) use ($tab) {
                    return strcasecmp($item['tab'], $tab) === 0;
                }));
            }

            $grouped = [];
            foreach ($catalog as $item) {
                $grouped[$item['tab']][] = [
                    'id'      => $item['id'],
                    'title'   => $item['title'],
                    'tab'     => $item['tab'],
                    'layout'  => $item['layout'],
                    'columns' => $item['columns'],
                ];
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => [
                    'reports'  => $catalog,
                    'grouped'  => $grouped,
                    'tabs'     => array_keys($grouped),
                ],
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to load reports catalog',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * GET flutex_admin_api/reports/{report_id}?from_date=&to_date=&period=month&filter=
     */
    public function show_get($report_id = '')
    {
        try {
            $report_id = trim($report_id ?: (string) $this->get('report_id'));
            if ($report_id === '') {
                return $this->response([
                    'status'  => false,
                    'message' => 'report_id is required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $params = [
                'from_date' => $this->get('from_date'),
                'to_date'   => $this->get('to_date'),
                'period'    => $this->get('period') ?: 'month',
                'filter'    => $this->get('filter') ?: 'All',
                'staff_id'  => $this->get('staff_id'),
            ];

            $data = $this->reports_model->build($report_id, $params);
            if ($data === null) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Unknown report: '.$report_id,
                ], RestController::HTTP_NOT_FOUND);
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', '[Reports][show] '.$th->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Failed to load report',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }
}
