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
Write-Host "📡 地址: http://$($HostAddress):$Port" -ForegroundColor Cyan
Write-Host "📌 按 Ctrl+C 停止服务器" -ForegroundColor Yellow
Write-Host ""

# 启动 PHP 内置服务器
php -S "$($HostAddress):$Port" -t public
