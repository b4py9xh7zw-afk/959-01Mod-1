<?php
$pageTitle = '许可证详情 - 许可证管理平台';
require_once __DIR__ . '/../layouts/header.php';

require_once __DIR__ . '/../../models/Device.php';
require_once __DIR__ . '/../../models/Invoice.php';
require_once __DIR__ . '/../../models/Contract.php';
require_once __DIR__ . '/../../models/OperationLog.php';
require_once __DIR__ . '/../../models/ActivationCode.php';
require_once __DIR__ . '/../../models/ChannelSettlement.php';
require_once __DIR__ . '/../../models/RenewalPool.php';

$deviceModel = new Device();
$invoiceModel = new Invoice();
$contractModel = new Contract();
$activationCodeModel = new ActivationCode();

$devices = $deviceModel->findByLicenseId($license['id']);
$invoices = $invoiceModel->findByLicenseId($license['id']);
$contracts = $contractModel->findByLicenseId($license['id']);
$activationCodes = $activationCodeModel->findByLicenseId($license['id']);
$boundDeviceCount = $deviceModel->countBoundByLicenseId($license['id']);
?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            许可证详情
        </h1>
        <div class="flex space-x-4">
            <a href="/licenses/history?id=<?php echo $license['id']; ?>" class="px-6 py-3 bg-indigo-500 text-white rounded-lg font-semibold hover:bg-indigo-600 transition-colors">
                历史记录
            </a>
            <a href="/dashboard/licenses" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                ← 返回许可证列表
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg border border-gray-100 p-8">
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">许可证密钥</label>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <code class="text-lg font-mono text-gray-800"><?php echo htmlspecialchars($license['license_key']); ?></code>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">状态</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full <?php 
                                echo $license['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                    ($license['status'] === 'expired' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'); 
                            ?>">
                                <?php 
                                echo $license['status'] === 'active' ? '活跃' : 
                                    ($license['status'] === 'expired' ? '已过期' : '未激活'); 
                                ?>
                            </span>
                            <?php if (isset($license['renewal_status']) && $license['renewal_status']): ?>
                                <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full <?php 
                                    echo $license['renewal_status'] === 'active' ? 'bg-blue-100 text-blue-800' : 
                                        ($license['renewal_status'] === 'in_grace_period' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($license['renewal_status'] === 'expired' ? 'bg-red-100 text-red-800' : 
                                        ($license['renewal_status'] === 'suspended' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'))); 
                                ?>">
                                    <?php 
                                    echo $license['renewal_status'] === 'active' ? '正常' : 
                                        ($license['renewal_status'] === 'in_grace_period' ? '宽限期' : 
                                        ($license['renewal_status'] === 'expired' ? '已过期' : 
                                        ($license['renewal_status'] === 'suspended' ? '已停用' : '待续费'))); 
                                    ?>
                                </span>
                            <?php endif; ?>
                            <?php if (isset($license['is_frozen']) && $license['is_frozen']): ?>
                                <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    已冻结
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">产品名称</label>
                        <p class="text-lg text-gray-800"><?php echo htmlspecialchars($license['product_name']); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">分配用户</label>
                        <p class="text-lg text-gray-800"><?php echo htmlspecialchars($license['username'] ?? 'N/A'); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($license['email'] ?? ''); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">席位数量</label>
                        <p class="text-lg text-gray-800"><?php echo $license['seats'] ?? 1; ?> 个席位</p>
                        <p class="text-sm text-gray-600">已绑定 <?php echo $boundDeviceCount; ?> 台设备</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">宽限期</label>
                        <p class="text-lg text-gray-800"><?php echo $license['grace_period_days'] ?? 30; ?> 天</p>
                        <?php if (!empty($license['grace_period_end'])): ?>
                            <p class="text-sm text-gray-600">截止到 <?php echo date('Y-m-d', strtotime($license['grace_period_end'])); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">创建时间</label>
                        <p class="text-lg text-gray-800"><?php echo date('Y-m-d H:i:s', strtotime($license['created_at'])); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">过期时间</label>
                        <p class="text-lg text-gray-800">
                            <?php echo $license['expires_at'] ? date('Y-m-d H:i:s', strtotime($license['expires_at'])) : '永不过期'; ?>
                        </p>
                        <?php if (!empty($license['last_renewed_at'])): ?>
                            <p class="text-sm text-gray-600">上次续费：<?php echo date('Y-m-d', strtotime($license['last_renewed_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($license['company_name']) && $license['company_name']): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">企业客户</label>
                        <p class="text-lg text-gray-800"><?php echo htmlspecialchars($license['company_name']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($license['channel_name']) && $license['channel_name']): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">渠道商</label>
                        <p class="text-lg text-gray-800"><?php echo htmlspecialchars($license['channel_name']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">管理员操作</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="/licenses/renew?id=<?php echo $license['id']; ?>" 
                           class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                            续费许可证
                        </a>
                        <button 
                            onclick="document.getElementById('updateForm').classList.toggle('hidden')"
                            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                        >
                            编辑许可证
                        </button>
                        
                        <?php if ($license['status'] === 'active'): ?>
                            <form method="POST" action="/licenses/deactivate" onsubmit="return confirm('确定要停用此许可证吗？停用后所有设备将被解绑。');" class="inline">
                                <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                                <input type="hidden" name="reason" value="管理员手动停用">
                                <button 
                                    type="submit"
                                    class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors"
                                >
                                    停用许可证
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="/licenses/reactivate" onsubmit="return confirm('确定要重新激活此许可证吗？');" class="inline">
                                <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                                <button 
                                    type="submit"
                                    class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                                >
                                    重新激活
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if (empty($license['is_frozen'])): ?>
                            <form method="POST" action="/licenses/freeze-seats" onsubmit="return confirm('确定要冻结席位吗？冻结后所有设备将被解绑。');" class="inline">
                                <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                                <button 
                                    type="submit"
                                    class="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors"
                                >
                                    冻结席位
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="/licenses/unfreeze-seats" onsubmit="return confirm('确定要解冻席位吗？');" class="inline">
                                <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                                <button 
                                    type="submit"
                                    class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                                >
                                    解冻席位
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="/licenses/generate-activation-code?id=<?php echo $license['id']; ?>" 
                           class="px-6 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
                            生成激活码
                        </a>
                        
                        <form method="POST" action="/licenses/delete" onsubmit="return confirm('确定要删除此许可证吗？此操作不可恢复。');" class="inline">
                            <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                            <button 
                                type="submit"
                                class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                            >
                                删除许可证
                            </button>
                        </form>
                    </div>
                
                <form id="updateForm" method="POST" action="/licenses/update" class="hidden mt-6 space-y-4 bg-gray-50 p-6 rounded-lg">
                    <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                    
                    <div>
                        <label for="product_name" class="block text-sm font-medium text-gray-700 mb-2">产品名称</label>
                        <input 
                            type="text" 
                            id="product_name" 
                            name="product_name" 
                            value="<?php echo htmlspecialchars($license['product_name']); ?>"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">状态</label>
                        <select 
                            id="status" 
                            name="status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="active" <?php echo $license['status'] === 'active' ? 'selected' : ''; ?>>活跃</option>
                            <option value="inactive" <?php echo $license['status'] === 'inactive' ? 'selected' : ''; ?>>未激活</option>
                            <option value="expired" <?php echo $license['status'] === 'expired' ? 'selected' : ''; ?>>已过期</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">过期时间</label>
                        <input 
                            type="date" 
                            id="expires_at" 
                            name="expires_at"
                            value="<?php echo $license['expires_at'] ? date('Y-m-d', strtotime($license['expires_at'])) : ''; ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    
                    <div class="flex space-x-4">
                        <button 
                            type="submit"
                            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                        >
                            更新许可证
                        </button>
                        <button 
                            type="button"
                            onclick="document.getElementById('updateForm').classList.add('hidden')"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                        >
                            取消
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="space-y-6">
            <?php if (!empty($devices)): ?>
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">设备绑定记录</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">设备名称</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">设备ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">绑定时间</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($devices as $device): ?>
                            <tr>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($device['device_name'] ?? '未知设备'); ?></td>
                                <td class="px-4 py-2"><code class="text-xs"><?php echo htmlspecialchars($device['device_uuid']); ?></code></td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $device['is_bound'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $device['is_bound'] ? '已绑定' : '已解绑'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-600"><?php echo date('Y-m-d', strtotime($device['bound_at'])); ?></td>
                                <td class="px-4 py-2">
                                    <?php if ($device['is_bound'] && $_SESSION['role'] === 'admin'): ?>
                                    <form method="POST" action="/licenses/unbind-device" onsubmit="return confirm('确定要解绑此设备吗？');" class="inline">
                                        <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                                        <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">解绑</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($invoices)): ?>
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">发票记录</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">发票号</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">金额</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">类型</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs"><?php echo htmlspecialchars($invoice['invoice_no']); ?></td>
                                <td class="px-4 py-2">¥<?php echo number_format($invoice['amount'], 2); ?></td>
                                <td class="px-4 py-2">
                                    <?php 
                                    $types = ['vat_special' => '增值税专用', 'vat_general' => '增值税普通', 'general' => '普通'];
                                    echo $types[$invoice['invoice_type']] ?? $invoice['invoice_type']; 
                                    ?>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded-full <?php 
                                        echo $invoice['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                            ($invoice['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($invoice['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')); 
                                    ?>">
                                        <?php 
                                        $statuses = ['pending' => '待确认', 'confirmed' => '已确认', 'rejected' => '已拒绝', 'cancelled' => '已取消'];
                                        echo $statuses[$invoice['status']] ?? $invoice['status']; 
                                        ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <?php if ($invoice['status'] === 'pending' && $_SESSION['role'] === 'admin'): ?>
                                    <div class="flex space-x-2">
                                        <form method="POST" action="/licenses/confirm-invoice" class="inline">
                                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                            <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                                            <input type="hidden" name="action" value="confirm">
                                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm">确认</button>
                                        </form>
                                        <form method="POST" action="/licenses/confirm-invoice" onsubmit="return confirm('确定要拒绝此发票吗？');" class="inline">
                                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                            <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">拒绝</button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($activationCodes)): ?>
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">激活码记录</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">激活码</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">使用次数</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">生成时间</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($activationCodes as $code): ?>
                            <tr>
                                <td class="px-4 py-2 font-mono"><?php echo htmlspecialchars($code['code']); ?></td>
                                <td class="px-4 py-2"><?php echo $code['used_count']; ?> / <?php echo $code['max_activations']; ?></td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded-full <?php 
                                        echo $code['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                            ($code['status'] === 'used' ? 'bg-blue-100 text-blue-800' : 
                                            ($code['status'] === 'expired' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')); 
                                    ?>">
                                        <?php 
                                        $statuses = ['active' => '有效', 'used' => '已使用', 'expired' => '已过期', 'revoked' => '已吊销'];
                                        echo $statuses[$code['status']] ?? $code['status']; 
                                        ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-600"><?php echo date('Y-m-d', strtotime($code['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
