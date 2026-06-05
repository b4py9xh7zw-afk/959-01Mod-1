<?php
/**
 * ActivationCode Model - 激活码（过期客户不能生成新的激活码）
 */

require_once __DIR__ . '/../config/database.php';

class ActivationCode {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    private function generateCode() {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 4) . '-' . 
                          substr(md5(uniqid(rand(), true)), 0, 4) . '-' . 
                          substr(md5(uniqid(rand(), true)), 0, 4));
    }
    
    public function create($data) {
        $sql = "INSERT INTO activation_codes (code, license_id, company_id, generated_by, expires_at, max_activations, status) 
                VALUES (:code, :license_id, :company_id, :generated_by, :expires_at, :max_activations, :status)";
        
        $params = [
            ':code' => $this->generateCode(),
            ':license_id' => $data['license_id'],
            ':company_id' => $data['company_id'] ?? null,
            ':generated_by' => $data['generated_by'],
            ':expires_at' => $data['expires_at'] ?? null,
            ':max_activations' => $data['max_activations'] ?? 1,
            ':status' => 'active'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT ac.*, l.license_key, l.product_name, c.name as company_name, 
                gen.username as generated_by_name, used.username as used_by_name 
                FROM activation_codes ac 
                LEFT JOIN licenses l ON ac.license_id = l.id 
                LEFT JOIN companies c ON ac.company_id = c.id 
                LEFT JOIN users gen ON ac.generated_by = gen.id 
                LEFT JOIN users used ON ac.used_by = used.id 
                WHERE ac.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByCode($code) {
        $sql = "SELECT ac.*, l.license_key, l.product_name, l.status as license_status, 
                l.expires_at as license_expires_at, c.name as company_name, 
                c.status as company_status 
                FROM activation_codes ac 
                LEFT JOIN licenses l ON ac.license_id = l.id 
                LEFT JOIN companies c ON ac.company_id = c.id 
                WHERE ac.code = :code";
        return $this->db->fetchOne($sql, [':code' => $code]);
    }
    
    public function findByLicenseId($licenseId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT ac.*, l.license_key, l.product_name, c.name as company_name, 
                gen.username as generated_by_name, used.username as used_by_name 
                FROM activation_codes ac 
                LEFT JOIN licenses l ON ac.license_id = l.id 
                LEFT JOIN companies c ON ac.company_id = c.id 
                LEFT JOIN users gen ON ac.generated_by = gen.id 
                LEFT JOIN users used ON ac.used_by = used.id 
                WHERE ac.license_id = :license_id 
                ORDER BY ac.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':license_id' => $licenseId]);
    }
    
    public function findByCompanyId($companyId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT ac.*, l.license_key, l.product_name, c.name as company_name, 
                gen.username as generated_by_name, used.username as used_by_name 
                FROM activation_codes ac 
                LEFT JOIN licenses l ON ac.license_id = l.id 
                LEFT JOIN companies c ON ac.company_id = c.id 
                LEFT JOIN users gen ON ac.generated_by = gen.id 
                LEFT JOIN users used ON ac.used_by = used.id 
                WHERE ac.company_id = :company_id 
                ORDER BY ac.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':company_id' => $companyId]);
    }
    
    public function validate($code) {
        $activationCode = $this->findByCode($code);
        if (!$activationCode) {
            return ['valid' => false, 'message' => '激活码不存在'];
        }
        
        if ($activationCode['status'] !== 'active') {
            return ['valid' => false, 'message' => '激活码已使用或已过期'];
        }
        
        if ($activationCode['expires_at'] && strtotime($activationCode['expires_at']) < time()) {
            return ['valid' => false, 'message' => '激活码已过期'];
        }
        
        if ($activationCode['used_count'] >= $activationCode['max_activations']) {
            return ['valid' => false, 'message' => '激活码已达到最大使用次数'];
        }
        
        if ($activationCode['license_status'] !== 'active') {
            return ['valid' => false, 'message' => '关联许可证未激活'];
        }
        
        if ($activationCode['company_status'] === 'expired') {
            return ['valid' => false, 'message' => '企业客户已过期'];
        }
        
        return ['valid' => true, 'activationCode' => $activationCode];
    }
    
    public function use($id, $usedBy) {
        $sql = "UPDATE activation_codes SET 
                used_by = :used_by, 
                used_at = NOW(), 
                used_count = used_count + 1,
                status = CASE 
                    WHEN used_count + 1 >= max_activations THEN 'used' 
                    ELSE status 
                END 
                WHERE id = :id AND status = 'active' AND used_count < max_activations";
        
        $this->db->execute($sql, [':id' => $id, ':used_by' => $usedBy]);
        return $this->db->query("SELECT ROW_COUNT()")->fetchColumn() > 0;
    }
    
    public function revoke($id) {
        $sql = "UPDATE activation_codes SET status = 'revoked' WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
    
    public function countByLicenseId($licenseId, $status = null) {
        $sql = "SELECT COUNT(*) as count FROM activation_codes WHERE license_id = :license_id";
        $params = [':license_id' => $licenseId];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
}
