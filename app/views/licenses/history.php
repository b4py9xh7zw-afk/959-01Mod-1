<?php
$pageTitle = '历史记录 - 许可证管理平台';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">历史记录</h1>
            <p class="mt-2 text-gray-600">许可证密钥：<code class="font-mono"><?php echo htmlspecialchars($license['license_key']); ?></code></p>
        </div>
        <a href="/licenses/view?id=<?php echo $license['id']; ?>" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">← 返回许可证详情</a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-purple-50">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <div class="text-sm text-gray-500">产品名称</div>
                    <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($license['product_name']); ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">企业客户</div>
                    <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($license['company_name'] ?? '-'); ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">渠道商</div>
                    <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($license['channel_name'] ?? '-'); ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">创建时间</div>
                    <div class="text-lg font-semibold text-gray-900"><?php echo date('Y-m-d', strtotime($license['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-800">操作日志</h3>
                <div class="text-sm text-gray-500">共 <?php echo count($history); ?> 条记录</div>
            </div>

            <?php if (empty($history)): ?>
            <div class="p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">暂无历史记录</h3>
                <p class="text-gray-500">该许可证尚未产生任何操作日志</p>
            </div>
            <?php else: ?>
            <div class="space-y-6">
                <?php 
                $lastDate = '';
                foreach ($history as $index => $log): 
                    $currentDate = date('Y年m月d日', strtotime($log['created_at']));
                    if ($currentDate !== $lastDate):
                        if ($lastDate !== '') {
                            echo '</div></div>';
                        }
                        $lastDate = $currentDate;
                ?>
                <div class="mb-8">
                    <div class="flex items-center mb-4">
                        <div class="h-px flex-1 bg-gray-200"></div>
                        <span class="px-4 text-sm font-medium text-gray-500 bg-white"><?php echo $currentDate; ?></span>
                        <div class="h-px flex-1 bg-gray-200"></div>
                    </div>
                    <div class="relative pl-8 space-y-6">
                <?php endif; ?>
                        <div class="relative group">
                            <div class="absolute -left-8 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border-4 border-white shadow 
                                <?php 
                                $actionTypeColors = [
                                    'create' => 'bg-green-500',
                                    'update' => 'bg-blue-500',
                                    'delete' => 'bg-red-500',
                                    'renewal' => 'bg-emerald-500',
                                    'suspension' => 'bg-orange-500',
                                    'activation' => 'bg-purple-500',
                                    'freeze' => 'bg-yellow-500',
                                    'unfreeze' => 'bg-teal-500',
                                    'unbinding' => 'bg-pink-500',
                                    'generate_code' => 'bg-indigo-500',
                                    'invoice' => 'bg-amber-500',
                                    'settlement' => 'bg-cyan-500',
                                ];
                                echo $actionTypeColors[$log['action_type']] ?? 'bg-gray-500';
                                ?>">
                            </div>
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-6 h-px bg-gray-300"></div>
                            
                            <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                <?php 
                                                echo $actionTypeColors[$log['action_type']] ?? 'bg-gray-500';
                                                ?> text-white">
                                                <?php 
                                                $actionTypeText = [
                                                    'create' => '创建',
                                                    'update' => '更新',
                                                    'delete' => '删除',
                                                    'renewal' => '续费',
                                                    'suspension' => '停用',
                                                    'activation' => '激活',
                                                    'freeze' => '冻结',
                                                    'unfreeze' => '解冻',
                                                    'unbinding' => '解绑',
                                                    'generate_code' => '生成激活码',
                                                    'invoice' => '发票',
                                                    'settlement' => '结算',
                                                ];
                                                echo $actionTypeText[$log['action_type']] ?? $log['action_type'];
                                                ?>
                                            </span>
                                            <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($log['action']); ?></span>
                                        </div>
                                        
                                        <?php if (!empty($log['description'])): ?>
                                        <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($log['description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($log['old_value']) || !empty($log['new_value'])): ?>
                                        <div class="bg-white rounded p-3 text-xs font-mono space-y-2">
                                            <?php if (!empty($log['old_value'])): ?>
                                            <div>
                                                <span class="text-red-500">- 旧值：</span>
                                                <span class="text-gray-700"><?php echo htmlspecialchars($log['old_value']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($log['new_value'])): ?>
                                            <div>
                                                <span class="text-green-500">+ 新值：</span>
                                                <span class="text-gray-700"><?php echo htmlspecialchars($log['new_value']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="text-right ml-4 flex-shrink-0">
                                        <div class="text-sm text-gray-500"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
                                        <?php if (!empty($log['username'])): ?>
                                        <div class="text-xs text-gray-400 mt-1">操作人：<?php echo htmlspecialchars($log['username']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php 
                endforeach; 
                if ($lastDate !== '') {
                    echo '</div></div>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        历史记录包含设备、合同、发票等所有相关操作，不可删除或修改
                    </span>
                </div>
                <div class="flex space-x-2">
                    <button onclick="window.print()" class="px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">打印</button>
                    <button onclick="exportHistory()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">导出</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportHistory() {
    const historyData = <?php echo json_encode($history); ?>;
    let csvContent = "操作时间,操作类型,操作,描述,操作人\n";
    
    historyData.forEach(log => {
        const row = [
            log.created_at,
            log.action_type,
            log.action ? log.action.replace(/"/g, '""') : '',
            log.description ? log.description.replace(/"/g, '""') : '',
            log.username || ''
        ].map(field => `"${field}"`).join(',');
        csvContent += row + "\n";
    });

    const blob = new Blob(["\ufeff" + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `license_history_<?php echo $license['id']; ?>_<?php echo date('Ymd'); ?>.csv`;
    link.click();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
