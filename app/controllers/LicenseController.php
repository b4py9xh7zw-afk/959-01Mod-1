<?php
/**
 * License Controller
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/License.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Channel.php';
require_once __DIR__ . '/../models/Device.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/OperationLog.php';
require_once __DIR__ . '/../models/ChannelSettlement.php';
require_once __DIR__ . '/../models/RenewalPool.php';
require_once __DIR__ . '/../models/ActivationCode.php';

class LicenseController {
    private $authController;
    private $licenseModel;
    private $userModel;
    private $companyModel;
    private $channelModel;
    private $deviceModel;
    private $invoiceModel;
    private $contractModel;
    private $operationLogModel;
    private $channelSettlementModel;
    private $renewalPoolModel;
    private $activationCodeModel;
    
    public function __construct() {
        $this->authController = new AuthController();
        $this->licenseModel = new License();
        $this->userModel = new User();
        $this->companyModel = new Company();
        $this->channelModel = new Channel();
        $this->deviceModel = new Device();
        $this->invoiceModel = new Invoice();
        $this->contractModel = new Contract();
        $this->operationLogModel = new OperationLog();
        $this->channelSettlementModel = new ChannelSettlement();
        $this->renewalPoolModel = new RenewalPool();
        $this->activationCodeModel = new ActivationCode();
    }
    
    private function requireChannelOrAdmin() {
        $this->authController->requireAuth();
        if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'channel_partner') {
            $_SESSION['error'] = '访问被拒绝，需要管理员或渠道商权限';
            header('Location: /dashboard');
            exit;
        }
    }
    
    private function getChannelIdForCurrentUser() {
        if ($_SESSION['role'] === 'admin') {
            return null;
        }
        if ($_SESSION['role'] === 'channel_partner') {
            $user = $this->userModel->findById($_SESSION['user_id']);
            return $user['channel_id'] ?? null;
        }
        return null;
    }
    
    private function logOperation($action, $actionType, $description, $licenseId = null, $companyId = null) {
        try {
            $this->operationLogModel->log(
                $_SESSION['user_id'],
                $action,
                $actionType,
                $description,
                $licenseId,
                $companyId
            );
        } catch (Exception $e) {
            error_log("Log operation error: " . $e->getMessage());
        }
    }
    
    public function create() {
        $this->authController->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productName = $_POST['product_name'] ?? '';
            $userId = $_POST['user_id'] ?? $_SESSION['user_id'];
            $status = $_POST['status'] ?? 'active';
            $expiresAt = $_POST['expires_at'] ?? null;
            
            if (empty($productName)) {
                $_SESSION['error'] = '产品名称是必填项';
                header('Location: /licenses/create');
                exit;
            }
            
            // Only admins can assign licenses to other users
            if ($userId != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
                $_SESSION['error'] = '访问被拒绝';
                header('Location: /dashboard');
                exit;
            }
            
            try {
                $licenseId = $this->licenseModel->create([
                    'user_id' => $userId,
                    'product_name' => $productName,
                    'status' => $status,
                    'expires_at' => $expiresAt ?: null
                ]);
                
                $_SESSION['success'] = '许可证创建成功';
                header('Location: /licenses/view?id=' . $licenseId);
                exit;
            } catch (Exception $e) {
                error_log("License creation error: " . $e->getMessage());
                $_SESSION['error'] = '创建许可证失败，请重试';
                header('Location: /licenses/create');
                exit;
            }
        }
        
        $users = [];
        if ($_SESSION['role'] === 'admin') {
            $users = $this->userModel->findAll(1000, 0);
        }
        
        require_once __DIR__ . '/../views/licenses/create.php';
    }
    
    public function view() {
        $this->authController->requireAuth();
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($id);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        // Users can only view their own licenses unless they're admin
        if ($license['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = '访问被拒绝';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        require_once __DIR__ . '/../views/licenses/view.php';
    }
    
    public function update() {
        $this->authController->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($id);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        // Only admins can update licenses
        if ($_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = '访问被拒绝，需要管理员权限';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        try {
            $data = [];
            if (isset($_POST['product_name'])) {
                $data['product_name'] = $_POST['product_name'];
            }
            if (isset($_POST['status'])) {
                $data['status'] = $_POST['status'];
            }
            if (isset($_POST['expires_at'])) {
                $data['expires_at'] = $_POST['expires_at'] ?: null;
            }
            if (isset($_POST['user_id'])) {
                $data['user_id'] = $_POST['user_id'];
            }
            
            $this->licenseModel->update($id, $data);
            $_SESSION['success'] = '许可证更新成功';
            header('Location: /licenses/view?id=' . $id);
            exit;
        } catch (Exception $e) {
            error_log("License update error: " . $e->getMessage());
            $_SESSION['error'] = '更新许可证失败，请重试';
            header('Location: /licenses/view?id=' . $id);
            exit;
        }
    }
    
    public function delete() {
        $this->authController->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        try {
            $this->licenseModel->delete($id);
            $_SESSION['success'] = '许可证删除成功';
            header('Location: /dashboard/licenses');
            exit;
        } catch (Exception $e) {
            error_log("License deletion error: " . $e->getMessage());
            $_SESSION['error'] = '删除许可证失败，请重试';
            header('Location: /dashboard/licenses');
            exit;
        }
    }
    
    public function renew() {
        $this->authController->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $_SESSION['error'] = '许可证ID是必填项';
                header('Location: /dashboard/licenses');
                exit;
            }
            $license = $this->licenseModel->findByIdWithDetails($id);
            if (!$license) {
                $_SESSION['error'] = '许可证不存在';
                header('Location: /dashboard/licenses');
                exit;
            }
            $invoices = $this->invoiceModel->findByLicenseId($id);
            $devices = $this->deviceModel->findByLicenseId($id);
            $companies = $this->companyModel->findAll(1000, 0);
            $channels = $this->channelModel->findActive();
            require_once __DIR__ . '/../views/licenses/renew.php';
            return;
        }
        
        $id = $_POST['id'] ?? null;
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($id);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        try {
            $renewalMonths = (int)($_POST['renewal_months'] ?? 12);
            $gracePeriodDays = (int)($_POST['grace_period_days'] ?? 30);
            $seats = (int)($_POST['seats'] ?? $license['seats'] ?? 1);
            $autoRenew = isset($_POST['auto_renew']) ? 1 : 0;
            $amount = (float)($_POST['amount'] ?? 0);
            $invoiceRequired = isset($_POST['invoice_required']) ? 1 : 0;
            $shouldFreezeSeats = isset($_POST['freeze_seats']) ? 1 : 0;
            $shouldUnbindDevices = isset($_POST['unbind_devices']) ? 1 : 0;
            $createSettlement = isset($_POST['create_settlement']) ? 1 : 0;
            $createContract = isset($_POST['create_contract']) ? 1 : 0;
            
            $renewalResult = $this->licenseModel->renew($id, [
                'renewal_months' => $renewalMonths,
                'grace_period_days' => $gracePeriodDays,
                'seats' => $seats,
                'auto_renew' => $autoRenew
            ]);
            
            $unboundCount = 0;
            if ($shouldUnbindDevices) {
                $unboundCount = $this->deviceModel->unbindAllByLicenseId($id);
            }
            
            if ($shouldFreezeSeats) {
                $this->licenseModel->freezeSeats($id);
            }
            
            $invoiceId = null;
            if ($invoiceRequired && $amount > 0) {
                $invoiceData = [
                    'license_id' => $id,
                    'company_id' => $license['company_id'],
                    'user_id' => $_SESSION['user_id'],
                    'channel_id' => $license['channel_id'],
                    'amount' => $amount,
                    'tax_amount' => (float)($_POST['tax_amount'] ?? 0),
                    'invoice_type' => $_POST['invoice_type'] ?? 'general',
                    'invoice_title' => $_POST['invoice_title'] ?? '',
                    'taxpayer_id' => $_POST['taxpayer_id'] ?? null,
                    'address' => $_POST['address'] ?? null,
                    'phone' => $_POST['phone'] ?? null,
                    'bank_name' => $_POST['bank_name'] ?? null,
                    'bank_account' => $_POST['bank_account'] ?? null,
                    'status' => 'pending'
                ];
                $invoiceId = $this->invoiceModel->create($invoiceData);
            }
            
            $settlementId = null;
            if ($createSettlement && $amount > 0 && $license['channel_id']) {
                $channel = $this->channelModel->findById($license['channel_id']);
                $commissionRate = $channel['commission_rate'] ?? 0.10;
                $commissionAmount = $amount * $commissionRate;
                
                $settlementData = [
                    'channel_id' => $license['channel_id'],
                    'license_id' => $id,
                    'invoice_id' => $invoiceId,
                    'company_id' => $license['company_id'],
                    'transaction_amount' => $amount,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'settlement_date' => date('Y-m-d'),
                    'status' => 'pending'
                ];
                $settlementId = $this->channelSettlementModel->create($settlementData);
            }
            
            if ($createContract) {
                $startDate = date('Y-m-d', strtotime($renewalResult['old_expires_at'] ?? 'now'));
                $endDate = date('Y-m-d', strtotime($renewalResult['new_expires_at']));
                
                $contractData = [
                    'license_id' => $id,
                    'company_id' => $license['company_id'],
                    'channel_id' => $license['channel_id'],
                    'contract_type' => 'renewal',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_amount' => $amount,
                    'seats' => $seats,
                    'status' => 'draft',
                    'notes' => $_POST['contract_notes'] ?? null
                ];
                $this->contractModel->create($contractData);
            }
            
            $this->logOperation(
                'license_renew',
                'renewal',
                "许可证续费成功：{$renewalMonths}个月，金额：{$amount}元" . 
                ($unboundCount > 0 ? "，解绑设备：{$unboundCount}台" : "") .
                ($invoiceId ? "，创建发票" : "") .
                ($settlementId ? "，创建渠道结算" : ""),
                $id,
                $license['company_id']
            );
            
            $_SESSION['success'] = '许可证续费成功' . 
                ($unboundCount > 0 ? "，已解绑{$unboundCount}台设备" : "") .
                ($invoiceId ? "，发票已创建" : "") .
                ($settlementId ? "，渠道结算已创建" : "");
            header('Location: /licenses/view?id=' . $id);
            exit;
            
        } catch (Exception $e) {
            error_log("License renewal error: " . $e->getMessage());
            $_SESSION['error'] = '续费失败：' . $e->getMessage();
            header('Location: /licenses/renew?id=' . $id);
            exit;
        }
    }
    
    public function deactivate() {
        $this->authController->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        $reason = $_POST['reason'] ?? null;
        
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($id);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        try {
            $result = $this->licenseModel->deactivate($id, $reason);
            
            $this->logOperation(
                'license_deactivate',
                'suspension',
                "许可证停用" . ($reason ? "，原因：{$reason}" : "") . "，解绑设备：{$result['unbound_devices']}台",
                $id,
                $license['company_id']
            );
            
            $_SESSION['success'] = '许可证已停用' . ($result['unbound_devices'] > 0 ? "，已解绑{$result['unbound_devices']}台设备" : "");
            header('Location: /licenses/view?id=' . $id);
            exit;
        } catch (Exception $e) {
            error_log("License deactivation error: " . $e->getMessage());
            $_SESSION['error'] = '停用失败：' . $e->getMessage();
            header('Location: /licenses/view?id=' . $id);
            exit;
        }
    }
    
    public function reactivate() {
        $this->authController->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($id);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        try {
            $this->licenseModel->reactivate($id);
            
            $this->logOperation(
                'license_reactivate',
                'activation',
                "许可证重新激活",
                $id,
                $license['company_id']
            );
            
            $_SESSION['success'] = '许可证已重新激活';
            header('Location: /licenses/view?id=' . $id);
            exit;
        } catch (Exception $e) {
            error_log("License reactivation error: " . $e->getMessage());
            $_SESSION['error'] = '激活失败：' . $e->getMessage();
            header('Location: /licenses/view?id=' . $id);
            exit;
        }
    }
    
    public function freezeSeats() {
        $this->authController->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($id);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        try {
            $result = $this->licenseModel->freezeSeats($id);
            
            $this->logOperation(
                'seats_freeze',
                'suspension',
                "席位冻结，解绑设备：{$result['unbound_devices']}台",
                $id,
                $license['company_id']
            );
            
            $_SESSION['success'] = '席位已冻结' . ($result['unbound_devices'] > 0 ? "，已解绑{$result['unbound_devices']}台设备" : "");
            header('Location: /licenses/view?id=' . $id);
            exit;
        } catch (Exception $e) {
            error_log("Seats freeze error: " . $e->getMessage());
            $_SESSION['error'] = '冻结失败：' . $e->getMessage();
            header('Location: /licenses/view?id=' . $id);
            exit;
        }
    }
    
    public function unfreezeSeats() {
        $this->authController->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($id);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        try {
            $this->licenseModel->unfreezeSeats($id);
            
            $this->logOperation(
                'seats_unfreeze',
                'activation',
                "席位解冻",
                $id,
                $license['company_id']
            );
            
            $_SESSION['success'] = '席位已解冻';
            header('Location: /licenses/view?id=' . $id);
            exit;
        } catch (Exception $e) {
            error_log("Seats unfreeze error: " . $e->getMessage());
            $_SESSION['error'] = '解冻失败：' . $e->getMessage();
            header('Location: /licenses/view?id=' . $id);
            exit;
        }
    }
    
    public function unbindDevice() {
        $this->authController->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $deviceId = $_POST['device_id'] ?? null;
        $licenseId = $_POST['license_id'] ?? null;
        
        if (!$deviceId) {
            $_SESSION['error'] = '设备ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        try {
            $device = $this->deviceModel->findById($deviceId);
            if (!$device) {
                $_SESSION['error'] = '设备不存在';
                header('Location: /dashboard/licenses');
                exit;
            }
            
            $this->deviceModel->unbind($deviceId);
            
            $this->logOperation(
                'device_unbind',
                'unbinding',
                "设备解绑：{$device['device_name']} ({$device['device_uuid']})",
                $device['license_id'],
                $device['company_id']
            );
            
            $_SESSION['success'] = '设备已解绑';
            if ($licenseId) {
                header('Location: /licenses/view?id=' . $licenseId);
            } else {
                header('Location: /dashboard/licenses');
            }
            exit;
        } catch (Exception $e) {
            error_log("Device unbind error: " . $e->getMessage());
            $_SESSION['error'] = '解绑失败：' . $e->getMessage();
            if ($licenseId) {
                header('Location: /licenses/view?id=' . $licenseId);
            } else {
                header('Location: /dashboard/licenses');
            }
            exit;
        }
    }
    
    public function generateActivationCode() {
        $this->authController->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $_SESSION['error'] = '许可证ID是必填项';
                header('Location: /dashboard/licenses');
                exit;
            }
            $license = $this->licenseModel->findByIdWithDetails($id);
            if (!$license) {
                $_SESSION['error'] = '许可证不存在';
                header('Location: /dashboard/licenses');
                exit;
            }
            $existingCodes = $this->activationCodeModel->findByLicenseId($id);
            require_once __DIR__ . '/../views/licenses/generate_activation_code.php';
            return;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($id);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $permissionCheck = $this->licenseModel->canGenerateActivationCode($id);
        if (!$permissionCheck['allowed']) {
            $_SESSION['error'] = $permissionCheck['message'];
            header('Location: /licenses/generate-activation-code?id=' . $id);
            exit;
        }
        
        try {
            $maxActivations = (int)($_POST['max_activations'] ?? 1);
            $expiresAt = $_POST['expires_at'] ?? null;
            
            $activationCodeId = $this->activationCodeModel->create([
                'license_id' => $id,
                'company_id' => $license['company_id'],
                'generated_by' => $_SESSION['user_id'],
                'expires_at' => $expiresAt ?: null,
                'max_activations' => $maxActivations
            ]);
            
            $this->logOperation(
                'activation_code_generate',
                'activation',
                "生成激活码，最大激活次数：{$maxActivations}",
                $id,
                $license['company_id']
            );
            
            $_SESSION['success'] = '激活码生成成功';
            header('Location: /licenses/view?id=' . $id);
            exit;
        } catch (Exception $e) {
            error_log("Activation code generation error: " . $e->getMessage());
            $_SESSION['error'] = '生成失败：' . $e->getMessage();
            header('Location: /licenses/generate-activation-code?id=' . $id);
            exit;
        }
    }
    
    public function confirmInvoice() {
        $this->authController->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $invoiceId = $_POST['invoice_id'] ?? null;
        $licenseId = $_POST['license_id'] ?? null;
        $action = $_POST['action'] ?? 'confirm';
        
        if (!$invoiceId) {
            $_SESSION['error'] = '发票ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        try {
            $invoice = $this->invoiceModel->findById($invoiceId);
            if (!$invoice) {
                $_SESSION['error'] = '发票不存在';
                header('Location: /dashboard/licenses');
                exit;
            }
            
            if ($action === 'reject') {
                $this->invoiceModel->reject($invoiceId, $_SESSION['user_id']);
                $this->logOperation(
                    'invoice_reject',
                    'invoice',
                    "发票拒绝：{$invoice['invoice_no']}",
                    $invoice['license_id'],
                    $invoice['company_id']
                );
                $_SESSION['success'] = '发票已拒绝';
            } else {
                $this->invoiceModel->confirm($invoiceId, $_SESSION['user_id']);
                $this->logOperation(
                    'invoice_confirm',
                    'invoice',
                    "发票确认：{$invoice['invoice_no']}",
                    $invoice['license_id'],
                    $invoice['company_id']
                );
                $_SESSION['success'] = '发票已确认';
            }
            
            if ($licenseId) {
                header('Location: /licenses/view?id=' . $licenseId);
            } else {
                header('Location: /dashboard/licenses');
            }
            exit;
        } catch (Exception $e) {
            error_log("Invoice confirmation error: " . $e->getMessage());
            $_SESSION['error'] = '操作失败：' . $e->getMessage();
            if ($licenseId) {
                header('Location: /licenses/view?id=' . $licenseId);
            } else {
                header('Location: /dashboard/licenses');
            }
            exit;
        }
    }
    
    public function renewalPool() {
        $this->requireChannelOrAdmin();
        
        $channelId = $this->getChannelIdForCurrentUser();
        $status = $_GET['status'] ?? null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        if ($channelId) {
            $renewals = $this->renewalPoolModel->findByChannelId($channelId, $status, $limit, $offset);
            $total = $this->renewalPoolModel->countByChannelId($channelId, $status);
        } else {
            $renewals = $this->renewalPoolModel->findAll($status, $limit, $offset);
            $total = count($this->renewalPoolModel->findAll($status, 1000, 0));
        }
        
        $totalPages = ceil($total / $limit);
        
        $stats = [
            'pending' => $channelId ? $this->renewalPoolModel->countByChannelId($channelId, 'pending') : count($this->renewalPoolModel->findAll('pending', 1000, 0)),
            'in_progress' => $channelId ? $this->renewalPoolModel->countByChannelId($channelId, 'in_progress') : count($this->renewalPoolModel->findAll('in_progress', 1000, 0)),
            'completed' => $channelId ? $this->renewalPoolModel->countByChannelId($channelId, 'completed') : count($this->renewalPoolModel->findAll('completed', 1000, 0)),
            'expired' => $channelId ? $this->renewalPoolModel->countByChannelId($channelId, 'expired') : count($this->renewalPoolModel->findAll('expired', 1000, 0)),
        ];
        
        $expiringSoon = $this->renewalPoolModel->getExpiringSoon($channelId, 30);
        
        require_once __DIR__ . '/../views/licenses/renewal_pool.php';
    }
    
    public function history() {
        $this->authController->requireAuth();
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($id);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        if ($license['user_id'] != $_SESSION['user_id'] && 
            $_SESSION['role'] !== 'admin' && 
            $_SESSION['role'] !== 'channel_partner') {
            $_SESSION['error'] = '访问被拒绝';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        if ($_SESSION['role'] === 'channel_partner') {
            $channelId = $this->getChannelIdForCurrentUser();
            if ($license['channel_id'] != $channelId) {
                $_SESSION['error'] = '访问被拒绝，只能查看自己客户的记录';
                header('Location: /dashboard/licenses');
                exit;
            }
        }
        
        $history = $this->licenseModel->getHistory($id);
        
        require_once __DIR__ . '/../views/licenses/history.php';
    }
    
    public function checkExpiry() {
        $this->authController->requireAdmin();
        
        try {
            $updatedCount = $this->licenseModel->checkAndUpdateExpiryStatus();
            $_SESSION['success'] = "已检查并更新了{$updatedCount}个许可证的过期状态";
        } catch (Exception $e) {
            error_log("Expiry check error: " . $e->getMessage());
            $_SESSION['error'] = '检查失败：' . $e->getMessage();
        }
        
        header('Location: /dashboard/licenses');
        exit;
    }
}
