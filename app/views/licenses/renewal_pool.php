<?php
$pageTitle = '续订池 - 许可证管理平台';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">续订池</h1>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="text-sm text-gray-500">管理员视图 - 查看所有渠道客户</div>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'channel_partner'): ?>
        <div class="text-sm text-gray-500">渠道商视图 - 仅查看您的客户</div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">状态筛选</label>
                    <select id="statusFilter" onchange="filterStatus()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">全部状态</option>
                        <option value="pending_renewal" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending_renewal') ? 'selected' : ''; ?>>待续费</option>
                        <option value="in_grace_period" <?php echo (isset($_GET['status']) && $_GET['status'] === 'in_grace_period') ? 'selected' : ''; ?>>宽限期</option>
                        <option value="expired" <?php echo (isset($_GET['status']) && $_GET['status'] === 'expired') ? 'selected' : ''; ?>>已过期</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : ''; ?>>正常</option>
                        <option value="suspended" <?php echo (isset($_GET['status']) && $_GET['status'] === 'suspended') ? 'selected' : ''; ?>>已停用</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">搜索</label>
                    <input type="text" id="searchInput" placeholder="搜索许可证、企业名称..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
        </div>

        <?php if (empty($renewals)): ?>
        <div class="p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">暂无续订记录</h3>
            <p class="text-gray-500">当前筛选条件下没有找到续订池记录</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full" id="renewalTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">许可证</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">企业客户</th>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">渠道商</th>
                        <?php endif; ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">产品</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">过期时间</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">宽限期</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($renewals as $renewal): ?>
                    <tr class="searchable-row hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 searchable-content"><?php echo htmlspecialchars($renewal['license_key']); ?></div>
                            <div class="text-sm text-gray-500">ID: <?php echo $renewal['license_id']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 searchable-content"><?php echo htmlspecialchars($renewal['company_name'] ?? '-'); ?></div>
                            <?php if (!empty($renewal['contact_email'])): ?>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($renewal['contact_email']); ?></div>
                            <?php endif; ?>
                        </td>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($renewal['channel_name'] ?? '-'); ?></div>
                        </td>
                        <?php endif; ?>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($renewal['product_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo $renewal['seats'] ?? 1; ?> 席位</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $renewal['expires_at'] ? date('Y-m-d', strtotime($renewal['expires_at'])) : '永不过期'; ?></div>
                            <?php 
                            if ($renewal['expires_at']) {
                                $expiresAt = new DateTime($renewal['expires_at']);
                                $now = new DateTime();
                                $interval = $now->diff($expiresAt);
                                if ($expiresAt < $now) {
                                    $daysOverdue = $interval->days;
                                    echo '<div class="text-sm text-red-600">已过期 ' . $daysOverdue . ' 天</div>';
                                } else {
                                    $daysRemaining = $interval->days;
                                    if ($daysRemaining <= 30) {
                                        echo '<div class="text-sm text-orange-600">剩余 ' . $daysRemaining . ' 天</div>';
                                    }
                                }
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $renewal['grace_period_days'] ?? 0; ?> 天</div>
                            <?php if (!empty($renewal['grace_period_end'])): ?>
                            <div class="text-sm text-gray-500">至 <?php echo date('Y-m-d', strtotime($renewal['grace_period_end'])); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $status = $renewal['renewal_status'] ?? 'active';
                            $statusConfig = [
                                'active' => ['bg-green-100', 'text-green-800', '正常'],
                                'pending_renewal' => ['bg-yellow-100', 'text-yellow-800', '待续费'],
                                'in_grace_period' => ['bg-orange-100', 'text-orange-800', '宽限期'],
                                'expired' => ['bg-red-100', 'text-red-800', '已过期'],
                                'suspended' => ['bg-gray-100', 'text-gray-800', '已停用'],
                            ];
                            $config = $statusConfig[$status] ?? ['bg-gray-100', 'text-gray-800', $status];
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $config[0] . ' ' . $config[1]; ?>"><?php echo $config[2]; ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="/licenses/view?id=<?php echo $renewal['license_id']; ?>" class="text-blue-600 hover:text-blue-900">查看</a>
                            <?php if ($status !== 'suspended'): ?>
                            <a href="/licenses/renew?id=<?php echo $renewal['license_id']; ?>" class="text-green-600 hover:text-green-900">续费</a>
                            <?php endif; ?>
                            <?php if ($status === 'suspended'): ?>
                            <form method="POST" action="/licenses/reactivate" class="inline" onsubmit="return confirm('确定要重新激活此许可证吗？');">
                                <input type="hidden" name="id" value="<?php echo $renewal['license_id']; ?>">
                                <button type="submit" class="text-purple-600 hover:text-purple-900">激活</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <div class="text-sm text-gray-500">显示 <?php echo $pagination['offset'] + 1; ?> - <?php echo min($pagination['offset'] + $pagination['limit'], $pagination['total']); ?> 条，共 <?php echo $pagination['total']; ?> 条</div>
            <div class="flex space-x-2">
                <?php if ($pagination['page'] > 1): ?>
                <a href="?page=<?php echo $pagination['page'] - 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">上一页</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" class="px-3 py-1 border rounded <?php echo $i === $pagination['page'] ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                <a href="?page=<?php echo $pagination['page'] + 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">下一页</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function filterStatus() {
    const status = document.getElementById('statusFilter').value;
    const url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.searchable-row');
    
    rows.forEach(row => {
        const licenseKey = row.querySelector('td:first-child .searchable-content')?.textContent?.toLowerCase() || '';
        const companyName = row.querySelector('td:nth-child(2) .searchable-content')?.textContent?.toLowerCase() || '';
        
        if (licenseKey.includes(searchTerm) || companyName.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = searchTerm ? 'none' : '';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
