<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="bi bi-folder2-open"></i> 浏览IPFS内容
            <a href="/pins" class="btn btn-outline-secondary btn-sm float-end">
                <i class="bi bi-arrow-left"></i> 返回
            </a>
        </h1>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-fingerprint"></i> CID信息
            </div>
            <div class="card-body">
                <div class="cid-badge mb-3">
                    <?php echo htmlspecialchars($cid); ?>
                </div>
                
                <?php if ($stat): ?>
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card blue">
                            <div class="stat-label">Hash</div>
                            <div class="stat-value" style="font-size: 1rem;">
                                <?php echo htmlspecialchars(substr($stat['Hash'] ?? '', 0, 12) . '...'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card green">
                            <div class="stat-label">大小</div>
                            <div class="stat-value" style="font-size: 1.3rem;">
                                <?php 
                                $size = $stat['CumulativeSize'] ?? 0;
                                if ($size < 1024) {
                                    echo $size . ' B';
                                } elseif ($size < 1024 * 1024) {
                                    echo number_format($size / 1024, 2) . ' KB';
                                } else {
                                    echo number_format($size / (1024 * 1024), 2) . ' MB';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card orange">
                            <div class="stat-label">块数</div>
                            <div class="stat-value"><?php echo $stat['NumLinks'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">数据大小</div>
                            <div class="stat-value" style="font-size: 1rem;">
                                <?php echo number_format(($stat['DataSize'] ?? 0) / 1024, 2); ?> KB
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="<?php echo htmlspecialchars($ipfs->getGatewayUrl($cid)); ?>" 
                       target="_blank" class="btn btn-primary">
                        <i class="bi bi-box-arrow-up-right"></i> 在网关中打开
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($links)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-diagram-3"></i> 包含的内容 (<?php echo count($links); ?> 项)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>名称</th>
                                <th>Hash</th>
                                <th>大小</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($links as $link): ?>
                            <tr>
                                <td>
                                    <i class="bi bi-file-earmark"></i>
                                    <strong><?php echo htmlspecialchars($link['Name'] ?: 'Unnamed'); ?></strong>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars(substr($link['Hash'], 0, 20) . '...'); ?></code>
                                </td>
                                <td>
                                    <?php 
                                    $size = $link['Size'] ?? 0;
                                    if ($size < 1024) {
                                        echo $size . ' B';
                                    } elseif ($size < 1024 * 1024) {
                                        echo number_format($size / 1024, 2) . ' KB';
                                    } else {
                                        echo number_format($size / (1024 * 1024), 2) . ' MB';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/browse?cid=<?php echo urlencode($link['Hash']); ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i> 查看
                                        </a>
                                        <a href="<?php echo htmlspecialchars($ipfs->getGatewayUrl($link['Hash'])); ?>" 
                                           target="_blank" class="btn btn-outline-secondary">
                                            <i class="bi bi-download"></i> 下载
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
