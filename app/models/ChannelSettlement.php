<?php
/**
 * ChannelSettlement Model - 渠道结算
 */

require_once __DIR__ . '/../config/database.php';

class ChannelSettlement {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    private function generateSettlementNo() {
        return 'SET' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 8));
    }
    
    public function create($data) {
        $sql = "INSERT INTO channel_settlements (settlement_no, channel_id, license_id, invoice_id, company_id, transaction_amount, commission_rate, commission_amount, settlement_date, status, notes) 
                VALUES (:settlement_no, :channel_id, :license_id, :invoice_id, :company_id, :transaction_amount, :commission_rate, :commission_amount, :settlement_date, :status, :notes)";
        
        $params = [
            ':settlement_no' => $this->generateSettlementNo(),
            ':channel_id' => $data['channel_id'],
            ':license_id' => $data['license_id'],
            ':invoice_id' => $data['invoice_id'] ?? null,
            ':company_id' => $data['company_id'] ?? null,
            ':transaction_amount' => $data['transaction_amount'],
            ':commission_rate' => $data['commission_rate'] ?? 0.10,
            ':commission_amount' => $data['commission_amount'] ?? ($data['transaction_amount'] * ($data['commission_rate'] ?? 0.10)),
            ':settlement_date' => $data['settlement_date'] ?? date('Y-m-d'),
            ':status' => $data['status'] ?? 'pending',
            ':notes' => $data['notes'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT cs.*, ch.name as channel_name, l.license_key, c.name as company_name, i.invoice_no 
                FROM channel_settlements cs 
                LEFT JOIN channels ch ON cs.channel_id = ch.id 
                LEFT JOIN licenses l ON cs.license_id = l.id 
                LEFT JOIN companies c ON cs.company_id = c.id 
                LEFT JOIN invoices i ON cs.invoice_id = i.id 
                WHERE cs.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByChannelId($channelId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT cs.*, ch.name as channel_name, l.license_key, c.name as company_name, i.invoice_no 
                FROM channel_settlements cs 
                LEFT JOIN channels ch ON cs.channel_id = ch.id 
                LEFT JOIN licenses l ON cs.license_id = l.id 
                LEFT JOIN companies c ON cs.company_id = c.id 
                LEFT JOIN invoices i ON cs.invoice_id = i.id 
                WHERE cs.channel_id = :channel_id 
                ORDER BY cs.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':channel_id' => $channelId]);
    }
    
    public function findByLicenseId($licenseId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT cs.*, ch.name as channel_name, l.license_key, c.name as company_name, i.invoice_no 
                FROM channel_settlements cs 
                LEFT JOIN channels ch ON cs.channel_id = ch.id 
                LEFT JOIN licenses l ON cs.license_id = l.id 
                LEFT JOIN companies c ON cs.company_id = c.id 
                LEFT JOIN invoices i ON cs.invoice_id = i.id 
                WHERE cs.license_id = :license_id 
                ORDER BY cs.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':license_id' => $licenseId]);
    }
    
    public function findByCompanyId($companyId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT cs.*, ch.name as channel_name, l.license_key, c.name as company_name, i.invoice_no 
                FROM channel_settlements cs 
                LEFT JOIN channels ch ON cs.channel_id = ch.id 
                LEFT JOIN licenses l ON cs.license_id = l.id 
                LEFT JOIN companies c ON cs.company_id = c.id 
                LEFT JOIN invoices i ON cs.invoice_id = i.id 
                WHERE cs.company_id = :company_id 
                ORDER BY cs.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':company_id' => $companyId]);
    }
    
    public function findByStatus($status, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT cs.*, ch.name as channel_name, l.license_key, c.name as company_name, i.invoice_no 
                FROM channel_settlements cs 
                LEFT JOIN channels ch ON cs.channel_id = ch.id 
                LEFT JOIN licenses l ON cs.license_id = l.id 
                LEFT JOIN companies c ON cs.company_id = c.id 
                LEFT JOIN invoices i ON cs.invoice_id = i.id 
                WHERE cs.status = :status 
                ORDER BY cs.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':status' => $status]);
    }
    
    public function markProcessing($id) {
        $sql = "UPDATE channel_settlements SET status = 'processing' WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
    
    public function markCompleted($id, $paidBy) {
        $sql = "UPDATE channel_settlements SET status = 'completed', paid_at = NOW(), paid_by = :paid_by WHERE id = :id";
        $this->db->execute($sql, [':id' => $id, ':paid_by' => $paidBy]);
        return true;
    }
    
    public function markCancelled($id) {
        $sql = "UPDATE channel_settlements SET status = 'cancelled' WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
    
    public function calculateCommission($transactionAmount, $commissionRate = 0.10) {
        return $transactionAmount * $commissionRate;
    }
    
    public function getTotalCommissionByChannel($channelId, $startDate = null, $endDate = null) {
        $sql = "SELECT SUM(commission_amount) as total_commission, COUNT(*) as count 
                FROM channel_settlements 
                WHERE channel_id = :channel_id AND status = 'completed'";
        $params = [':channel_id' => $channelId];
        
        if ($startDate) {
            $sql .= " AND settlement_date >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND settlement_date <= :end_date";
            $params[':end_date'] = $endDate;
        }
        
        return $this->db->fetchOne($sql, $params);
    }
}
