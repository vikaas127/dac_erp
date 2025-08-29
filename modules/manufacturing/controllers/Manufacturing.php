<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Class Manufacturing
 */
class Manufacturing extends AdminController
// deepanshu madrchod gaandu ne code uda diya 

{
	/**
	 * __construct
	 */
	public function __construct()
	{
	  parent::__construct();
	  $this->load->model('manufacturing_model');
	  hooks()->do_action('manufacturing_init');

	}
	public function get_products_by_estimate($estimate_id)
	{
	
    log_message('info', 'API called to fetch estimate items for estimate_id: ' . $estimate_id);

    if (!is_numeric($estimate_id)) {
        log_message('error', 'Invalid estimate_id provided: ' . print_r($estimate_id, true));
        
        header('Content-Type: application/json');
        echo json_encode([]); // PHP array → JSON
        return;
    }

    log_message('info', 'Valid estimate_id. Fetching items...');
    
     $items = get_items_by_type('estimate', $estimate_id); // This is a PHP array

     log_message('info', 'Fetched items: ' . json_encode($items));


     header('Content-Type: application/json');
     echo json_encode($items); // Convert PHP array to JSON


	}
		public function bom_costing() {
		// Ensure it's an AJAX request
		if (!$this->input->is_ajax_request()) {
			show_error('No direct script access allowed');
		}
	
		$bill_of_material_id = $this->input->post('bill_of_material_id');
	
		if (!$bill_of_material_id) {
			log_message('error', 'Missing BOM ID');
			echo json_encode(['status' => 'error', 'message' => 'Invalid Work Order']);
			return;
		}
	
		$updateData = [
			'labour_charges' => $this->input->post('labour_charges'),
			'electricity_charges' => $this->input->post('electricity_charges'),
			'machinery_charges' => $this->input->post('machinery_charges'),
			'other_charges' => $this->input->post('other_charges'),
			'labour_charges_description' => $this->input->post('labour_charges_description'),
			'electricity_charges_description' => $this->input->post('electricity_charges_description'),
			'machinery_charges_description' => $this->input->post('machinery_charges_description'),
			'other_charges_description' => $this->input->post('other_charges_description'),
		];
	
		log_message('debug', 'Received POST data: ' . json_encode($updateData));
	
		// Validate that fields are not empty (optional check)
		foreach ($updateData as $key => $value) {
			if ($value === null || $value === '') {
				log_message('warning', "Field '$key' is empty or NULL.");
			}
		}
	
	
	
		// Update the database
		$this->db->where('id', $bill_of_material_id);
		$this->db->update(db_prefix().'mrp_bill_of_materials', $updateData);
	
		// Debugging database update failure
		if ($this->db->affected_rows() > 0) {
			log_message('info', 'BOM ID '.$bill_of_material_id.' updated successfully.');
			echo json_encode(['status' => 'success', 'message' => 'BOM Updated Successfully']);
		} else {
			log_message('error', 'Database update failed for BOM '.$bill_of_material_id);
			echo json_encode(['status' => 'error', 'message' => 'No changes made or update failed']);
		}
	}
	
public function delete_manufacturing_permission($id) {
		if (!is_admin()) {
			access_denied('Manufacturing');
		}
          $this->load->model('Manufacturing_model');
		$response = $this->Manufacturing_model->delete_Manufacturing_permission($id);

		if (is_array($response) && isset($response['referenced'])) {
			set_alert('warning', _l('hr_is_referenced', _l('department_lowercase')));
		} elseif ($response == true) {
			set_alert('success', _l('deleted', _l('hr_department')));
		} else {
			set_alert('warning', _l('problem_deleting', _l('department_lowercase')));
		}
		redirect(admin_url('manufacturing/setting?group=mf_permission'));

	}
public function manufacturing_permission_table() {
		if ($this->input->is_ajax_request()) {

			$select = [
				'staffid',
				'CONCAT(firstname," ",lastname) as full_name',
				'firstname', //for role name
				'email',
				'phonenumber',
			];
			$where = [];
			$where[] = 'AND ' . db_prefix() . 'staff.admin != 1';
			

			$arr_staff_id = manufacturing_get_staff_id_manufacturing_permissions();


			if (count($arr_staff_id) > 0) {
				$where[] = 'AND ' . db_prefix() . 'staff.staffid IN (' . implode(', ', $arr_staff_id) . ')';
			} else {
				$where[] = 'AND ' . db_prefix() . 'staff.staffid IN ("")';
			}

			$aColumns = $select;
			$sIndexColumn = 'staffid';
			$sTable = db_prefix() . 'staff';
			$join = ['LEFT JOIN ' . db_prefix() . 'roles ON ' . db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role'];

			$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'roles.name as role_name', db_prefix() . 'staff.role']);

			$output = $result['output'];
			$rResult = $result['rResult'];

			$not_hide = '';

			foreach ($rResult as $aRow) {
				$row = [];

				$row[] = '<a href="' . admin_url('staff/member/' . $aRow['staffid']) . '">' . $aRow['full_name'] . '</a>';

				$row[] = $aRow['role_name'];
				$row[] = $aRow['email'];
				$row[] = $aRow['phonenumber'];

				$options = '';

				if (has_permission('mrp_settings', '', 'edit')) {
					$options = icon_btn('#', 'fa-regular fa-pen-to-square', 'btn-default', [
						'title' => _l('hr_edit'),
						'onclick' => 'manufacturing_permissions_update(' . $aRow['staffid'] . ', ' . $aRow['role'] . ', ' . $not_hide . '); return false;',
					]);
				}

				if (has_permission('mrp_settings', '', 'delete')) {
					$options .= icon_btn('manufacturing/delete_manufacturing_permission/' . $aRow['staffid'], 'fa fa-remove', 'btn-danger _delete', ['title' => _l('delete')]);
				}

				$row[] = $options;

				$output['aaData'][] = $row;
			}

			echo json_encode($output);
			die();
		}
	}

	/**
	 * permission modal
	 * @return [type]
	 */


	/**
	 * hr profile update permissions
	 * @param  string $id
	 * @return [type]
	 */
	
public function permission_modal() {
    if (!$this->input->is_ajax_request()) {
        log_message('error', 'Permission modal accessed without AJAX request.');
        show_404();
    }

    $this->load->model('staff_model');

    log_message('info', 'Permission modal function triggered.');

    if ($this->input->post('slug') === 'update') {
        $staff_id = $this->input->post('staff_id');
        $role_id = $this->input->post('role_id');

        log_message('info', 'Updating permission modal.', [
            'staff_id' => 5,
            'role_id' => $role_id
        ]);

        $data = ['funcData' => ['staff_id' => isset($staff_id) ? $staff_id : null]];

        if (isset($staff_id)) {
            $data['member'] = $this->staff_model->get($staff_id);
            log_message('debug', 'Fetched staff member data.', $data['member']);
        }

        $data['roles_value'] = $this->roles_model->get();
        log_message('debug', 'Fetched role values.', $data['roles_value']);

        $data['staffs'] = manufacturing_get_staff_id_dont_permissions();
        log_message('debug', 'Fetched staff without permissions.', $data['staffs']);

        $add_new = $this->input->post('add_new');

        if ($add_new == ' hide') {
            $data['add_new'] = ' hide';
            $data['display_staff'] = '';
            log_message('info', 'Add new is hidden.');
        } else {
            $data['add_new'] = '';
            $data['display_staff'] = ' hide';
            log_message('info', 'Displaying new staff.');
        }

        $this->load->view('includes/permissions', $data);
        log_message('info', 'Permissions view loaded.');
    }
}

public function copy_bom($bom_id)
{
    $this->load->model('Manufacturing_model');

    // Copy the BOM
    $new_bom_id = $this->Manufacturing_model->copy_bill_of_material($bom_id);

    if ($new_bom_id) {
        set_alert('success', 'Bill of Materials copied successfully! New BOM ID: ' . $new_bom_id);
    } else {
        set_alert('danger', 'Failed to copy Bill of Materials.');
    }

    // Redirect back to the BOM list
    redirect(admin_url('manufacturing/bill_of_material_manage'));
}

	/**
	 * hr profile update permissions
	 * @param  string $id
	 * @return [type] warehouse_permission_table
	 */
	public function manufacturing_update_permissions($id = '') {
		if (!is_admin()) {
			access_denied('hr_profile');
		}
		$data = $this->input->post();

		if (!isset($id) || $id == '') {
			$id = $data['staff_id'];
		}

		if (isset($id) && $id != '') {

			$data = hooks()->apply_filters('before_update_staff_member', $data, $id);

			if (is_admin()) {
				if (isset($data['administrator'])) {
					$data['admin'] = 1;
					unset($data['administrator']);
				} else {
					if ($id != get_staff_user_id()) {
						if ($id == 1) {
							return [
								'cant_remove_main_admin' => true,
							];
						}
					} else {
						return [
							'cant_remove_yourself_from_admin' => true,
						];
					}
					$data['admin'] = 0;
				}
			}

			$this->db->where('staffid', $id);
			$this->db->update(db_prefix() . 'staff', [
				'role' => $data['role'],
			]);

			$response = $this->staff_model->update_permissions((isset($data['admin']) && $data['admin'] == 1 ? [] : $data['permissions']), $id);
		} else {
			$this->load->model('roles_model');

			$role_id = $data['role'];
			unset($data['role']);
			unset($data['staff_id']);

			$data['update_staff_permissions'] = true;

			$response = $this->roles_model->update($data, $role_id);
		}

		if (is_array($response)) {
			if (isset($response['cant_remove_main_admin'])) {
				set_alert('warning', _l('staff_cant_remove_main_admin'));
			} elseif (isset($response['cant_remove_yourself_from_admin'])) {
				set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
			}
		} elseif ($response == true) {
			set_alert('success', _l('updated_successfully', _l('staff_member')));
		}
		redirect(admin_url('manufacturing/setting?group=mf_permission'));

	}
	/**
	 * setting
	 * @return [type] 
	 */
	public function setting()
	{
		if (!has_permission('manufacturing', '', 'edit') && !is_admin() && !has_permission('manufacturing', '', 'create')) {
			access_denied('manufacturing');
		}

		$data['group'] = $this->input->get('group');
		$data['title'] = _l('setting');

		$data['tab'][] = 'working_hour';
		$data['tab'][] = 'unit_of_measure_categories';
		$data['tab'][] = 'unit_of_measure';
		$data['tab'][] = 'mf_permission';

		$data['tab'][] = 'prefix_number';

		if ($data['group'] == '') {
			$data['group'] = 'working_hour';
			$data['working_hours'] = $this->manufacturing_model->get_routings();
		}elseif ($data['group'] == 'working_hour') {
			$data['working_hours'] = $this->manufacturing_model->get_routings();
		}

		if($data['group'] == 'unit_of_measure_categories'){
			$data['tabs']['view'] = 'settings/unit_of_measure_categories/' . $data['group'];
			$data['categories']	= $this->manufacturing_model->get_unit_categories();
		}elseif($data['group'] == 'unit_of_measure'){
			$data['tabs']['view'] = 'settings/unit_of_measure/' . $data['group'];
		}else{

			$data['tabs']['view'] = 'settings/' . $data['group'];
		}

		$required_inventory_purchase = mrp_required_inventory_purchase_module();
		//required inventory purchase
		if($required_inventory_purchase['inventory'] == false || $required_inventory_purchase['purchase'] == false){
			$this->load->view('manufacturing/settings/required_inventory_module', $data);
		}else{
			$this->load->view('settings/manage_setting', $data);
		}
	}

public function bom_export_pdf($id)
{
    log_message('info', 'Entered bom_export_pdf function with ID: ' . $id);

    if (!$id) {
        log_message('error', 'No BOM ID provided, redirecting...');
        redirect(admin_url('manufacturing/bill_of_materials/bom_export'));
    }

    require_once(module_dir_path('manufacturing', 'libraries/pdf/Bom_pdf.php'));

    $bill_of_material = $this->manufacturing_model->get_bill_of_material_for_pdf($id);

    if (!$bill_of_material) {
        log_message('error', 'BOM not found for ID: ' . $id);
        echo 'Error: Bill of Material not found.';
        return;
    }

    try {
        $pdf = new Bom_pdf($bill_of_material);
        $pdf->prepare();

        $filename = 'bom_export_' . $bill_of_material->bom_code . '.pdf';
        $output_type = $this->input->get('output_type') ?? 'D';
        if ($this->input->get('print')) {
            $output_type = 'I';
        }

        ob_end_clean(); // prevent PDF corruption
        $pdf->Output($filename, $output_type);
        log_message('info', 'PDF generated and output for BOM ID: ' . $id);

    } catch (Exception $e) {
        log_message('error', 'PDF generation failed: ' . $e->getMessage());
        echo 'Error: ' . html_escape($e->getMessage());
    }
}

public function mo_export_pdf($id)
{
    log_message('info', 'Entered mo_export_pdf function with ID: ' . $id);

    if (!has_permission('manufacturing', '', 'view') && !is_admin()) {
        access_denied('manufacturing_order');
    }

    if (!$id) {
        log_message('error', 'No Manufacturing Order ID provided, redirecting...');
        redirect(admin_url('manufacturing/manufacturing_orders'));
    }

    require_once(module_dir_path('manufacturing', 'libraries/pdf/Mo_pdf.php'));

    // Gather manufacturing order data
    $manufacturing_order = $this->manufacturing_model->get_manufacturing_order($id);
    if (!$manufacturing_order) {
        log_message('error', 'Manufacturing order not found for ID: ' . $id);
        echo 'Error: Manufacturing Order not found.';
        return;
    }

    $manufacturing_scrap = $this->manufacturing_model->get_scrap_by_manufacturing_order_id($id);
    $product_tab_details = $manufacturing_order['manufacturing_order_detail'];
    $product_for_hansometable = $this->manufacturing_model->get_product_for_hansometable();
    $unit_for_hansometable = $this->manufacturing_model->get_unit_for_hansometable();
    $manufacturing_order_costing = $this->manufacturing_model->get_manufacturing_order_costing($id);
    $check_manufacturing_order = $this->manufacturing_model->check_manufacturing_order_type($id);

    $check_planned = ($manufacturing_order['manufacturing_order']->status == 'confirmed') ? $check_manufacturing_order['check_planned'] : false;

    $pur_order_exist = false;
    if (is_numeric($manufacturing_order['manufacturing_order']->purchase_request_id)) {
        $this->load->model('purchase/purchase_model');
        $get_purchase_request = $this->purchase_model->get_purchase_request($manufacturing_order['manufacturing_order']->purchase_request_id);
        if ($get_purchase_request) {
            $pur_order_exist = true;
        }
    }

    try {
        $pdf_data = [
            'manufacturing_order'         => $manufacturing_order['manufacturing_order'],
            'product_tab_details'         => $product_tab_details,
            'product_for_scrap'           => $manufacturing_scrap,
            'product_for_hansometable'    => $product_for_hansometable,
            'unit_for_hansometable'       => $unit_for_hansometable,
            'manufacturing_order_costing' => $manufacturing_order_costing,
            'check_planned'               => $check_planned,
            'check_mark_done'             => $check_manufacturing_order['check_mo_done'],
            'check_create_purchase_request' => $check_manufacturing_order['check_create_purchase_request'],
            'check_availability'          => $check_manufacturing_order['check_availability'],
            'data_color'                  => $check_manufacturing_order['data_color'],
            'currency'                    => get_base_currency(),
            'pur_order_exist'             => $pur_order_exist,
        ];

        $pdf = new Mo_pdf($pdf_data);
        $pdf->prepare();

        $filename = 'mo_export_' . $manufacturing_order['manufacturing_order']->mo_code . '.pdf';
        $output_type = $this->input->get('output_type') ?? 'D';
        if ($this->input->get('print')) {
            $output_type = 'I';
        }

        ob_end_clean(); // prevent PDF corruption
        $pdf->Output($filename, $output_type);
        log_message('info', 'PDF generated and output for Manufacturing Order ID: ' . $id);

    } catch (Exception $e) {
        log_message('error', 'PDF generation failed: ' . $e->getMessage());
        echo 'Error: ' . html_escape($e->getMessage());
    }
}



	/**
	 * work center manage
	 * @return [type] 
	 */
	public function work_center_manage()
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('work_center');
		}

		$data['title'] = _l('mrp_work_centers');
		
		$required_inventory_purchase = mrp_required_inventory_purchase_module();
		//required inventory purchase
		if($required_inventory_purchase['inventory'] == false || $required_inventory_purchase['purchase'] == false){
			$this->load->view('manufacturing/settings/required_inventory_module', $data);
		}else{
			$this->load->view('manufacturing/work_centers/work_center_manage', $data);
		}
	}

	/**
	 * work center table
	 * @return [type] 
	 */
	public function work_center_table()
	{
			$this->app->get_table_data(module_views_path('manufacturing', 'work_centers/work_center_table'));
	}

	/**
	 * work center modal
	 * @return [type] 
	 */
	public function work_center_modal()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		$this->load->model('staff_model');

		$data=[];
		if ($this->input->post('slug') === 'update') {
			$id = $this->input->post('id');
			$data['work_center'] = $this->manufacturing_model->get_work_centers($id);
		}
		$this->load->view('settings/work_center_modal', $data);
	}


	/**
	 * add edit work center
	 * @param string $id 
	 */
	public function add_edit_work_center($id = '')
	{
		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('work_center');
		}
		
		if ($this->input->post()) {
			$data = $this->input->post();
			$data['description']     = $this->input->post('description', false);
			
			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('work_center');
				}

				$id = $this->manufacturing_model->add_work_center($data);
				if ($id) {
					$success = true;
					$message = _l('mrp_added_successfully', _l('work_center'));
				}

				if ($id) {
					set_alert('success', _l('mrp_added_successfully', _l('contract')));
					redirect(admin_url('manufacturing/work_center_manage'));
				}

			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('work_center');
				}

				$response = $this->manufacturing_model->update_work_center($data, $id);

				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('mrp_updated_successfully', _l('contract')));
				}
				redirect(admin_url('manufacturing/work_center_manage'));
			}
		}
		
		$data=[];
		if ($id != ''){
			$data['work_center'] = $this->manufacturing_model->get_work_centers($id);
		}
		$data['working_hours'] = $this->manufacturing_model->get_working_hours();

		$this->load->view('manufacturing/work_centers/add_edit_work_center', $data);
	}


	/**
	 * view work center
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function view_work_center($id)
	{
		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('work_center');
		}

	    $work_center = $this->manufacturing_model->get_work_centers($id);

		if (!$work_center) {
			blank_page('Work Center Not Found', 'danger');
		}

		$data['work_center'] = $work_center;
		$this->load->view('manufacturing/work_centers/view_work_center', $data);
	}


	/**
	 * delete work center
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_work_center($id)
	{
	    if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('work_center');
		}

		$success = $this->manufacturing_model->delete_work_center($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/work_center_manage'));

	}

	/**
	 * working hour table
	 * @return [type] 
	 */
	public function working_hour_table()
	{
		$this->app->get_table_data(module_views_path('manufacturing', 'settings/working_hour_table'));
	}


	/**
	 * add edit working hour
	 * @param string $id 
	 */
	public function add_edit_working_hour($id = '')
	{
		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('working_hour');
		}
		
		if ($this->input->post()) {
			$data = $this->input->post();

			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('working_hour');
				}

				$id = $this->manufacturing_model->add_working_hour($data);
				if ($id) {
					set_alert('success', _l('mrp_added_successfully', _l('working_hour')));
					redirect(admin_url('manufacturing/setting?group=working_hour'));
				}

			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('working_hour');
				}

				$response = $this->manufacturing_model->update_working_hour($data, $id);

				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('mrp_updated_successfully', _l('working_hour')));
				}
				redirect(admin_url('manufacturing/setting?group=working_hour'));
			}
		}
		
		$data=[];
		if ($id != ''){
			$working_hour = $this->manufacturing_model->get_working_hour($id);
			$data['working_hour'] = $working_hour['working_hour'];
			$data['working_hour_details'] = $working_hour['working_hour_details'];
			$data['time_off'] = $working_hour['time_off'];
			
		}

		$day_period_type =[];
		$day_period_type[] = [
			'id' => 'morning',
			'label' => _l('morning'),
		];
		$day_period_type[] = [
			'id' => 'afternoon',
			'label' => _l('afternoon'),
		];

		$day_of_week_types=[];
		foreach (mrp_date_of_week() as $key => $value) {
		    array_push($day_of_week_types, [
		    	'id' => $key,
		    	'label' => _l($value),
		    ]);
		}

		$data['day_of_week_types'] = $day_of_week_types;
		$data['day_period_type'] = $day_period_type;
		$data['working_hour_sample_data'] = working_hour_sample_data();
		
		$this->load->view('manufacturing/settings/add_edit_working_hour', $data);
	}

public function update_scrap_data() {
     $scrap_data = $this->input->post('scrap_data');
    $work_order_id = $this->input->post('work_order_id');
    $manufacturing_order_id = $this->input->post('manufacturing_order_id');

    if (!empty($scrap_data) && !empty($work_order_id) && !empty($manufacturing_order_id)) {
        $this->load->model('Manufacturing_model');
        $success = $this->Manufacturing_model->update_scrap_data($scrap_data, $work_order_id, $manufacturing_order_id);

        // Log the update attempt
        log_message('info', "Scrap data update attempted for Work Order ID: $work_order_id, Manufacturing Order ID: $manufacturing_order_id. Data: " . json_encode($scrap_data));

        echo json_encode(['success' => $success]);
    } else {
        log_message('error', 'Scrap data update failed: Missing required parameters.');
        echo json_encode(['success' => false]);
    }
}



	/**
	 * delete working hour
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_working_hour($id)
	{
	    if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('work_center');
		}

		$success = $this->manufacturing_model->delete_working_hour($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/setting?group=working_hour'));
	}


	/*Routings*/

	/**
	 * routing manage
	 * @return [type] 
	 */
	public function routing_manage()
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('work_center');
		}

		$data['title'] = _l('routing');
		
		$required_inventory_purchase = mrp_required_inventory_purchase_module();
		//required inventory purchase
		if($required_inventory_purchase['inventory'] == false || $required_inventory_purchase['purchase'] == false){
			$this->load->view('manufacturing/settings/required_inventory_module', $data);
		}else{
			$this->load->view('manufacturing/routings/routing_manage', $data);
		}
	}

	/**
	 * routing table
	 * @return [type] 
	 */
	public function routing_table()
	{
			$this->app->get_table_data(module_views_path('manufacturing', 'routings/routing_table'));
	}

	/**
	 * add routing modal
	 */
	public function routing_modal()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		$data=[];
		 $data['staffs'] = $this->staff_model->get();
		$data['routing_code'] = $this->manufacturing_model->create_code('routing_code');
		
		$this->load->view('routings/add_routing_modal', $data);
	}


	/**
	 * add routing modal
	 * @param string $id 
	 */
	public function add_routing_modal($id='')
	{

		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('routing');
		}
		
		if ($this->input->post()) {
			$data = $this->input->post();
			$data['description']     = $this->input->post('description', false);

			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('routing');
				}

				$id = $this->manufacturing_model->add_routing($data);
				if ($id) {
					set_alert('success', _l('mrp_added_successfully', _l('routing')));
					redirect(admin_url('manufacturing/operation_manage/'.$id));
				}

			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('routing');
				}

				$response = $this->manufacturing_model->update_routing($data, $id);

				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('mrp_updated_successfully', _l('routing')));
				}
				redirect(admin_url('manufacturing/operation_manage/'.$id));
			}
		}
		
		$data=[];
		if ($id != ''){
			$working_hour = $this->manufacturing_model->get_working_hour($id);
			$data['working_hour'] = $working_hour['working_hour'];
			$data['working_hour_details'] = $working_hour['working_hour_details'];
			$data['time_off'] = $working_hour['time_off'];
			
		}
		

		$day_period_type =[];
		$day_period_type[] = [
			'id' => 'morning',
			'label' => _l('morning'),
		];
		$day_period_type[] = [
			'id' => 'afternoon',
			'label' => _l('afternoon'),
		];

		$day_of_week_types=[];
		foreach (mrp_date_of_week() as $key => $value) {
			array_push($day_of_week_types, [
				'id' => $key,
				'label' => $value,
			]);
		}

		$data['day_of_week_types'] = $day_of_week_types;
		$data['day_period_type'] = $day_period_type;
		
		$this->load->view('manufacturing/settings/add_edit_working_hour', $data);
	}

	/**
	 * delete routing
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_routing($id)
	{
	    if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('routing');
		}

		$success = $this->manufacturing_model->delete_routing($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/routing_manage'));
	}


	/**
	 * operation manage
	 * @return [type] 
	 */
	public function operation_manage($id='')
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('work_center');
		}

		$data['title'] = _l('operation');
		if($id != ''){
			$data['routing'] = $this->manufacturing_model->get_routings($id);
		}
		
		$this->load->view('manufacturing/routings/routing_details/operation_manage', $data);
	}

	/**
	 * operation manage
	 * @return [type] 
	 */
	public function view_routing($id='')
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('work_center');
		}

		$data['title'] = _l('operation');
		if($id != ''){
			$data['routing'] = $this->manufacturing_model->get_routings($id);
		}
		
		$this->load->view('manufacturing/routings/view_routing', $data);
	}


	/**
	 * operation table
	 * @return [type] 
	 */
	public function operation_table()
	{
			$this->app->get_table_data(module_views_path('manufacturing', 'routings/routing_details/operation_table'));
	}


	/**
	 * operation_modal
	 * @return [type] 
	 */
	public function operation_modal()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		$data=[];
		$data = $this->input->post();
		if($data['operation_id'] != 0){
			$data['operation'] = $this->manufacturing_model->get_operation($data['operation_id']);
			$data['operation_attachment'] = $this->manufacturing_model->mrp_get_attachments_file($data['operation_id'], 'mrp_operation');
		}

		$data['work_centers'] = $this->manufacturing_model->get_work_centers();
		 $data['staffs'] = $this->staff_model->get();
		$this->load->view('routings/routing_details/add_edit_operation_modal', $data);
	}


	/**
	 * add edit operation
	 * @param [type] $operation_id 
	 */
	public function add_edit_operation($id='')
	{
	    if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('operation');
		}
		
		if ($this->input->post()) {
			$data = $this->input->post();
			$data['description']     = $this->input->post('description', false);
			$routing_id = $data['routing_id'];

			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('operation');
				}

				$id = $this->manufacturing_model->add_operation($data);
				if ($id) {
					$uploadedFiles = handle_mrp_operation_attachments_array($id,'file');

					set_alert('success', _l('mrp_added_successfully', _l('operation')));
					redirect(admin_url('manufacturing/operation_manage/'.$routing_id));
				}

			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('operation');
				}

				$response = $this->manufacturing_model->update_operation($data, $id);

				$uploadedFiles = handle_mrp_operation_attachments_array($id,'file');

				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('mrp_updated_successfully', _l('operation')));
				}
				redirect(admin_url('manufacturing/operation_manage/'.$routing_id));
			
			}
		}

	}


	/**
	 * delete operation
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_operation($id, $routing_id)
	{
	    if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('work_center');
		}

		$success = $this->manufacturing_model->delete_operation($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/operation_manage/'.$routing_id));


	}

	/**
	 * mrp view attachment file
	 * @param  [type] $id     
	 * @param  [type] $rel_id 
	 * @return [type]         
	 */
	public function mrp_view_attachment_file($id, $rel_id, $rel_type)
	{
		$data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
		$data['current_user_is_admin']             = is_admin();
		$data['file'] = $this->misc_model->get_file($id);
		if (!$data['file']) {
			header('HTTP/1.0 404 Not Found');
			die;
		}

		switch ($rel_type) {
			case 'operation':
				$folder_link = 'manufacturing/routings/routing_details/view_operation_file';
				break;
			
			default:
				# code...
				break;
		}

		$this->load->view($folder_link, $data);
	}


	/**
	 * delete operation attachment file
	 * @param  [type] $attachment_id 
	 * @return [type]                
	 */
	public function delete_operation_attachment_file($attachment_id)
	{
		if (!has_permission('manufacturing', '', 'delete') && !is_admin()) {
			access_denied('operation');
		}

		echo json_encode([
			'success' => $this->manufacturing_model->delete_mrp_attachment_file($attachment_id, MANUFACTURING_OPERATION_ATTACHMENTS_UPLOAD_FOLDER),
		]);
	}


	/**
	 * add edit category
	 * @param string $id 
	 */
	public function add_edit_category($id='')
	{

		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('unit_of_measure_categories');
		}
		
		if ($this->input->post()) {
			$data = $this->input->post();
			if(isset($data['id'])){
				$id = $data['id'];
			}

			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('unit_of_measure_categories');
				}

				$id = $this->manufacturing_model->add_unit_categories($data);
				if ($id) {
					set_alert('success', _l('mrp_added_successfully', _l('unit_of_measure_categories')));
					redirect(admin_url('manufacturing/setting?group=unit_of_measure_categories'));
				}

			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('unit_of_measure_categories');
				}

				$response = $this->manufacturing_model->update_unit_categories($data, $id);

				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('mrp_updated_successfully', _l('unit_of_measure_categories')));
				}
				redirect(admin_url('manufacturing/setting?group=unit_of_measure_categories'));
			}
		}

	}

	/**
	 * delete category
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_category($id)
	{
	    if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('unit_of_measure_categories');
		}

		$success = $this->manufacturing_model->delete_unit_categories($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/setting?group=unit_of_measure_categories'));
	}

	/**
	 * unit of measure table
	 * @return [type] 
	 */
	public function unit_of_measure_table()
	{
		$this->app->get_table_data(module_views_path('manufacturing', 'settings/unit_of_measure/unit_of_measure_table'));
	}


	/**
	 * add edit unit of measure
	 * @param string $id 
	 */
	public function add_edit_unit_of_measure($id = '')
	{
		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('unit_of_measure');
		}
		
		if ($this->input->post()) {
			$data = $this->input->post();

			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('unit_of_measure');
				}

				$id = $this->manufacturing_model->add_unit_of_measure($data);
				if ($id) {
					set_alert('success', _l('mrp_added_successfully', _l('unit_of_measure')));
					redirect(admin_url('manufacturing/setting?group=unit_of_measure'));
				}

			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('unit_of_measure');
				}

				$response = $this->manufacturing_model->update_unit_of_measure($data, $id);

				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('mrp_updated_successfully', _l('unit_of_measure')));
				}
				redirect(admin_url('manufacturing/setting?group=unit_of_measure'));
			}
		}
		
		$data=[];
		if ($id != ''){
			$working_hour = $this->manufacturing_model->get_working_hour($id);
			$data['working_hour'] = $working_hour['working_hour'];
			$data['working_hour_details'] = $working_hour['working_hour_details'];
			$data['time_off'] = $working_hour['time_off'];
			
		}
		

		$day_period_type =[];
		$day_period_type[] = [
			'id' => 'morning',
			'label' => _l('morning'),
		];
		$day_period_type[] = [
			'id' => 'afternoon',
			'label' => _l('afternoon'),
		];

		$day_of_week_types=[];
		foreach (mrp_date_of_week() as $key => $value) {
		    array_push($day_of_week_types, [
		    	'id' => $key,
		    	'label' => $value,
		    ]);
		}

		$data['day_of_week_types'] = $day_of_week_types;
		$data['day_period_type'] = $day_period_type;
		
		$this->load->view('manufacturing/settings/add_edit_working_hour', $data);
	}

	/**
	 * unit of measure modal
	 * @return [type] 
	 */
	public function unit_of_measure_modal()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		$data=[];
		$data = $this->input->post();
		if($data['unit_id'] != 0){
			$data['unit_of_measure'] = $this->manufacturing_model->get_unit_of_measure($data['unit_id']);
		}

		$unit_types=[];
		$unit_types[] = [
			'id' => 'bigger',
			'value' => _l('bigger_than_the_reference_Unit_of_Measure'),
		];
		$unit_types[] = [
			'id' => 'reference',
			'value' => _l('reference_Unit_of_Measure_for_this_category'),
		];
		$unit_types[] = [
			'id' => 'smaller',
			'value' => _l('smaller_than_the_reference_Unit_of_Measure'),
		];
		$data['unit_types'] = $unit_types;

		$data['categories'] = $this->manufacturing_model->get_unit_categories();
		$this->load->view('settings/unit_of_measure/add_edit_unit_of_measure_modal', $data);
	}

	/**
	 * delete unit of measure
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_unit_of_measure($id)
	{
	    if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('work_center');
		}

		$success = $this->manufacturing_model->delete_unit_of_measure($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/setting?group=unit_of_measure'));
	}


	/**
	 * product table
	 * @return [type] 
	 */
	public function product_table()
	{
		$this->app->get_table_data(module_views_path('manufacturing', 'products/products/product_table'));
	}


	/**
	 * product management
	 * @param  string $id 
	 * @return [type]     
	 */
	public function product_management($id = '')
	{

		$data['title'] = _l('product_management');
		$data['commodity_filter'] = $this->manufacturing_model->get_product();
		$data['product_id'] = $id;
		$data['parent_products'] = $this->manufacturing_model->get_parent_product();
		$data['product_types'] = mrp_product_type();
		$data['product_categories'] = $this->manufacturing_model->mrp_get_item_group();

		$required_inventory_purchase = mrp_required_inventory_purchase_module();
		//required inventory purchase
		if($required_inventory_purchase['inventory'] == false || $required_inventory_purchase['purchase'] == false){
			$this->load->view('manufacturing/settings/required_inventory_module', $data);
		}else{
			$this->load->view('products/products/product_manage', $data);
		}

	}


	/**
	 * add edit product
	 * @param [type] $type : product or product variant
	 * @param string $id   
	 */
	public function add_edit_product($type, $id = '')
	{
		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('work_center');
		}
		
		if ($this->input->post()) {
			$data = $this->input->post();
			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('work_center');
				}

				$result = $this->manufacturing_model->add_product($data, $type);

				if($type == 'product_variant'){
					$url = admin_url('manufacturing/product_variant_management');
				}else{
					$url = admin_url('manufacturing/product_management');
				}

				if ($result) {

					set_alert('success', _l('mrp_added_successfully'));
					/*upload multifile*/
					echo json_encode([
						'url' => $url,
						'commodityid' => $result['insert_id'],
						'add_variant' => $result['add_variant'],
						'rel_type' => $type,
						'add_or_update' => 'add',

					]);
					die;
				}

				set_alert('warning', _l('mrp_added_failed'));

				if($type == 'product_variant'){
					$url = admin_url('manufacturing/product_variant_management');
				}else{
					$url = admin_url('manufacturing/product_management');
				}

				echo json_encode([
					'url' => $url,
					'rel_type' => $type,
					'add_or_update' => 'add',

				]);
				die;

			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('work_center');
				}
				$success = $this->manufacturing_model->update_product($data, $id, $type);
				/*update file*/
				set_alert('success', _l('mrp_updated_successfully'));

				if($type == 'product_variant'){
					$url = admin_url('manufacturing/product_variant_management');
				}else{
					$url = admin_url('manufacturing/product_management');
				}

				echo json_encode([
					'url' => $url,
					'commodityid' => $id,
					'rel_type' => $type,
					'add_or_update' => 'update',

				]);
				die;
			}
		}
		
		$data=[];
		$data['title'] = _l('add_product');
		if ($id != ''){
			$data['product'] = $this->manufacturing_model->get_product($id);
			$data['product_attachments'] = $this->manufacturing_model->mrp_get_attachments_file($id, 'commodity_item_file');
			$data['title'] = _l('update_product');
		}

		$data['array_product_type'] = mrp_product_type();
		$data['type'] = $type;
		$data['product_group'] = $this->manufacturing_model->mrp_get_item_group();
		$data['units'] = $this->manufacturing_model->mrp_get_unit();
		$data['taxes'] = mrp_get_taxes();

		$this->load->view('manufacturing/products/add_edit_product', $data);
	}


	/**
	 * check sku duplicate
	 * @return [type] 
	 */
	public function check_sku_duplicate()
    {
    	$data = $this->input->post();
    	$result = $this->manufacturing_model->check_sku_duplicate($data);

    	echo json_encode([
    		'message' => $result
    	]);
    	die;	
    }


    /**
     * add product attachment
     * @param [type] $id 
     */
    public function add_product_attachment($id, $rel_type, $add_variant='')
    {

    	mrp_handle_product_attachments($id);

    	if($rel_type == 'product_variant'){
    		$url = admin_url('manufacturing/product_variant_management');
    	}else{
    		$url = admin_url('manufacturing/product_management');
    	}

    	echo json_encode([
    		'url' => $url,
    		'id' => $id,
    		'rel_type' => $rel_type,
    		'add_variant' => $add_variant,
    	]);
    }


	/**
	 * delete product attachment
	 * @param  [type] $attachment_id 
	 * @param  [type] $rel_type      
	 * @return [type]                
	 */
	public function delete_product_attachment($attachment_id, $rel_type)
	{
	    if (!has_permission('manufacturing', '', 'delete') && !is_admin()) {
			access_denied('manufacturing');
		}

		$folder_name = '';

		switch ($rel_type) {
			case 'manufacturing':
				$folder_name = MANUFACTURING_PRODUCT_UPLOAD;
				break;
			case 'warehouse':
				$folder_name = module_dir_path('warehouse', 'uploads/item_img/');
				break;
			case 'purchase':
				$folder_name = module_dir_path('purchase', 'uploads/item_img/');
				break;
		}

		echo json_encode([
			'success' => $this->manufacturing_model->delete_mrp_attachment_file($attachment_id, $folder_name),
		]);
	}


	/**
	 * delete product
	 * @param  [type] $id       
	 * @param  [type] $rel_type 
	 * @return [type]           
	 */
	public function delete_product($id, $rel_type)
	{

		if (!$id) {
			redirect(admin_url('manufacturing/product_management'));
		}

		if(!has_permission('manufacturing', '', 'delete')  &&  !is_admin()) {
			access_denied('manufacturing');
		}

		$response = $this->manufacturing_model->delete_product($id, $rel_type);
		if (is_array($response) && isset($response['referenced'])) {
			set_alert('warning', _l('is_referenced', _l('commodity')));
		} elseif ($response == true) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		if($rel_type == 'product_variant'){
			redirect(admin_url('manufacturing/product_variant_management'));
		}else{
			redirect(admin_url('manufacturing/product_management'));
		}
	}


	/**
	 * product variant table
	 * @return [type] 
	 */
	public function product_variant_table()
	{
		$this->app->get_table_data(module_views_path('manufacturing', 'products/product_variants/product_variant_table'));
	}


	/**
	 * product variant management
	 * @param  string $id 
	 * @return [type]     
	 */
	public function product_variant_management($id = '')
	{

		$data['title'] = _l('product_variant_management');
		$data['commodity_filter'] = $this->manufacturing_model->get_product();
		$data['product_id'] = $id;
		$data['product_variants'] = $this->manufacturing_model->get_product_variant();
		$data['product_types'] = mrp_product_type();
		$data['product_categories'] = $this->manufacturing_model->mrp_get_item_group();
		
		$required_inventory_purchase = mrp_required_inventory_purchase_module();
		//required inventory purchase
		if($required_inventory_purchase['inventory'] == false || $required_inventory_purchase['purchase'] == false){
			$this->load->view('manufacturing/settings/required_inventory_module', $data);
		}else{
			$this->load->view('products/product_variants/product_variant_manage', $data);
		}
	}


	/**
	 * copy product image
	 * @param  [type] $id       
	 * @param  [type] $rel_type 
	 * @return [type]           
	 */
	public function copy_product_image($id, $rel_type)
    {

    	$this->manufacturing_model->copy_product_image($id);
    	if($rel_type == 'product_variant'){
    		$url = admin_url('manufacturing/product_variant_management');
    	}else{
    		$url = admin_url('manufacturing/product_management');
    	}

    	echo json_encode([
    		'url' => $url,
    	]);
    }


    /**
     * bill of material manage
     * @return [type] 
     */
    public function bill_of_material_manage()
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('work_center');
		}

		$data['title'] = _l('bill_of_material');
		$data['products'] = $this->manufacturing_model->get_product();
		$data['routings'] = $this->manufacturing_model->get_routings();
		$bom_type=[];
		
		$bom_type[] = [
			'name' => 'kit',
			'label' => _l('kit'),
		];

		$bom_type[] = [
			'name' => 'manufacture_this_product',
			'label' => _l('manufacture_this_product'),
		];
		$data['bom_types'] = $bom_type;
		
		$required_inventory_purchase = mrp_required_inventory_purchase_module();
		//required inventory purchase
		if($required_inventory_purchase['inventory'] == false || $required_inventory_purchase['purchase'] == false){
			$this->load->view('manufacturing/settings/required_inventory_module', $data);
		}else{
			$this->load->view('manufacturing/bill_of_materials/bill_of_material_manage', $data);
		}
	}

	
	/**
	 * bill of material table
	 * @return [type] 
	 */
	public function bill_of_material_table()
	{
			$this->app->get_table_data(module_views_path('manufacturing', 'bill_of_materials/bill_of_material_table'));
	}


	/**
	 * bill of material modal
	 * @return [type] 
	 */
	public function bill_of_material_modal()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		$data=[];
		
		$ready_to_produce_type=[];
		$consumption_type=[];
		
		$ready_to_produce_type[] = [
			'name' => 'all_available',
			'label' => _l('when_all_components_are_available'),
		];

		$ready_to_produce_type[] = [
			'name' => 'components_for_1st',
			'label' => _l('when_components_for_1st_operation_are_available'),
		];

		$consumption_type[] = [
			'name' => 'strict',
			'label' => _l('strict'),
		];

		$consumption_type[] = [
			'name' => 'flexible',
			'label' => _l('flexible'),
		];

		

		$data['title'] = _l('bills_of_materials');
		$data['units'] = $this->manufacturing_model->mrp_get_unit();
		$data['ready_to_produce_type'] = $ready_to_produce_type;
		$data['consumption_type'] = $consumption_type;
		$data['routings'] = $this->manufacturing_model->get_routings();
		$data['parent_product'] = $this->manufacturing_model->get_parent_product();
		$data['bom_code'] = $this->manufacturing_model->create_code('bom_code');


		$this->load->view('bill_of_materials/add_edit_bill_of_material_modal', $data);
	}


	/**
	 * add bill of material modal
	 * @param string $id 
	 */
	public function add_bill_of_material_modal($id='')
	{

		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('bill_of_material_label');
		}
		
		if ($this->input->post()) {
			$data = $this->input->post();

			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('bill_of_material_label');
				}

				$id = $this->manufacturing_model->add_bill_of_material($data);
				if ($id) {
					set_alert('success', _l('mrp_added_successfully', _l('bill_of_material_label')));
					redirect(admin_url('manufacturing/bill_of_material_detail_manage/'.$id));
				}

			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('bill_of_material_label');
				}

				$response = $this->manufacturing_model->update_bill_of_material($data, $id);

				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('mrp_updated_successfully', _l('bill_of_material_label')));
				}
				redirect(admin_url('manufacturing/bill_of_material_detail_manage/'.$id));
			}
		}
		
		$data=[];
		if ($id != ''){
			$working_hour = $this->manufacturing_model->get_working_hour($id);
			$data['working_hour'] = $working_hour['working_hour'];
			$data['working_hour_details'] = $working_hour['working_hour_details'];
			$data['time_off'] = $working_hour['time_off'];
			
		}
		

		$ready_to_produce_type=[];
		$consumption_type=[];
		
		$ready_to_produce_type[] = [
			'name' => 'all_available',
			'label' => _l('when_all_components_are_available'),
		];

		$ready_to_produce_type[] = [
			'name' => 'components_for_1st',
			'label' => _l('when_components_for_1st_operation_are_available'),
		];

		$consumption_type[] = [
			'name' => 'strict',
			'label' => _l('strict'),
		];

		$consumption_type[] = [
			'name' => 'flexible',
			'label' => _l('flexible'),
		];
		$data['units'] = $this->manufacturing_model->mrp_get_unit();
		$data['ready_to_produce_type'] = $ready_to_produce_type;
		$data['consumption_type'] = $consumption_type;
		$data['routings'] = $this->manufacturing_model->get_routings();
		$data['parent_product'] = $this->manufacturing_model->get_parent_product();

		
		$this->load->view('manufacturing/settings/add_edit_working_hour', $data);
	}


	/**
	 * operation table
	 * @return [type] 
	 */
	public function operation_table_view()
	{
			$this->app->get_table_data(module_views_path('manufacturing', 'routings/routing_details/operation_table_view'));
	}




public function save_scrap_modal($id = '') {
    // Log entry point
    log_message('info', 'Entered save_scrap_modal function with ID: ' . $id);

    if (!has_permission('manufacturing', '', 'view') && !is_admin()) {
        log_message('error', 'Access denied for viewing manufacturing orders');
        access_denied('manufacturing_order');
    }

    if ($this->input->post()) {
        $data = $this->input->post();
       log_message('info', 'Received POST data: ' . print_r($data, true));
      
      $bill_of_material_id = $data['bill_of_material_id'];
        if ($id == '') {
            if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
                log_message('error', 'Access denied for creating components');
                access_denied('component');
            }

            // Adding scrap
            log_message('info', 'Adding new scrap for manufacturing order ID: ' . $bill_of_material_id);
            $id = $this->manufacturing_model->addbom_scrap($data);

            if ($id) {
                log_message('info', 'Scrap added successfully with ID: ' . $id);
                set_alert('success', _l('scrap_added_successfully', _l('bill_of_material_id')));
                redirect(admin_url('manufacturing/bill_of_material_detail_manage/' . $bill_of_material_id));
            } else {
                log_message('error', 'Failed to add scrap for manufacturing order ID: ' . $bill_of_material_id);
            }

        } else {
            if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
                log_message('error', 'Access denied for editing components');
                access_denied('component');
            }

            // Updating scrap
            log_message('info', 'Updating scrap with ID: ' . $id . ' for manufacturing order ID: ' . $bill_of_material_id);
            $response = $this->manufacturing_model->updatebom_scrap($data, $id);

            if (is_array($response)) {
                if (isset($response['cant_remove_main_admin'])) {
                    log_message('warning', 'Attempt to remove main admin');
                    set_alert('warning', _l('staff_cant_remove_main_admin'));
                } elseif (isset($response['cant_remove_yourself_from_admin'])) {
                    log_message('warning', 'Attempt to remove yourself from admin');
                    set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
                }
            } elseif ($response == true) {
                log_message('info', 'Scrap updated successfully with ID: ' . $id);
                set_alert('success', _l('mrp_updated_successfully', _l('manufacturing_order')));
            } else {
                log_message('error', 'Failed to update scrap with ID: ' . $id);
            }

            redirect(admin_url('manufacturing/bill_of_material_detail_manage/' . $bill_of_material_id));
        }
    }

    log_message('info', 'Exiting save_scrap_modal function');
}

	/**
	 * delete bill of material
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_bill_of_material($id)
	{
	    if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('routing');
		}

		$success = $this->manufacturing_model->delete_bill_of_material($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/bill_of_material_manage'));
	}


	/**
	 * bill of material detail manage
	 * @param  string $id 
	 * @return [type]     
	 */
	public function bill_of_material_detail_manage($id='')
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('bill_of_material_label');
		}

		$data['title'] = _l('bill_of_material_label');
		if($id != ''){
			$data['bill_of_material'] = $this->manufacturing_model->get_bill_of_materials($id);
		}
		
		$ready_to_produce_type=[];
		$consumption_type=[];
		
		$ready_to_produce_type[] = [
			'name' => 'all_available',
			'label' => _l('when_all_components_are_available'),
		];

		$ready_to_produce_type[] = [
			'name' => 'components_for_1st',
			'label' => _l('when_components_for_1st_operation_are_available'),
		];

		$consumption_type[] = [
			'name' => 'strict',
			'label' => _l('strict'),
		];

		$consumption_type[] = [
			'name' => 'flexible',
			'label' => _l('flexible'),
		];
		$data['units'] = $this->manufacturing_model->mrp_get_unit();
		$data['ready_to_produce_type'] = $ready_to_produce_type;
		$data['consumption_type'] = $consumption_type;
		$data['routings'] = $this->manufacturing_model->get_routings();
		$data['parent_product'] = $this->manufacturing_model->get_parent_product();
		$data['product_variant'] = $this->manufacturing_model->get_product_variant();

		$this->load->view('manufacturing/bill_of_materials/bill_of_material_details/bill_of_material_detail_manage', $data);
	}
/**
 * Get component data for the Bill of Material Detail table
 * @param int $bill_of_material_id
 * @return void
 */
 
 /*
public function view_bill_of_material_detail_json($id = '')
{
    log_message('info', 'Entered view_bill_of_material_detail_json function with ID: ' . $id);

    if (!has_permission('manufacturing', '', 'view')) {
        log_message('error', 'Permission denied for view_bill_of_material_detail_json');
        access_denied('bill_of_material_label');
    }

    $response = [
        'status' => 'error',
        'message' => 'Bill of Material not found'
    ];

    if ($id != '') {
        log_message('info', 'Fetching Bill of Material with ID: ' . $id);
        
        // Ensure bill_of_material is an array
        $bill_of_material = (array) $this->manufacturing_model->get_bill_of_materials($id);

        if (!empty($bill_of_material)) {
            log_message('info', 'Bill of Material found: ' . json_encode($bill_of_material));

            $components = [];
            $bill_of_material_details = $this->manufacturing_model->get_bill_of_material_details(false, $id);
             $units = $this->manufacturing_model->mrp_get_unit();
            foreach ($bill_of_material_details as $component) {
                $component = (array) $component; // Convert each component to an array
                log_message('info', 'Bill of Material component: ' . json_encode($component));

                // Fetch product details using get_product and correct field names
                $product_details = $this->manufacturing_model->get_product($component['product_id']);
                log_message('info', 'Product details: ' . json_encode($product_details));

                $components[] = [
                    'component_name' => isset($component['name']) ? $component['name'] : 'N/A',
                    'product_id' => isset($component['product_id']) ? $component['product_id'] : 'N/A',
                    'product_name' => isset($product_details->description) ? $product_details->description : 'N/A', // Using 'description' for product name
                    'price' => isset($product_details->purchase_price) ? $product_details->purchase_price : 'N/A', // Using 'rate' for price
                    'product_qty' => isset($component['product_qty']) ? $component['product_qty'] : 'N/A',
                    'product_unit' => isset($component['unit_id']) ? $component['unit_id'] : 'N/A'
                ];

                log_message('info', 'Processed component with details: ' . json_encode($components[count($components) - 1]));
            }

            $response = [
                'status' => 'success',
                'bill_of_material' => [
                    'id' => $bill_of_material['id'],
                    'name' => isset($bill_of_material['name']) ? $bill_of_material['name'] : 'N/A',
                    'quality'=>$bill_of_material['product_qty'],
                    'components' => $components,
                    'units' => $units,
                    'ready_to_produce_type' => [
                        [
                            'name' => 'all_available',
                            'label' => _l('when_all_components_are_available')
                        ],
                        [
                            'name' => 'components_for_1st',
                            'label' => _l('when_components_for_1st_operation_are_available')
                        ]
                    ],
                    'consumption_type' => [
                        [
                            'name' => 'strict',
                            'label' => _l('strict')
                        ],
                        [
                            'name' => 'flexible',
                            'label' => _l('flexible')
                        ]
                    ]
                ]
            ];

            log_message('info', 'Bill of Material response constructed: ' . json_encode($response));
        } else {
            log_message('error', 'Bill of Material not found for ID: ' . $id);
        }
    } else {
        log_message('error', 'No ID provided to view_bill_of_material_detail_json');
    }

    echo json_encode($response);
    log_message('info', 'Response sent for view_bill_of_material_detail_json');
}

public function view_bill_of_material_detail($id='')
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('bill_of_material_label');
		}

		$data['title'] = _l('bill_of_material_label');
		if($id != ''){
			$data['bill_of_material'] = $this->manufacturing_model->get_bill_of_materials($id);
		}
		
		$ready_to_produce_type=[];
		$consumption_type=[];
		
		$ready_to_produce_type[] = [
			'name' => 'all_available',
			'label' => _l('when_all_components_are_available'),
		];

		$ready_to_produce_type[] = [
			'name' => 'components_for_1st',
			'label' => _l('when_components_for_1st_operation_are_available'),
		];

		$consumption_type[] = [
			'name' => 'strict',
			'label' => _l('strict'),
		];

		$consumption_type[] = [
			'name' => 'flexible',
			'label' => _l('flexible'),
		];
		$data['units'] = $this->manufacturing_model->mrp_get_unit();
		$data['ready_to_produce_type'] = $ready_to_produce_type;
		$data['consumption_type'] = $consumption_type;
		$data['routings'] = $this->manufacturing_model->get_routings();
		$data['parent_product'] = $this->manufacturing_model->get_parent_product();
		$data['product_variant'] = $this->manufacturing_model->get_product_variant();

		$this->load->view('manufacturing/bill_of_materials/bill_of_material_details/view_bill_of_material_detail', $data);
	}
	*/
	
	public function view_bill_of_material_detail_json($id = '')
{
    log_message('info', 'Entered view_bill_of_material_detail_json function with ID: ' . $id);

    if (!has_permission('manufacturing', '', 'view')) {
        log_message('error', 'Permission denied for view_bill_of_material_detail_json');
        access_denied('bill_of_material_label');
    }

    $response = [
        'status' => 'error',
        'message' => 'Bill of Material not found'
    ];

    if ($id != '') {
        log_message('info', 'Fetching Bill of Material with ID: ' . $id);
        
        // Ensure bill_of_material is an array
        $bill_of_material = (array) $this->manufacturing_model->get_bill_of_materials($id);

        if (!empty($bill_of_material)) {
            log_message('info', 'Bill of Material found: ' . json_encode($bill_of_material));

            $components = [];
            $bill_of_material_details = $this->manufacturing_model->get_bill_of_material_details(false, $id);
             $units = $this->manufacturing_model->mrp_get_unit();
            foreach ($bill_of_material_details as $component) {
                $component = (array) $component; // Convert each component to an array
                log_message('info', 'Bill of Material component: ' . json_encode($component));

                // Fetch product details using get_product and correct field names
                $product_details = $this->manufacturing_model->get_product($component['product_id']);
                log_message('info', 'Product details: ' . json_encode($product_details));

                $components[] = [
                    'component_name' => isset($component['name']) ? $component['name'] : 'N/A',
                    'product_id' => isset($component['product_id']) ? $component['product_id'] : 'N/A',
                    'product_name' => isset($product_details->description) ? $product_details->description : 'N/A', // Using 'description' for product name
                    'price' => isset($product_details->purchase_price) ? $product_details->purchase_price : 'N/A', // Using 'rate' for price
                    'product_qty' => isset($component['product_qty']) ? $component['product_qty'] : 'N/A',
                    'product_unit' => isset($component['unit_id']) ? $component['unit_id'] : 'N/A'
                ];

                log_message('info', 'Processed component with details: ' . json_encode($components[count($components) - 1]));
            }

            $response = [
                'status' => 'success',
                'bill_of_material' => [
                    'id' => $bill_of_material['id'],
                    'name' => isset($bill_of_material['name']) ? $bill_of_material['name'] : 'N/A',
                    'quality'=>$bill_of_material['product_qty'],
                    'components' => $components,
                    'units' => $units,
                    'ready_to_produce_type' => [
                        [
                            'name' => 'all_available',
                            'label' => _l('when_all_components_are_available')
                        ],
                        [
                            'name' => 'components_for_1st',
                            'label' => _l('when_components_for_1st_operation_are_available')
                        ]
                    ],
                    'consumption_type' => [
                        [
                            'name' => 'strict',
                            'label' => _l('strict')
                        ],
                        [
                            'name' => 'flexible',
                            'label' => _l('flexible')
                        ]
                    ]
                ]
            ];

            log_message('info', 'Bill of Material response constructed: ' . json_encode($response));
        } else {
            log_message('error', 'Bill of Material not found for ID: ' . $id);
        }
    } else {
        log_message('error', 'No ID provided to view_bill_of_material_detail_json');
    }

    echo json_encode($response);
    log_message('info', 'Response sent for view_bill_of_material_detail_json');
}

public function view_bill_of_material_detail($id = '')
{
    log_message('info', 'Entered view_bill_of_material_detail with ID: ' . $id);

    if (!has_permission('manufacturing', '', 'view')) {
        log_message('error', 'Access denied for bill_of_material_label in view_bill_of_material_detail');
        access_denied('bill_of_material_label');
    }

    $data['title'] = _l('bill_of_material_label');

    if ($id != '') {
        // Fetch main BOM data
        $data['bill_of_material'] = $this->manufacturing_model->get_bill_of_materials($id);
        log_message('info', 'Fetched bill_of_material: ' . print_r($data['bill_of_material'], true));

        $data['labour_charges'] = $data['bill_of_material']->labour_charges ?? 0;
        $data['electricity_charges'] = $data['bill_of_material']->electricity_charges ?? 0;
        $data['machinery_charges'] = $data['bill_of_material']->machinery_charges ?? 0;
        $data['other_charges'] = $data['bill_of_material']->other_charges ?? 0;
        $data['labour_charges_description'] = $data['bill_of_material']->labour_charges_description ?? '';
        $data['electricity_charges_description'] = $data['bill_of_material']->electricity_charges_description ?? '';
        $data['machinery_charges_description'] = $data['bill_of_material']->machinery_charges_description ?? '';
        $data['other_charges_description'] = $data['bill_of_material']->other_charges_description ?? '';

        // Fetch scrap details by BOM id
		$data['scrap_details'] = $this->manufacturing_model->get_scrap_details_by_bom($id);
		log_message('info', 'Fetched scrap_details: ' . print_r($data['scrap_details'], true));
		
		$formatted_scrap_details = [];
		
		foreach ($data['scrap_details'] as $scrap) {
			$scrap = (array) $scrap;
		
			$formatted_scrap_details[] = [
				'product_id' => $scrap['product_id'] ?? 'N/A',
				'scrap_qty' => $scrap['scrap_qty'] ?? 0,
				'product_name' => $scrap['product_name'] ?? 'N/A',
				'unit' => $scrap['unit_id'] ?? 'N/A',
				'unit_name' => $scrap['unit_name'] ?? 'N/A',
				'price' => isset($scrap['rate']) ? (float)$scrap['rate'] : 0,
				'scrap_subtotal_cost' => (isset($scrap['rate']) && isset($scrap['scrap_qty']))
					? ((float)$scrap['rate'] * (float)$scrap['scrap_qty'])
					: 0,
			];
		}
		

    } else {
        log_message('warning', 'No ID provided to view_bill_of_material_detail');
        $data['scrap_details'] = [];
    }

    // Fetch BOM components details
    $components = [];
    $bill_of_material_details = $this->manufacturing_model->get_bill_of_material_details(false, $id);
    log_message('info', 'Fetched bill_of_material_details: ' . print_r($bill_of_material_details, true));

    foreach ($bill_of_material_details as $component) {
        $component = (array) $component;  // Ensure consistency by casting to array
        $product_details = $this->manufacturing_model->get_product($component['product_id']);
        log_message('info', 'Component product details: ' . print_r($product_details, true));

        $components[] = [
            'component_name' => isset($component['name']) ? $component['name'] : 'N/A',
            'product_id' => isset($component['product_id']) ? $component['product_id'] : 'N/A',
            'product_name' => isset($product_details->description) ? $product_details->description : 'N/A',
            'price' => isset($product_details->rate) ? (float)$product_details->rate : 0,
            'product_qty' => isset($component['product_qty']) ? (float)$component['product_qty'] : 0,
			'product_unit' => isset($product_details->unit) ? $product_details->unit : 'N/A',

 			'subtotal_cost' => isset($product_details->rate) && isset($component['product_qty']) ? (float)$product_details->rate * (float)$component['product_qty'] : 0,
        ];
    }
    log_message('info', 'Prepared components array: ' . print_r($components, true));

    $ready_to_produce_type = [
        ['name' => 'all_available', 'label' => _l('when_all_components_are_available')],
        ['name' => 'components_for_1st', 'label' => _l('when_components_for_1st_operation_are_available')],
    ];

    $consumption_type = [
        ['name' => 'strict', 'label' => _l('strict')],
        ['name' => 'flexible', 'label' => _l('flexible')],
    ];

    $data['units'] = $this->manufacturing_model->mrp_get_unit();
    log_message('info', 'Fetched units: ' . print_r($data['units'], true));

    $data['ready_to_produce_type'] = $ready_to_produce_type;
    $data['consumption_type'] = $consumption_type;
    $data['routings'] = $this->manufacturing_model->get_routings();
    log_message('info', 'Fetched routings: ' . print_r($data['routings'], true));

    $data['parent_product'] = $this->manufacturing_model->get_parent_product();
    log_message('info', 'Fetched parent_product: ' . print_r($data['parent_product'], true));

    $data['product_variant'] = $this->manufacturing_model->get_product_variant();
    log_message('info', 'Fetched product_variant: ' . print_r($data['product_variant'], true));

    $data['components'] = $components;

    $this->load->view('manufacturing/bill_of_materials/bill_of_material_details/view_bill_of_material_detail', $data);
    log_message('info', 'Loaded view: view_bill_of_material_detail');
}
	
	public function view_scrap_detail($id='')
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('bill_of_material_label');
		}

		$data['title'] = _l('bill_of_material_label');
		if($id != ''){
			$data['bill_of_material'] = $this->manufacturing_model->get_bill_of_materials($id);
		}
		
		$ready_to_produce_type=[];
		$consumption_type=[];
		
		$ready_to_produce_type[] = [
			'name' => 'all_available',
			'label' => _l('when_all_components_are_available'),
		];

		$ready_to_produce_type[] = [
			'name' => 'components_for_1st',
			'label' => _l('when_components_for_1st_operation_are_available'),
		];

		$consumption_type[] = [
			'name' => 'strict',
			'label' => _l('strict'),
		];

		$consumption_type[] = [
			'name' => 'flexible',
			'label' => _l('flexible'),
		];
		$data['units'] = $this->manufacturing_model->mrp_get_unit();
		$data['ready_to_produce_type'] = $ready_to_produce_type;
		$data['consumption_type'] = $consumption_type;
		$data['routings'] = $this->manufacturing_model->get_routings();
		$data['parent_product'] = $this->manufacturing_model->get_parent_product();
		$data['product_variant'] = $this->manufacturing_model->get_product_variant();

		$this->load->view('manufacturing/bill_of_materials/bill_of_material_details/view_bill_of_material_detail', $data);
	}
	/**
	 * bill_of_material_detail table
	 * @return [type] 
	 */
	public function bill_of_material_detail_table()
	{   log_message('info', 'bill_of_material_detail_table() called.');
			$this->app->get_table_data(module_views_path('manufacturing', 'bill_of_materials/bill_of_material_details/bill_of_material_detail_table'));
	}
public function bill_of_material_scrap_table()
{
    log_message('info', 'bill_of_material_scrap_table() called.');

    $table_path = module_views_path('manufacturing', 'bill_of_materials/bill_of_material_details/bill_of_material_scrap_table');
    log_message('debug', 'Resolved table path: ' . $table_path);

    $this->app->get_table_data($table_path);
    
    log_message('info', 'bill_of_material_scrap_table() completed.');
}

	/**
	 * bill of material detail modal
	 * @return [type] 
	 */
	public function bill_of_material_detail_modal()
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		$data=[];
		$data = $this->input->post();

 $jsonData = json_encode($data, JSON_PRETTY_PRINT);

// Log the JSON string
log_message('info', 'scrap load data add_sc: ' . $jsonData);
		if($data['component_id'] != 0){
			$data['bill_of_material_detail'] = $this->manufacturing_model->get_bill_of_material_details($data['component_id']);
		}
		// get product_variants
		$product_variant_id = 0;
		$bill_of_materials = $this->manufacturing_model->get_bill_of_materials($data['bill_of_material_id']);
		if($bill_of_materials){
			$product_variant_id = $bill_of_materials->product_variant_id;
		}
		//get variant of product
		$data['arr_variants'] = $this->manufacturing_model->get_variant_attribute($data['bill_of_material_product_id'], $product_variant_id);
		//get operation of routing
		$data['arr_operations'] = $this->manufacturing_model->get_operation(false, $data['routing_id']);

		$data['products'] = $this->manufacturing_model->get_rawproduct();
		$data['product_variants'] = $this->manufacturing_model->get_product_variant();
		$data['units'] = $this->manufacturing_model->mrp_get_unit();

		$this->load->view('bill_of_materials/bill_of_material_details/add_edit_bill_of_material_detail_modal', $data);
	}
public function add_scrap_modal($id = '') {
    // Log entry point
    log_message('info', 'Entered add_scrap_modal function');

    // Check if the request is an AJAX request
    if (!$this->input->is_ajax_request()) {
        log_message('error', 'Invalid request type. Expected AJAX.');
        show_404();
    }
    log_message('info', 'Valid AJAX request');

    // Initialize data array
    $data = [];

    // Get POST data
    $postData = $this->input->post();

    // Ensure postData exists before using it
    if (!empty($postData)) {
        $data = array_merge($data, $postData);
    }

    // Ensure required fields exist in the data array to prevent undefined index errors
    $data['bill_of_material_id'] = isset($data['bill_of_material_id']) ? $data['bill_of_material_id'] : 0;
    $data['bill_of_material_product_id'] = isset($data['bill_of_material_product_id']) ? $data['bill_of_material_product_id'] : 0;
    $data['routing_id'] = isset($data['routing_id']) ? $data['routing_id'] : 0;
    $data['component_id'] = isset($data['component_id']) ? $data['component_id'] : 0;

    // Fetch bill of material details if a component is selected
    if ($data['component_id'] != 0) {
        $data['bill_of_material_detail'] = $this->manufacturing_model->get_bill_of_material_details($data['component_id']);
        log_message('info', 'Fetched bill of material details for component ID: ' . $data['component_id']);
    }

    // Fetch product variant ID
    $product_variant_id = 0;
    $bill_of_materials = $this->manufacturing_model->get_bill_of_materials($data['bill_of_material_id']);
    if ($bill_of_materials) {
        $product_variant_id = $bill_of_materials->product_variant_id;
    }
    
    // Get product variants and operations of routing
    $data['arr_variants'] = $this->manufacturing_model->get_variant_attribute($data['bill_of_material_product_id'], $product_variant_id);
    $data['arr_operations'] = $this->manufacturing_model->get_operation(false, $data['routing_id']);

    // Fetch other required data
    $data['units'] = $this->manufacturing_model->mrp_get_unit();
    $data['products'] = $this->manufacturing_model->get_product();
    $data['product_variants'] = $this->manufacturing_model->get_product_variant();

    // Log data retrieval
    log_message('info', 'Fetched all necessary data for scrap modal');

    // Load the view
    log_message('info', 'Loading view: manufacturing/scrap/add_edit_scrap_modal');
    $this->load->view('bill_of_materials/add_edit_scrap_modal', $data);

    // Log exit point
    log_message('info', 'Exiting add_scrap_modal function');
}

	/**
	 * add edit bill of material detail
	 * @param string $id 
	 */
	public function add_edit_bill_of_material_detail($id='')
	{
	    if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('component');
		}
		
		if ($this->input->post()) {
			$data = $this->input->post();
			$bill_of_material_id = $data['bill_of_material_id'];

			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('component');
				}

				$id = $this->manufacturing_model->add_bill_of_material_detail($data);
				if ($id) {

					set_alert('success', _l('mrp_added_successfully', _l('component')));
					redirect(admin_url('manufacturing/bill_of_material_detail_manage/'.$bill_of_material_id));
				}

			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('component');
				}

				$response = $this->manufacturing_model->update_bill_of_material_detail($data, $id);

				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('mrp_updated_successfully', _l('component')));
				}
				redirect(admin_url('manufacturing/bill_of_material_detail_manage/'.$bill_of_material_id));
			
			}
		}

	}

	/**
	 * delete bill of material detail
	 * @param  [type] $id         
	 * @param  [type] $routing_id 
	 * @return [type]             
	 */
	public function delete_bill_of_material_detail($id, $bill_of_material_id)
	{
	    if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('work_center');
		}

		$success = $this->manufacturing_model->delete_bill_of_material_detail($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/bill_of_material_detail_manage/'.$bill_of_material_id));


	}
	public function delete_bom_scrap($id, $bill_of_material_id)
	{
	    if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('work_center');
		}

		$success = $this->manufacturing_model->delete_bom_scrap($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/bill_of_material_detail_manage/'.$bill_of_material_id));


	}

	/**
	 * get product variants
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_product_variants($id)
	{
		$product_variants = $this->manufacturing_model->get_product_variants($id);
		$product = $this->manufacturing_model->get_product($id);
		if($product){
			$unit_id = $product->unit_id;
		}else{
			$unit_id = '';
		}

		echo json_encode([
			'product_variants' => $product_variants,
			'unit_id' => $unit_id,
		]);
	    
	}


	/**
	 * manufacturing order manage
	 * @return [type] 
	 */
	public function manufacturing_order_manage()
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('manufacturing_order');
		}

		
		$data['title'] = _l('manufacturing_order');
		$data['products'] = $this->manufacturing_model->get_product();
	    $data['customers'] = $this->clients_model->get();
		$data['routings'] = $this->manufacturing_model->get_routings();
		$status_data=[];
		$status_data[]=[
			'name' => 'draft',
			'label' => _l('mrp_draft'),
		];
		$status_data[]=[
			'name' => 'planned',
			'label' => _l('mrp_planned'),
		];
		$status_data[]=[
			'name' => 'cancelled',
			'label' => _l('mrp_cancelled'),
		];
		$status_data[]=[
			'name' => 'confirmed',
			'label' => _l('mrp_confirmed'),
		];
		$status_data[]=[
			'name' => 'done',
			'label' => _l('mrp_done'),
		];
		$status_data[]=[
			'name' => 'in_progress',
			'label' => _l('mrp_in_progress'),
		];
		
		$data['status_data'] = $status_data;
		log_message('debug', 'Row Data  | Data: '.json_encode($data));

		
		$required_inventory_purchase = mrp_required_inventory_purchase_module();
		//required inventory purchase
		if($required_inventory_purchase['inventory'] == false || $required_inventory_purchase['purchase'] == false){
			$this->load->view('manufacturing/settings/required_inventory_module', $data);
		}else{
			$this->load->view('manufacturing/manufacturing_orders/manufacturing_order_manage', $data);
		}
	}

	
	/**
	 * manufacturing order table
	 * @return [type] 
	 */
	public function manufacturing_order_table()
	{
			$this->app->get_table_data(module_views_path('manufacturing', 'manufacturing_orders/manufacturing_order_table'));
	}

	
	/**
	 * add edit manufacturing order
	 * @param string $id 
	 */
	/*
	public function add_edit_manufacturing_order($id = '')
	{
		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('manufacturing_order');
		}
		
		$this->load->model('staff_model');
		$this->load->model('warehouse/warehouse_model');

		if ($this->input->post()) {
			$data = $this->input->post();
			log_message('info',  json_encode($data));
			 

			if ($id == '') {
				if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					access_denied('manufacturing_order');
				}
				$id = $this->manufacturing_model->add_manufacturing_order($data);
				if ($id) {
					set_alert('success', _l('mrp_added_successfully', _l('manufacturing_order')));
					$scrapdata= $data['scrab_tab_hs'];
					
        $scrapdata['manufacturing_order_id'] = $id;
        log_message('info', 'Received POST data for manufacturing order ID: ' . $manufacturing_order_id);

        if ($id == '') {
            if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
                log_message('error', 'Access denied for creating components');
                access_denied('component');
            }

            // Adding scrap
            log_message('info', 'Adding new scrap for manufacturing order ID: ' . $manufacturing_order_id);
            $id = $this->manufacturing_model->add_scrap($scrapdata);

            if ($id) {
                log_message('info', 'Scrap added successfully with ID: ' . $id);
                set_alert('success', _l('mrp_added_successfully', _l('manufacturing_order')));
                redirect(admin_url('manufacturing/add_edit_manufacturing_order/' . $manufacturing_order_id));
            } else {
                log_message('error', 'Failed to add scrap for manufacturing order ID: ' . $manufacturing_order_id);
            }
					$this.
					redirect(admin_url('manufacturing/view_manufacturing_order/'.$id));
				}
			}
			} else {
				if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					access_denied('manufacturing_order');
				}
				
				$response = $this->manufacturing_model->update_manufacturing_order($data, $id);

				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('mrp_updated_successfully', _l('manufacturing_order')));
				}
				redirect(admin_url('manufacturing/add_edit_manufacturing_order/'.$id));
			}
		}
		
		$data=[];
		if ($id != ''){
			$data['title'] = _l('update_manufacturing_order_lable');
			$manufacturing_order = $this->manufacturing_model->get_manufacturing_order($id);
			
		$manufacturing_scrap = $this->manufacturing_model->get_scrap_by_manufacturing_order_id($id);
		
	    	$data['product_for_scrap'] = $manufacturing_scrap;
			$data['manufacturing_order'] = $manufacturing_order['manufacturing_order'];
			$data['product_tab_details'] = $manufacturing_order['manufacturing_order_detail'];
			$data['bill_of_materials'] = $this->manufacturing_model->get_list_bill_of_material_by_product($data['manufacturing_order']->product_id);

		}else{
			$data['title'] = _l('add_manufacturing_order_lable');
			$data['bill_of_materials'] = $this->manufacturing_model->get_bill_of_material_detail_with_product_name();
		}

		$data['products'] = $this->manufacturing_model->get_product();
		$data['units'] = $this->manufacturing_model->mrp_get_unit();
		$data['product_for_hansometable'] = $this->manufacturing_model->get_product_for_hansometable();
		$data['unit_for_hansometable'] = $this->manufacturing_model->get_unit_for_hansometable();
		$data['staffs'] = $this->staff_model->get();
		$data['warehouses'] = $this->warehouse_model->get_warehouse();
		$data['mo_code'] = $this->manufacturing_model->create_code('mo_code');

		$this->load->view('manufacturing/manufacturing_orders/add_edit_manufacturing_order', $data);
	}
	*/
	public function add_edit_manufacturing_order($id = '')
	{

		 if (!has_permission('manufacturing', '', 'view') && !is_admin()) {
			 access_denied('manufacturing_order');
		 }

		 $this->load->model('staff_model');
		 $this->load->model('warehouse/warehouse_model');

		 if ($this->input->post()) {
			 $data = $this->input->post();
			
			

		log_message('info', 'Available columns in manufacturing order: ' . implode(', ', array_keys($data)));
		
			$clientid = $this->input->post('clientid'); // define it first
			$estimate_id = $this->input->post('estimate_id');

		log_message('debug', 'Received clientid: ' . print_r($clientid, true));
		log_message('debug', 'Received estimate_id: ' . print_r($estimate_id, true));

		// Fallback: if clientid is missing, get it from the estimate
		if (empty($clientid) && !empty($estimate_id)) {
			$this->db->where('id', $estimate_id);
			$estimate = $this->db->get(db_prefix() . 'estimates')->row();

			if ($estimate) {
				log_message('debug', 'Fetched estimate for fallback: ' . json_encode($estimate));
			} else {
				log_message('error', 'Estimate not found for estimate_id: ' . $estimate_id);
			}

			if ($estimate && isset($estimate->clientid)) {
				$clientid = $estimate->clientid;
				log_message('debug', 'Fallback clientid from estimate: ' . $clientid);
			}
		}

		// Assign to contact_id if found
		if (!empty($clientid)) {
			$data['contact_id'] = $clientid;
			log_message('debug', 'Assigned contact_id: ' . $clientid);
		} else {
			log_message('error', 'Client ID is missing and could not be resolved from estimate.');
		}



		// Remove the old form field to prevent SQL error
		unset($data['clientid']);
		log_message('debug', 'Final data after cleanup: ' . json_encode($data));




			 log_message('info', 'POST Data received: ' . print_r($data, true));

			 if (empty($data['manufacturing_order_id'])) {
				 $data['manufacturing_order_id'] = $this->manufacturing_model->create_code('mo_code');
			 }


			 /*if (!empty($data['scrab_tab_hs'])) {	 
				 $scrap_data = json_decode($data['scrab_tab_hs'], true);
				 $data['scrap_data'] = $scrap_data; // You can pass it further for insertion if necessary
			 }*/


			 log_message('info', 'Processed Data: ' . print_r($data, true));

			 if ($id == '') {
				 // Check if the user has permission to create a new manufacturing order
				 if (!has_permission('manufacturing', '', 'create') && !is_admin()) {
					 access_denied('manufacturing_order');
				 }
				 	 log_message('info', 'Data for View: ' . print_r($data, true));

				 // Add manufacturing order and get the new ID
				 $id = $this->manufacturing_model->add_manufacturing_order($data);
		

				if ($id) {
		// Fetch BOM scrap data
		$bom_scrap = $this->manufacturing_model->get_scrap_details_by_bom($data['bom_id']);

		if (!empty($bom_scrap)) {
			foreach ($bom_scrap as $scrap) {
				$updated_quantity = $scrap['estimated_quantity'] * $data['product_qty']; // Adjust quantity

				$mo_scrap_data = [
					'manufacturing_order_id' => $id,
					'product_id' => $scrap['product_id'],
					'item_type' => $scrap['item_type'],
					'unit_id' => $scrap['unit_id'],
					'scrap_type' => $scrap['scrap_type'],
					'scrap_source' => 'bom',
					'estimated_quantity' => $updated_quantity, // Updated value
					'actual_quantity' => 0,  // Initially 0, updated later
					'scrap_value' => NULL,
					'cost_allocation' => NULL,
					'oee_impact' => NULL,
					'scrap_status' => 'pending',
					'scrap_location_id' => $scrap['scrap_location_id'],
					'reason' => $scrap['reason'],
					'bill_of_material_id' => $scrap['bill_of_material_id'],
					'routing_id' => $scrap['routing_id'],
					'operation_id' => $scrap['operation_id'],
					'bill_of_material_product_id' => $scrap['bill_of_material_product_id'],
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s')
				];

				// Insert into MO Scrap Table
				$this->manufacturing_model->addmo_scrap($mo_scrap_data);
			}
		}

   


					 set_alert('success', _l('mrp_added_successfully', _l('manufacturing_order')));
					 // Proper redirection after successful creation
					 redirect(admin_url('manufacturing/view_manufacturing_order/' . $id));
				 }
			 } else {
				 // Check if the user has permission to edit the manufacturing order
				 if (!has_permission('manufacturing', '', 'edit') && !is_admin()) {
					 access_denied('manufacturing_order');
				 }

				 // Update manufacturing order
				 $response = $this->manufacturing_model->update_manufacturing_order($data, $id);
				 if (is_array($response)) {
					 if (isset($response['cant_remove_main_admin'])) {
						 set_alert('warning', _l('staff_cant_remove_main_admin'));
					 } elseif (isset($response['cant_remove_yourself_from_admin'])) {
						 set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					 }
				 } elseif ($response == true) {
					 set_alert('success', _l('mrp_updated_successfully', _l('manufacturing_order')));
				 }

				 redirect(admin_url('manufacturing/view_manufacturing_order/' . $id));
			 }
		 }

		 // Preparing data for the view
		 $data = [];
		 if ($id != '') {
			 $data['title'] = _l('update_manufacturing_order_lable');
			 $manufacturing_order = $this->manufacturing_model->get_manufacturing_order($id);

			 // Log the fetched manufacturing order details
			 log_message('info', 'Fetched Manufacturing Order: ' . print_r($manufacturing_order, true));

			 // Fetch scrap details for this manufacturing order
			 $manufacturing_scrap = $this->manufacturing_model->get_scrap_by_manufacturing_order_id($id);
			 $data['product_for_scrap'] = $manufacturing_scrap;

			 // Log the fetched scrap details
			 log_message('info', 'Fetched Scrap Data: ' . print_r($manufacturing_scrap, true));

			 $data['manufacturing_order'] = $manufacturing_order['manufacturing_order'];
			 $data['product_tab_details'] = $manufacturing_order['manufacturing_order_detail'];
			 foreach ($data['product_tab_details'] as &$row) {
			if (!empty($row['warehouse_id']) && is_numeric($row['warehouse_id'])) {
				$warehouse = $this->db->select('warehouse_name')->from(db_prefix().'warehouse')->where('warehouse_id', $row['warehouse_id'])->get()->row();
				$row['warehouse_id'] = $warehouse ? $warehouse->warehouse_name : $row['warehouse_id'];
			}

			if (!empty($row['lot_id']) && is_numeric($row['lot_id'])) {
				$lot = $this->db->select('lot_name')->from(db_prefix().'wr_lot')->where('lot_id', $row['lot_id'])->get()->row();
				$row['lot_id'] = $lot ? $lot->lot_name : $row['lot_id'];
			}

			if (!empty($row['rack_id']) && is_numeric($row['rack_id'])) {
				$rack = $this->db->select('rack_name')->from(db_prefix().'wr_rack')->where('rack_id', $row['rack_id'])->get()->row();
				$row['rack_id'] = $rack ? $rack->rack_name : $row['rack_id'];
			}

			if (!empty($row['shelf_id']) && is_numeric($row['shelf_id'])) {
				$shelf = $this->db->select('shelf_name')->from(db_prefix().'wr_shelf')->where('shelf_id', $row['shelf_id'])->get()->row();
				$row['shelf_id'] = $shelf ? $shelf->shelf_name : $row['shelf_id'];
			}
		}

			 $data['bill_of_materials'] = $this->manufacturing_model->get_list_bill_of_material_by_product($data['manufacturing_order']->product_id);
		 } else {
			 $data['title'] = _l('add_manufacturing_order_lable');
			 $data['bill_of_materials'] = $this->manufacturing_model->get_bill_of_material_detail_with_product_name();
		 }

		 // Additional data for the form
		 $data['products'] = $this->manufacturing_model->get_product();
		 $data['estimate'] = $this->estimates_model->get();
		 $data['units'] = $this->manufacturing_model->mrp_get_unit();
		$data['customers'] = $this->clients_model->get();
		 $data['product_for_hansometable'] = $this->manufacturing_model->get_product_for_hansometable();
		 $data['unit_for_hansometable'] = $this->manufacturing_model->get_unit_for_hansometable();
		 $data['staffs'] = $this->staff_model->get();
		 $data['warehouses'] = $this->warehouse_model->get_warehouse();
		 $data['mo_code'] = $this->manufacturing_model->create_code('mo_code');

		 // Log all data to be passed to the view
	

		 // Load the view
		 $this->load->view('manufacturing/manufacturing_orders/add_edit_manufacturing_order', $data);
	}

public function get_inventory_dropdowns($commodity_id)
{
    $this->load->model('manufacturing_model');
    $data = $this->manufacturing_model->get_inventory_options($commodity_id);
    echo json_encode($data);
}




	/**
	 * delete manufacturing order
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_manufacturing_order($id)
	{
		if (!has_permission('manufacturing', '', 'delete')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$success = $this->manufacturing_model->delete_manufacturing_order($id);
		if ($success) {
			set_alert('success', _l('mrp_deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('manufacturing/manufacturing_order_manage'));
	}

	/**
	 * get data create manufacturing order
	 * @param  [type] $id 
	 * @return [type]     
	 */
/*	public function get_data_create_manufacturing_order($id)
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$results = $this->manufacturing_model->get_data_create_manufacturing_order($id);

		echo json_encode([
			'bill_of_material_option' =>$results['bill_of_material_option'],
			'routing_id' => $results['routing_option'],
			'routing_name' => mrp_get_routing_name($results['routing_option']),
			'component_arr' => $results['component_arr'],
			'component_row' => $results['component_row'],
			'unit_id' => $results['unit_id'],
		]);
	}*/
public function get_data_create_manufacturing_order($id)
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }

    $results = $this->manufacturing_model->get_data_create_manufacturing_order($id);

    $response = [
        'bill_of_material_option' => $results['bill_of_material_option'],
        'routing_id' => $results['routing_option'],
        'routing_name' => mrp_get_routing_name($results['routing_option']),
        'component_arr' => $results['component_arr'],
        'component_row' => $results['component_row'],
        'unit_id' => $results['unit_id'],
        'scrap_arr' => $results['scrap_arr'] ?? [],
        'scrap_row' => count($results['scrap_arr'] ?? []),
    ];

    echo json_encode($response);
}


	/**
	 * get bill of material detail
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_bill_of_material_detail($bill_of_material_id, $product_id, $product_qty='')
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		$component_arr=[];
		$routing_id=0;

		$product = $this->manufacturing_model->get_product($product_id);
		if($product){
			$component_arr = $this->manufacturing_model->get_bill_of_material_details_by_product($bill_of_material_id, $product->attributes, $product_qty);
		}
 $scrap_arr = $this->manufacturing_model->get_scrap_details_by_bom($bill_of_material_id);

		$bill_of_material = $this->manufacturing_model->get_bill_of_materials($bill_of_material_id);
		if($bill_of_material){
			$routing_id = $bill_of_material->routing_id;
		}

		echo json_encode([
			'component_arr' => $component_arr,
			'component_row' => count($component_arr),
			'scrap_arr' => $scrap_arr,
        'scrap_row' => count($scrap_arr),
			'routing_id' => $routing_id,
			'routing_name' => mrp_get_routing_name($routing_id),
		]);
	}


	/**
	 * view manufacturing order
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function view_manufacturing_order($id)
	{
		if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$manufacturing_order = $this->manufacturing_model->get_manufacturing_order($id);
		$manufacturing_scrap = $this->manufacturing_model->get_scrap_by_manufacturing_order_id($id);
		$data['manufacturing_order'] = $manufacturing_order['manufacturing_order'];
		$data['product_tab_details'] = $manufacturing_order['manufacturing_order_detail'];
		$data['product_for_scrap'] = $manufacturing_scrap;
		$data['product_for_hansometable'] = $this->manufacturing_model->get_product_for_hansometable();
		$manufacturing_order = $this->manufacturing_model->get_manufacturing_order($id);
		$data['unit_for_hansometable'] = $this->manufacturing_model->get_unit_for_hansometable();
		$data['manufacturing_order_costing'] = $this->manufacturing_model->get_manufacturing_order_costing($id);
		$check_manufacturing_order = $this->manufacturing_model->check_manufacturing_order_type($id);

		if($data['manufacturing_order']->status == 'confirmed'){
			$check_planned = $check_manufacturing_order['check_planned'];
		}else{
			$check_planned = false;
		}
		$data['check_planned'] = $check_planned;
		$data['check_mark_done'] = $check_manufacturing_order['check_mo_done'];
		$data['check_create_purchase_request'] = $check_manufacturing_order['check_create_purchase_request'];
		$data['check_availability'] = $check_manufacturing_order['check_availability'];
		$data['data_color'] = $check_manufacturing_order['data_color'];
		$data['title'] = _l('manufacturing_order_details');
		$data['currency'] = get_base_currency();

		//check pur order exist
		$pur_order_exist = false;
		if(is_numeric($data['manufacturing_order']->purchase_request_id)){
			$this->load->model('purchase/purchase_model');
			$get_purchase_request = $this->purchase_model->get_purchase_request($data['manufacturing_order']->purchase_request_id);
			if($get_purchase_request){
				$pur_order_exist = true;
			}
		}
		
		$data['pur_order_exist'] = $pur_order_exist;
		if (!$data['manufacturing_order']) {
			blank_page(_l('manufacturing_order'), 'danger');
		}

		$this->load->view('manufacturing/manufacturing_orders/view_manufacturing_order', $data);
	}

	/**
	 * mo mark as todo
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function mo_mark_as_todo($id, $type)
	{
		//Check inventory quantity => create purchase request on work order
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		if (!has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$mo_mark_as_todo = $this->manufacturing_model->mo_mark_as_todo($id, $type);

		if($mo_mark_as_todo['status']){
			$status='success';
			$message = _l('mrp_updated_successfully');
		}else{
			$status='warning';
			$message = $mo_mark_as_todo['message'];
		}

		echo json_encode([
			'status' => $status,
			'message' => $message,
		]);
	}

	/**
	 * mo mark as todo
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function mo_mark_as_planned($id)
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		if (!has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$mo_mark_as_planned = $this->manufacturing_model->mo_mark_as_planned($id);

		if($mo_mark_as_planned){
			$status='success';
			$message = _l('mrp_updated_successfully');
		}else{
			$status='warning';
			$message = _l('mrp_updated_failed');
		}

		echo json_encode([
			'status' => $status,
			'message' => $message,
		]);
	}

	/**
	 * work order manage
	 * @return [type] 
	 */
	public function work_order_manage()
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('manufacturing_order');
		}

		
		$data['title'] = _l('manufacturing_order');
		$data['products'] = $this->manufacturing_model->get_product();
		$data['routings'] = $this->manufacturing_model->get_routings();
		$data['customers'] = $this->clients_model->get();
        $data['operation_names'] = $this->manufacturing_model->get_distinct_operation_names();

		$status_data=[];
		$status_data[]=[
			'name' => 'waiting_for_another_wo',
			'label' => _l('waiting_for_another_wo'),
		];
		$status_data[]=[
			'name' => 'ready',
			'label' => _l('ready'),
		];
		$status_data[]=[
			'name' => 'in_progress',
			'label' => _l('in_progress'),
		];
		$status_data[]=[
			'name' => 'finished',
			'label' => _l('finished'),
		];
		$status_data[]=[
			'name' => 'pause',
			'label' => _l('pause'),
		];
		
		$data['status_data'] = $status_data;
		$data['manufacturing_orders'] = $this->manufacturing_model->get_list_manufacturing_order();

$data['oprater']=5;
		$required_inventory_purchase = mrp_required_inventory_purchase_module();
		//required inventory purchase
		if($required_inventory_purchase['inventory'] == false || $required_inventory_purchase['purchase'] == false){
			$this->load->view('manufacturing/settings/required_inventory_module', $data);
		}else{
			$this->load->view('manufacturing/work_orders/work_order_manage', $data);
		}
	}

	/**
	 * work order table
	 * @return [type] 
	 */
	public function work_order_table()
	{
		$this->app->get_table_data(module_views_path('manufacturing', 'work_orders/work_order_table'));
	}

	/**
	 * view work order
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function view_work_order($id, $manufacturing_order_id)
	{
		if (!has_permission('manufacturing', '', 'view') && !has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('work_order_label');
		}

		$data=[];
		$data['work_order'] = $this->manufacturing_model->get_work_order($id);
		$routing_detail_id = $data['work_order']->routing_detail_id;
		$routing_detail = $this->manufacturing_model->get_operation($routing_detail_id); 

		$data['routing_operation_description'] = $routing_detail ? $routing_detail->description : ''; 
 
		if (!$data['work_order']) {
			blank_page(_l('work_order_label'), 'danger');
		}
		// Log Work Order Operator Data
		$data['work_order_oprater'] = $this->manufacturing_model->get_work_order_with_operator($id);
    
    if ($data['work_order_oprater']) {
        log_message('info', 'Work Order Operator Retrieved: ' . json_encode($data['work_order_oprater']));
    } else {
        log_message('error', 'No Operator Found for Work Order ID: ' . $id);
    }
     // Fetch Scrap Data Based on Work Order Details
    $data['scrap_items'] = $this->manufacturing_model->get_work_order_scrap(
        $manufacturing_order_id, 
        $data['work_order']->routing_detail_id, 
       
    );

    log_message('info', 'Scrap Data Retrieved: ' . json_encode($data['scrap_items']));

    
		$data['work_order_file'] = $this->manufacturing_model->mrp_get_attachments_file($data['work_order']->routing_detail_id, 'mrp_operation');
		$work_order_prev_next = $this->manufacturing_model->get_work_order_previous_next($id, $manufacturing_order_id);
		$data['prev_id'] = $work_order_prev_next['prev_id'];
		$data['next_id'] = $work_order_prev_next['next_id'];
		$data['pager_value'] = $work_order_prev_next['pager_value'];
		$data['pager_limit'] = $work_order_prev_next['pager_limit'];
		$data['manufacturing_order_id'] = $manufacturing_order_id;
		$data['header'] = _l('work_order_label').' / '.mrp_get_manufacturing_code($manufacturing_order_id).' - '.mrp_get_product_name($data['work_order']->product_id).' - '.$data['work_order']->operation_name;
		$time_tracking_details = $this->manufacturing_model->get_time_tracking_details($id);
	$data['product_for_hansometable'] = $this->manufacturing_model->get_product_for_hansometable();
		$data['unit_for_hansometable'] = $this->manufacturing_model->get_unit_for_hansometable();
		$data['time_tracking_details'] = $time_tracking_details['time_trackings'];
		$data['rows'] = $time_tracking_details['rows'];
		$mo = $this->manufacturing_model->get_manufacturing_order($manufacturing_order_id);
		$check_mo_cancelled= false;
		if($mo['manufacturing_order']){
			if($mo['manufacturing_order']->status == 'cancelled'){
				$check_mo_cancelled= true;
			}
		}
		$data['check_mo_cancelled'] = $check_mo_cancelled;
 log_message('info', 'Work Order full data: ' . json_encode($data));
		$this->load->view('manufacturing/work_orders/view_work_order', $data);
	}

	/**
	 * mo mark as start working
	 * @param  [type] $work_order_id 
	 * @return [type]                
	 */
	public function mo_mark_as_start_working($work_order_id, $manufacturing_order)
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		if (!has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$current_time=date('Y-m-d H:i:s');

		$mo_mark_as_start_working = $this->manufacturing_model->update_work_order_status($work_order_id, ['status' => 'in_progress', 'date_start' => to_sql_date($current_time, true)]);
		//update MO to in process
		$this->manufacturing_model->update_manufacturing_order_status($manufacturing_order, ['status' => 'in_progress']);
		
		//Add time tracking
		$data_tracking=[
			'work_order_id' => $work_order_id,
			'from_date' => $current_time,
			'staff_id' => get_staff_user_id(),
		];
		$this->manufacturing_model->add_time_tracking($data_tracking);


		if($mo_mark_as_start_working){
			$status='success';
			$message = _l('mrp_updated_successfully');
		}else{
			$status='warning';
			$message = _l('mrp_updated_failed');
		}

		echo json_encode([
			'status' => $status,
			'message' => $message,
		]);
	}

	/**
	 * mo mark as mark pause
	 * @param  [type] $work_order_id 
	 * @return [type]                
	 */
	public function mo_mark_as_mark_pause($work_order_id)
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		if (!has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$mo_mark_as_start_working = $this->manufacturing_model->update_work_order_status($work_order_id, ['status' => 'pause']);

		$current_time=date('Y-m-d H:i:s');

		//Update time tracking
		$data_update=[
			'work_order_id' => $work_order_id,
			'to_date' => $current_time,
			'staff_id' => get_staff_user_id(),
		];
		$update_time_tracking = $this->manufacturing_model->update_time_tracking($work_order_id, $data_update);

		if($update_time_tracking){
			$status='success';
			$message = _l('mrp_updated_successfully');
		}else{
			$status='warning';
			$message = _l('mrp_updated_failed');
		}

		echo json_encode([
			'status' => $status,
			'message' => $message,
		]);
	}

	/**
	 * mo mark as mark done
	 * @param  [type] $work_order_id 
	 * @return [type]                
	 */
	public function mo_mark_as_mark_done($work_order_id, $manufacturing_order_id)
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		if (!has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$wo_mark_as_done = $this->manufacturing_model->wo_mark_as_done($work_order_id, $manufacturing_order_id);

		if($wo_mark_as_done){
			$status='success';
			$message = _l('mrp_updated_successfully');
		}else{
			$status='warning';
			$message = _l('mrp_updated_failed');
		}

		echo json_encode([
			'status' => $status,
			'message' => $message,
		]);
	}
	
	/**
	 * mo work order manage
	 * @return [type] 
	 */
	public function mo_work_order_manage($mo_id='')
	{
	    if (!has_permission('manufacturing', '', 'view') ) {
			access_denied('manufacturing_order');
		}

		
		$data['title'] = _l('manufacturing_order');
		$data['products'] = $this->manufacturing_model->get_product();
		$data['routings'] = $this->manufacturing_model->get_routings();
		$status_data=[];
		$status_data[]=[
			'name' => 'draft',
			'label' => _l('mrp_draft'),
		];
		$status_data[]=[
			'name' => 'planned',
			'label' => _l('mrp_planned'),
		];
		$status_data[]=[
			'name' => 'cancelled',
			'label' => _l('mrp_cancelled'),
		];
		$status_data[]=[
			'name' => 'confirmed',
			'label' => _l('mrp_confirmed'),
		];
		$status_data[]=[
			'name' => 'done',
			'label' => _l('mrp_done'),
		];
		$status_data[]=[
			'name' => 'in_progress',
			'label' => _l('mrp_in_progress'),
		];
		
		$data['status_data'] = $status_data;
		$data['manufacturing_orders'] = $this->manufacturing_model->get_list_manufacturing_order();
		$data['mo_id'] = $mo_id;
		$data['data_timeline'] = $this->manufacturing_model->get_work_order_timeline($mo_id);

		$this->load->view('manufacturing/manufacturing_orders/mo_list_work_order', $data);
	}

	/**
	 * mo work order table
	 * @return [type] 
	 */
	public function mo_work_order_table()
	{
		$this->app->get_table_data(module_views_path('manufacturing', 'manufacturing_orders/mo_list_work_order_table'));
	}

	/**
	 * mo mark as done
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function mo_mark_as_done($id, $quantity)
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		if (!has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$mo_mark_as_done = $this->manufacturing_model->mo_mark_as_done($id, (float)$quantity);

		if($mo_mark_as_done){
			$status='success';
			$message = _l('mrp_updated_successfully');
		}else{
			$status='warning';
			$message = _l('mrp_updated_failed');
		}

		echo json_encode([
			'status' => $status,
			'message' => $message,
		]);
	}

	/**
	 * mo create purchase request
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function mo_create_purchase_request($id)
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		if (!has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$purchase_request_id = $this->manufacturing_model->mo_create_purchase_request($id);

		if($purchase_request_id){
			//update Purchase request id to Manufacturing order
			$this->manufacturing_model->update_manufacturing_order_status($id, ['purchase_request_id' => $purchase_request_id]);

			$status='success';
			$message = _l('mrp_added_successfully');
		}else{
			$status='warning';
			$message = _l('mrp_added_failed');
		}

		echo json_encode([
			'status' => $status,
			'message' => $message,
		]);
	}

	/**
	 * mo mark as unreserved
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function mo_mark_as_unreserved($id)
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		if (!has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$mo_mark_as_unreserved = $this->manufacturing_model->mo_mark_as_unreserved($id);

		if($mo_mark_as_unreserved){
			$status='success';
			$message = _l('mrp_updated_successfully');
		}else{
			$status='warning';
			$message = _l('mrp_updated_failed');
		}

		echo json_encode([
			'status' => $status,
			'message' => $message,
		]);
	}

	/**
	 * mo mark as cancel
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function mo_mark_as_cancel($id)
	{
		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		if (!has_permission('manufacturing', '', 'create')  && !has_permission('manufacturing', '', 'edit')  && !is_admin()) {
			access_denied('manufacturing_order');
		}

		$mo_mark_as_cancel = $this->manufacturing_model->mo_mark_as_cancel($id);

		if($mo_mark_as_cancel){
			$status='success';
			$message = _l('mrp_updated_successfully');
		}else{
			$status='warning';
			$message = _l('mrp_updated_failed');
		}

		echo json_encode([
			'status' => $status,
			'message' => $message,
		]);
	}
	
	/**
	 * mrp product delete bulk action
	 * @return [type] 
	 */
	public function mrp_product_delete_bulk_action()
	{
		if (!is_staff_member()) {
			ajax_access_denied();
		}

		$total_deleted = 0;

		if ($this->input->post()) {

			$ids                   = $this->input->post('ids');
			$rel_type                   = $this->input->post('rel_type');

			/*check permission*/
			switch ($rel_type) {
				case 'commodity_list':
				if (!has_permission('manufacturing', '', 'delete') && !is_admin()) {
					access_denied('product');
				}
				break;

				case 'bill_of_material':
				if (!has_permission('manufacturing', '', 'delete') && !is_admin()) {
					access_denied('product');
				}
				break;

				case 'manufacturing_order':
				if (!has_permission('manufacturing', '', 'delete') && !is_admin()) {
					access_denied('product');
				}
				break;

				case 'component_bill_of_material':
				if (!has_permission('manufacturing', '', 'delete') && !is_admin()) {
					access_denied('product');
				}
				break;
				

				default:
				break;
			}

			/*delete data*/
			if ($this->input->post('mass_delete')) {
				if (is_array($ids)) {
					switch ($rel_type) {
						case 'commodity_list':
							foreach ($ids as $id) {
								if ($this->manufacturing_model->delete_product($id, 'product')) {
									$total_deleted++;
								}
							}

							break;

						case 'bill_of_material':

							$this->db->where('bill_of_material_id IN ('.implode(",",$ids) .')');
							$this->db->delete(db_prefix() . 'mrp_bill_of_material_details');
							$delete_bom_detail = $this->db->affected_rows();
							
							//delete data
							$this->db->where('id IN ('.implode(",",$ids) .')');
							$this->db->delete(db_prefix() . 'mrp_bill_of_materials');
							$delete_bom = $this->db->affected_rows();
							if ($delete_bom > 0) {
								$total_deleted += $delete_bom;
							}

							break;

						case 'manufacturing_order':
							foreach ($ids as $id) {
								if ($this->manufacturing_model->delete_manufacturing_order($id)) {
									$total_deleted++;
								}
							}

							break;

						case 'component_bill_of_material':

							$this->db->where('id IN ('.implode(",",$ids) .')');
							$this->db->delete(db_prefix() . 'mrp_bill_of_material_details');
							$delete_bom_detail = $this->db->affected_rows();
							
							if ($delete_bom_detail > 0) {
								$total_deleted += $delete_bom_detail;
							}

							break;
						
						default:
							# code...
							break;
					}

				}

				/*return result*/
				switch ($rel_type) {
					case 'commodity_list':
					set_alert('success', _l('total_product'). ": " .$total_deleted);
					break;

					case 'bill_of_material':
					set_alert('success', _l('total_bill_of_material'). ": " .$total_deleted);
					break;
					
					case 'manufacturing_order':
					set_alert('success', _l('total_manufacturing_order'). ": " .$total_deleted);
					break;

					case 'component_bill_of_material':
					set_alert('success', _l('total_component_bill_of_material'). ": " .$total_deleted);
					break;
					

					default:
					break;

				}


			}

		}

	}

	/**
	 * item print barcode
	 * @return [type] 
	 */
	public function item_print_barcode()
	{
		$data = $this->input->post();

		$stock_export = $this->manufacturing_model->get_print_barcode_pdf_html($data);
		
		try {
			$pdf = $this->manufacturing_model->print_barcode_pdf($stock_export);

		} catch (Exception $e) {
			echo new_html_entity_decode($e->getMessage());
			die;
		}

		$type = 'I';

		if ($this->input->get('output_type')) {
			$type = $this->input->get('output_type');
		}

		if ($this->input->get('print')) {
			$type = 'I';
		}


		$pdf->Output('print_barcode_'.strtotime(date('Y-m-d H:i:s')).'.pdf', $type);

	}

	/**
	 * dashboard
	 * @return [type] 
	 */
	public function dashboard()
	{
	    if (!has_permission('manufacturing', '', 'view')  && !is_admin()) {
			access_denied('dashboard');
		}

		$data['title'] = _l('dashboard');
		$data['work_centers'] = $this->manufacturing_model->dasboard_get_work_center();

		$mo_measures_type=[];
		
		$mo_measures_type[]=[
			'name' => 'count',
			'label' => _l('count'),
		];
		$mo_measures_type[]=[
			'name' => 'total_qty',
			'label' => _l('total_qty'),
		];

		$wo_measures_type=[];
		
		$wo_measures_type[]=[
			'name' => 'count',
			'label' => _l('count'),
		];
		
		$wo_measures_type[]=[
			'name' => 'duration_per_unit',
			'label' => _l('duration_per_unit'),
		];
		$wo_measures_type[]=[
			'name' => 'expected_duration',
			'label' => _l('expected_duration'),
		];
		$wo_measures_type[]=[
			'name' => 'quantity',
			'label' => _l('quantity'),
		];
		$wo_measures_type[]=[
			'name' => 'real_duration',
			'label' => _l('real_duration'),
		];
		
		$data['mo_measures_type'] = $mo_measures_type;
		$data['wo_measures_type'] = $wo_measures_type;

		$required_inventory_purchase = mrp_required_inventory_purchase_module();
		//required inventory purchase
		if($required_inventory_purchase['inventory'] == false || $required_inventory_purchase['purchase'] == false){
			$this->load->view('manufacturing/settings/required_inventory_module', $data);
		}else{
			$this->load->view('manufacturing/dashboards/dashboard', $data);
		}

	}

	/**
	 * report by manufacturing order
	 * @param  [type] $sort_from     
	 * @param  string $months_report 
	 * @param  string $report_from   
	 * @param  string $report_to     
	 * @return [type]                
	 */
	public function report_by_manufacturing_order()
	{
		if ($this->input->is_ajax_request()) { 
			$data = $this->input->get();

			$mo_measures = $data['mo_measures'];
			$months_report = $data['months_report'];
			$report_from = $data['report_from'];
			$report_to = $data['report_to'];

			if($months_report == ''){

				$from_date = date('Y-m-d', strtotime('1997-01-01'));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'this_month'){
				$from_date = date('Y-m-01');
				$to_date   = date('Y-m-t');
			}

			if($months_report == '1'){ 
				$from_date = date('Y-m-01', strtotime('first day of last month'));
				$to_date   = date('Y-m-t', strtotime('last day of last month'));
			}

			if($months_report == 'this_year'){
				$from_date = date('Y-m-d', strtotime(date('Y-01-01')));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'last_year'){

				$from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
				$to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  


			}

			if($months_report == '3'){
				$months_report = 3;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');

			}

			if($months_report == '6'){
				$months_report = 6;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '12'){
				$months_report = 12;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == 'custom'){
				$from_date = to_sql_date($report_from);
				$to_date   = to_sql_date($report_to);
			}
	
			$mo_data = $this->manufacturing_model->get_mo_report_data($mo_measures, $from_date, $to_date);


			echo json_encode([
				'categories' => $mo_data['categories'],
				'draft' => $mo_data['draft'],
				'planned' => $mo_data['planned'],
				'cancelled' => $mo_data['cancelled'],
				'confirmed' => $mo_data['confirmed'],
				'done' => $mo_data['done'],
				'in_progress' => $mo_data['in_progress'],
			]); 
		}
	}

	/**
	 * report by work order
	 * @return [type] 
	 */
	public function report_by_work_order()
	{
		if ($this->input->is_ajax_request()) { 
			$data = $this->input->get();

			$mo_measures = $data['wo_measures'];
			$months_report = $data['months_report'];
			$report_from = $data['report_from'];
			$report_to = $data['report_to'];

			if($months_report == ''){

				$from_date = date('Y-m-d', strtotime('1997-01-01'));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'this_month'){
				$from_date = date('Y-m-01');
				$to_date   = date('Y-m-t');
			}

			if($months_report == '1'){ 
				$from_date = date('Y-m-01', strtotime('first day of last month'));
				$to_date   = date('Y-m-t', strtotime('last day of last month'));
			}

			if($months_report == 'this_year'){
				$from_date = date('Y-m-d', strtotime(date('Y-01-01')));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'last_year'){

				$from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
				$to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  


			}

			if($months_report == '3'){
				$months_report = 3;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');

			}

			if($months_report == '6'){
				$months_report = 6;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '12'){
				$months_report = 12;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == 'custom'){
				$from_date = to_sql_date($report_from);
				$to_date   = to_sql_date($report_to);
			}
	
			$mo_data = $this->manufacturing_model->get_wo_report_data($mo_measures, $from_date, $to_date);


			echo json_encode([
				'categories' => $mo_data['categories'],
				'mo_data' => $mo_data['mo_data'],

			]); 
		}
	}

	/**
	 * prefix number
	 * @return [type] 
	 */
	public function prefix_number()
	{
		if (!has_permission('manufacturing', '', 'edit') && !is_admin() && !has_permission('manufacturing', '', 'create')) {
			access_denied('manufacturing');
		}

		$data = $this->input->post();

		if ($data) {

			$success = $this->manufacturing_model->update_prefix_number($data);

			if ($success == true) {

				$message = _l('mrp_updated_successfully');
				set_alert('success', $message);
			}

			redirect(admin_url('manufacturing/setting?group=prefix_number'));
		}
	}

	public function view_product_detail($product_id) {
		$commodity_item = get_commodity_name($product_id);

		if (!$commodity_item) {
			blank_page('Product item Not Found', 'danger');
		}

		$this->load->model('warehouse/warehouse_model');

		//user for sub
		$data['units'] = $this->warehouse_model->get_unit_add_commodity();
		$data['commodity_types'] = $this->warehouse_model->get_commodity_type_add_commodity();
		$data['commodity_groups'] = $this->warehouse_model->get_commodity_group_add_commodity();
		$data['warehouses'] = $this->warehouse_model->get_warehouse_add_commodity();
		$data['taxes'] = get_taxes();
		$data['styles'] = $this->warehouse_model->get_style_add_commodity();
		$data['models'] = $this->warehouse_model->get_body_add_commodity();
		$data['sizes'] = $this->warehouse_model->get_size_add_commodity();
		$data['sub_groups'] = $this->warehouse_model->get_sub_group();
		$data['colors'] = $this->warehouse_model->get_color_add_commodity();
		$data['item_tags'] = $this->warehouse_model->get_item_tag_filter();
		$data['commodity_filter'] = $this->warehouse_model->get_commodity_active();
		$data['title'] = _l("item_detail");


		$data['commodity_item'] = $commodity_item;
		$data['commodity_file'] = $this->warehouse_model->get_warehourse_attachments($product_id);

		$this->load->view('products/view_product_detail', $data);

	}

	public function table_commodity_list() {
		$this->app->get_table_data(module_views_path('manufacturing', 'products/view_table_product_detail'));
	}

	/**
	 * bom change log table
	 * @return [type] 
	 */
	public function bom_change_log_table()
	{
		$this->app->get_table_data(module_views_path('manufacturing', 'manufacturing_orders/bom_change_logs/bom_change_log_table'));
	}

//end file
}

