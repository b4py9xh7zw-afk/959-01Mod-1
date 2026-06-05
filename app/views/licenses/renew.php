<?php
$pageTitle = '许可证续费 - 许可证管理平台';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            许可证续费
        </h1>
        <a href="/licenses/view?id=<?php echo $license['id']; ?>" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
            ← 返回许可证详情
        </a>
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
                    <span class="text-gray-500">当前过期时间：</span>
                    <span class="font-medium"><?php echo $license['expires_at'] ? date('Y-m-d', strtotime($license['expires_at'])) : '永不过期'; ?></span>
                </div>
                <div>
                    <span class="text-gray-500">当前席位：</span>
                    <span class="font-medium"><?php echo $license['seats'] ?? 1; ?> 个</span>
                </div>
                <?php if (!empty($license['company_name'])): ?>
                <div>
                    <span class="text-gray-500">企业客户：</span>
                    <span class="font-medium"><?php echo htmlspecialchars($license['company_name']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($license['channel_name'])): ?>
                <div>
                    <span class="text-gray-500">渠道商：</span>
                    <span class="font-medium"><?php echo htmlspecialchars($license['channel_name']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($devices)): ?>
        <div class="mb-8 p-6 bg-yellow-50 rounded-lg border border-yellow-200">
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">⚠️ 注意</h3>
            <p class="text-yellow-700">该许可证当前绑定了 <?php echo count($devices); ?> 台设备。续费过程中可以选择解绑所有设备。</p>
        </div>
        <?php endif; ?>

        <form method="POST" action="/licenses/renew" class="space-y-6">
            <input type="hidden" name="id" value="<?php echo $license['id']; ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="renewal_months" class="block text-sm font-medium text-gray-700 mb-2">续费时长（月）</label>
                    <select 
                        id="renewal_months" 
                        name="renewal_months"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="1">1 个月</option>
                        <option value="3">3 个月</option>
                        <option value="6">6 个月</option>
                        <option value="12" selected>12 个月</option>
                        <option value="24">24 个月</option>
                        <option value="36">36 个月</option>
                    </select>
                </div>

                <div>
                    <label for="grace_period_days" class="block text-sm font-medium text-gray-700 mb-2">宽限期（天）</label>
                    <input 
                        type="number" 
                        id="grace_period_days" 
                        name="grace_period_days"
                        value="<?php echo $license['grace_period_days'] ?? 30; ?>"
                        min="0"
                        max="365"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>

                <div>
                    <label for="seats" class="block text-sm font-medium text-gray-700 mb-2">席位数量</label>
                    <input 
                        type="number" 
                        id="seats" 
                        name="seats"
                        value="<?php echo $license['seats'] ?? 1; ?>"
                        min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">续费金额（元）</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        id="amount" 
                        name="amount"
                        value="0.00"
                        min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">续费选项</h3>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="invoice_required" class="w-4 h-4 text-blue-600 rounded" checked>
                        <span class="ml-2 text-gray-700">需要开具发票</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="unbind_devices" class="w-4 h-4 text-blue-600 rounded">
                        <span class="ml-2 text-gray-700">解绑所有设备（续费后需要重新激活</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="freeze_seats" class="w-4 h-4 text-blue-600 rounded">
                        <span class="ml-2 text-gray-700">冻结席位（需手动解冻后使用）</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="create_contract" class="w-4 h-4 text-blue-600 rounded" checked>
                        <span class="ml-2 text-gray-700">创建续费合同</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="create_settlement" class="w-4 h-4 text-blue-600 rounded" checked>
                        <span class="ml-2 text-gray-700">创建渠道结算（如果有关联渠道商</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="auto_renew" class="w-4 h-4 text-blue-600 rounded">
                        <span class="ml-2 text-gray-700">自动续费（下次到期前自动提醒）</span>
                    </label>
                </div>
            </div>

            <div id="invoiceSection" class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">发票信息</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="invoice_type" class="block text-sm font-medium text-gray-700 mb-2">发票类型</label>
                        <select 
                            id="invoice_type" 
                            name="invoice_type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="general">普通发票</option>
                            <option value="vat_general">增值税普通发票</option>
                            <option value="vat_special">增值税专用发票</option>
                        </select>
                    </div>

                    <div>
                        <label for="tax_amount" class="block text-sm font-medium text-gray-700 mb-2">税额（元）</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            id="tax_amount" 
                            name="tax_amount"
                            value="0.00"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label for="invoice_title" class="block text-sm font-medium text-gray-700 mb-2">发票抬头 *</label>
                        <input 
                            type="text" 
                            id="invoice_title" 
                            name="invoice_title"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="请输入发票抬头"
                        >
                    </div>

                    <div>
                        <label for="taxpayer_id" class="block text-sm font-medium text-gray-700 mb-2">纳税人识别号</label>
                        <input 
                            type="text" 
                            id="taxpayer_id" 
                            name="taxpayer_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">联系电话</label>
                        <input 
                            type="text" 
                            id="phone" 
                            name="phone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">地址</label>
                        <input 
                            type="text" 
                            id="address" 
                            name="address"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">开户银行</label>
                        <input 
                            type="text" 
                            id="bank_name" 
                            name="bank_name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <label for="bank_account" class="block text-sm font-medium text-gray-700 mb-2">银行账号</label>
                        <input 
                            type="text" 
                            id="bank_account" 
                            name="bank_account"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <label for="contract_notes" class="block text-sm font-medium text-gray-700 mb-2">合同备注</label>
                    <textarea 
                        id="contract_notes" 
                        name="contract_notes" 
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="可选：输入合同相关备注信息"
                    ></textarea>
                </div>

                <div class="flex space-x-4 pt-4">
                    <button 
                        type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg font-semibold hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-lg"
                    >
                        确认续费
                    </button>
                    <a href="/licenses/view?id=<?php echo $license['id']; ?>" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                        取消
                    </a>
                </div>
        </form>
    </div>
</div>

<script>
document.getElementById('invoiceSection').style.display = document.querySelector('input[name="invoice_required"]').checked ? 'block' : 'none';
document.querySelector('input[name="invoice_required"]').addEventListener('change', function() {
    document.getElementById('invoiceSection').style.display = this.checked ? 'block' : 'none';
    document.querySelectorAll('#invoiceSection input, #invoiceSection select').forEach(el => {
        if (this.checked) {
            el.removeAttribute('disabled');
        } else {
            el.setAttribute('disabled', 'disabled');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
