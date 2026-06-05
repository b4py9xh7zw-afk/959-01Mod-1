<?php
/**
 * License Model
 */

require_once __DIR__ . '/../config/database.php';

class License {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO licenses (license_key, user_id, product_name, status, expires_at, created_at) 
                VALUES (:license_key, :user_id, :product_name, :status, :expires_at, NOW())";
        
        $params = [
            ':license_key' => $this->generateLicenseKey(),
            ':user_id' => $data['user_id'],
            ':product_name' => $data['product_name'],
            ':status' => $data['status'] ?? 'active',
            ':expires_at' => $data['expires_at'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT l.*, u.username, u.email 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE l.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByKey($key) {
        $sql = "SELECT l.*, u.username, u.email 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE l.license_key = :key";
        return $this->db->fetchOne($sql, [':key' => $key]);
    }
    
    public function findByUserId($userId, $limit = 100, $offset = 0) {
        // Ensure limit and offset are integers to prevent SQL injection
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT l.*, u.username, u.email 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE l.user_id = :user_id 
                ORDER BY l.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':user_id' => $userId]);
    }
    
    public function findAll($limit = 100, $offset = 0) {
        // Ensure limit and offset are integers to prevent SQL injection
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT l.*, u.username, u.email 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                ORDER BY l.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql);
    }
    
    public function count() {
        $sql = "SELECT COUNT(*) as count FROM licenses";
        $result = $this->db->fetchOne($sql);
        return $result['count'] ?? 0;
    }
    
    public function countByStatus($status) {
        $sql = "SELECT COUNT(*) as count FROM licenses WHERE status = :status";
        $result = $this->db->fetchOne($sql, [':status' => $status]);
        return $result['count'] ?? 0;
    }
    
    public function countByUserId($userId) {
        $sql = "SELECT COUNT(*) as count FROM licenses WHERE user_id = :user_id";
        $result = $this->db->fetchOne($sql, [':user_id' => $userId]);
        return $result['count'] ?? 0;
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['product_name'])) {
            $fields[] = "product_name = :product_name";
            $params[':product_name'] = $data['product_name'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        if (isset($data['expires_at'])) {
            $fields[] = "expires_at = :expires_at";
            $params[':expires_at'] = $data['expires_at'];
        }
        if (isset($data['user_id'])) {
            $fields[] = "user_id = :user_id";
            $params[':user_id'] = $data['user_id'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE licenses SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function delete($id) {
        $sql = "DELETE FROM licenses WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
    
    private function generateLicenseKey() {
        return strtoupper(
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8)
        );
    }
    
    public function validate($licenseKey) {
        $license = $this->findByKey($licenseKey);
        if (!$license) {
            return ['valid' => false, 'message' => 'License key not found'];
        }
        
        if ($license['status'] !== 'active') {
            return ['valid' => false, 'message' => 'License is not active'];
        }
        
        if ($license['is_frozen']) {
            return ['valid' => false, 'message' => 'License is frozen'];
        }
        
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            if (!$license['grace_period_end'] || strtotime($license['grace_period_end']) < time()) {
                return ['valid' => false, 'message' => 'License has expired'];
            }
        }
        
        return ['valid' => true, 'license' => $license];
    }
    
    public function findByIdWithDetails($id) {
        $sql = "SELECT l.*, u.username, u.email, c.name as company_name, ch.name as channel_name 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                LEFT JOIN companies c ON l.company_id = c.id 
                LEFT JOIN channels ch ON l.channel_id = ch.id 
                WHERE l.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByChannelId($channelId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT l.*, u.username, u.email, c.name as company_name 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                LEFT JOIN companies c ON l.company_id = c.id 
                WHERE l.channel_id = :channel_id 
                ORDER BY l.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':channel_id' => $channelId]);
    }
    
    public function findByCompanyId($companyId, $limit = 100, $offset = 0) {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT l.*, u.username, u.email, ch.name as channel_name 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                LEFT JOIN channels ch ON l.channel_id = ch.id 
                WHERE l.company_id = :company_id 
                ORDER BY l.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, [':company_id' => $companyId]);
    }
    
    public function findExpiring($days = 30, $channelId = null) {
        $sql = "SELECT l.*, u.username, u.email, c.name as company_name, ch.name as channel_name 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                LEFT JOIN companies c ON l.company_id = c.id 
                LEFT JOIN channels ch ON l.channel_id = ch.id 
                WHERE l.expires_at IS NOT NULL 
                AND l.expires_at <= DATE_ADD(NOW(), INTERVAL :days DAY) 
                AND l.expires_at > NOW() 
                AND l.status = 'active'";
        
        $params = [':days' => $days];
        
        if ($channelId) {
            $sql .= " AND l.channel_id = :channel_id";
            $params[':channel_id'] = $channelId;
        }
        
        $sql .= " ORDER BY l.expires_at ASC";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findInGracePeriod($channelId = null) {
        $sql = "SELECT l.*, u.username, u.email, c.name as company_name, ch.name as channel_name 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                LEFT JOIN companies c ON l.company_id = c.id 
                LEFT JOIN channels ch ON l.channel_id = ch.id 
                WHERE l.grace_period_end IS NOT NULL 
                AND l.grace_period_end > NOW() 
                AND l.expires_at < NOW() 
                AND l.status = 'active'";
        
        $params = [];
        
        if ($channelId) {
            $sql .= " AND l.channel_id = :channel_id";
            $params[':channel_id'] = $channelId;
        }
        
        $sql .= " ORDER BY l.grace_period_end ASC";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findExpired($channelId = null) {
        $sql = "SELECT l.*, u.username, u.email, c.name as company_name, ch.name as channel_name 
                FROM licenses l 
                LEFT JOIN users u ON l.user_id = u.id 
                LEFT JOIN companies c ON l.company_id = c.id 
                LEFT JOIN channels ch ON l.channel_id = ch.id 
                WHERE (l.expires_at IS NULL OR l.expires_at < NOW()) 
                AND (l.grace_period_end IS NULL OR l.grace_period_end < NOW()) 
                AND l.status = 'expired'";
        
        $params = [];
        
        if ($channelId) {
            $sql .= " AND l.channel_id = :channel_id";
            $params[':channel_id'] = $channelId;
        }
        
        $sql .= " ORDER BY l.expires_at DESC";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function countByChannelId($channelId) {
        $sql = "SELECT COUNT(*) as count FROM licenses WHERE channel_id = :channel_id";
        $result = $this->db->fetchOne($sql, [':channel_id' => $channelId]);
        return $result['count'] ?? 0;
    }
    
    public function countByCompanyId($companyId) {
        $sql = "SELECT COUNT(*) as count FROM licenses WHERE company_id = :company_id";
        $result = $this->db->fetchOne($sql, [':company_id' => $companyId]);
        return $result['count'] ?? 0;
    }
    
    public function countByRenewalStatus($renewalStatus, $channelId = null) {
        $sql = "SELECT COUNT(*) as count FROM licenses WHERE renewal_status = :renewal_status";
        $params = [':renewal_status' => $renewalStatus];
        
        if ($channelId) {
            $sql .= " AND channel_id = :channel_id";
            $params[':channel_id'] = $channelId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
    
    public function renew($licenseId, $renewalData) {
        $license = $this->findById($licenseId);
        if (!$license) {
            throw new Exception('License not found');
        }
        
        $oldExpiresAt = $license['expires_at'];
        $renewalMonths = $renewalData['renewal_months'] ?? 12;
        
        if ($oldExpiresAt && strtotime($oldExpiresAt) > time()) {
            $newExpiresAt = date('Y-m-d H:i:s', strtotime($oldExpiresAt . " + {$renewalMonths} months"));
        } else {
            $newExpiresAt = date('Y-m-d H:i:s', strtotime("+{$renewalMonths} months"));
        }
        
        $gracePeriodDays = $renewalData['grace_period_days'] ?? $license['grace_period_days'] ?? 30;
        $gracePeriodEnd = date('Y-m-d H:i:s', strtotime($newExpiresAt . " + {$gracePeriodDays} days"));
        
        $sql = "UPDATE licenses SET 
                expires_at = :expires_at, 
                grace_period_end = :grace_period_end, 
                grace_period_days = :grace_period_days,
                renewal_status = 'active', 
                status = 'active', 
                is_frozen = 0,
                last_renewed_at = NOW(),
                seats = :seats,
                auto_renew = :auto_renew
                WHERE id = :id";
        
        $params = [
            ':id' => $licenseId,
            ':expires_at' => $newExpiresAt,
            ':grace_period_end' => $gracePeriodEnd,
            ':grace_period_days' => $gracePeriodDays,
            ':seats' => $renewalData['seats'] ?? $license['seats'] ?? 1,
            ':auto_renew' => $renewalData['auto_renew'] ?? 0
        ];
        
        $this->db->execute($sql, $params);
        
        return [
            'license_id' => $licenseId,
            'old_expires_at' => $oldExpiresAt,
            'new_expires_at' => $newExpiresAt,
            'grace_period_end' => $gracePeriodEnd
        ];
    }
    
    public function deactivate($licenseId, $reason = null) {
        $sql = "UPDATE licenses SET 
                status = 'inactive', 
                renewal_status = 'suspended', 
                is_frozen = 1 
                WHERE id = :id";
        
        $this->db->execute($sql, [':id' => $licenseId]);
        
        require_once __DIR__ . '/Device.php';
        $deviceModel = new Device();
        $unboundCount = $deviceModel->unbindAllByLicenseId($licenseId);
        
        return [
            'license_id' => $licenseId,
            'unbound_devices' => $unboundCount,
            'reason' => $reason
        ];
    }
    
    public function reactivate($licenseId) {
        $license = $this->findById($licenseId);
        if (!$license) {
            throw new Exception('License not found');
        }
        
        $renewalStatus = 'active';
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            if ($license['grace_period_end'] && strtotime($license['grace_period_end']) > time()) {
                $renewalStatus = 'in_grace_period';
            } else {
                $renewalStatus = 'expired';
            }
        }
        
        $sql = "UPDATE licenses SET 
                status = 'active', 
                renewal_status = :renewal_status, 
                is_frozen = 0 
                WHERE id = :id";
        
        $this->db->execute($sql, [
            ':id' => $licenseId,
            ':renewal_status' => $renewalStatus
        ]);
        
        return true;
    }
    
    public function freezeSeats($licenseId) {
        $sql = "UPDATE licenses SET is_frozen = 1, renewal_status = 'suspended' WHERE id = :id";
        $this->db->execute($sql, [':id' => $licenseId]);
        
        require_once __DIR__ . '/Device.php';
        $deviceModel = new Device();
        $unboundCount = $deviceModel->unbindAllByLicenseId($licenseId);
        
        return [
            'license_id' => $licenseId,
            'unbound_devices' => $unboundCount
        ];
    }
    
    public function unfreezeSeats($licenseId) {
        $license = $this->findById($licenseId);
        $renewalStatus = $license['renewal_status'] === 'suspended' ? 'active' : $license['renewal_status'];
        
        $sql = "UPDATE licenses SET is_frozen = 0, renewal_status = :renewal_status WHERE id = :id";
        $this->db->execute($sql, [
            ':id' => $licenseId,
            ':renewal_status' => $renewalStatus
        ]);
        return true;
    }
    
    public function checkAndUpdateExpiryStatus() {
        $sql = "UPDATE licenses SET 
                renewal_status = CASE 
                    WHEN expires_at IS NULL THEN renewal_status
                    WHEN expires_at > NOW() THEN 'active'
                    WHEN grace_period_end IS NOT NULL AND grace_period_end > NOW() THEN 'in_grace_period'
                    ELSE 'expired'
                END,
                status = CASE 
                    WHEN expires_at IS NULL THEN status
                    WHEN expires_at > NOW() THEN status
                    WHEN grace_period_end IS NOT NULL AND grace_period_end > NOW() THEN 'active'
                    ELSE 'expired'
                END
                WHERE renewal_status IN ('active', 'in_grace_period', 'pending_renewal')";
        
        $this->db->execute($sql);
        return $this->db->query("SELECT ROW_COUNT()")->fetchColumn();
    }
    
    public function canGenerateActivationCode($licenseId) {
        $license = $this->findById($licenseId);
        if (!$license) {
            return ['allowed' => false, 'message' => 'License not found'];
        }
        
        if ($license['company_id']) {
            require_once __DIR__ . '/Company.php';
            $companyModel = new Company();
            $company = $companyModel->findById($license['company_id']);
            if ($company && $company['status'] === 'expired') {
                return ['allowed' => false, 'message' => '企业客户已过期，不能生成新的激活码'];
            }
        }
        
        if ($license['renewal_status'] === 'expired') {
            return ['allowed' => false, 'message' => '许可证已过期，不能生成新的激活码'];
        }
        
        if ($license['is_frozen']) {
            return ['allowed' => false, 'message' => '许可证已冻结，不能生成新的激活码'];
        }
        
        if ($license['status'] !== 'active') {
            return ['allowed' => false, 'message' => '许可证未激活，不能生成新的激活码'];
        }
        
        return ['allowed' => true, 'license' => $license];
    }
    
    public function getHistory($licenseId) {
        $result = [];
        
        require_once __DIR__ . '/OperationLog.php';
        $operationLogModel = new OperationLog();
        $result['operation_logs'] = $operationLogModel->findByLicenseId($licenseId);
        
        require_once __DIR__ . '/Device.php';
        $deviceModel = new Device();
        $result['devices'] = $deviceModel->findByLicenseId($licenseId);
        
        require_once __DIR__ . '/Contract.php';
        $contractModel = new Contract();
        $result['contracts'] = $contractModel->findByLicenseId($licenseId);
        
        require_once __DIR__ . '/Invoice.php';
        $invoiceModel = new Invoice();
        $result['invoices'] = $invoiceModel->findByLicenseId($licenseId);
        
        require_once __DIR__ . '/ActivationCode.php';
        $activationCodeModel = new ActivationCode();
        $result['activation_codes'] = $activationCodeModel->findByLicenseId($licenseId);
        
        require_once __DIR__ . '/ChannelSettlement.php';
        $settlementModel = new ChannelSettlement();
        $result['settlements'] = $settlementModel->findByLicenseId($licenseId);
        
        require_once __DIR__ . '/RenewalPool.php';
        $renewalPoolModel = new RenewalPool();
        $result['renewal_pools'] = $renewalPoolModel->findByLicenseId($licenseId);
        
        return $result;
    }
}
