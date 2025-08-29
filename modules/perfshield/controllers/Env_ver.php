<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Env_ver extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        show_404();
    }

    public function activate()
    {
        $res = modules\perfshield\core\Apiinit::pre_validate($this->input->post('module_name'), $this->input->post('purchase_key'));
        if ($res['status']) {
            $res['original_url'] = $this->input->post('original_url');
        }
        echo json_encode($res);
    }

    public function upgrade_database()
    {
        $res = modules\perfshield\core\Apiinit::pre_validate($this->input->post('module_name'), $this->input->post('purchase_key'));
        if ($res['status']) {
            $res['original_url'] = $this->input->post('original_url');
        }
        echo json_encode($res);
    }
}
