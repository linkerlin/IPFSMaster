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
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light"
                            hx-get="/pins/table" hx-target="#pinsTable" hx-swap="outerHTML">
                        <i class="bi bi-arrow-clockwise"></i> 刷新列表
                    </button>
                    <a href="/pins/sync" class="btn btn-light">
                        <i class="bi bi-cloud-arrow-down"></i> 从IPFS同步
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php include __DIR__ . '/partials/pins_table.php'; ?>
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
                <form id="unpinForm" method="POST" action="/pins/remove"
                      hx-post="/pins/remove" hx-target="#pinsTable" hx-swap="outerHTML">
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
    
    document.body.addEventListener('toast', function(evt) {
        if (evt.detail && evt.detail.message) {
            showToast(evt.detail.message, evt.detail.type || 'success');
        }
    });

    document.body.addEventListener('htmx:afterSwap', function(evt) {
        if (evt.detail && evt.detail.target && evt.detail.target.id === 'pinsTable') {
            if (unpinModal) {
                unpinModal.hide();
            }
        }
    });
</script>
