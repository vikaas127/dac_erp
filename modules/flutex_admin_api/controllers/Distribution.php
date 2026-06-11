<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__ . '/RestController.php';

use FlutexAdminApi\RestController;

class Distribution extends RestController
{
    protected $staffInfo;

    public function __construct()
    {
        parent::__construct();

        register_language_files('flutex_admin_api');
        load_admin_language();

        $this->load->helper('flutex_admin_api');
        $this->load->helper('admin');
        $this->load->helper('distribution_channel/dc_schema');
        if (!function_exists('dc_ensure_mobile_api_schema')) {
            $schemaHelper = module_dir_path('distribution_channel', 'helpers/dc_schema_helper.php');
            if (is_file($schemaHelper)) {
                require_once $schemaHelper;
            }
        }
        try {
            dc_ensure_mobile_api_schema();
        } catch (\Throwable $e) {
            log_message('error', '[Distribution][schema] '.$e->getMessage());
        }
        $dsrOrderHelper = module_dir_path('distribution_channel', 'helpers/dsr_order_helper.php');
        if (is_file($dsrOrderHelper)) {
            require_once $dsrOrderHelper;
        }
        $this->load->model('distribution_channel/channel_orders_model');
        $this->load->model('distribution_channel/territories_model');
        $this->load->model('distribution_channel/sales_area_model');
        $this->load->model('distribution_channel/sales_targets_model');
        $this->load->model('distribution_channel/sales_assignment_model');
        $this->load->model('distribution_channel/distributor_targets_model');

        /* AUTH CHECK */

        $auth = isAuthorized();

        if (!isset($auth['status'])) {
            $this->response(
                $auth['response'],
                $auth['response_code']
            );
        }

        $this->staffInfo = $auth;
    }
 public function pdf_get($id)
{
    log_message('info', '================ CHANNEL ORDER PDF START ================');

    if (!$id) {
        show_error('Order ID required', 400);
        return;
    }

    $staff_id = $this->staffInfo['data']->staff_id ?? 0;

    // 🚫 Clean buffer
    $this->output->enable_profiler(false);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // 🔎 Load model
    $this->load->model('distribution_channel/Channel_orders_model');

    $order = $this->Channel_orders_model->get_order($id);

    if (!$order) {
        show_error('Order not found', 404);
        return;
    }

    // ✅ Attach items
    $order['items'] = $this->Channel_orders_model->get_order_items($id);

    // ✅ Load PDF
   require_once(APPPATH . 'libraries/pdf/Channel_order_pdf.php');

$pdf = new Channel_order_pdf($order);
   
    $pdf->prepare();

    // 🔥 MODE (view / download)
    $mode = $this->get('mode') ?? $this->input->get('mode');

    $filename = 'channel_order_'.$id.'.pdf';

    header('Content-Type: application/pdf');

    if ($mode === 'download' || $mode === 'share') {
        header('Content-Disposition: attachment; filename="'.$filename.'"');
    } else {
        header('Content-Disposition: inline; filename="'.$filename.'"');
    }

    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

  $pdfContent = $pdf->output('', 'S'); // get raw PDF string

log_message('info', 'PDF Size: ' . strlen($pdfContent));

if (strlen($pdfContent) < 1000) {
    log_message('error', 'Invalid PDF generated!');
}

// Clean again (safety)
while (ob_get_level()) {
    ob_end_clean();
}

// Send headers properly
header('Content-Type: application/pdf');
header('Content-Length: ' . strlen($pdfContent));
header('Content-Disposition: inline; filename="'.$filename.'"');

// Output PDF
echo $pdfContent;
exit;
}
    /* ==================================
       GET ALL ORDERS
    ================================== */
public function territories_get()
{
    try {

        $data      = $this->territories_model->get();
        $staff_id  = $this->auth_staff_id();

        if ($staff_id && $this->sales_assignment_model->should_scope_customers($staff_id)) {
            $scope = $this->sales_assignment_model->get_staff_scope($staff_id);
            if (!empty($scope['visible_territory_ids'])) {
                $allowed = array_flip($scope['visible_territory_ids']);
                $data    = array_values(array_filter($data, function ($row) use ($allowed) {
                    return isset($allowed[(int) ($row['id'] ?? 0)]);
                }));
            } else {
                $data = [];
            }
        }

        $this->response([
            'status' => true,
            'data'   => $data
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {
        log_message('error', '[Distribution][territories_get] '.$th->getMessage());

        $this->response([
            'status' => false,
            'message' => 'Failed to fetch territories'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

public function sales_areas_get()
{
    try {

        $territory_id = $this->get('territory_id');
        $staff_id     = $this->auth_staff_id();

        if ($staff_id && $this->sales_assignment_model->should_scope_customers($staff_id)) {
            $scope = $this->sales_assignment_model->get_staff_scope($staff_id);
            if (!empty($scope['sales_area_ids'])) {
                $this->db->where_in('id', $scope['sales_area_ids']);
            } elseif (!empty($scope['territory_ids'])) {
                $this->db->where_in('territory_id', $scope['territory_ids']);
            } else {
                $this->db->where('1=0', null, false);
            }
        }

        if ($territory_id) {
            $this->db->where('territory_id', (int) $territory_id);
        }

        $data = $this->db->get(db_prefix().'sales_area')->result_array();

        $this->response([
            'status' => true,
            'data'   => $data
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        $this->response([
            'status' => false,
            'message' => 'Failed to fetch sales areas'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}
public function client_master_details_get()
{
    try {

        $client_id = $this->get('client_id');

        if (empty($client_id)) {
            return $this->response([
                'status' => false,
                'message' => 'client_id is required'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $this->db->select('
            c.userid,
            c.company,

            c.sales_area_id,
            sa.name as sales_area_name,

            c.territory_id,
            t.name as territory_name,

            c.parent_partner_id,
            pp.company as parent_partner_name,
            pp.partner_type as parent_partner_type
        ');

        $this->db->from(db_prefix().'clients as c');

        $this->db->join(
            db_prefix().'sales_area as sa',
            'sa.id = c.sales_area_id',
            'left'
        );

        $this->db->join(
            db_prefix().'territories as t',
            't.id = c.territory_id',
            'left'
        );

        $this->db->join(
            db_prefix().'clients as pp',
            'pp.userid = c.parent_partner_id',
            'left'
        );

        $this->db->where('c.userid', $client_id);

        $result = $this->db->get()->row_array();

        if (!$result) {
            return $this->response([
                'status' => false,
                'message' => 'Client not found'
            ], RestController::HTTP_NOT_FOUND);
        }

        return $this->response([
            'status' => true,
            'data'   => $result
        ], RestController::HTTP_OK);

    } catch (\Throwable $e) {

        log_message('error', $e->getMessage());

        return $this->response([
            'status' => false,
            'message' => 'Something went wrong'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}
public function territories_post()
{
    try {
   $data = [
            'sales_area_id' => $this->post('sales_area_id'),
            'name'          => $this->post('name'),
            'state'         => $this->post('state'),
            'city'          => $this->post('city'),
        ];

        $id = $this->territories_model->add($data);

        if (!$id) {
            return $this->response([
                'status' => false,
                'message' => 'Invalid data'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $this->response([
            'status' => true,
            'message' => 'Territory created',
            'id'      => $id
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        $this->response([
            'status' => false,
            'message' => 'Failed to create territory'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}
public function orders_get()
{
    try {
    $auth = isAuthorized();


        if (!isset($auth['status']) || !$auth['status']) {
           
            return $this->response($auth['response'], $auth['response_code']);
        }

        $user = $auth['data'];

       

        // =============================
        // 🔎 FILTER INPUT
        // =============================
        $filters = [
            'type'          => $this->get('type'),
            'customer_id'   => $this->get('customer_id'),
            'supplier_id'   => $this->get('supplier_id'),
            'status'        => $this->get('status'),
            'from_date'     => $this->get('from_date'),
            'to_date'       => $this->get('to_date'),
            'territory_id'  => $this->get('territory_id'),
            'sales_area_id' => $this->get('sales_area_id'),
        ];

        
        /*
        |--------------------------------------------------------------------------
        | 👤 CLIENT FLOW
        |--------------------------------------------------------------------------
        */
        if ($user->type === 'client') {

            $filters['customer_id'] = $user->client_id;

            
        }

        /*
        |--------------------------------------------------------------------------
        | 👨‍💼 STAFF FLOW
        |--------------------------------------------------------------------------
        */
        elseif ($user->type === 'staff') {

            $staffID = (int) ($user->staff_id ?? 0);

           

            // 🔐 Permission check
            $canViewAll = staff_can('view', 'distribution_channel', $staffID);
            $canViewOwn = staff_can('view_own', 'distribution_channel', $staffID);

            

            if (!$canViewAll && !$canViewOwn) {

                log_message('error', '❌ NO PERMISSION');

                return $this->response([
                    'status'  => false,
                    'message' => 'No permission'
                ], 403);
            }

            // Mobile may pass staff_id explicitly for dashboard scoping
            if ($this->get('staff_id')) {
                $filters['staff_id'] = (int) $this->get('staff_id');
            } elseif (!$canViewAll && $canViewOwn) {
                $filters['staff_id'] = $staffID;
            }
        }

        

        /*
        |--------------------------------------------------------------------------
        | 📦 FETCH ORDERS
        |--------------------------------------------------------------------------
        */
        $orders = $this->channel_orders_model->get_orderss($filters);
        $prefix = get_option('dc_order_prefix');
        foreach ($orders as &$order) {
            $order = $this->format_order_for_mobile($order);
            $order['order_display_id'] = $prefix.str_pad($order['id'], 5, '0', STR_PAD_LEFT);

    // 🔹 get items of this order
    $items = $this->channel_orders_model->get_order_items($order['id']);

    if (!empty($items)) {

        // 🔹 first item name
        $firstItem = $items[0]['product_name'] ?? '';

        // 🔹 remaining count
        $remaining = count($items) - 1;

        // 🔹 summary बनाने का logic
        $order['item_summary'] = $remaining > 0
            ? $firstItem . ' +' . $remaining
            : $firstItem;

    } else {
        $order['item_summary'] = 'No Items';
    }
}
        /*
        |--------------------------------------------------------------------------
        | 📊 SUMMARY
        |--------------------------------------------------------------------------
        */
        $summary = [
            'total'      => count($orders),
            'pending'    => 0,
            'accepted'   => 0,
            'dispatched' => 0,
        ];

        foreach ($orders as $order) {
            switch ($order['status']) {
                case 'pending':
                    $summary['pending']++;
                    break;
                case 'accepted':
                    $summary['accepted']++;
                    break;
                case 'dispatched':
                    $summary['dispatched']++;
                    break;
            }
        }

       

        

        /*
        |--------------------------------------------------------------------------
        | 🚀 RESPONSE
        |--------------------------------------------------------------------------
        */
        return $this->response([
            'status'  => true,
            'success' => true,
            'message' => 'Orders fetched successfully',
            'summary' => $summary,
            'data'    => $orders,
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {
        log_message('error', '[Distribution][orders_get] '.$th->getMessage());

        return $this->response([
            'status'  => false,
            'message' => 'Failed to fetch orders'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

    /* ==================================
       GET SINGLE ORDER
    ================================== */
public function order_get($id = null)
{
    try {

        log_message('info', '🟡 ORDER DETAILS API CALLED | ID: ' . $id);

        // =============================
        // 🔐 AUTH (SAME AS ESTIMATES)
        // =============================
        $auth = isAuthorized();

        if (!isset($auth['status']) || !$auth['status']) {
            return $this->response($auth['response'], $auth['response_code']);
        }

        $user = $auth['data'];

        // 🔹 VALIDATION
        if (!$id) {
            return $this->response([
                'status'  => false,
                'message' => 'Order ID required'
            ], RestController::HTTP_BAD_REQUEST);
        }

        // 🔹 FETCH ORDER
        $order = $this->channel_orders_model->get_order($id);

        if (empty($order)) {
            return $this->response([
                'status'  => false,
                'message' => 'Order not found'
            ], RestController::HTTP_NOT_FOUND);
        }

        /*
        |--------------------------------------------------------------------------
        | 🔹 CLIENT FLOW
        |--------------------------------------------------------------------------
        */
       

        $order = $this->format_order_for_mobile($order);

        // 🔹 FETCH ITEMS
        $items = $this->channel_orders_model->get_order_items($id);

        // 🔹 SUCCESS
        return $this->response([
            'status'  => true,
            'success' => true,
            'message' => 'Order fetched successfully',
            'data'    => [
                'order' => $order,
                'items' => $items ?? [],
            ],
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        log_message('error', '🔴 ORDER GET ERROR: ' . $th->getMessage());

        return $this->response([
            'status'  => false,
            'message' => 'Failed to fetch order'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

    /* ==================================
       CREATE ORDER
    ================================== */
public function orders_post()
{
    try {

        log_message('info', '================ ORDER API START =================');
        log_message('info', '➡️ RAW INPUT: ' . file_get_contents('php://input'));

      
        $auth = isAuthorized();
            log_message('error', 'AUTH RESPONSE: ' . json_encode($auth));

        if (!isset($auth['status']) || !$auth['status']) {
            log_message('error', '❌ AUTH FAILED');
            return $this->response($auth['response'], $auth['response_code']);
        }

        $user = $auth['data'];

        log_message('error', 'USER DATA: ' . json_encode($user));


       
       

        if (!$user) {
            log_message('error', '❌ USER NOT FOUND');
            return $this->response([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        log_message('info', '👤 USER DATA: ' . json_encode($user));

        // =============================
        // 📥 INPUT
        // =============================
       $raw = file_get_contents('php://input');
       $data = json_decode($raw, true);

if (empty($data)) {
    $data = $this->post(); // fallback for form-data
}
        log_message('info', '📥 PARSED INPUT: ' . json_encode($data));

        $items = $data['items'] ?? [];

        if (empty($items)) {
            log_message('error', '❌ ITEMS EMPTY');
            return $this->response([
                'status' => false,
                'message' => 'Items are required'
            ], 400);
        }

        // =============================
        // 👤 CLIENT DETECTION
        // =============================
        if (isset($user->client_id) && $user->type === 'client') {

    // ✅ CLIENT
    $client_id = $user->client_id;
    log_message('info', '✅ CLIENT LOGIN | CLIENT ID: ' . $client_id);

} elseif (isset($user->staff_id) && $user->type === 'staff') {

    // ✅ STAFF
   $client_id = $data['client_id'] ?? null;

    if (empty($client_id)) {
        log_message('error', '❌ CLIENT ID MISSING FOR STAFF');
        return $this->response([
            'status' => false,
            'message' => 'Client ID is required for staff'
        ], 400);
    }

    log_message('info', '✅ STAFF LOGIN | STAFF ID: ' . $user->staff_id . ' | CLIENT ID: ' . $client_id);


} else {

    log_message('error', '❌ UNKNOWN USER TYPE: ' . json_encode($user));

    return $this->response([
        'status' => false,
        'message' => 'Unauthorized user type'
    ], 401);
}

        $supplier_id = $data['supplier_id'] ?? null;
        log_message('info', '🏭 SUPPLIER ID: ' . ($supplier_id ?? 'NULL'));

      
        log_message('info', '🔄 DB TRANSACTION START');
        $this->db->trans_start();

        
        $order_data = [
            'client_id'   => $client_id,
            'supplier_id' => $supplier_id,
            'created_at'  => date('Y-m-d H:i:s'),
            'status'      => 'pending',
        ];

        if (isset($user->staff_id) && $user->type === 'staff') {
            $order_data['created_by'] = (int) $user->staff_id;
            if ($this->db->field_exists('salesperson_id', db_prefix().'channel_orders')) {
                $order_data['salesperson_id'] = (int) ($data['salesperson_id'] ?? $user->staff_id);
            }
        }

        $visit_id = !empty($data['visit_id']) ? (int) $data['visit_id'] : null;
        if ($visit_id) {
            dc_ensure_dsr_order_schema();
            $staffForVisit = (isset($user->staff_id) && $user->type === 'staff') ? (int) $user->staff_id : null;
            $visitCheck    = dc_validate_visit_for_order($visit_id, (int) $client_id, $staffForVisit);
            if (empty($visitCheck['valid'])) {
                return $this->response([
                    'status'  => false,
                    'message' => $visitCheck['message'] ?? 'Invalid visit_id',
                ], 400);
            }
            if ($this->db->field_exists('visit_id', db_prefix().'channel_orders')) {
                $order_data['visit_id'] = $visit_id;
            }
        }

        log_message('info', '📝 ORDER DATA: ' . json_encode($order_data));

        $this->db->insert(db_prefix() . 'channel_orders', $order_data);
        $order_id = $this->db->insert_id();

        log_message('info', '✅ ORDER CREATED | ID: ' . $order_id);

        // =============================
        // 📦 ITEMS PROCESS
        // =============================
        $order_total = 0;
        $batch = [];

        foreach ($items as $index => $item) {
          $item_name = $this->get_item_name($item['product_id'] ?? null);
          log_message('error', 'Item Name: ' . $item_name) ;

            log_message('info', "➡️ PROCESSING ITEM #$index: " . json_encode($item));

            $product_id = $item['product_id'] ?? null;
            $qty        = $item['qty'] ?? 0;
            $rate       = $item['rate'] ?? 0;
       
            if (!$product_id || $qty <= 0) {
                log_message('error', '❌ INVALID ITEM DATA: ' . json_encode($item));
                throw new Exception('Invalid item data');
            }

            $total = $qty * $rate;
            $order_total += $total;

            $batch[] = [
    'order_id'     => $order_id,
    'product_id'   => $product_id,
    'product_name' =>  $item_name ?? null, // optional
    'quantity'     => $qty,        // ✅ FIXED
    'price'        => $rate,       // ✅ FIXED
    'total'        => $total,
    'warehouse_id' => $data['warehouse_id'] ?? null,
    'discount'     => $item['discount'] ?? 0
];
        }

        log_message('info', '📦 FINAL BATCH: ' . json_encode($batch));
        log_message('info', '💰 ORDER TOTAL: ' . $order_total);

        // 🔹 INSERT ITEMS
        $this->db->insert_batch(db_prefix() . 'channel_order_items', $batch);
        log_message('info', '✅ ITEMS INSERTED');

        // 🔹 UPDATE TOTAL
        $this->db->where('id', $order_id)->update(db_prefix() . 'channel_orders', [
            'total_amount' => $order_total
        ]);

        log_message('info', '✅ ORDER TOTAL UPDATED');

        // =============================
        // ✅ COMMIT
        // =============================
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            log_message('error', '❌ DB TRANSACTION FAILED');
            throw new Exception('Transaction failed');
        }

        log_message('info', '🎉 ORDER CREATED SUCCESSFULLY');

        // =============================
        // 🎉 RESPONSE
        // =============================
        return $this->response([
            'status'  => true,
            'message' => 'Order created successfully',
            'data'    => [
                'order_id'                => $order_id,
                'distribution_order_id'   => $order_id,
                'visit_id'                => $visit_id,
                'total_amount'            => $order_total,
            ]
        ], 200);

    } catch (\Throwable $th) {

       
    

        return $this->response([
            'status'  => false,
            'message' => $th->getMessage()
        ], 500);
    }
}

    /* ==================================
       UPDATE ORDER
    ================================== */

    public function orders_put($id = null)
    {
        try {

            if (!$id) {

                return $this->response([
                    'status' => false,
                    'message' => 'Order ID required'
                ], RestController::HTTP_BAD_REQUEST);
            }

            $data = [];

            parse_str(file_get_contents("php://input"), $data);

            $success = $this->channel_orders_model->update_order($id, $data);

            if ($success) {

                $this->response([
                    'status'  => true,
                    'message' => 'Order updated successfully'
                ], RestController::HTTP_OK);

            } else {

                $this->response([
                    'status'  => false,
                    'message' => 'Update failed'
                ], RestController::HTTP_BAD_REQUEST);

            }

        } catch (\Throwable $th) {

            $this->response([
                'status'  => false,
                'message' => 'Something went wrong'
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /* ==================================
       DELETE ORDER
    ================================== */

    public function orders_delete($id = null)
    {
        try {

            if (!$id) {

                return $this->response([
                    'status' => false,
                    'message' => 'Order ID required'
                ], RestController::HTTP_BAD_REQUEST);
            }

            $this->channel_orders_model->delete_order($id);

            $this->response([
                'status'  => true,
                'message' => 'Order deleted successfully'
            ], RestController::HTTP_OK);

        } catch (\Throwable $th) {

            $this->response([
                'status'  => false,
                'message' => 'Delete failed'
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    public function client_orders_get($client_id = null)
{
    try {

        if (!$client_id) {
            return $this->response([
                'status' => false,
                'message' => 'Client ID required'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $orders = $this->channel_orders_model
            ->get_orders_by_client($client_id);

        $this->response([
            'status' => true,
            'message' => 'Orders retrieved successfully',
            'data'   => $orders
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        $this->response([
            'status' => false,
            'message' => 'Something went wrong'
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

public function partners_get()
{
    try {

        log_message('info', 'Partners API called');

        $group_id     = $this->get('group_id') ?: $this->post('group_id');
        $territory_id = $this->get('territory_id') ?: $this->post('territory_id');

        // ✅ Step 1: Get group_order from group_id
        $group_order = null;

        if (!empty($group_id)) {
            $group = $this->db
                ->select('group_order')
                ->from(db_prefix().'customers_groups')
                ->where('id', (int)$group_id)
                ->get()
                ->row();

            if ($group) {
                $group_order = (int)$group->group_order;
            }
        }

        log_message('info', 'Params: group_id=' . $group_id . ', group_order=' . $group_order . ', territory_id=' . $territory_id);

        // ✅ Step 2: Main Query
       $this->db->select('
    c.userid,
    c.company,
    c.phonenumber,
    c.city,
    c.state,
    c.territory_id,
    GROUP_CONCAT(cg.groupid) as group_ids,
    GROUP_CONCAT(cgs.group_order) as group_orders,
    GROUP_CONCAT(cgs.name) as group_names
');

        $this->db->from(db_prefix().'clients as c');

        $this->db->join(db_prefix().'customer_groups as cg', 'cg.customer_id = c.userid', 'left');
        $this->db->join(db_prefix().'customers_groups as cgs', 'cgs.id = cg.groupid', 'left');

        $this->db->where('c.active', 1);

        $staff_id = $this->auth_staff_id();
        if ($staff_id && $this->sales_assignment_model->should_scope_customers($staff_id)) {
            $scope = $this->sales_assignment_model->get_staff_scope($staff_id);

            if (!empty($territory_id) && !in_array((int) $territory_id, $scope['visible_territory_ids'], true)) {
                $this->response([
                    'status'  => true,
                    'message' => 'Territory is outside your assignment',
                    'data'    => [],
                ], RestController::HTTP_OK);

                return;
            }

            $this->sales_assignment_model->apply_client_scope_to_query($staff_id, 'c');
        }

        // ✅ Territory filter
        if (!empty($territory_id)) {
            $this->db->where('c.territory_id', (int)$territory_id);
        }

        // ✅ Parent group logic
        if (!empty($group_order)) {

    $parent_group_order = $group_order - 1;

    // ❌ No parent exists → return empty
    if ($parent_group_order <= 0) {

        log_message('info', 'No parent group exists → returning empty');

        $this->response([
            'status'  => true,
            'message' => 'No parent group found',
            'data'    => []
        ], RestController::HTTP_OK);

        return; // 🚨 VERY IMPORTANT
    }

    // ✅ Apply parent filter
    $this->db->where('cgs.group_order', $parent_group_order);
}

        $this->db->group_by('c.userid');

        $query = $this->db->get();

        log_message('info', 'SQL: ' . $this->db->last_query());

        $partners = $query->result_array();

        $this->load->helper('distribution_channel/dc_settings');

        $this->response([
            'status'  => true,
            'message' => 'Partners retrieved successfully',
            'meta'    => [
                'customer_scope_mode' => dc_mobile_customer_scope_mode(),
                'scope_customers'     => $staff_id
                    ? $this->sales_assignment_model->should_scope_customers($staff_id)
                    : false,
            ],
            'data'    => $partners
        ], RestController::HTTP_OK);

    } catch (\Throwable $th) {

        log_message('error', 'Partners API Error: ' . $th->getMessage());

        $this->response([
            'status'  => false,
            'message' => $th->getMessage()
        ], RestController::HTTP_INTERNAL_ERROR);
    }
}

    /* ==================================
       ORDER ITEMS
    ================================== */
    public function order_items_get($id = null)
    {
        try {
            if (!$id) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Order ID required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $items = $this->channel_orders_model->get_order_items($id);

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $items ?? [],
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to fetch order items',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /* ==================================
       SALES TARGETS  GET sales/targets
    ================================== */
    public function sales_targets_get()
    {
        try {
            $staff_id = $this->get('staff_id');
            $rows     = $this->sales_targets_model->get($staff_id ? (int) $staff_id : null);
            $data     = [];

            foreach ($rows as $row) {
                $data[] = $this->format_target_for_mobile($row);
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'message' => 'Sales targets fetched',
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to fetch sales targets',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function sales_targets_post()
    {
        try {
            $payload = $this->read_json_body();
            $id      = $this->sales_targets_model->add([
                'staff_id'     => (int) ($payload['staff_id'] ?? 0),
                'target_type'  => $payload['target_type'] ?? 'Sales Value',
                'period_type'  => $payload['period_type'] ?? 'Monthly',
                'from_date'    => $payload['from_date'] ?? null,
                'to_date'      => $payload['to_date'] ?? null,
                'target_value' => $payload['target_value'] ?? 0,
                'target_qty'   => $payload['target_qty'] ?? 0,
                'created_by'   => $this->auth_staff_id(),
            ]);

            return $this->response([
                'status'  => true,
                'success' => true,
                'message' => 'Target created',
                'data'    => ['id' => $id],
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to save target',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /* ==================================
       SALES ACHIEVEMENTS  GET/POST sales/achievements
    ================================== */
    public function sales_achievements_get()
    {
        try {
            $staff_id = $this->get('staff_id');

            if ($staff_id) {
                $this->db->where('staff_id', (int) $staff_id);
            }

            $rows = $this->db
                ->order_by('from_date', 'DESC')
                ->get(db_prefix().'sales_achievements')
                ->result_array();
            $data = [];

            foreach ($rows as $row) {
                $data[] = [
                    'id'              => (int) $row['id'],
                    'staff_id'        => (int) $row['staff_id'],
                    'from_date'       => $row['from_date'],
                    'to_date'         => $row['to_date'],
                    'achieved_value'  => (float) $row['achieved_value'],
                    'achieved_value_cr' => (float) $row['achieved_value'],
                ];
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to fetch achievements',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function sales_achievements_post()
    {
        try {
            $payload = $this->read_json_body();
            $staff_id = (int) ($payload['staff_id'] ?? $this->auth_staff_id());
            $achieved_cr = (float) ($payload['achieved_value'] ?? $payload['achieved_value_cr'] ?? 0);

            if (!$staff_id) {
                return $this->response([
                    'status'  => false,
                    'message' => 'staff_id is required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $from = $payload['from_date'] ?? null;
            $to   = $payload['to_date'] ?? null;

            if (!$from || !$to) {
                $month = $payload['month'] ?? date('Y-m');
                [$from, $to] = $this->sales_targets_model->month_bounds($month);
            }

            $achievements = $payload['achievements'] ?? null;
            $saved = [];

            if (is_array($achievements)) {
                foreach ($achievements as $sid => $value) {
                    $id = $this->sales_targets_model->upsert_achievement(
                        (int) $sid,
                        $from,
                        $to,
                        (float) $value,
                        $this->auth_staff_id()
                    );
                    $saved[] = ['staff_id' => (int) $sid, 'id' => $id];
                }
            } else {
                $id = $this->sales_targets_model->upsert_achievement(
                    $staff_id,
                    $from,
                    $to,
                    $achieved_cr,
                    $this->auth_staff_id()
                );
                $saved[] = ['staff_id' => $staff_id, 'id' => $id];
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'message' => 'Achievements saved',
                'data'    => $saved,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to save achievements',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /* ==================================
       SALES TEAM  GET sales/team
    ================================== */
    public function sales_team_get()
    {
        try {
            $team = $this->sales_targets_model->build_nsh_team();
            $data = [];

            foreach ($team as $member) {
                $data[] = $this->format_team_member_for_mobile($member);
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to fetch sales team',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /* ==================================
       SALES DASHBOARD  GET sales/dashboard
    ================================== */
    public function sales_dashboard_get()
    {
        try {
            $staff_id = (int) ($this->get('staff_id') ?: $this->auth_staff_id());

            if (!$staff_id) {
                return $this->response([
                    'status'  => false,
                    'message' => 'staff_id is required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $assignment = $this->sales_assignment_model->get($staff_id);
            $member     = $this->sales_targets_model->build_team_member(
                $staff_id,
                !empty($assignment) ? $assignment[0] : null
            );

            $scope = $this->sales_assignment_model->get_mobile_profile($staff_id);
            $data  = $this->format_dashboard_for_mobile($member);
            $data['sales_assignment'] = [
                'has_assignment'      => (bool) $scope['has_assignment'],
                'can_fill_dsr'        => (bool) $scope['can_fill_dsr'],
                'position'            => $scope['position'],
                'manager_name'        => $scope['manager_name'],
                'regions_label'       => $scope['regions_label'],
                'territories'         => $scope['territories'],
                'sales_areas'         => $scope['sales_areas'],
                'customer_scope_mode' => $scope['customer_scope_mode'],
                'scope_customers'     => (bool) $scope['scope_customers'],
            ];

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to fetch sales dashboard',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /* ==================================
       WEEKLY SUMMARY  GET sales/weekly_summary
    ================================== */
    public function sales_weekly_summary_get()
    {
        try {
            $staff_id   = (int) ($this->get('staff_id') ?: $this->auth_staff_id());
            $week_start = $this->get('week_start');

            [$start, $end] = $this->sales_targets_model->week_bounds_monday($week_start);
            $rollup        = $this->sales_targets_model->weekly_rollup($staff_id, $start, $end);

            $orders = $this->channel_orders_model->get_orderss([
                'staff_id'  => $staff_id,
                'from_date' => $start,
                'to_date'   => $end,
            ]);

            $dsr_visits = $this->sales_targets_model->get_dsr_visits($staff_id, $start, $end);

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => [
                    'week_start'                  => $start,
                    'week_end'                    => $end,
                    'can_fill_dsr'                => $this->sales_assignment_model->staff_can_fill_dsr($staff_id),
                    'dsr_visit_count'             => (int) ($rollup['dsr_visit_count'] ?? 0),
                    'distribution_order_count'    => count($orders),
                    'revenue_booked_lakhs'        => (float) ($rollup['revenue_booked_lakhs'] ?? 0),
                    'dsr_order_value_lakhs'       => (float) ($rollup['dsr_order_value_lakhs'] ?? 0),
                    'distribution_revenue_lakhs'  => (float) ($rollup['distribution_revenue_lakhs'] ?? 0),
                    'collection_lakhs'            => (float) ($rollup['collection_lakhs'] ?? 0),
                    'distributors_ordered_count'  => (int) ($rollup['distributors_ordered_count'] ?? 0),
                    'railway_visits_count'        => (int) ($rollup['railway_visits_count'] ?? 0),
                    'new_accounts_count'          => (int) ($rollup['new_accounts_count'] ?? 0),
                    'dsr_visits'                  => $dsr_visits,
                ],
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to fetch weekly summary',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /* ==================================
       WEEKLY REPORT  POST sales/weekly_report
    ================================== */
    public function sales_weekly_report_post()
    {
        try {
            $payload  = $this->read_json_body();
            $staff_id = (int) ($payload['staff_id'] ?? $this->auth_staff_id());

            if (!$this->sales_assignment_model->staff_can_fill_dsr($staff_id)) {
                return $this->response([
                    'status'  => false,
                    'message' => 'No territory or sales area assigned, and mobile settings require an assignment for weekly reports.',
                ], RestController::HTTP_FORBIDDEN);
            }

            [$week_start, $week_end] = $this->sales_targets_model->week_bounds_monday(
                $payload['week_start'] ?? null
            );

            $rollup = $this->sales_targets_model->weekly_rollup($staff_id, $week_start, $week_end);

            $dsr_snapshot = $payload['dsr_snapshot'] ?? null;
            if ($dsr_snapshot === null || $dsr_snapshot === '') {
                $dsr_snapshot = $rollup['dsr_snapshot'] ?? null;
            } elseif (is_array($dsr_snapshot)) {
                $dsr_snapshot = json_encode($dsr_snapshot);
            }

            $id = $this->sales_targets_model->save_weekly_report([
                'staff_id'                      => $staff_id,
                'week_start'                    => $week_start,
                'week_end'                      => $week_end,
                'submitting_for'                => $payload['submitting_for'] ?? null,
                'revenue_booked_lakhs'          => $payload['revenue_booked_lakhs'] ?? $rollup['revenue_booked_lakhs'] ?? 0,
                'existing_distributors_ordered' => $payload['existing_distributors_ordered'] ?? $payload['distributors_ordered_count'] ?? $rollup['distributors_ordered_count'] ?? 0,
                'new_distributors'              => $payload['new_distributors'] ?? 0,
                'railway_visits'                => $payload['railway_visits'] ?? $payload['railway_visits_count'] ?? $rollup['railway_visits_count'] ?? 0,
                'new_accounts'                  => $payload['new_accounts'] ?? $payload['new_accounts_count'] ?? $rollup['new_accounts_count'] ?? 0,
                'kra_status'                    => $payload['kra_status'] ?? null,
                'blocker'                       => $payload['blocker'] ?? null,
                'dsr_snapshot'                  => $dsr_snapshot,
            ]);

            return $this->response([
                'status'  => true,
                'success' => true,
                'message' => 'Weekly report submitted',
                'data'    => ['id' => $id],
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to submit weekly report',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /* ==================================
       WEEKLY REPORTS LIST  GET sales/weekly_reports
    ================================== */
    public function sales_weekly_reports_get()
    {
        try {
            $viewer_id  = $this->auth_staff_id();
            $staff_id   = $this->get('staff_id');
            $week_start = $this->get('week_start');
            $id         = $this->get('id');
            $limit      = min(100, max(1, (int) ($this->get('limit') ?: 20)));

            $filters = ['limit' => $limit];

            if ($id) {
                $filters['id'] = (int) $id;
            }

            if ($week_start) {
                $filters['week_start'] = $week_start;
            }

            $can_view_team = is_admin($viewer_id) || staff_can('view', 'timesheets_dashboard', $viewer_id);

            if ($staff_id) {
                if (!$can_view_team && (int) $staff_id !== $viewer_id) {
                    return $this->response([
                        'status'  => false,
                        'message' => 'Forbidden',
                    ], RestController::HTTP_FORBIDDEN);
                }
                $filters['staff_id'] = (int) $staff_id;
            } elseif ($can_view_team && $this->get('team')) {
                $team = $this->sales_targets_model->build_nsh_team();
                $filters['staff_ids'] = array_column($team, 'staff_id');
            } else {
                $filters['staff_id'] = $viewer_id;
            }

            $rows = $this->sales_targets_model->get_weekly_reports($filters);
            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->format_weekly_report_for_mobile($row);
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => $data,
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to fetch weekly reports',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /* ==================================
       MONTHLY KRAS  GET sales/kras
    ================================== */
    public function sales_kras_get()
    {
        try {
            $staff_id = (int) ($this->get('staff_id') ?: $this->auth_staff_id());
            [$fy_from] = $this->sales_targets_model->fiscal_year_bounds();
            $fiscal_start_year = (int) date('Y', strtotime($fy_from));
            $fiscal_start_year = (int) ($this->get('fiscal_start_year') ?: $fiscal_start_year);

            $kras = $this->distributor_targets_model->get_monthly_kras($fiscal_start_year, $staff_id);

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => [
                    'staff_id'          => $staff_id,
                    'fiscal_start_year' => $fiscal_start_year,
                    'fiscal_year'       => $this->sales_targets_model->fiscal_year_label(),
                    'kras'              => $kras,
                ],
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to fetch KRAs',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    private function auth_staff_id()
    {
        return (int) ($this->staffInfo['data']->staff_id ?? 0);
    }

    private function format_weekly_report_for_mobile(array $row)
    {
        $snapshot = $row['dsr_snapshot'] ?? null;
        if (is_string($snapshot) && $snapshot !== '') {
            $decoded = json_decode($snapshot, true);
            $snapshot = is_array($decoded) ? $decoded : $snapshot;
        }

        return [
            'id'                            => (int) $row['id'],
            'staff_id'                      => (int) $row['staff_id'],
            'staff_name'                    => $row['staff_name'] ?? '',
            'week_start'                    => $row['week_start'],
            'week_end'                      => $row['week_end'],
            'submitting_for'                => $row['submitting_for'] ?? null,
            'revenue_booked_lakhs'          => (float) ($row['revenue_booked_lakhs'] ?? 0),
            'existing_distributors_ordered' => (int) ($row['existing_distributors_ordered'] ?? 0),
            'new_distributors'              => (int) ($row['new_distributors'] ?? 0),
            'railway_visits'                => (int) ($row['railway_visits'] ?? 0),
            'new_accounts'                  => (int) ($row['new_accounts'] ?? 0),
            'kra_status'                    => $row['kra_status'] ?? null,
            'blocker'                       => $row['blocker'] ?? null,
            'dsr_snapshot'                  => $snapshot,
            'datecreated'                   => $row['datecreated'] ?? null,
        ];
    }

    private function read_json_body()
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        return !empty($data) ? $data : $this->post();
    }

    private function format_order_for_mobile(array $order)
    {
        if (!isset($order['total']) && isset($order['total_amount'])) {
            $order['total'] = $order['total_amount'];
        }
        if (!isset($order['order_date']) && !empty($order['created_at'])) {
            $order['order_date'] = date('Y-m-d', strtotime($order['created_at']));
        }

        return $order;
    }

    private function format_target_for_mobile(array $row)
    {
        $assignment = $this->sales_assignment_model->get($row['staff_id']);
        $meta       = !empty($assignment) ? $assignment[0] : [];

        return [
            'id'               => (int) $row['id'],
            'staff_id'         => (int) $row['staff_id'],
            'target_type'      => $row['target_type'],
            'period_type'      => $row['period_type'],
            'from_date'        => $row['from_date'],
            'to_date'          => $row['to_date'],
            'target_value'     => $this->sales_targets_model->rupees_to_crore($row['target_value']),
            'target_value_cr'  => $this->sales_targets_model->rupees_to_crore($row['target_value']),
            'target_qty'       => (float) ($row['target_qty'] ?? 0),
            'full_name'        => $row['full_name'] ?? trim(($row['firstname'] ?? '').' '.($row['lastname'] ?? '')),
            'firstname'        => $row['firstname'] ?? '',
            'lastname'         => $row['lastname'] ?? '',
            'position'         => $meta['position'] ?? null,
            'role'             => $meta['position'] ?? null,
            'territory'        => $meta['territory_name'] ?? null,
            'territory_name'   => $meta['territory_name'] ?? null,
            'sales_area'       => $meta['sales_area_name'] ?? null,
            'sales_area_name'  => $meta['sales_area_name'] ?? null,
            'regions'          => trim(($meta['territory_name'] ?? '').' · '.($meta['sales_area_name'] ?? ''), ' ·'),
        ];
    }

    private function format_team_member_for_mobile(array $member)
    {
        return [
            'staff_id'            => (int) $member['staff_id'],
            'name'                => $member['name'],
            'full_name'           => $member['name'],
            'role'                => $member['role'],
            'position'            => $member['role'],
            'location'            => $member['regions'],
            'regions'             => $member['regions'],
            'annual_target_cr'    => (float) $member['annual_target_cr'],
            'ytd_achieved_cr'     => (float) $member['ytd_achieved_cr'],
            'ytd_achievement_pct' => (int) $member['ytd_pct'],
            'to_date_target_cr'   => (float) $member['current_target_cr'],
            'to_date_achieved_cr'  => (float) $member['current_achieved_cr'],
            'gap_cr'              => (float) $member['gap_cr'],
            'distributors_assigned' => (int) $member['distributors_count'],
            'at_risk'             => !empty($member['at_risk']),
        ];
    }

    private function format_dashboard_for_mobile(array $member)
    {
        $months = [];
        foreach ($member['month_rows'] as $row) {
            $status = $row['status'];
            if ($status === 'watch') {
                $status = 'at_risk';
            }
            $months[] = [
                'month_label'       => $row['month_label'],
                'quarter'           => $row['quarter'],
                'target_cr'         => (float) $row['target_cr'],
                'achieved_cr'       => (float) $row['achieved_cr'],
                'gap_cr'            => (float) $row['gap_cr'],
                'achievement_pct'   => (int) $row['achievement_pct'],
                'status'            => $status,
                'is_current_month'  => !empty($row['is_current_month']),
            ];
        }

        $regions = explode(' · ', $member['regions']);
        $location = $regions[0] ?? $member['regions'];

        return [
            'staff_id'              => (string) $member['staff_id'],
            'name'                  => $member['name'],
            'role'                  => $member['role'],
            'location'              => $location,
            'regions'               => $member['regions'],
            'fiscal_year'           => $this->sales_targets_model->fiscal_year_label(),
            'joining_status'        => 'Active',
            'ytd_achievement_pct'   => (float) $member['ytd_pct'],
            'annual_target_cr'      => (float) $member['annual_target_cr'],
            'to_date_target_cr'     => (float) $member['current_target_cr'],
            'to_date_achieved_cr'   => (float) $member['current_achieved_cr'],
            'distributors_assigned' => (int) $member['distributors_count'],
            'distributor_cities'    => $member['regions'],
            'months'                => $months,
            'kras'                  => [],
        ];
    }

    private function count_railway_visits($staff_id, $from, $to)
    {
        if (!$this->db->table_exists(db_prefix().'sales_visits')) {
            return 0;
        }

        $this->db->from(db_prefix().'sales_visits v');
        $this->db->where('v.staff_id', (int) $staff_id);
        $this->db->where('v.checkout_time IS NOT NULL', null, false);
        $this->db->where('DATE(v.visit_date) >=', $from);
        $this->db->where('DATE(v.visit_date) <=', $to);
        $this->db->group_start();
        $this->db->like('v.notes', 'railway');
        $this->db->or_like('v.notes', 'depot');
        $this->db->or_like('v.notes', 'linen');
        $this->db->or_like('v.notes', 'rly');
        $this->db->or_like('v.notes', 'b2b');
        if ($this->db->field_exists('area', db_prefix().'sales_visits')) {
            $this->db->or_like('v.area', 'railway');
            $this->db->or_like('v.area', 'depot');
        }
        $this->db->group_end();

        return $this->db->count_all_results();
    }

    private function count_new_accounts_week($staff_id, $from, $to)
    {
        $has_salesperson = $this->db->field_exists('salesperson_id', db_prefix().'channel_orders');
        $staff_id        = (int) $staff_id;

        $this->db->select('o.client_id, MIN(DATE(o.created_at)) as first_order', false);
        $this->db->from(db_prefix().'channel_orders o');
        $this->db->group_start();
        $this->db->where('o.created_by', $staff_id);
        if ($has_salesperson) {
            $this->db->or_where('o.salesperson_id', $staff_id);
        }
        $this->db->group_end();
        $this->db->where_in('o.status', ['accepted', 'approved', 'completed', 'dispatched']);
        $this->db->group_by('o.client_id');

        $count = 0;
        foreach ($this->db->get()->result_array() as $row) {
            if ($row['first_order'] >= $from && $row['first_order'] <= $to) {
                $count++;
            }
        }

        return $count;
    }

    private function get_item_name($item_id)
    {
        $this->db->select('description, sku_name, long_description');
        $this->db->from(db_prefix().'items');
        $this->db->where('id', $item_id);
        $item = $this->db->get()->row_array();

        if (!$item) {
            return null;
        }

        return $item['description']
            ?? $item['sku_name']
            ?? $item['long_description']
            ?? ('Item #'.$item_id);
    }
}