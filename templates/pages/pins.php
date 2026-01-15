<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="bi bi-pin-angle"></i> Pin管理</h1>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul"></i> 已固定的内容</span>
                <a href="/pins/sync" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> 从IPFS同步
                </a>
            </div>
            <div class="card-body">
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
        </div>
    </div>
</div>

<!-- Unpin confirmation modal -->
<div class="modal fade" id="unpinModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-warning"></i> 确认取消Pin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>您确定要取消固定以下内容吗？</p>
                <div class="cid-badge" id="unpinCid"></div>
                <p class="text-danger mt-3">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <strong>警告:</strong> 取消Pin后，该内容可能会在下次垃圾回收时被删除。
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <form id="unpinForm" method="POST" action="/pins/remove">
                    <input type="hidden" name="cid" id="unpinCidInput">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> 确认取消Pin
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let unpinModal = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        unpinModal = new bootstrap.Modal(document.getElementById('unpinModal'));
    });
    
    function confirmUnpin(cid) {
        document.getElementById('unpinCid').textContent = cid;
        document.getElementById('unpinCidInput').value = cid;
        unpinModal.show();
    }
    
    document.getElementById('unpinForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('/pins/remove', {
            method: 'POST',
            body: formData,
            headers: {
                'HX-Request': 'true'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Pin已成功移除', 'success');
                unpinModal.hide();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('错误: ' + data.error, 'danger');
            }
        })
        .catch(error => {
            showToast('操作失败: ' + error.message, 'danger');
        });
    });
</script>
