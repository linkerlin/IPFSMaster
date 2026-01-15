# IPFS Master 启动脚本（PowerShell）
# 使用方法: .\start.ps1 [端口] [主机]
# 示例: .\start.ps1 8080
# 示例: .\start.ps1 7789 0.0.0.0

param(
    [int]$Port = 7789,
    [string]$HostAddress = "localhost"
)

Write-Host ""
Write-Host "🚀 启动 IPFS Master..." -ForegroundColor Green

# 启动后台 Worker 进程
Write-Host "🔧 启动后台任务处理器..." -ForegroundColor Cyan
$workerScript = Join-Path $PSScriptRoot "worker.php"
$workerLogPath = Join-Path $PSScriptRoot "logs\worker.log"
$logsDir = Join-Path $PSScriptRoot "logs"

if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir -Force | Out-Null
}

# 启动 worker 进程（后台运行）
$workerErrorLogPath = Join-Path $PSScriptRoot "logs\worker_error.log"
$workerProcess = Start-Process -FilePath "php" `
    -ArgumentList $workerScript, "1" `
    -WindowStyle Hidden `
    -PassThru `
    -RedirectStandardOutput $workerLogPath `
    -RedirectStandardError $workerErrorLogPath

Write-Host "✓ Worker 进程已启动 (PID: $($workerProcess.Id))" -ForegroundColor Green
Write-Host "  日志: $workerLogPath" -ForegroundColor Gray

Write-Host ""
Write-Host "📡 地址: http://$($HostAddress):$Port" -ForegroundColor Cyan
Write-Host "📌 按 Ctrl+C 停止服务器" -ForegroundColor Yellow
Write-Host ""

# 注册清理函数
$cleanup = {
    Write-Host ""
    Write-Host "🛑 正在停止服务..." -ForegroundColor Yellow
    
    if ($workerProcess -and -not $workerProcess.HasExited) {
        Write-Host "  停止 Worker 进程..." -ForegroundColor Gray
        Stop-Process -Id $workerProcess.Id -Force -ErrorAction SilentlyContinue
        Write-Host "✓ Worker 已停止" -ForegroundColor Green
    }
    
    Write-Host "👋 服务已停止" -ForegroundColor Cyan
}

# 注册退出事件
Register-EngineEvent PowerShell.Exiting -Action $cleanup | Out-Null

# 启动 PHP 内置服务器
try {
    php -S "$($HostAddress):$Port" -t public
} finally {
    & $cleanup
}
