<div id="pinsTable">
    <?php if (empty($pins)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h4 class="mt-3 text-muted">暂无固定内容</h4>
            <p class="text-muted">您可以在<a href="/upload">上传页面</a>添加新内容</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 30%;">名称</th>
                        <th style="width: 40%;">CID</th>
                        <th style="width: 10%;">大小</th>
                        <th style="width: 10%;">类型</th>
                        <th style="width: 10%;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pins as $pin): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($pin['name'] ?: 'Unnamed'); ?></strong>
                            <br>
                            <small class="text-muted">
                                <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($pin['pinned_at']))); ?>
                            </small>
                        </td>
                        <td>
                            <div class="cid-badge">
                                <?php echo htmlspecialchars($pin['cid']); ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            $size = $pin['size'];
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
                            <span class="badge bg-info">
                                <?php echo htmlspecialchars($pin['type']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/browse?cid=<?php echo urlencode($pin['cid']); ?>"
                                   class="btn btn-outline-primary" title="浏览">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger"
                                        onclick="confirmUnpin('<?php echo htmlspecialchars($pin['cid']); ?>')"
                                        title="取消Pin">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <p class="text-muted small">
                <i class="bi bi-info-circle"></i>
                共 <strong><?php echo count($pins); ?></strong> 个已固定的内容
            </p>
        </div>
    <?php endif; ?>
</div>
