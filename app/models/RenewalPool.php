<?php
/**
 * RenewalPool Model - 续订池（渠道商查看自己客户的续订池）
 */

require_once __DIR__ . '/../config/database.php';

class RenewalPool {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO renewal_pools (license_id, company_id, channel_id, original_expires_at, renewal_deadline, renewal_status, renewal_amount, renewal_months, assigned_to, notes) 
                VALUES (:license_id, :company_id, :channel_id, :original_expires_at, :renewal_deadline, :renewal_status, :renewal_amount, :renewal_months, :assigned_to, :notes)";
        
        $params = [
            ':license_id' => $data['license_id'],
            ':company_id' => $data['company_id'],
            ':channel_id' => $data['channel_id'],
            ':original_expires_at' => $data['original_expires_at'],
            ':renewal_deadline' => $data['renewal_deadline'],
            ':renewal_status' => $data['renewal_status'] ?? 'pending',
            ':renewal_amount' => $data['renewal_amount'] ?? null,
            ':renewal_months' => $data['renewal_months'] ?? 12,
            ':assigned_to' => $data['assigned_to'] ?? null,
            ':notes' => $data['notes'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT rp.*, l.license_key, l.product_name, c.name as company_name, ch.name as channel_name, u.username as assigned_to_name 
                FROM renewal_pools rp 
                LEFT JOIN licenses l ON rp.license_id = l.id 
                LEFT JOIN companies c ON rp.company_id = c.id 
                LEFT JOIN channels ch ON rp.channel_id = ch.id 
                LEFT JOIN users u ON rp.assigned_to = u.id 
                WHERE rp.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByChannelId($channelId, $status = null, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT rp.*, l.license_key, l.product_name, c.name as company_name, ch.name as channel_name, u.username as assigned_to_name 
                FROM renewal_pools rp 
                LEFT JOIN licenses l ON rp.license_id = l.id 
                LEFT JOIN companies c ON rp.company_id = c.id 
                LEFT JOIN channels ch ON rp.channel_id = ch.id 
                LEFT JOIN users u ON rp.assigned_to = u.id 
                WHERE rp.channel_id = :channel_id";
        
        $params = [':channel_id' => $channelId];
        
        if ($status) {
            $sql .= " AND rp.renewal_status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY rp.renewal_deadline ASC LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findByCompanyId($companyId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT rp.*, l.license_key, l.product_name, c.name as company_name, ch.name as channel_name, u.username as assigned_to_name 
                FROM renewal_pools rp 
                LEFT JOIN licenses l ON rp.license_id = l.id 
                LEFT JOIN companies c ON rp.company_id = c.id 
                LEFT JOIN channels ch ON rp.channel_id = ch.id 
                LEFT JOIN users u ON rp.assigned_to = u.id 
                WHERE rp.company_id = :company_id 
                ORDER BY rp.renewal_deadline ASC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':company_id' => $companyId]);
    }
    
    public function findByLicenseId($licenseId) {
        $sql = "SELECT rp.*, l.license_key, l.product_name, c.name as company_name, ch.name as channel_name, u.username as assigned_to_name 
                FROM renewal_pools rp 
                LEFT JOIN licenses l ON rp.license_id = l.id 
                LEFT JOIN companies c ON rp.company_id = c.id 
                LEFT JOIN channels ch ON rp.channel_id = ch.id 
                LEFT JOIN users u ON rp.assigned_to = u.id 
                WHERE rp.license_id = :license_id 
                ORDER BY rp.created_at DESC";
        return $this->db->fetchAll($sql, [':license_id' => $licenseId]);
    }
    
    public function findAll($status = null, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT rp.*, l.license_key, l.product_name, c.name as company_name, ch.name as channel_name, u.username as assigned_to_name 
                FROM renewal_pools rp 
                LEFT JOIN licenses l ON rp.license_id = l.id 
                LEFT JOIN companies c ON rp.company_id = c.id 
                LEFT JOIN channels ch ON rp.channel_id = ch.id 
                LEFT JOIN users u ON rp.assigned_to = u.id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE rp.renewal_status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY rp.renewal_deadline ASC LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE renewal_pools SET renewal_status = :status WHERE id = :id";
        $this->db->execute($sql, [':id' => $id, ':status' => $status]);
        return true;
    }
    
    public function markNotified($id) {
        $sql = "UPDATE renewal_pools SET notified_count = notified_count + 1, last_notified_at = NOW() WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
    
    public function countByChannelId($channelId, $status = null) {
        $sql = "SELECT COUNT(*) as count FROM renewal_pools WHERE channel_id = :channel_id";
        $params = [':channel_id' => $channelId];
        
        if ($status) {
            $sql .= " AND renewal_status = :status";
            $params[':status'] = $status;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
    
    public function getExpiringSoon($channelId = null, $days = 30) {
        $sql = "SELECT rp.*, l.license_key, l.product_name, c.name as company_name, ch.name as channel_name 
                FROM renewal_pools rp 
                LEFT JOIN licenses l ON rp.license_id = l.id 
                LEFT JOIN companies c ON rp.company_id = c.id 
                LEFT JOIN channels ch ON rp.channel_id = ch.id 
                WHERE rp.renewal_status IN ('pending', 'in_progress') 
                AND rp.renewal_deadline <= DATE_ADD(NOW(), INTERVAL :days DAY)";
        
        $params = [':days' => $days];
        
        if ($channelId) {
            $sql .= " AND rp.channel_id = :channel_id";
            $params[':channel_id'] = $channelId;
        }
        
        $sql .= " ORDER BY rp.renewal_deadline ASC";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['original_expires_at', 'renewal_deadline', 'renewal_status', 'renewal_amount', 'renewal_months', 'assigned_to', 'notes'];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE renewal_pools SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function delete($id) {
        $sql = "DELETE FROM renewal_pools WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
}
