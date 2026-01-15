<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="bi bi-gear"></i> 系统设置</h1>
    </div>
</div>

<?php if (isset($success) && $success): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle"></i> 设置已成功保存！
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-sliders"></i> IPFS连接配置
            </div>
            <div class="card-body">
                <form method="POST" action="/settings/update">
                    <div class="mb-4">
                        <label for="ipfs_rpc_url" class="form-label">
                            <i class="bi bi-hdd-network"></i> IPFS Kubo RPC地址
                        </label>
                        <input type="text" class="form-control" id="ipfs_rpc_url" name="ipfs_rpc_url" 
                               value="<?php echo htmlspecialchars($settings['ipfs_rpc_url'] ?? '/ip4/127.0.0.1/tcp/5001'); ?>"
                               required>
                        <div class="form-text">
                            支持多地址格式（如 /ip4/127.0.0.1/tcp/5001）或HTTP URL格式（如 http://127.0.0.1:5001）
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">示例:</small>
                            <ul class="small text-muted mb-0">
                                <li><code>/ip4/127.0.0.1/tcp/5001</code> - 本地默认</li>
                                <li><code>http://100.113.134.128:5001</code> - 远程节点</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="ipfs_gateway_url" class="form-label">
                            <i class="bi bi-globe"></i> IPFS网关地址
                        </label>
                        <input type="text" class="form-control" id="ipfs_gateway_url" name="ipfs_gateway_url" 
                               value="<?php echo htmlspecialchars($settings['ipfs_gateway_url'] ?? 'http://127.0.0.1:8080'); ?>"
                               required>
                        <div class="form-text">
                            用于访问IPFS内容的HTTP网关地址。系统会自动尝试8080、8081、8082端口
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">示例:</small>
                            <ul class="small text-muted mb-0">
                                <li><code>http://127.0.0.1:8080</code> - 本地默认</li>
                                <li><code>http://127.0.0.1:8088</code> - 自定义端口</li>
                                <li><code>https://ipfs.io</code> - 公共网关</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-toggles"></i> Pin选项
                        </label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="auto_pin" name="auto_pin" value="1"
                                   <?php echo ($settings['auto_pin'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="auto_pin">
                                自动Pin上传的文件
                            </label>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="recursive_pin" name="recursive_pin" value="1"
                                   <?php echo ($settings['recursive_pin'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="recursive_pin">
                                递归Pin（推荐）
                            </label>
                        </div>
                        <div class="form-text">
                            递归Pin会自动固定所有关联的内容，防止数据丢失
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> 保存设置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> 配置说明
            </div>
            <div class="card-body">
                <h6><i class="bi bi-1-circle text-primary"></i> RPC地址</h6>
                <p class="small text-muted">
                    IPFS节点的API端点，用于所有IPFS操作。默认使用本地节点的5001端口。
                </p>
                
                <h6 class="mt-3"><i class="bi bi-2-circle text-primary"></i> 网关地址</h6>
                <p class="small text-muted">
                    用于通过HTTP访问IPFS内容。如果主网关不可用，系统会自动尝试备用端口。
                </p>
                
                <h6 class="mt-3"><i class="bi bi-3-circle text-primary"></i> Pin设置</h6>
                <p class="small text-muted">
                    开启递归Pin确保导入的所有内容及其依赖都被固定，避免垃圾回收时丢失数据。
                </p>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-shield-check"></i> 安全提示
            </div>
            <div class="card-body">
                <p class="small">
                    <i class="bi bi-exclamation-triangle text-warning"></i> 
                    请确保IPFS节点RPC接口的安全性，不要暴露在公网上。
                </p>
                <p class="small mb-0">
                    <i class="bi bi-lock text-success"></i> 
                    建议仅在本地或可信网络中使用。
                </p>
            </div>
        </div>
    </div>
</div>
