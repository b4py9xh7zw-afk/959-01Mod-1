<?php
/**
 * Channel Model - Channel Partner Model
 */

require_once __DIR__ . '/../config/database.php';

class Channel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO channels (name, contact_person, contact_phone, contact_email, commission_rate, status) 
                VALUES (:name, :contact_person, :contact_phone, :contact_email, :commission_rate, :status)";
        
        $params = [
            ':name' => $data['name'],
            ':contact_person' => $data['contact_person'] ?? null,
            ':contact_phone' => $data['contact_phone'] ?? null,
            ':contact_email' => $data['contact_email'] ?? null,
            ':commission_rate' => $data['commission_rate'] ?? 0.10,
            ':status' => $data['status'] ?? 'active'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findByName($name) {
        $sql = "SELECT * FROM channels WHERE name = :name";
        return $this->db->fetchOne($sql, [':name' => $name]);
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM channels WHERE id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findAll($limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT * FROM channels ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql);
    }
    
    public function findActive() {
        $sql = "SELECT * FROM channels WHERE status = 'active' ORDER BY name";
        return $this->db->fetchAll($sql);
    }
    
    public function count() {
        $sql = "SELECT COUNT(*) as count FROM channels";
        $result = $this->db->fetchOne($sql);
        return $result['count'] ?? 0;
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
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
        if (isset($data['commission_rate'])) {
            $fields[] = "commission_rate = :commission_rate";
            $params[':commission_rate'] = $data['commission_rate'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE channels SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function delete($id) {
        $sql = "DELETE FROM channels WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
}
