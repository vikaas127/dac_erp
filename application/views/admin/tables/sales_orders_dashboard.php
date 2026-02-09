<?php

defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('sales_orders_dashboard')
    ->outputUsing(function () {

        $quick_date = $this->ci->input->post('quick_date');
        $from_date  = $this->ci->input->post('from_date');
        $to_date    = $this->ci->input->post('to_date');
        $item_id    = $this->ci->input->post('item_id');
        $sale_agent        = $this->ci->input->post('sale_agent');
        $production_filter = $this->ci->input->post('production_filter');
        $stock_filter = $this->ci->input->post('stock_filter');



        $aColumns = [
            'e.number',
            'DATE(e.date)',
                  'e.status', 
            'i.description',
            'it.qty',
               '(SELECT COALESCE(SUM(ii.wh_delivered_quantity),0)
                    FROM ' . db_prefix() . 'itemable ii
                    WHERE ii.rel_type = "invoice"
                    AND ii.rel_id = e.invoiceid
                    AND ii.item_id = it.item_id
                )',
            'COALESCE(SUM(im.inventory_number),0)',
           
             
             
            'it.production_status',
            'CONCAT(s.firstname," ",s.lastname)',
        ];

        $sIndexColumn = 'it.id';
        $sTable       = db_prefix() . 'itemable it';

        $join = [
            'INNER JOIN ' . db_prefix() . 'estimates e
                ON e.id = it.rel_id AND it.rel_type = "estimate"',
            'LEFT JOIN ' . db_prefix() . 'items i ON i.id = it.item_id',
            'LEFT JOIN ' . db_prefix() . 'inventory_manage im ON im.commodity_id = i.id',
            'LEFT JOIN ' . db_prefix() . 'staff s ON s.staffid = e.sale_agent',
        ];

        $where = [];

        // 🔒 Only status = 4
        // $where[] = 'AND e.status = 4';

        // Item filter
        if (!empty($item_id)) {
            $where[] = 'AND it.item_id=' . intval($item_id);
        }
        if (!empty($sale_agent)) {
    $where[] = 'AND e.sale_agent = ' . intval($sale_agent);
}



        // Date range
     // Custom date range ONLY when quick_date = custom
if ($quick_date === 'custom' && $from_date && $to_date) {
    $where[] = 'AND e.date BETWEEN "' . $from_date . '" AND "' . $to_date . ' 23:59:59"';
}

// Quick date ONLY when not custom
if ($quick_date && $quick_date !== 'custom') {
    switch ($quick_date) {
        case 'today':
            $where[] = 'AND DATE(e.date)=CURDATE()';
            break;
        case 'yesterday':
            $where[] = 'AND DATE(e.date)=CURDATE()-INTERVAL 1 DAY';
            break;
        case 'week':
            $where[] = 'AND e.date>=CURDATE()-INTERVAL 7 DAY';
            break;
        case 'month':
            $where[] = 'AND e.date>=CURDATE()-INTERVAL 1 MONTH';
            break;
        case 'year':
            $where[] = 'AND e.date>=CURDATE()-INTERVAL 1 YEAR';
            break;
    }
}


        $result = data_tables_init(
            $aColumns,
            $sIndexColumn,
            $sTable,
            $join,
            $where,
            [
                'e.id as estimate_id',
                'e.date as estimate_date',
                'e.status as estimate_status',
                'i.description as item_name',
                'it.qty as order_qty',
                  '(SELECT COALESCE(SUM(ii.wh_delivered_quantity),0)
            FROM ' . db_prefix() . 'itemable ii
            WHERE ii.rel_type = "invoice"
            AND ii.rel_id = e.invoiceid
            AND ii.item_id = it.item_id
        ) as delivered_qty',
        
                'COALESCE(SUM(im.inventory_number),0) as available_qty',
                
      
      
                'it.production_status',
                'CONCAT(s.firstname," ",s.lastname) as sales_agent',
            ],
            'GROUP BY it.id'
        );

        $output  = $result['output'];
        $rResult = $result['rResult'];
        $totalOrdered = 0;
        $totalDelivered = 0;
        $totalRemaining = 0;
        $totalInProduction = 0;
        $totalAvailable = 0;
        $totalNeedAssign = 0;



    foreach ($rResult as $aRow) {

        $skipRow = false;
        $productionColumn = '<span class="text-muted">Not Started</span>';

        $hasMO = false;
        $isCompleted = false;
        $prod = null;

        /* ---------- DETECT MO & STATUS ---------- */
        if (!empty($aRow['production_status'])) {

            $productionData = json_decode($aRow['production_status'], true);

            if (json_last_error() === JSON_ERROR_NONE && !empty($productionData)) {

                $prod = reset($productionData);

                if (!empty($prod['mo_link'])) {

                    $hasMO = true;

                    preg_match('/view_manufacturing_order\/(\d+)/', $prod['mo_link'], $m);
                    $moId = isset($m[1]) ? (int)$m[1] : 0;

                    if ($moId > 0) {
                        $mo = $this->ci->db
                            ->select('status')
                            ->from(db_prefix() . 'mrp_manufacturing_orders')
                            ->where('id', $moId)
                            ->get()
                            ->row();

                        if ($mo && $mo->status === 'done') {
                            $isCompleted = true;
                        }
                    }
                }
            }
        }

        /* ---------- PRODUCTION FILTER ---------- */
        if (!empty($production_filter)) {

            if ($production_filter === 'not_started' && $hasMO) {
                continue;
            }

            if ($production_filter === 'assigned' && (!$hasMO || $isCompleted)) {
                continue;
            }

            if ($production_filter === 'completed' && !$isCompleted) {
                continue;
            }
        }

        /* ---------- STOCK FILTER ---------- */
        if (!empty($stock_filter)) {

            $inStock = ((float)$aRow['available_qty'] >= (float)$aRow['order_qty']);

            if ($stock_filter === 'in_stock' && !$inStock) {
                continue;
            }

            if ($stock_filter === 'short' && $inStock) {
                continue;
            }
        }

        /* ---------- CALCULATE CARDS (ONLY NOW) ---------- */
        $totalOrdered += (float) $aRow['order_qty'];
        $totalDelivered += (float) $aRow['delivered_qty'];
        $totalAvailable += (float) $aRow['available_qty'];

        $remaining = (float) $aRow['order_qty'] - (float) $aRow['delivered_qty'];
        $totalRemaining += max(0, $remaining);

        if ($hasMO) {
            if (!$isCompleted) {
                $totalInProduction += (float) $aRow['order_qty'];
            }
        } else {          
            $totalNeedAssign = max(
                0,
                $totalOrdered - $totalInProduction - $totalAvailable - $totalDelivered
            );

        }


        /* ---------- RENDER PRODUCTION COLUMN ---------- */
        if ($hasMO && $prod) {

            $prodLabel = $isCompleted
                ? '<span class="label label-success">Completed</span>'
                : '<span class="label label-warning">Assigned</span>';

            $productionColumn = '
                <div>
                    <div>' . $prodLabel . '</div>
                    <div class="mtop5">' . $prod['mo_link'] . '</div>
                </div>
            ';
        }

        /* ---------- OUTPUT ---------- */
        $output['aaData'][] = [
            '<a href="' . admin_url('estimates/list_estimates/' . $aRow['estimate_id']) . '" target="_blank">'
                . format_estimate_number($aRow['estimate_id']) . '</a>',
            _d($aRow['estimate_date']),
            format_estimate_status($aRow['estimate_status']),
            $aRow['item_name'],
            $aRow['order_qty'],
            $aRow['delivered_qty'],
            $aRow['available_qty'],
            $aRow['available_qty'] >= $aRow['order_qty']
                ? '<span class="label label-success">In Stock</span>'
                : '<span class="label label-danger">Short</span>',
            $productionColumn,
            $aRow['sales_agent'],
        ];
    }


    $output['cards'] = [
    'ordered'        => number_format($totalOrdered, 2),
    'delivered'      => number_format($totalDelivered, 2),
    'remaining'      => number_format($totalRemaining, 2),
    'in_production'  => number_format($totalInProduction, 2),
    'available'      => number_format($totalAvailable, 2),
    'need_assign'    => number_format($totalNeedAssign, 2),
    ];


        return $output;
    });
