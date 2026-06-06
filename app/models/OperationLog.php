<?php
/**
 * OperationLog Model
 */

require_once __DIR__ . '/../config/database.php';

class OperationLog {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function log($userId, $action, $actionType = 'other', $description = null, $licenseId = null, $companyId = null, $oldValue = null, $newValue = null) {
        $sql = "INSERT INTO operation_logs (user_id, company_id, license_id, action, action_type, description, old_value, new_value, ip_address, user_agent) 
                VALUES (:user_id, :company_id, :license_id, :action, :action_type, :description, :old_value, :new_value, :ip_address, :user_agent)";
        
        $params = [
            ':user_id' => $userId,
            ':company_id' => $companyId,
            ':license_id' => $licenseId,
            ':action' => $action,
            ':action_type' => $actionType,
            ':description' => $description,
            ':old_value' => $oldValue,
            ':new_value' => $newValue,
            ':ip_address' => $this->getIpAddress(),
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    private function getIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    public function findById($id) {
        $sql = "SELECT ol.*, u.username, l.license_key, c.name as company_name 
                FROM operation_logs ol 
                LEFT JOIN users u ON ol.user_id = u.id 
                LEFT JOIN licenses l ON ol.license_id = l.id 
                LEFT JOIN companies c ON ol.company_id = c.id 
                WHERE ol.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByLicenseId($licenseId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT ol.*, u.username, l.license_key, c.name as company_name 
                FROM operation_logs ol 
                LEFT JOIN users u ON ol.user_id = u.id 
                LEFT JOIN licenses l ON ol.license_id = l.id 
                LEFT JOIN companies c ON ol.company_id = c.id 
                WHERE ol.license_id = :license_id 
                ORDER BY ol.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':license_id' => $licenseId]);
    }
    
    public function findByCompanyId($companyId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT ol.*, u.username, l.license_key, c.name as company_name 
                FROM operation_logs ol 
                LEFT JOIN users u ON ol.user_id = u.id 
                LEFT JOIN licenses l ON ol.license_id = l.id 
                LEFT JOIN companies c ON ol.company_id = c.id 
                WHERE ol.company_id = :company_id 
                ORDER BY ol.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':company_id' => $companyId]);
    }
    
    public function findByUserId($userId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT ol.*, u.username, l.license_key, c.name as company_name 
                FROM operation_logs ol 
                LEFT JOIN users u ON ol.user_id = u.id 
                LEFT JOIN licenses l ON ol.license_id = l.id 
                LEFT JOIN companies c ON ol.company_id = c.id 
                WHERE ol.user_id = :user_id 
                ORDER BY ol.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':user_id' => $userId]);
    }
    
    public function findByActionType($actionType, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT ol.*, u.username, l.license_key, c.name as company_name 
                FROM operation_logs ol 
                LEFT JOIN users u ON ol.user_id = u.id 
                LEFT JOIN licenses l ON ol.license_id = l.id 
                LEFT JOIN companies c ON ol.company_id = c.id 
                WHERE ol.action_type = :action_type 
                ORDER BY ol.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':action_type' => $actionType]);
    }
    
    public function findAll($limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT ol.*, u.username, l.license_key, c.name as company_name 
                FROM operation_logs ol 
                LEFT JOIN users u ON ol.user_id = u.id 
                LEFT JOIN licenses l ON ol.license_id = l.id 
                LEFT JOIN companies c ON ol.company_id = c.id 
                ORDER BY ol.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql);
    }
    
    public function countByLicenseId($licenseId) {
        $sql = "SELECT COUNT(*) as count FROM operation_logs WHERE license_id = :license_id";
        $result = $this->db->fetchOne($sql, [':license_id' => $licenseId]);
        return $result['count'] ?? 0;
    }
}
