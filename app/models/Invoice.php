<?php
/**
 * Invoice Model
 */

require_once __DIR__ . '/../config/database.php';

class Invoice {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    private function generateInvoiceNo() {
        return 'INV' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
    }
    
    public function create($data) {
        $sql = "INSERT INTO invoices (invoice_no, license_id, company_id, user_id, channel_id, amount, tax_amount, invoice_type, invoice_title, taxpayer_id, address, phone, bank_name, bank_account, status, issued_at) 
                VALUES (:invoice_no, :license_id, :company_id, :user_id, :channel_id, :amount, :tax_amount, :invoice_type, :invoice_title, :taxpayer_id, :address, :phone, :bank_name, :bank_account, :status, NOW())";
        
        $params = [
            ':invoice_no' => $this->generateInvoiceNo(),
            ':license_id' => $data['license_id'],
            ':company_id' => $data['company_id'] ?? null,
            ':user_id' => $data['user_id'] ?? null,
            ':channel_id' => $data['channel_id'] ?? null,
            ':amount' => $data['amount'],
            ':tax_amount' => $data['tax_amount'] ?? 0,
            ':invoice_type' => $data['invoice_type'] ?? 'general',
            ':invoice_title' => $data['invoice_title'],
            ':taxpayer_id' => $data['taxpayer_id'] ?? null,
            ':address' => $data['address'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':bank_name' => $data['bank_name'] ?? null,
            ':bank_account' => $data['bank_account'] ?? null,
            ':status' => $data['status'] ?? 'pending'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT i.*, l.license_key, c.name as company_name, u.username, ch.name as channel_name 
                FROM invoices i 
                LEFT JOIN licenses l ON i.license_id = l.id 
                LEFT JOIN companies c ON i.company_id = c.id 
                LEFT JOIN users u ON i.user_id = u.id 
                LEFT JOIN channels ch ON i.channel_id = ch.id 
                WHERE i.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByLicenseId($licenseId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT i.*, l.license_key, c.name as company_name, u.username, ch.name as channel_name 
                FROM invoices i 
                LEFT JOIN licenses l ON i.license_id = l.id 
                LEFT JOIN companies c ON i.company_id = c.id 
                LEFT JOIN users u ON i.user_id = u.id 
                LEFT JOIN channels ch ON i.channel_id = ch.id 
                WHERE i.license_id = :license_id 
                ORDER BY i.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':license_id' => $licenseId]);
    }
    
    public function findByCompanyId($companyId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT i.*, l.license_key, c.name as company_name, u.username, ch.name as channel_name 
                FROM invoices i 
                LEFT JOIN licenses l ON i.license_id = l.id 
                LEFT JOIN companies c ON i.company_id = c.id 
                LEFT JOIN users u ON i.user_id = u.id 
                LEFT JOIN channels ch ON i.channel_id = ch.id 
                WHERE i.company_id = :company_id 
                ORDER BY i.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':company_id' => $companyId]);
    }
    
    public function findByChannelId($channelId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT i.*, l.license_key, c.name as company_name, u.username, ch.name as channel_name 
                FROM invoices i 
                LEFT JOIN licenses l ON i.license_id = l.id 
                LEFT JOIN companies c ON i.company_id = c.id 
                LEFT JOIN users u ON i.user_id = u.id 
                LEFT JOIN channels ch ON i.channel_id = ch.id 
                WHERE i.channel_id = :channel_id 
                ORDER BY i.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':channel_id' => $channelId]);
    }
    
    public function confirm($id, $confirmedBy) {
        $sql = "UPDATE invoices SET status = 'confirmed', confirmed_at = NOW(), confirmed_by = :confirmed_by WHERE id = :id";
        $this->db->execute($sql, [':id' => $id, ':confirmed_by' => $confirmedBy]);
        return true;
    }
    
    public function reject($id, $confirmedBy) {
        $sql = "UPDATE invoices SET status = 'rejected', confirmed_at = NOW(), confirmed_by = :confirmed_by WHERE id = :id";
        $this->db->execute($sql, [':id' => $id, ':confirmed_by' => $confirmedBy]);
        return true;
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['amount', 'tax_amount', 'invoice_type', 'invoice_title', 'taxpayer_id', 'address', 'phone', 'bank_name', 'bank_account', 'status'])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE invoices SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function delete($id) {
        $sql = "DELETE FROM invoices WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
}
