# IPFS CORS 快速修复脚本（PowerShell）
# 用于配置 Kubo 允许 IPFSMaster 访问

Write-Host ""
Write-Host "🔧 正在配置 IPFS CORS..." -ForegroundColor Green
Write-Host ""

# 配置允许的来源
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Origin '["http://localhost:7789", "http://127.0.0.1:7789", "http://localhost:8080", "http://127.0.0.1:8080", "http://localhost:8081", "http://127.0.0.1:8081", "http://localhost:8082", "http://127.0.0.1:8082", "https://webui.ipfs.io", "http://webui.ipfs.io.ipns.localhost:8080"]'

# 配置允许的方法
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Methods '["PUT", "POST", "GET", "OPTIONS"]'

Write-Host "✅ CORS 配置完成！" -ForegroundColor Green
Write-Host ""
Write-Host "📌 下一步：" -ForegroundColor Yellow
Write-Host "  1. 停止 IPFS: ipfs shutdown"
Write-Host "  2. 启动 IPFS: ipfs daemon"
Write-Host "  3. 刷新浏览器访问 IPFSMaster"
Write-Host ""
