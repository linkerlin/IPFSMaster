<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="bi bi-speedometer2"></i> IPFS节点仪表盘</h1>
    </div>
</div>

<div id="dashboardStats" hx-get="/dashboard/stats" hx-trigger="load, every 10s" hx-swap="innerHTML">
    <!-- IPFS stats loading via htmx -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card blue">
                <div class="stat-label"><i class="bi bi-hdd-network"></i> 节点状态</div>
                <div class="stat-value"><span class="spinner-border spinner-border-sm"></span></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card indigo">
                <div class="stat-label"><i class="bi bi-info-circle"></i> IPFS版本</div>
                <div class="stat-value"><span class="spinner-border spinner-border-sm"></span></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card teal">
                <div class="stat-label"><i class="bi bi-pin-angle"></i> 已固定</div>
                <div class="stat-value"><?php echo number_format((int)($pinnedCount ?? 0)); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card green">
                <div class="stat-label"><i class="bi bi-hdd"></i> 仓库占用</div>
                <div class="stat-value"><span class="spinner-border spinner-border-sm"></span></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> 最近固定的内容
            </div>
            <div class="card-body">
                <?php if (empty($recentPins)): ?>
                    <p class="text-muted text-center py-3">
                        <i class="bi bi-inbox"></i> 暂无固定内容
                    </p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach (array_slice($recentPins, 0, 5) as $pin): ?>
                            <a href="/browse?cid=<?php echo urlencode($pin['cid']); ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($pin['name'] ?: 'Unnamed'); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($pin['cid'], 0, 30) . '...'); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-primary">
                                        <?php echo number_format(($pin['size'] ?? 0) / 1024, 2); ?> KB
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/pins" class="btn btn-outline-primary btn-sm">
                            查看全部 <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning"></i> 快速操作
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-6 mb-3">
                        <a href="/upload" class="text-decoration-none">
                            <div class="p-4 rounded bg-light">
                                <i class="bi bi-cloud-upload display-4 text-primary"></i>
                                <h5 class="mt-3">上传文件</h5>
                                <p class="text-muted small">添加文件到IPFS</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="/pins" class="text-decoration-none">
                            <div class="p-4 rounded bg-light">
                                <i class="bi bi-pin-angle display-4 text-success"></i>
                                <h5 class="mt-3">管理Pin</h5>
                                <p class="text-muted small">查看和管理固定内容</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="/settings" class="text-decoration-none">
                            <div class="p-4 rounded bg-light">
                                <i class="bi bi-gear display-4 text-warning"></i>
                                <h5 class="mt-3">系统设置</h5>
                                <p class="text-muted small">配置IPFS连接</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <div class="p-4 rounded bg-light">
                            <i class="bi bi-info-circle display-4 text-info"></i>
                            <h5 class="mt-3">帮助文档</h5>
                            <p class="text-muted small">了解如何使用</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

