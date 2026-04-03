<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Enterprise_sales_analytics_model extends App_Model
{

/* ----------------------------------------------------------
   TOTAL LEADS
----------------------------------------------------------- */

public function total_leads()
{
    return $this->db->count_all_results(db_prefix().'leads');
}


/* ----------------------------------------------------------
   PIPELINE VALUE
----------------------------------------------------------- */

public function pipeline_value()
{
    $this->db->select_sum('total');
    $result = $this->db->get(db_prefix().'proposals')->row();

    return $result ? $result->total : 0;
}


/* ----------------------------------------------------------
   TOTAL REVENUE
----------------------------------------------------------- */

public function revenue()
{
    $this->db->select_sum('total');
    $result = $this->db->get(db_prefix().'invoices')->row();

    return $result ? $result->total : 0;
}


/* ----------------------------------------------------------
   LEAD SOURCES
----------------------------------------------------------- */

public function lead_sources()
{
    $this->db->select('source, COUNT(*) total');
    $this->db->group_by('source');

    return $this->db
        ->get(db_prefix().'leads')
        ->result();
}


/* ----------------------------------------------------------
   SALES FUNNEL
----------------------------------------------------------- */

public function sales_funnel()
{
    $this->db->select('status, COUNT(*) total');
    $this->db->group_by('status');

    return $this->db
        ->get(db_prefix().'leads')
        ->result();
}


/* ----------------------------------------------------------
   SALES PIPELINE
----------------------------------------------------------- */

public function pipeline()
{

    return $this->db->query("
       SELECT 
    p.id,
    p.subject,
    p.total,
    c.company
FROM ".db_prefix()."proposals p
LEFT JOIN ".db_prefix()."clients c
ON c.userid = p.rel_id
ORDER BY p.total DESC
    ")->result();

}


/* ----------------------------------------------------------
   SALESPERSON PERFORMANCE
----------------------------------------------------------- */

public function sales_team()
{
    return $this->db->query("
        SELECT 
            s.staffid,
            s.firstname,
            s.lastname,

            COUNT(l.id) as leads,

            SUM(CASE WHEN ls.name = 'New' THEN 1 ELSE 0 END) as new,
            SUM(CASE WHEN ls.name = 'Contacted' THEN 1 ELSE 0 END) as contacted,
            SUM(CASE WHEN ls.name = 'Qualified' THEN 1 ELSE 0 END) as qualified,
            SUM(CASE WHEN ls.name = 'Meeting Done' THEN 1 ELSE 0 END) as meeting,
            SUM(CASE WHEN ls.name = 'Proposal Sent' THEN 1 ELSE 0 END) as proposal,
            SUM(CASE WHEN ls.name = 'Negotiation' THEN 1 ELSE 0 END) as negotiation,
            SUM(CASE WHEN ls.name = 'Lost' THEN 1 ELSE 0 END) as lost

        FROM ".db_prefix()."staff s

        LEFT JOIN ".db_prefix()."leads l 
            ON l.assigned = s.staffid

        LEFT JOIN ".db_prefix()."leads_status ls 
            ON ls.id = l.status

        GROUP BY s.staffid
        ORDER BY leads DESC
    ")->result();
}
public function sales_team_dynamic($filter_type = null, $from = null, $to = null)
{
    $statuses = $this->db
        ->order_by('statusorder', 'ASC')
        ->get(db_prefix().'leads_status')
        ->result_array();

    $statusSelect = "";

    foreach ($statuses as $status) {
        $key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', trim($status['name'])));
        $statusSelect .= "SUM(CASE WHEN l.status = {$status['id']} THEN 1 ELSE 0 END) as `$key`, ";
    }

    // 🔥 FILTER
    $where = "";

    if ($filter_type == 'monthly') {
        $where = "WHERE MONTH(l.dateadded)=MONTH(CURDATE()) AND YEAR(l.dateadded)=YEAR(CURDATE())";
    } elseif ($filter_type == 'weekly') {
        $where = "WHERE YEARWEEK(l.dateadded,1)=YEARWEEK(CURDATE(),1)";
    } elseif ($filter_type == 'yearly') {
        $where = "WHERE YEAR(l.dateadded)=YEAR(CURDATE())";
    } elseif ($filter_type == 'custom' && $from && $to) {
        $where = "WHERE DATE(l.dateadded) BETWEEN '$from' AND '$to'";
    }

    $query = "
        SELECT 
            s.staffid,
            s.firstname,
            s.lastname,

            COUNT(l.id) as leads,

            $statusSelect

            -- ✅ DEAL WON
            COUNT(DISTINCT CASE 
                WHEN c.userid IS NOT NULL 
                AND e.id IS NOT NULL 
                THEN l.id 
            END) as won,

            -- ✅ REVENUE
            SUM(DISTINCT CASE 
                WHEN c.userid IS NOT NULL 
                AND e.id IS NOT NULL 
                THEN e.total 
                ELSE 0 
            END) as revenue

        FROM ".db_prefix()."staff s

        LEFT JOIN ".db_prefix()."leads l 
            ON l.assigned = s.staffid

        LEFT JOIN ".db_prefix()."clients c 
            ON c.leadid = l.id

        LEFT JOIN ".db_prefix()."estimates e 
            ON e.clientid = c.userid

        $where

        GROUP BY s.staffid
        ORDER BY leads DESC
    ";

    return [
        'team' => $this->db->query($query)->result(),
        'statuses' => $statuses
    ];
}
/* ----------------------------------------------------------
   MONTHLY REVENUE
----------------------------------------------------------- */
public function staff_kpis($filter_type = null, $from = null, $to = null)
{
    $where = "";

    if ($filter_type == 'monthly') {
        $where = "WHERE MONTH(l.dateadded)=MONTH(CURDATE()) AND YEAR(l.dateadded)=YEAR(CURDATE())";
    } elseif ($filter_type == 'weekly') {
        $where = "WHERE YEARWEEK(l.dateadded,1)=YEARWEEK(CURDATE(),1)";
    } elseif ($filter_type == 'yearly') {
        $where = "WHERE YEAR(l.dateadded)=YEAR(CURDATE())";
    } elseif ($filter_type == 'custom' && $from && $to) {
        $where = "WHERE DATE(l.dateadded) BETWEEN '$from' AND '$to'";
    }

    $row = $this->db->query("
        SELECT 
            COUNT(l.id) as total_leads,

            COUNT(DISTINCT CASE 
                WHEN c.userid IS NOT NULL 
                AND e.id IS NOT NULL 
                 AND e.status = 4
                THEN l.id 
            END) as won,

            SUM(CASE 
                WHEN c.userid IS NOT NULL 
                AND e.id IS NOT NULL 
                 AND e.status = 4
                THEN e.total 
                ELSE 0 
            END) as revenue

        FROM ".db_prefix()."leads l

        LEFT JOIN ".db_prefix()."clients c 
            ON c.leadid = l.id

        LEFT JOIN ".db_prefix()."estimates e 
            ON e.clientid = c.userid

        $where
    ")->row();

    $conversion = 0;

    if ($row->total_leads > 0) {
        $conversion = round(($row->won / $row->total_leads) * 100, 2);
    }

    return [
        'total_leads' => $row->total_leads ?? 0,
        'won' => $row->won ?? 0,
        'revenue' => $row->revenue ?? 0,
        'conversion' => $conversion
    ];
}


/* ----------------------------------------------------------
   PRODUCT SALES
----------------------------------------------------------- */

public function product_sales($filter_type = null, $from = null, $to = null)
{
    $where = "";

    if ($filter_type == 'monthly') {
        $where = "AND MONTH(e.datecreated)=MONTH(CURDATE()) AND YEAR(e.datecreated)=YEAR(CURDATE())";
    } elseif ($filter_type == 'weekly') {
        $where = "AND YEARWEEK(e.datecreated,1)=YEARWEEK(CURDATE(),1)";
    } elseif ($filter_type == 'yearly') {
        $where = "AND YEAR(e.datecreated)=YEAR(CURDATE())";
    } elseif ($filter_type == 'custom' && $from && $to) {
        $where = "AND DATE(e.datecreated) BETWEEN '$from' AND '$to'";
    }

    return $this->db->query("
        SELECT 
            i.description,
            SUM(i.qty) as units,
            SUM(i.qty * i.rate) as revenue

        FROM ".db_prefix()."itemable i

        LEFT JOIN ".db_prefix()."estimates e 
            ON e.id = i.rel_id

        WHERE i.rel_type = 'estimate'
        $where

        GROUP BY i.description
        ORDER BY revenue DESC
    ")->result();
}
public function customer_sales($filter_type = null, $from = null, $to = null)
{
    $where = "";

    if ($filter_type == 'monthly') {
        $where = "AND MONTH(e.datecreated)=MONTH(CURDATE()) AND YEAR(e.datecreated)=YEAR(CURDATE())";
    } elseif ($filter_type == 'weekly') {
        $where = "AND YEARWEEK(e.datecreated,1)=YEARWEEK(CURDATE(),1)";
    } elseif ($filter_type == 'yearly') {
        $where = "AND YEAR(e.datecreated)=YEAR(CURDATE())";
    } elseif ($filter_type == 'custom' && $from && $to) {
        $where = "AND DATE(e.datecreated) BETWEEN '$from' AND '$to'";
    }

    return $this->db->query("
        SELECT 
            c.company,
            SUM(i.qty) as units,
            SUM(i.qty * i.rate) as revenue

        FROM ".db_prefix()."itemable i

        LEFT JOIN ".db_prefix()."estimates e 
            ON e.id = i.rel_id

        LEFT JOIN ".db_prefix()."clients c 
            ON c.userid = e.clientid

        WHERE i.rel_type = 'estimate'
        $where

        GROUP BY e.clientid
        ORDER BY revenue DESC
    ")->result();
}
public function monthly_revenue($filter_type = null, $from = null, $to = null)
{
    $where = "";

    if ($filter_type == 'custom' && $from && $to) {
        $where = "WHERE DATE(date) BETWEEN '$from' AND '$to'";
    }

    return $this->db->query("
        SELECT 
            MONTH(date) month,
            SUM(total) revenue

        FROM ".db_prefix()."invoices

        $where

        GROUP BY MONTH(date)
        ORDER BY MONTH(date)
    ")->result();
}
/* ----------------------------------------------------------
   GEOGRAPHIC SALES
----------------------------------------------------------- */

public function region_sales()
{

    return $this->db->query("
        SELECT 
            state,
            COUNT(*) leads
        FROM ".db_prefix()."clients
        GROUP BY state
        ORDER BY leads DESC
    ")->result();

}


/* ----------------------------------------------------------
   LEAD AGING
----------------------------------------------------------- */

public function lead_aging()
{

    return $this->db->query("
        SELECT 
            name,
            company,
            DATEDIFF(NOW(),dateadded) aging
        FROM ".db_prefix()."leads
        ORDER BY aging DESC
    ")->result();

}


/* ----------------------------------------------------------
   LOST DEAL ANALYSIS
----------------------------------------------------------- */

public function lost_deals()
{

return $this->db->query("
SELECT 
ls.name status,
COUNT(l.id) total
FROM ".db_prefix()."leads l
LEFT JOIN ".db_prefix()."leads_status ls
ON ls.id = l.status
GROUP BY l.status
ORDER BY total DESC
")->result();

}

public function conversion_rate()
{

    $total_leads = $this->db->count_all_results(db_prefix().'leads');

    $this->db->where('status','converted');
    $converted = $this->db->count_all_results(db_prefix().'leads');

    if($total_leads == 0){
        return 0;
    }

    return round(($converted / $total_leads) * 100,2);

}
/* ----------------------------------------------------------
   SALES FORECAST
----------------------------------------------------------- */
public function monthly_status_report()
{

return $this->db->query("
SELECT 
MONTH(l.dateadded) month,
s.name status,
COUNT(l.id) total
FROM ".db_prefix()."leads l
LEFT JOIN ".db_prefix()."leads_status s
ON s.id = l.status
GROUP BY MONTH(l.dateadded), l.status
ORDER BY MONTH(l.dateadded)
")->result();

}
public function staff_monthly_status()
{
    return $this->db->query("
        SELECT 
            MONTH(l.dateadded) as month,

            IFNULL(CONCAT(s.firstname,' ',s.lastname), 'Unassigned') as staff,

            st.name as status,

            COUNT(l.id) as total

        FROM ".db_prefix()."leads l

        LEFT JOIN ".db_prefix()."staff s
            ON s.staffid = l.assigned

        LEFT JOIN ".db_prefix()."leads_status st
            ON st.id = l.status

        GROUP BY 
            MONTH(l.dateadded), 
            s.staffid, 
            st.id

        ORDER BY 
            MONTH(l.dateadded)
    ")->result();
}
public function sales_forecast()
{

$this->db->select_sum('total');

$result = $this->db->get(db_prefix().'proposals')->row();

return (object)[
    'forecast' => $result ? $result->total : 0
];

}

}