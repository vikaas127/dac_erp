<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Vouchers_api_model extends App_Model
{
    public function financialYearDates()
    {
        $currentMonth = (int) date('n');
        $currentYear  = (int) date('Y');

        if ($currentMonth >= 4) {
            return [
                'from_date' => $currentYear.'-04-01',
                'to_date'   => ($currentYear + 1).'-03-31',
            ];
        }

        return [
            'from_date' => ($currentYear - 1).'-04-01',
            'to_date'   => $currentYear.'-03-31',
        ];
    }

    public function resolveDates($fromDate = null, $toDate = null)
    {
        $fy = $this->financialYearDates();

        return [
            $fromDate ?: $fy['from_date'],
            $toDate ?: $fy['to_date'],
        ];
    }

    public function fetchByType($type, $fromDate, $toDate, $id = null)
    {
        switch ($type) {
            case 'Estimate':
                $this->db->where('date >=', $fromDate);
                $this->db->where('date <=', $toDate);
                if ($id) {
                    $this->db->where('id', (int) $id);

                    return $this->db->get(db_prefix().'estimates')->row_array();
                }

                return $this->db->get(db_prefix().'estimates')->result_array();

            case 'Sales':
                $this->db->where('date >=', $fromDate);
                $this->db->where('date <=', $toDate);
                if ($id) {
                    $this->db->where('id', (int) $id);

                    return $this->db->get(db_prefix().'invoices')->row_array();
                }

                return $this->db->get(db_prefix().'invoices')->result_array();

            case 'Credit Note':
                if ($id) {
                    $this->db->where('id', (int) $id);

                    return $this->db->get(db_prefix().'creditnotes')->row_array();
                }

                return $this->db->get(db_prefix().'creditnotes')->result_array();

            case 'Payment':
                $this->db->select('p.id, p.amount, p.date, p.transactionid, i.number, i.prefix, c.company');
                $this->db->from(db_prefix().'invoicepaymentrecords p');
                $this->db->join(db_prefix().'invoices i', 'i.id = p.invoiceid', 'left');
                $this->db->join(db_prefix().'clients c', 'c.userid = i.clientid', 'left');
                $this->db->where('p.date >=', $fromDate);
                $this->db->where('p.date <=', $toDate);
                if ($id) {
                    $this->db->where('p.id', (int) $id);

                    return $this->db->get()->row_array();
                }
                $this->db->order_by('p.id', 'DESC');

                return $this->db->get()->result_array();

            case 'Expense':
                $this->db->where('date >=', $fromDate);
                $this->db->where('date <=', $toDate);
                if ($id) {
                    $this->db->where('id', (int) $id);

                    return $this->db->get(db_prefix().'expenses')->row_array();
                }

                return $this->db->get(db_prefix().'expenses')->result_array();

            case 'PO':
                if (!$this->db->table_exists(db_prefix().'pur_orders')) {
                    return $id ? null : [];
                }
                $this->db->select('po.*, v.company as vendor_name');
                $this->db->from(db_prefix().'pur_orders po');
                $this->db->join(db_prefix().'pur_vendor v', 'v.userid = po.vendor', 'left');
                $this->db->where('po.order_date >=', $fromDate);
                $this->db->where('po.order_date <=', $toDate);
                if ($id) {
                    $this->db->where('po.id', (int) $id);

                    return $this->db->get()->row_array();
                }
                $this->db->order_by('po.id', 'DESC');

                return $this->db->get()->result_array();

            case 'Journal':
                if (!$this->db->table_exists(db_prefix().'acc_journal_entries')) {
                    return $id ? null : [];
                }
                $this->db->where('journal_date >=', $fromDate);
                $this->db->where('journal_date <=', $toDate);
                if ($id) {
                    $this->db->where('id', (int) $id);

                    return $this->db->get(db_prefix().'acc_journal_entries')->row_array();
                }
                $this->db->order_by('journal_date', 'DESC');

                return $this->db->get(db_prefix().'acc_journal_entries')->result_array();

            default:
                return $id ? null : [];
        }
    }

    public function fetchDetails($type, $id)
    {
        $row = $this->fetchByType($type, '1970-01-01', '2099-12-31', $id);
        if (!$row) {
            return null;
        }

        $details = ['voucher' => $row, 'lines' => []];

        if ($type === 'Sales' && $this->db->table_exists(db_prefix().'itemable')) {
            $this->db->where('rel_id', (int) $id);
            $this->db->where('rel_type', 'invoice');
            $details['lines'] = $this->db->get(db_prefix().'itemable')->result_array();
        } elseif ($type === 'Expense' && $this->db->table_exists(db_prefix().'expenses')) {
            $details['lines'] = [];
        } elseif ($type === 'Journal' && $this->loadAccountingModel()) {
            $details['voucher'] = $this->accounting_model->get_journal_entry((int) $id);
            if ($this->db->table_exists(db_prefix().'acc_journal_entry_details')) {
                $this->db->where('journal_entry', (int) $id);
                $details['lines'] = $this->db->get(db_prefix().'acc_journal_entry_details')->result_array();
            }
        }

        return $details;
    }

    public function fetchLedger($fromDate, $toDate)
    {
        if (!$this->db->table_exists(db_prefix().'acc_journal_entries')) {
            return [];
        }

        $this->db->where('journal_date >=', $fromDate);
        $this->db->where('journal_date <=', $toDate);
        $this->db->order_by('journal_date', 'DESC');
        $this->db->order_by('id', 'DESC');

        return $this->db->get(db_prefix().'acc_journal_entries')->result_array();
    }

    public function fetchInventory($fromDate, $toDate)
    {
        $rows = [];

        if ($this->db->table_exists(db_prefix().'goods_receipt')) {
            $this->db->select('id, goods_receipt_code as code, date_add as date, total_money as amount, supplier_name as party, "receipt" as voucher_kind', false);
            $this->db->from(db_prefix().'goods_receipt');
            $this->db->where('date_add >=', $fromDate);
            $this->db->where('date_add <=', $toDate);
            foreach ($this->db->get()->result_array() as $r) {
                $rows[] = $r;
            }
        }

        if ($this->db->table_exists(db_prefix().'goods_delivery')) {
            $this->db->select('id, goods_delivery_code as code, date_add as date, customer_name as party, "delivery" as voucher_kind', false);
            $this->db->from(db_prefix().'goods_delivery');
            $this->db->where('date_add >=', $fromDate);
            $this->db->where('date_add <=', $toDate);
            foreach ($this->db->get()->result_array() as $r) {
                $r['amount'] = null;
                $rows[] = $r;
            }
        }

        usort($rows, function ($a, $b) {
            return strcmp($b['date'] ?? '', $a['date'] ?? '');
        });

        return $rows;
    }

    public function createJournal(array $data, $staffId)
    {
        if (!$this->loadAccountingModel()) {
            return ['success' => false, 'message' => 'Accounting module required for journal vouchers'];
        }

        $payload = [
            'description'  => $data['description'] ?? '',
            'journal_date' => $data['journal_date'] ?? date('Y-m-d'),
            'amount'       => $data['amount'] ?? 0,
            'number'       => $data['number'] ?? '',
        ];

        if (empty($payload['description'])) {
            return ['success' => false, 'message' => 'description is required'];
        }

        $payload['addedfrom'] = (int) $staffId;
        $id = $this->accounting_model->add_journal_entry($payload);

        return ['success' => $id > 0, 'id' => (int) $id, 'type' => 'Journal'];
    }

    public function updateJournal($id, array $data)
    {
        if (!$this->loadAccountingModel()) {
            return ['success' => false, 'message' => 'Accounting module required'];
        }

        $payload = array_filter([
            'description'  => $data['description'] ?? null,
            'journal_date' => $data['journal_date'] ?? null,
            'amount'       => isset($data['amount']) ? $data['amount'] : null,
            'number'       => $data['number'] ?? null,
        ], function ($v) {
            return $v !== null;
        });

        if (empty($payload)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $ok = $this->accounting_model->update_journal_entry($payload, (int) $id);

        return ['success' => (bool) $ok, 'id' => (int) $id, 'type' => 'Journal'];
    }

    public function deleteJournal($id)
    {
        if (!$this->loadAccountingModel()) {
            return ['success' => false, 'message' => 'Accounting module required'];
        }

        $ok = $this->accounting_model->delete_journal_entry((int) $id);

        return ['success' => (bool) $ok, 'id' => (int) $id, 'type' => 'Journal'];
    }

    private function loadAccountingModel()
    {
        if (!$this->db->table_exists(db_prefix().'acc_journal_entries')) {
            return false;
        }

        $this->load->library('app_modules');
        if (!$this->app_modules->is_active('accounting')) {
            return false;
        }

        $this->load->model('accounting/accounting_model');

        return true;
    }
}
