# IPFS Kubo 配置指南

## 问题：HTTP 405 - Method Not Allowed

如果你在访问 IPFSMaster 时看到此错误：
```
连接错误: IPFS API returned HTTP 405: 405 - Method Not Allowed
```

这是因为 Kubo 的 CORS（跨域资源共享）配置不允许来自本地 Web 应用的请求。

## 解决方案：配置 CORS

### 方法 1：使用命令行配置（推荐）

在终端运行以下命令：

```bash
# 允许所有本地来源（开发环境）
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Origin '["http://localhost:7789", "http://127.0.0.1:7789", "http://localhost:8080", "http://127.0.0.1:8080", "http://localhost:8081", "http://127.0.0.1:8081", "http://localhost:8082", "http://127.0.0.1:8082"]'

# 允许所有方法
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Methods '["PUT", "POST", "GET", "OPTIONS"]'

# 重启 IPFS 使配置生效
ipfs shutdown
ipfs daemon
```

### 方法 2：手动编辑配置文件

1. **找到配置文件位置**：
   ```bash
   # Linux/Mac
   ~/.ipfs/config
   
   # Windows
   %USERPROFILE%\.ipfs\config
   ```

2. **编辑配置文件**，在 `API` 部分添加或修改 `HTTPHeaders`：

   ```json
   "API": {
       "HTTPHeaders": {
           "Access-Control-Allow-Origin": [
               "http://localhost:7789",
               "http://127.0.0.1:7789",
               "http://localhost:8080",
               "http://127.0.0.1:8080",
               "http://localhost:8081",
               "http://127.0.0.1:8081",
               "http://localhost:8082",
               "http://127.0.0.1:8082",
               "https://webui.ipfs.io",
               "http://webui.ipfs.io.ipns.localhost:8080"
           ],
           "Access-Control-Allow-Methods": [
               "PUT",
               "POST",
               "GET",
               "OPTIONS"
           ]
       }
   }
   ```

3. **重启 IPFS**：
   ```bash
   ipfs shutdown
   ipfs daemon
   ```

### 方法 3：开发环境允许所有来源（仅用于开发）

⚠️ **警告**：此配置不安全，仅用于开发环境！

```bash
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Origin '["*"]'
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Methods '["PUT", "POST", "GET", "OPTIONS"]'
ipfs shutdown
ipfs daemon
```

## 验证配置

配置完成后，验证是否生效：

### 1. 检查配置
```bash
ipfs config API.HTTPHeaders
```

应该看到：
```json
{
  "Access-Control-Allow-Methods": [
    "PUT",
    "POST",
    "GET",
    "OPTIONS"
  ],
  "Access-Control-Allow-Origin": [
    "http://localhost:7789",
    "http://127.0.0.1:7789",
    "http://localhost:8080",
    "http://127.0.0.1:8080",
    "http://localhost:8081",
    "http://127.0.0.1:8081",
    "http://localhost:8082",
    "http://127.0.0.1:8082",
    ...
  ]
}
```

### 2. 测试 API 连接
```bash
curl -X POST http://127.0.0.1:5001/api/v0/version
```

应该返回版本信息，而不是 405 错误。

### 3. 访问 IPFSMaster
打开浏览器访问：
```
http://localhost:7789
```

应该能正常看到仪表板，没有连接错误。

## 常见端口配置

根据你的 IPFSMaster 运行端口，添加相应的 CORS 配置：

| IPFSMaster 端口 | 需要添加的 Origin |
|----------------|------------------|
| 7789 (默认) | `http://localhost:7789`, `http://127.0.0.1:7789` |
| 8080 | `http://localhost:8080`, `http://127.0.0.1:8080` |
| 8081 | `http://localhost:8081`, `http://127.0.0.1:8081` |
| 8082 | `http://localhost:8082`, `http://127.0.0.1:8082` |
| 9000 | `http://localhost:9000`, `http://127.0.0.1:9000` |

## 生产环境建议

在生产环境中，应该：

1. **只允许特定域名**，不要使用 `"*"`
2. **使用 HTTPS** 而不是 HTTP
3. **限制允许的方法**，只开放必要的 HTTP 方法
4. **考虑使用反向代理**（如 Nginx）来统一管理 CORS

示例生产配置：
```json
"API": {
    "HTTPHeaders": {
        "Access-Control-Allow-Origin": [
            "https://ipfs.yourdomain.com"
        ],
        "Access-Control-Allow-Methods": [
            "POST",
            "GET",
            "OPTIONS"
        ],
        "Access-Control-Allow-Credentials": [
            "true"
        ]
    }
}
```

## 故障排查

### 问题 1：配置后仍然 405 错误
- 确保已重启 IPFS daemon
- 检查 IPFS 是否真的运行在 5001 端口：`netstat -an | grep 5001`
- 查看 IPFS 日志：`ipfs log tail`

### 问题 2：IPFS daemon 启动失败
- 配置文件 JSON 格式错误，使用在线工具验证：https://jsonlint.com/
- 恢复默认配置：`ipfs config --json API.HTTPHeaders '{"Access-Control-Allow-Origin": []}'`

### 问题 3：其他应用（如 WebUI）无法访问
- 确保保留了原有的 WebUI 配置
- 完整配置应该包含 WebUI 和 IPFSMaster 的来源

## 安全注意事项

1. **不要在公网暴露 5001 API 端口**
   - API 端口应该只监听 127.0.0.1
   - 检查配置：`ipfs config Addresses.API`
   - 应该是：`/ip4/127.0.0.1/tcp/5001`

2. **使用防火墙保护**
   ```bash
   # Linux - 只允许本地访问
   sudo ufw deny 5001
   ```

3. **定期更新 Kubo**
   ```bash
   ipfs update
   ```

## 参考资料

- [IPFS Kubo 官方文档](https://docs.ipfs.tech/install/command-line/)
- [CORS 配置说明](https://github.com/ipfs/kubo/blob/master/docs/config.md#apihttpheaders)
- [IPFSMaster 项目文档](README.md)

## 快速修复脚本

创建文件 `fix-cors.sh`（Linux/Mac）或 `fix-cors.bat`（Windows）：

**Linux/Mac (fix-cors.sh)**:
```bash
#!/bin/bash
echo "正在配置 IPFS CORS..."
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Origin '["http://localhost:7789", "http://127.0.0.1:7789", "http://localhost:8080", "http://127.0.0.1:8080", "http://localhost:8081", "http://127.0.0.1:8081", "http://localhost:8082", "http://127.0.0.1:8082", "https://webui.ipfs.io"]'
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Methods '["PUT", "POST", "GET", "OPTIONS"]'
echo "配置完成！请重启 IPFS daemon:"
echo "  ipfs shutdown"
echo "  ipfs daemon"
```

**Windows PowerShell (fix-cors.ps1)**:
```powershell
Write-Host "正在配置 IPFS CORS..." -ForegroundColor Green
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Origin '["http://localhost:7789", "http://127.0.0.1:7789", "http://localhost:8080", "http://127.0.0.1:8080", "http://localhost:8081", "http://127.0.0.1:8081", "http://localhost:8082", "http://127.0.0.1:8082", "https://webui.ipfs.io"]'
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Methods '["PUT", "POST", "GET", "OPTIONS"]'
Write-Host "配置完成！请重启 IPFS daemon:" -ForegroundColor Yellow
Write-Host "  ipfs shutdown"
Write-Host "  ipfs daemon"
```

使用方法：
```bash
# Linux/Mac
chmod +x fix-cors.sh
./fix-cors.sh

# Windows PowerShell
.\fix-cors.ps1
```

---

**完成配置后，刷新浏览器页面即可正常使用 IPFSMaster！**
