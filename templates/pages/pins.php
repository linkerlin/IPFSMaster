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

<div class="alert alert-info <?php echo empty($syncTask) ? 'd-none' : ''; ?>" id="syncProgress">
    <div class="d-flex align-items-center">
        <div class="spinner-border spinner-border-sm me-2" role="status" id="syncSpinner">
            <span class="visually-hidden">同步中...</span>
        </div>
        <div class="flex-grow-1">
            <strong>正在从IPFS同步...</strong>
            <div class="progress mt-2" style="height: 20px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                     style="width: 0%" id="syncProgressBar">0%</div>
            </div>
            <small class="text-muted" id="syncStatus">准备中...</small>
        </div>
    </div>
</div>

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
                    <button type="button" class="btn btn-light" id="syncBtn" onclick="startSync()">
                        <i class="bi bi-cloud-arrow-down"></i> 从IPFS同步
                    </button>
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
    let syncTaskId = <?php echo !empty($syncTask) ? (int)$syncTask['id'] : 'null'; ?>;
    let syncPollInterval = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        unpinModal = new bootstrap.Modal(document.getElementById('unpinModal'));
        
        // Start polling if sync task is active
        if (syncTaskId) {
            showSyncBanner();
            pollSyncStatus();
        }
    });
    
    function confirmUnpin(cid) {
        document.getElementById('unpinCid').textContent = cid;
        document.getElementById('unpinCidInput').value = cid;
        unpinModal.show();
    }
    
    function startSync() {
        const btn = document.getElementById('syncBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> 启动中...';
        
        fetch('/pins/sync', {
            method: 'GET',
            headers: {
                'HX-Request': 'true'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                syncTaskId = data.taskId;
                showSyncBanner();
                pollSyncStatus();
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cloud-arrow-down"></i> 从IPFS同步';
            } else {
                showToast(data.error || '启动同步失败', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cloud-arrow-down"></i> 从IPFS同步';
            }
        })
        .catch(error => {
            showToast('请求失败: ' + error.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-arrow-down"></i> 从IPFS同步';
        });
    }
    
    function pollSyncStatus() {
        if (!syncTaskId) return;
        
        syncPollInterval = setInterval(() => {
            fetch('/pins/task-status?id=' + syncTaskId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    showToast(data.error, 'danger');
                    hideSyncBanner();
                    clearInterval(syncPollInterval);
                    syncTaskId = null;
                    return;
                }

                updateSyncProgress(data);

                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(syncPollInterval);
                    setTimeout(() => {
                        hideSyncBanner();
                        htmx.trigger('#pinsTable', 'refresh');
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Failed to poll sync status:', error);
            });
        }, 1000); // Poll every second
    }

    function showSyncBanner() {
        const progressAlert = document.getElementById('syncProgress');
        if (progressAlert) {
            progressAlert.classList.remove('d-none');
        }
    }

    function hideSyncBanner() {
        const progressAlert = document.getElementById('syncProgress');
        if (progressAlert) {
            progressAlert.classList.add('d-none');
        }
    }
    
    function updateSyncProgress(task) {
        const progressBar = document.getElementById('syncProgressBar');
        const statusText = document.getElementById('syncStatus');
        const progressAlert = document.getElementById('syncProgress');
        
        if (!progressBar || !statusText) return;
        
        if (task.status === 'pending') {
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            statusText.textContent = '准备中...';
        } else if (task.status === 'running') {
            const percentage = task.percentage || 0;
            progressBar.style.width = percentage + '%';
            progressBar.textContent = percentage + '%';
            
            if (task.current_item) {
                statusText.textContent = `正在处理: ${task.current_item} (${task.progress}/${task.total})`;
            } else {
                statusText.textContent = `进度: ${task.progress}/${task.total}`;
            }
        } else if (task.status === 'completed') {
            progressBar.style.width = '100%';
            progressBar.textContent = '100%';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
            document.getElementById('syncSpinner')?.classList.add('d-none');
            
            if (task.result) {
                statusText.textContent = `同步完成！已同步 ${task.result.synced} 个CID`;
                if (task.result.errors > 0) {
                    statusText.textContent += ` (${task.result.errors} 个错误)`;
                }
            } else {
                statusText.textContent = '同步完成！';
            }
            
            progressAlert.classList.remove('alert-info');
            progressAlert.classList.add('alert-success');
            
            // Refresh pins table
            htmx.trigger('#pinsTable', 'refresh');
        } else if (task.status === 'failed') {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-danger');
            statusText.textContent = '同步失败: ' + (task.error || '未知错误');
            document.getElementById('syncSpinner')?.classList.add('d-none');
            
            progressAlert.classList.remove('alert-info');
            progressAlert.classList.add('alert-danger');
        }
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
