<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> <strong>连接错误:</strong> <?php echo htmlspecialchars($error); ?>
        <br><small>请检查IPFS节点是否运行，并在<a href="/settings">设置</a>中配置正确的RPC地址。</small>
    </div>
<?php else: ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card blue">
                <div class="stat-label"><i class="bi bi-hdd-network"></i> 节点状态</div>
                <div class="stat-value">在线</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card indigo">
                <div class="stat-label"><i class="bi bi-info-circle"></i> IPFS版本</div>
                <div class="stat-value">
                    <?php echo htmlspecialchars($version['Version'] ?? 'N/A'); ?>
                </div>
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
                <div class="stat-value">
                    <?php
                    $repoSize = $repoStat['RepoSize'] ?? 0;
                    if ($repoSize < 1024 * 1024) {
                        echo number_format($repoSize / 1024, 2) . ' KB';
                    } elseif ($repoSize < 1024 * 1024 * 1024) {
                        echo number_format($repoSize / (1024 * 1024), 2) . ' MB';
                    } else {
                        echo number_format($repoSize / (1024 * 1024 * 1024), 2) . ' GB';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> 节点信息
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 40%;">节点ID</th>
                            <td>
                                <div class="cid-badge">
                                    <?php echo htmlspecialchars(substr($nodeInfo['ID'] ?? '', 0, 20) . '...'); ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>协议版本</th>
                            <td><?php echo htmlspecialchars($nodeInfo['ProtocolVersion'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>代理版本</th>
                            <td><?php echo htmlspecialchars($nodeInfo['AgentVersion'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>RPC地址</th>
                            <td><code><?php echo htmlspecialchars($ipfs->getRpcUrl()); ?></code></td>
                        </tr>
                        <tr>
                            <th>网关地址</th>
                            <td><code><?php echo htmlspecialchars($ipfs->getGatewayBaseUrl()); ?></code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-activity"></i> 实时带宽
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-label">入站</div>
                            <div class="stat-value" style="font-size: 1.2rem;">
                                <?php
                                $rateIn = $bwStat['RateIn'] ?? 0;
                                echo number_format($rateIn / 1024, 2) . ' KB/s';
                                ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-label">出站</div>
                            <div class="stat-value" style="font-size: 1.2rem;">
                                <?php
                                $rateOut = $bwStat['RateOut'] ?? 0;
                                echo number_format($rateOut / 1024, 2) . ' KB/s';
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-muted small">
                        <i class="bi bi-info-circle"></i> 数据每10秒自动刷新
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
