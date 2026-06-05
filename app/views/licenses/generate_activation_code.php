<?php
$pageTitle = '生成激活码 - 许可证管理平台';
require_once __DIR__ . '/../layouts/header.php';

$permissionCheck = [];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    require_once __DIR__ . '/../../models/License.php';
    $licenseModel = new License();
    $permissionCheck = $licenseModel->canGenerateActivationCode($license['id']);
}
?>

<div class="max-w-4xl mx-auto space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">生成激活码</h1>
        <a href="/licenses/view?id=<?php echo $license['id']; ?>" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">← 返回许可证详情</a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
        <div class="mb-8 p-6 bg-blue-50 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">许可证信息</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">许可证密钥：</span>
                    <code class="font-mono"><?php echo htmlspecialchars($license['license_key']); ?></code>
                </div>
                <div>
                    <span class="text-gray-500">产品名称：</span>
                    <span class="font-medium"><?php echo htmlspecialchars($license['product_name']); ?></span>
                </div>
                <div>
                    <span class="text-gray-500">过期时间：</span>
                    <span class="font-medium"><?php echo $license['expires_at'] ? date('Y-m-d', strtotime($license['expires_at'])) : '永不过期'; ?></span>
                </div>
                <div>
                    <span class="text-gray-500">状态：</span>
                    <span class="font-medium">
                        <?php 
                        $renewalStatus = $license['renewal_status'] ?? 'active';
                        $statusText = ['active' => '正常', 'in_grace_period' => '宽限期', 'expired' => '已过期', 'suspended' => '已停用', 'pending_renewal' => '待续费'];
                        echo $statusText[$renewalStatus] ?? $renewalStatus;
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($permissionCheck) && !$permissionCheck['allowed']): ?>
            <div class="p-6 bg-red-50 border border-red-200 rounded-lg mb-6">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4a3 3 0 00-5.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <h4 class="text-red-800 font-semibold">无法生成激活码</h4>
                        <p class="text-red-600 text-sm mt-1"><?php echo htmlspecialchars($permissionCheck['message']); ?></p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($existingCodes)): ?>
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">已生成的激活码</h3>
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
                            <?php foreach ($existingCodes as $code): ?>
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

            <form method="POST" action="/licenses/generate-activation-code" class="border-t border-gray-200 pt-6 space-y-6">
                <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="max_activations" class="block text-sm font-medium text-gray-700 mb-2">最大激活次数</label>
                        <input type="number" id="max_activations" name="max_activations" value="1" min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">过期时间（可选）</label>
                        <input type="date" id="expires_at" name="expires_at" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-sm text-gray-500 mt-1">留空表示永不过期</p>
                    </div>
                </div>
                <div class="flex space-x-4 pt-4">
                    <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-lg font-semibold hover:from-purple-600 hover:to-indigo-700 transition-all transform hover:scale-105 shadow-lg">生成激活码</button>
                    <a href="/licenses/view?id=<?php echo $license['id']; ?>" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">取消</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
