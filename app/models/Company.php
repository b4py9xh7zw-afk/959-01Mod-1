<?php
/**
 * Company Model - Enterprise Customer Model
 */

require_once __DIR__ . '/../config/database.php';

class Company {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO companies (name, business_license, contact_person, contact_phone, contact_email, address, channel_id, status) 
                VALUES (:name, :business_license, :contact_person, :contact_phone, :contact_email, :address, :channel_id, :status)";
        
        $params = [
            ':name' => $data['name'],
            ':business_license' => $data['business_license'] ?? null,
            ':contact_person' => $data['contact_person'] ?? null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':contact_email' => $data['contact_email'] ?? null,
            ':address' => $data['address'] ?? null,
            ':channel_id' => $data['channel_id'] ?? null,
            ':status' => $data['status'] ?? 'active'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findByName($name) {
        $sql = "SELECT c.*, ch.name as channel_name 
                FROM companies c 
                LEFT JOIN channels ch ON c.channel_id = ch.id 
                WHERE c.name = :name";
        return $this->db->fetchOne($sql, [':name' => $name]);
    }
    
    public function findById($id) {
        $sql = "SELECT c.*, ch.name as channel_name 
                FROM companies c 
                LEFT JOIN channels ch ON c.channel_id = ch.id 
                WHERE c.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByChannelId($channelId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT c.*, ch.name as channel_name 
                FROM companies c 
                LEFT JOIN channels ch ON c.channel_id = ch.id 
                WHERE c.channel_id = :channel_id 
                ORDER BY c.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':channel_id' => $channelId]);
    }
    
    public function findAll($limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT c.*, ch.name as channel_name 
                FROM companies c 
                LEFT JOIN channels ch ON c.channel_id = ch.id 
                ORDER BY c.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql);
    }
    
    public function count() {
        $sql = "SELECT COUNT(*) as count FROM companies";
        $result = $this->db->fetchOne($sql);
        return $result['count'] ?? 0;
    }
    
    public function countByChannelId($channelId) {
        $sql = "SELECT COUNT(*) as count FROM companies WHERE channel_id = :channel_id";
        $result = $this->db->fetchOne($sql, [':channel_id' => $channelId]);
        return $result['count'] ?? 0;
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }
        if (isset($data['business_license'])) {
            $fields[] = "business_license = :business_license";
            $params[':business_license'] = $data['business_license'];
        }
        if (isset($data['contact_person'])) {
            $fields[] = "contact_person = :contact_person";
            $params[':contact_person'] = $data['contact_person'];
        }
        if (isset($data['contact_phone'])) {
            $fields[] = "contact_phone = :contact_phone";
            $params[':contact_phone'] = $data['contact_phone'];
        }
        if (isset($data['contact_email'])) {
            $fields[] = "contact_email = :contact_email";
            $params[':contact_email'] = $data['contact_email'];
        }
        if (isset($data['address'])) {
            $fields[] = "address = :address";
            $params[':address'] = $data['address'];
        }
        if (isset($data['channel_id'])) {
            $fields[] = "channel_id = :channel_id";
            $params[':channel_id'] = $data['channel_id'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE companies SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function delete($id) {
        $sql = "DELETE FROM companies WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
}
