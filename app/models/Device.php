<?php
/**
 * Device Model - Device Binding Records
 */

require_once __DIR__ . '/../config/database.php';

class Device {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO devices (license_id, user_id, company_id, device_uuid, device_name, device_type, os_version, hardware_info, ip_address, mac_address, last_activated_at, is_bound) 
                VALUES (:license_id, :user_id, :company_id, :device_uuid, :device_name, :device_type, :os_version, :hardware_info, :ip_address, :mac_address, NOW(), 1)";
        
        $params = [
            ':license_id' => $data['license_id'],
            ':user_id' => $data['user_id'],
            ':company_id' => $data['company_id'] ?? null,
            ':device_uuid' => $data['device_uuid'],
            ':device_name' => $data['device_name'] ?? null,
            ':device_type' => $data['device_type'] ?? null,
            ':os_version' => $data['os_version'] ?? null,
            ':hardware_info' => $data['hardware_info'] ?? null,
            ':ip_address' => $data['ip_address'] ?? null,
            ':mac_address' => $data['mac_address'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT d.*, l.license_key, u.username, u.email, c.name as company_name 
                FROM devices d 
                LEFT JOIN licenses l ON d.license_id = l.id 
                LEFT JOIN users u ON d.user_id = u.id 
                LEFT JOIN companies c ON d.company_id = c.id 
                WHERE d.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByLicenseId($licenseId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT d.*, l.license_key, u.username, u.email, c.name as company_name 
                FROM devices d 
                LEFT JOIN licenses l ON d.license_id = l.id 
                LEFT JOIN users u ON d.user_id = u.id 
                LEFT JOIN companies c ON d.company_id = c.id 
                WHERE d.license_id = :license_id 
                ORDER BY d.bound_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':license_id' => $licenseId]);
    }
    
    public function findByCompanyId($companyId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT d.*, l.license_key, u.username, u.email, c.name as company_name 
                FROM devices d 
                LEFT JOIN licenses l ON d.license_id = l.id 
                LEFT JOIN users u ON d.user_id = u.id 
                LEFT JOIN companies c ON d.company_id = c.id 
                WHERE d.company_id = :company_id 
                ORDER BY d.bound_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':company_id' => $companyId]);
    }
    
    public function findByUserId($userId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT d.*, l.license_key, u.username, u.email, c.name as company_name 
                FROM devices d 
                LEFT JOIN licenses l ON d.license_id = l.id 
                LEFT JOIN users u ON d.user_id = u.id 
                LEFT JOIN companies c ON d.company_id = c.id 
                WHERE d.user_id = :user_id 
                ORDER BY d.bound_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':user_id' => $userId]);
    }
    
    public function findByDeviceUuid($deviceUuid) {
        $sql = "SELECT d.*, l.license_key, u.username, u.email, c.name as company_name 
                FROM devices d 
                LEFT JOIN licenses l ON d.license_id = l.id 
                LEFT JOIN users u ON d.user_id = u.id 
                LEFT JOIN companies c ON d.company_id = c.id 
                WHERE d.device_uuid = :device_uuid 
                ORDER BY d.bound_at DESC";
        return $this->db->fetchAll($sql, [':device_uuid' => $deviceUuid]);
    }
    
    public function unbind($id) {
        $sql = "UPDATE devices SET is_bound = 0, unbound_at = NOW() WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
    
    public function unbindAllByLicenseId($licenseId) {
        $sql = "UPDATE devices SET is_bound = 0, unbound_at = NOW() WHERE license_id = :license_id AND is_bound = 1";
        $this->db->execute($sql, [':license_id' => $licenseId]);
        return $this->db->query("SELECT ROW_COUNT()")->fetchColumn();
    }
    
    public function countBoundByLicenseId($licenseId) {
        $sql = "SELECT COUNT(*) as count FROM devices WHERE license_id = :license_id AND is_bound = 1";
        $result = $this->db->fetchOne($sql, [':license_id' => $licenseId]);
        return $result['count'] ?? 0;
    }
    
    public function delete($id) {
        $sql = "DELETE FROM devices WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
}
