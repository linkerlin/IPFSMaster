# 后台任务系统

## 概述

IPFSMaster 实现了一个基于 SQLite 的后台任务系统，用于处理长时间运行的操作，如从 IPFS 节点同步大量 Pin。

## 架构设计

### 1. 数据库表结构

**background_tasks** 表：
- `id`: 任务ID（主键）
- `task_type`: 任务类型（如：pin_sync）
- `status`: 任务状态（pending, running, completed, failed）
- `progress`: 当前进度
- `total`: 总数量
- `current_item`: 当前处理的项目
- `result`: 结果JSON
- `error_message`: 错误信息
- `created_at`: 创建时间
- `started_at`: 开始时间
- `completed_at`: 完成时间
- `pid`: 进程ID

### 2. 工作流程

```
用户点击同步按钮
    ↓
PinController::sync() 创建任务记录
    ↓
启动独立 PHP 进程 (scripts/sync_pins.php)
    ↓
前端轮询任务状态 (/pins/task-status?id=xxx)
    ↓
后台进程更新进度到数据库
    ↓
完成后刷新页面显示结果
```

### 3. 进程通信

- **单向通信**：后台进程通过 SQLite 写入状态
- **状态查询**：前端通过 HTTP API 读取状态
- **无阻塞**：主进程立即返回，不等待同步完成

### 4. 平台兼容性

**Windows**:
```php
$cmd = "powershell -Command \"Start-Process -FilePath '$phpBinary' -ArgumentList '$scriptPath $taskId' -WindowStyle Hidden\"";
pclose(popen($cmd, 'r'));
```

**Linux/macOS**:
```php
$cmd = "nohup $phpBinary $scriptPath $taskId > /dev/null 2>&1 &";
exec($cmd);
```

## 使用示例

### 启动同步任务

```javascript
// 前端 JavaScript
fetch('/pins/sync', {
    method: 'GET',
    headers: { 'HX-Request': 'true' }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // 开始轮询任务状态
        pollTaskStatus(data.taskId);
    }
});
```

### 查询任务状态

```javascript
function pollTaskStatus(taskId) {
    fetch('/pins/task-status?id=' + taskId)
    .then(response => response.json())
    .then(task => {
        console.log(`进度: ${task.progress}/${task.total} (${task.percentage}%)`);
        
        if (task.status === 'completed') {
            console.log('同步完成！');
            location.reload();
        } else if (task.status === 'running') {
            // 继续轮询
            setTimeout(() => pollTaskStatus(taskId), 1000);
        }
    });
}
```

### 后台脚本执行

```bash
# 直接执行（调试用）
php scripts/sync_pins.php 1

# 输出示例：
# [Task 1] Fetching pins from IPFS...
# [Task 1] Found 150 pins to sync
# [Task 1] Progress: 10 / 150
# [Task 1] Progress: 20 / 150
# ...
# [Task 1] Sync completed: 150 pins synced
```

## 错误处理

### 1. 并发控制

同一时间只允许一个同步任务运行：

```php
$result = $db->query("SELECT id FROM background_tasks 
                      WHERE task_type = 'pin_sync' 
                      AND status IN ('pending', 'running') 
                      LIMIT 1");
if ($result->fetchArray()) {
    throw new Exception('已有同步任务正在运行中');
}
```

### 2. 超时保护

后台脚本设置无限执行时间：

```php
set_time_limit(0);
ini_set('max_execution_time', 0);
```

### 3. 失败恢复

任务失败时记录详细错误：

```php
catch (Exception $e) {
    $stmt = $db->prepare("UPDATE background_tasks 
                          SET status = 'failed', 
                              error_message = :error 
                          WHERE id = :id");
    $stmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
    $stmt->execute();
}
```

## 性能优化

### 1. 批量进度更新

每处理10个项目打印一次日志，避免过度IO：

```php
if ($processed % 10 === 0) {
    echo "[Task $taskId] Progress: $processed / $total\n";
}
```

### 2. 错误限制

只保留前10个错误详情，避免数据库膨胀：

```php
'error_details' => array_slice($errors, 0, 10)
```

### 3. 数据库索引

为常用查询添加索引：

```sql
CREATE INDEX idx_tasks_status ON background_tasks(status, created_at);
```

## 扩展性

这个系统可以轻松扩展支持其他后台任务：

```php
// 示例：后台上传大文件
case 'file_upload':
    $stmt = $db->prepare("INSERT INTO background_tasks 
                          (task_type, status) 
                          VALUES ('file_upload', 'pending')");
    $stmt->execute();
    $taskId = $db->lastInsertRowID();
    
    // 启动后台进程
    exec("php scripts/upload_file.php $taskId &");
    break;
```

## 监控和调试

### 查看所有任务

```sql
SELECT id, task_type, status, progress, total, 
       created_at, completed_at 
FROM background_tasks 
ORDER BY created_at DESC;
```

### 清理历史任务

```sql
-- 删除30天前的已完成任务
DELETE FROM background_tasks 
WHERE status IN ('completed', 'failed') 
AND created_at < datetime('now', '-30 days');
```

### 手动终止任务

```sql
-- 标记为失败
UPDATE background_tasks 
SET status = 'failed', 
    error_message = 'Manually terminated' 
WHERE id = 123;
```

## 注意事项

1. **资源限制**：确保 PHP 有足够的内存处理大量 Pin
2. **数据库锁**：SQLite 支持并发读，但写操作会锁表
3. **进程管理**：异常退出的进程可能留下"running"状态，需要定期清理
4. **权限问题**：确保 PHP 进程有权限创建子进程

## 最佳实践

1. 定期清理历史任务记录
2. 监控失败任务并调查原因
3. 为大型同步任务设置进度通知
4. 考虑添加任务取消功能
5. 实现任务重试机制
