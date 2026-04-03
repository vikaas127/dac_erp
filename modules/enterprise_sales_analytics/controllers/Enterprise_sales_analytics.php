<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Enterprise_sales_analytics extends AdminController
{

public function __construct()
{
parent::__construct();
$this->load->model('enterprise_sales_analytics_model');
}


/* ------------------------------------------
EXECUTIVE OVERVIEW
------------------------------------------- */

public function executive_overview()
{

$data['title'] = 'Sales Intelligence Dashboard';

$data['total_leads'] =
$this->enterprise_sales_analytics_model->total_leads();

$data['pipeline'] =
$this->enterprise_sales_analytics_model->pipeline_value();

$data['revenue'] =
$this->enterprise_sales_analytics_model->revenue();

$data['conversion_rate'] =
$this->enterprise_sales_analytics_model->conversion_rate();

$data['monthly_revenue'] =
$this->enterprise_sales_analytics_model->monthly_revenue();

$data['funnel'] =
$this->enterprise_sales_analytics_model->sales_funnel();

$this->load->view(
'enterprise_sales_analytics/executive_overview',
$data
);

}


/* ------------------------------------------
LEADS ANALYTICS
------------------------------------------- */

public function leads_dashboard()
{

$data['title'] = 'Leads Analytics';
$data['monthly_status'] =
$this->enterprise_sales_analytics_model->monthly_status_report();
$data['sources'] =
$this->enterprise_sales_analytics_model->lead_sources();

$data['aging'] =
$this->enterprise_sales_analytics_model->lead_aging();

$data['funnel'] =
$this->enterprise_sales_analytics_model->sales_funnel();

$this->load->view(
'enterprise_sales_analytics/leads_dashboard',
$data
);

}


/* ------------------------------------------
SALES ANALYTICS
------------------------------------------- */

public function sales_dashboard()
{
    $data['title'] = 'Sales Analytics';

    // ✅ FILTER
    $filter_type = $this->input->get('filter_type');
    $from = $this->input->get('from');
    $to = $this->input->get('to');

    // ✅ PIPELINE
    $data['pipeline_list'] =
        $this->enterprise_sales_analytics_model->pipeline();

    // ✅ PRODUCT SALES (REAL / INVOICE BASED)
    $data['products'] =
        $this->enterprise_sales_analytics_model
        ->product_sales($filter_type, $from, $to);

    // ✅ CUSTOMER SALES (🔥 NEW)
    $data['customers'] =
        $this->enterprise_sales_analytics_model
        ->customer_sales($filter_type, $from, $to);

    // ✅ REGION SALES
    $data['regions'] =
        $this->enterprise_sales_analytics_model->region_sales();

    // ✅ FORECAST
    $data['forecast'] =
        $this->enterprise_sales_analytics_model->sales_forecast();

    // ✅ MONTHLY REVENUE (WITH FILTER)
    $data['monthly_revenue'] =
        $this->enterprise_sales_analytics_model
        ->monthly_revenue($filter_type, $from, $to);

    $this->load->view(
        'enterprise_sales_analytics/sales_dashboard',
        $data
    );
}

/* ------------------------------------------
STAFF PERFORMANCE
------------------------------------------- */

public function staff_dashboard()
{
    $data['title'] = 'Staff Performance';

    $filter_type = $this->input->get('filter_type');
    $from = $this->input->get('from');
    $to = $this->input->get('to');

    // // ✅ TEAM DATA
    // $result = $this->enterprise_sales_analytics_model
    //     ->sales_team_dynamic($filter_type, $from, $to);

    // $data['team'] = $result['team'];
    // $data['statuses'] = $result['statuses'];
    $this->load->model('staff_model');
    $data['members']           = $this->staff_model->get('', ['active' => 1]);
    $data['statuses'] = $this->db
    ->order_by('statusorder','ASC')
    ->get(db_prefix().'leads_status')
    ->result_array();

    // ✅ KPI DATA (🔥 IMPORTANT)
    $kpi = $this->enterprise_sales_analytics_model
        ->staff_kpis($filter_type, $from, $to);

    $data['total_staff_leads'] = $kpi['total_leads'];
    $data['deals_won'] = $kpi['won'];
    $data['staff_revenue'] = $kpi['revenue'];
    $data['staff_conversion'] = $kpi['conversion'];

    // ✅ CHART
    $data['staff_status'] =
        $this->enterprise_sales_analytics_model
        ->staff_monthly_status();

    $this->load->view(
        'enterprise_sales_analytics/staff_dashboard',
        $data
    );
}

public function staff_table()
{
    $this->app->get_table_data(module_views_path('enterprise_sales_analytics', 'tables/staff_performance'));
}

public function lead_first_estimate_table()
{
    $this->app->get_table_data(
        module_views_path('enterprise_sales_analytics', 'tables/lead_first_estimate_table')
    );
}

}