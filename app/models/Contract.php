<?php
/**
 * Contract Model
 */

require_once __DIR__ . '/../config/database.php';

class Contract {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    private function generateContractNo() {
        return 'CT' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 8));
    }
    
    public function create($data) {
        $sql = "INSERT INTO contracts (contract_no, license_id, company_id, channel_id, contract_type, start_date, end_date, total_amount, seats, status, signed_by_company, signed_by_platform, contract_file, notes) 
                VALUES (:contract_no, :license_id, :company_id, :channel_id, :contract_type, :start_date, :end_date, :total_amount, :seats, :status, :signed_by_company, :signed_by_platform, :contract_file, :notes)";
        
        $params = [
            ':contract_no' => $this->generateContractNo(),
            ':license_id' => $data['license_id'],
            ':company_id' => $data['company_id'] ?? null,
            ':channel_id' => $data['channel_id'] ?? null,
            ':contract_type' => $data['contract_type'] ?? 'new',
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':total_amount' => $data['total_amount'],
            ':seats' => $data['seats'] ?? 1,
            ':status' => $data['status'] ?? 'draft',
            ':signed_by_company' => $data['signed_by_company'] ?? null,
            ':signed_by_platform' => $data['signed_by_platform'] ?? null,
            ':contract_file' => $data['contract_file'] ?? null,
            ':notes' => $data['notes'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT c.*, l.license_key, comp.name as company_name, ch.name as channel_name 
                FROM contracts c 
                LEFT JOIN licenses l ON c.license_id = l.id 
                LEFT JOIN companies comp ON c.company_id = comp.id 
                LEFT JOIN channels ch ON c.channel_id = ch.id 
                WHERE c.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByLicenseId($licenseId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT c.*, l.license_key, comp.name as company_name, ch.name as channel_name 
                FROM contracts c 
                LEFT JOIN licenses l ON c.license_id = l.id 
                LEFT JOIN companies comp ON c.company_id = comp.id 
                LEFT JOIN channels ch ON c.channel_id = ch.id 
                WHERE c.license_id = :license_id 
                ORDER BY c.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':license_id' => $licenseId]);
    }
    
    public function findByCompanyId($companyId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT c.*, l.license_key, comp.name as company_name, ch.name as channel_name 
                FROM contracts c 
                LEFT JOIN licenses l ON c.license_id = l.id 
                LEFT JOIN companies comp ON c.company_id = comp.id 
                LEFT JOIN channels ch ON c.channel_id = ch.id 
                WHERE c.company_id = :company_id 
                ORDER BY c.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':company_id' => $companyId]);
    }
    
    public function findByChannelId($channelId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT c.*, l.license_key, comp.name as company_name, ch.name as channel_name 
                FROM contracts c 
                LEFT JOIN licenses l ON c.license_id = l.id 
                LEFT JOIN companies comp ON c.company_id = comp.id 
                LEFT JOIN channels ch ON c.channel_id = ch.id 
                WHERE c.channel_id = :channel_id 
                ORDER BY c.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':channel_id' => $channelId]);
    }
    
    public function sign($id, $signedBy = null, $signedByCompany = null) {
        $fields = ['status = :status', 'signed_at = NOW()'];
        $params = [':id' => $id, ':status' => 'active'];
        
        if ($signedBy) {
            $fields[] = 'signed_by_platform = :signed_by_platform';
            $params[':signed_by_platform'] = $signedBy;
        }
        if ($signedByCompany) {
            $fields[] = 'signed_by_company = :signed_by_company';
            $params[':signed_by_company'] = $signedByCompany;
        }
        
        $sql = "UPDATE contracts SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function terminate($id) {
        $sql = "UPDATE contracts SET status = 'terminated' WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['start_date', 'end_date', 'total_amount', 'seats', 'status', 'signed_by_company', 'signed_by_platform', 'contract_file', 'notes', 'contract_type'];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE contracts SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function delete($id) {
        $sql = "DELETE FROM contracts WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
}
