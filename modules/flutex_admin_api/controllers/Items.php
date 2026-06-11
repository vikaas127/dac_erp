<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';

use FlutexAdminApi\RestController;

class Items extends RestController
{
    protected $staffInfo;

    public function __construct()
    {
        parent::__construct();
        register_language_files('flutex_admin_api');
        load_admin_language();
        
        $this->load->helper('flutex_admin_api');
        if (!isset(isAuthorized()['status'])) {
            $this->response(isAuthorized()['response'], isAuthorized()['response_code']);
        }

        $this->staffInfo = isAuthorized();

       /* if (staff_cant('view', 'items', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }*/
    }
    
 public function items_get()
{
$requestData = $this->get();


if (!empty($requestData) && !array_key_exists('id', $requestData)) {
    return $this->response([
        'status'  => false,
        'message' => _l('something_went_wrong')
    ], RestController::HTTP_BAD_REQUEST);
}

$itemID = $this->get('id');

$this->load->model('Invoice_items_model');

$itemData = $this->Invoice_items_model->get($itemID);

if (empty($itemData)) {
    return $this->response([
        'status'  => false,
        'message' => _l('data_not_found')
    ], RestController::HTTP_NOT_FOUND);
}

// LIST API
if (is_array($itemData)) {

    foreach ($itemData as &$item) {

        $master = $this->db
            ->where('id', $item['itemid'])
            ->get(db_prefix() . 'items')
            ->row();

        if ($master) {

            $item['purchase_price'] =
                $master->purchase_price ?? '0.00';

            $item['sku_code'] =
                $master->sku_code ?? '';

            $item['commodity_code'] =
                $master->commodity_code ?? '';

            $item['warehouse_id'] =
                $master->warehouse_id ?? '';

            $item['unit_id'] =
                $master->unit_id ?? '';

            $item['commodity_type'] =
                $master->commodity_type ?? '';

            $item['series_id'] =
                $master->series_id ?? '';

            // Unit Name
            if (!empty($master->unit_id)) {

                $unit = $this->db
                    ->select('unit_name')
                    ->where('unit_type_id', $master->unit_id)
                    ->get(db_prefix() . 'ware_unit_type')
                    ->row();

                $item['unit_name'] =
                    $unit->unit_name ?? '';
            }

            // Total Stock
            $stock = $this->db
                ->select_sum('inventory_number')
                ->where('commodity_id', $item['itemid'])
                ->get(db_prefix() . 'inventory_manage')
                ->row();

            $item['available_stock'] =
                $stock->inventory_number ?? 0;
        }

        $item['gst_rate'] =
            (float)($item['taxrate'] ?? 0) +
            (float)($item['taxrate_2'] ?? 0);

        $item['gst_label'] =
            $item['gst_rate'] . '%';
    }
}

// DETAIL API
elseif (is_object($itemData)) {

    $itemMaster = $this->db
        ->where('id', $itemData->itemid)
        ->get(db_prefix() . 'items')
        ->row();

    if ($itemMaster) {

        $itemData->purchase_price =
            $itemMaster->purchase_price ?? '0.00';

        $itemData->sku_code =
            $itemMaster->sku_code ?? '';

        $itemData->commodity_code =
            $itemMaster->commodity_code ?? '';

        $itemData->warehouse_id =
            $itemMaster->warehouse_id ?? '';

        $itemData->unit_id =
            $itemMaster->unit_id ?? '';

        $itemData->commodity_type =
            $itemMaster->commodity_type ?? '';

        $itemData->series_id =
            $itemMaster->series_id ?? '';

        // Warehouse
        if (!empty($itemMaster->warehouse_id)) {

            $warehouse = $this->db
                ->select('warehouse_name')
                ->where('warehouse_id', $itemMaster->warehouse_id)
                ->get(db_prefix() . 'warehouse')
                ->row();

            $itemData->warehouse_name =
                $warehouse->warehouse_name ?? '';
        }

        // Unit
        if (!empty($itemMaster->unit_id)) {

            $unit = $this->db
                ->select('unit_name')
                ->where('unit_type_id', $itemMaster->unit_id)
                ->get(db_prefix() . 'ware_unit_type')
                ->row();

            $itemData->unit_name =
                $unit->unit_name ?? '';
        }

        // Commodity Type
        if (!empty($itemMaster->commodity_type)) {

            $commodityType = $this->db
                ->select('commondity_name')
                ->where('commodity_type_id', $itemMaster->commodity_type)
                ->get(db_prefix() . 'ware_commodity_type')
                ->row();

            $itemData->commodity_type_name =
                $commodityType->commondity_name ?? '';
        }

        // Brand
        if (!empty($itemMaster->brand_id)) {

            $brand = $this->db
                ->select('name')
                ->where('id', $itemMaster->brand_id)
                ->get(db_prefix() . 'wh_brand')
                ->row();

            $itemData->brand_name =
                $brand->name ?? '';
        }

        // Model
        if (!empty($itemMaster->model_id)) {

            $model = $this->db
                ->select('name')
                ->where('id', $itemMaster->model_id)
                ->get(db_prefix() . 'wh_model')
                ->row();

            $itemData->model_name =
                $model->name ?? '';
        }

        // Series
        if (!empty($itemMaster->series_id)) {

            $series = $this->db
                ->select('name')
                ->where('id', $itemMaster->series_id)
                ->get(db_prefix() . 'wh_series')
                ->row();

            $itemData->series_name =
                $series->name ?? '';
        }

        // Available Stock
        $stock = $this->db
            ->select_sum('inventory_number')
            ->where('commodity_id', $itemData->itemid)
            ->get(db_prefix() . 'inventory_manage')
            ->row();

        $itemData->available_stock =
            $stock->inventory_number ?? 0;
    }

    $itemData->gst_rate =
        (float)($itemData->taxrate ?? 0) +
        (float)($itemData->taxrate_2 ?? 0);

    $itemData->gst_label =
        $itemData->gst_rate . '%';
}

return $this->response([
    'status'  => true,
    'message' => _l('data_retrieved_successfully'),
    'data'    => $itemData
], RestController::HTTP_OK);


}


   public function ditems_get()
{
    try {

        log_message('error', '📥 ditems_get API called');

        // 🔐 AUTH CHECK
        $auth = isAuthorized();
        log_message('error', '🔐 Auth Response: ' . json_encode($auth));

        if (!isset($auth['status']) || !$auth['status']) {
            log_message('error', '❌ Auth Failed');
            return $this->response($auth['response'], $auth['response_code']);
        }

        $itemID = $this->get('id');
        log_message('error', '📦 Item ID: ' . json_encode($itemID));

        $this->load->model('Invoice_items_model');

        // 🔥 SAFE UN-SERIALIZE
        $safe_unserialize = function ($data) {
            if (empty($data)) return [];

            if (is_array($data)) return $data;

            if (is_string($data)) {
                $res = @unserialize($data);
                return ($res !== false && is_array($res)) ? $res : [];
            }

            return [];
        };

        $CI = &get_instance();

        // 🔹 SETTINGS FETCH
        $g = $CI->db->where('setting_name', 'dc_allowed_item_groups')
            ->get(db_prefix() . 'dc_settings')->row();

        $sg = $CI->db->where('setting_name', 'dc_allowed_item_subgroups')
            ->get(db_prefix() . 'dc_settings')->row();

        $t = $CI->db->where('setting_name', 'dc_allowed_item_types')
            ->get(db_prefix() . 'dc_settings')->row();

        $p = $CI->db->where('setting_name', 'dc_show_price_on_client')
            ->get(db_prefix() . 'dc_settings')->row();

        $allowed_groups    = $safe_unserialize($g->setting_value ?? []);
        $allowed_subgroups = $safe_unserialize($sg->setting_value ?? []);
        $allowed_types     = $safe_unserialize($t->setting_value ?? []);
        $show_price        = ($p && $p->setting_value == '1');

        log_message('error', '⚙️ SETTINGS: ' . json_encode([
            'groups' => $allowed_groups,
            'subgroups' => $allowed_subgroups,
            'types' => $allowed_types,
            'show_price' => $show_price
        ]));

        // 🔹 GET ITEMS
       // 🔹 FILTERS
$group      = $this->get('group');
$sub_group  = $this->get('sub_group');
$category   = $this->get('category');
$unit       = $this->get('unit');
$godown     = $this->get('godown');

// 🔹 ITEM QUERY
$this->db->from(db_prefix() . 'items');

if (!empty($itemID)) {
    $this->db->where('itemid', $itemID);
}

if ($group) {
    $this->db->where('group_id', $group);
}

if ($sub_group) {
    $this->db->where('sub_group', $sub_group);
}

if ($category) {
    $this->db->where('commodity_type', $category);
}

if ($unit) {
    $this->db->where('unit_id', $unit);
}

if ($godown) {
    $this->db->where('warehouse_id', $godown);
}

$items = $this->db->get()->result_array();

        log_message('error', '📦 Raw Items Count: ' . count($items));

        $filteredItems = [];

        foreach ($items as $item) {

            if (empty($item)) {
                log_message('error', '⚠️ Empty item skipped');
                continue;
            }

            $item = (array)$item;

            // ✅ GROUP FILTER
            if (!empty($allowed_groups) && !in_array($item['group_id'], $allowed_groups)) {
                continue;
            }

            // ✅ SUBGROUP FILTER
            if (!empty($allowed_subgroups) && isset($item['sub_group_id']) &&
                !in_array($item['sub_group_id'], $allowed_subgroups)) {
                continue;
            }

            // ✅ TYPE FILTER
            if (!empty($allowed_types) && isset($item['commodity_type_id']) &&
                !in_array($item['commodity_type_id'], $allowed_types)) {
                continue;
            }

            // 🔥 PRICE CONTROL
            if (!$show_price) {
                unset($item['rate']);
                unset($item['taxrate']);
                unset($item['taxrate_2']);
            }

            // OPTIONAL FLAG (UI use)
            $item['show_price'] = $show_price;

            $filteredItems[] = $item;
        }

        log_message('error', '📊 Filtered Count: ' . count($filteredItems));

        // 🔹 RESPONSE
        if (!empty($filteredItems)) {
            return $this->response([
                'status'  => true,
                'message' => 'Items fetched successfully',
                'data'    => $filteredItems
            ], RestController::HTTP_OK);
        } else {
            log_message('error', '⚠️ No items after filtering');
            return $this->response([
                'status'  => false,
                'message' => 'No items found as per settings'
            ], RestController::HTTP_NOT_FOUND);
        }

    } catch (\Throwable $th) {

        log_message('error', '🔥 ditems_get ERROR: ' . $th->getMessage());
        log_message('error', '🔥 TRACE: ' . $th->getTraceAsString());

        return $this->response([
            'status'  => false,
            'message' => 'Something went wrong'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
} 
public function groups_get()
{
    try {

        $data = $this->db
            ->select('id,name,commodity_group_code')
            ->from(db_prefix() . 'items_groups')
            ->where('display', 1)
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();

        return $this->response([
            'status' => true,
            'message' => 'Groups fetched successfully',
            'data' => $data
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        return $this->response([
            'status' => false,
            'message' => $th->getMessage()
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

public function sub_groups_get()
{
    try {

        $data = $this->db
            ->select('
                '.db_prefix().'wh_sub_group.id,
                '.db_prefix().'wh_sub_group.sub_group_name,
                '.db_prefix().'wh_sub_group.group_id,
                '.db_prefix().'items_groups.name as group_name
            ')
            ->from(db_prefix() . 'wh_sub_group')
            ->join(
                db_prefix().'items_groups',
                db_prefix().'items_groups.id = '.db_prefix().'wh_sub_group.group_id',
                'left'
            )
            ->where(db_prefix().'wh_sub_group.display', 1)
            ->order_by(db_prefix().'wh_sub_group.sub_group_name', 'ASC')
            ->get()
            ->result_array();

        return $this->response([
            'status' => true,
            'message' => 'Sub groups fetched successfully',
            'data' => $data
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        return $this->response([
            'status' => false,
            'message' => $th->getMessage()
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

public function categories_get()
{
    try {

        $data = $this->db
            ->select('commodity_type_id,commondity_name')
            ->from(db_prefix() . 'ware_commodity_type')
            ->where('display', 1)
            ->order_by('commondity_name', 'ASC')
            ->get()
            ->result_array();

        return $this->response([
            'status' => true,
            'message' => 'Categories fetched successfully',
            'data' => $data
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        return $this->response([
            'status' => false,
            'message' => $th->getMessage()
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

public function units_get()
{
    try {

        $data = $this->db
            ->select('unit_type_id,unit_name,unit_symbol')
            ->from(db_prefix() . 'ware_unit_type')
            ->where('display', 1)
            ->order_by('unit_name', 'ASC')
            ->get()
            ->result_array();

        return $this->response([
            'status' => true,
            'message' => 'Units fetched successfully',
            'data' => $data
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        return $this->response([
            'status' => false,
            'message' => $th->getMessage()
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

public function godowns_get()
{
    try {

        $data = $this->db
            ->select('warehouse_id,warehouse_name,warehouse_code')
            ->from(db_prefix() . 'warehouse')
            ->where('display', 1)
            ->order_by('warehouse_name', 'ASC')
            ->get()
            ->result_array();

        return $this->response([
            'status' => true,
            'message' => 'Godowns fetched successfully',
            'data' => $data
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        return $this->response([
            'status' => false,
            'message' => $th->getMessage()
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}
    
    public function search_get()
    {
        try {
            
            if (!empty($this->get()) && !in_array('search', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
            }
            
            $keySearch = $this->get('search');
            
            $where = '';
            
            if ($keySearch) {
                $keySearch = trim(urldecode($keySearch));
                $keySearch = $this->db->escape_like_str($keySearch);
                $this->db->like('description', $keySearch);
                $this->db->or_like('long_description', $keySearch);
            }
            
            $itemsData = $this->db->get(db_prefix() . 'items')->result_array();
            
            if (!empty($itemsData)) {
                $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $itemsData], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function items_post()
    {
        if (staff_cant('create', 'items', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
        
        try {
            
            $this->form_validation->set_rules('description', 'Description', 'required|max_length[255]');
		    $this->form_validation->set_rules('rate', 'Rate', 'required|greater_than[0]');
            
            if (!$this->form_validation->run()) {
                $this->response(['message' => strip_tags(validation_errors()),'error' => $this->form_validation->error_array()], RestController::HTTP_BAD_REQUEST);
            } else {
                $data = [
                    'description' => $this->input->post('description'),
                    'rate' => $this->input->post('rate'),
                    'long_description' => $this->input->post('long_description')??'',
                    'unit' => $this->input->post('unit')??'',
                    'tax' => $this->input->post('tax')??'',
                    'tax2' => $this->input->post('tax2')??'',
                ];
                $group_id = $this->input->post('group_id') ?? '';
                if ($group_id != '') {
                    $data['group_id'] = $group_id;
                }
                
                $this->load->model('Invoice_items_model');
                $success = $this->Invoice_items_model->add($data);
                if ($success) {
                    $this->response(['message' => _l('item_added_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('item_add_failed')], RestController::HTTP_NOT_FOUND);
                }
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function items_put()
    {
        if (staff_cant('edit', 'items', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
        
        try {
            
            if (!empty($this->get()) && !in_array('id', array_keys($this->get()))) {
                $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_BAD_REQUEST);
            }
            
            $itemID = $this->get('id');
            $this->load->model('Invoice_items_model');
            $item = $this->Invoice_items_model->get($itemID);
            
            if (is_object($item)) {
                $data = array();
                parse_str(file_get_contents('php://input'), $data);
                $data['itemid'] = $itemID;
                $success = $this->Invoice_items_model->edit($data);
                if ($success) {
                    $this->response(['message' => _l('item_updated_successfully')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('item_update_failed')], RestController::HTTP_NOT_FOUND);
                }
            } else {
                $this->response(['message' => _l('invalid_item_id')], RestController::HTTP_NOT_FOUND);
            }
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function items_delete()
    {
        $itemID = $this->get('id');
        
        if (staff_cant('delete', 'items', $this->staffInfo['data']->staff_id)) {
            $this->response(['message' => _l('not_permission_to_perform_this_action')], RestController::HTTP_FORBIDDEN);
        }
        
        $this->load->model('Invoice_items_model');
        $item = $this->Invoice_items_model->get($itemID);
        if (is_object($item)) {
            $output = $this->Invoice_items_model->delete($itemID);
            if ($output === TRUE) {
                $this->response(['message' => _l('item_deleted_successfully')], RestController::HTTP_OK);
            } else {
                $this->response(['message' => _l('item_delete_failed')], RestController::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(['message' => _l('invalid_item_id')], RestController::HTTP_NOT_FOUND);
        }
    }
}