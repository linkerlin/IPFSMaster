# 快速启动指南

## 一键启动

### Linux/Mac
```bash
chmod +x start.sh
./start.sh
```

### Windows
```powershell
.\start.ps1
```

或在 PowerShell 中：
```powershell
.\start.ps1
```

## 自定义端口

### Linux/Mac
```bash
./start.sh 8080           # 使用 8080 端口
./start.sh 9000 0.0.0.0   # 使用 9000 端口，监听所有网络接口
```

### Windows
```powershell
.\start.ps1 8080                    # 使用 8080 端口
.\start.ps1 9000 -HostAddress 0.0.0.0  # 使用 9000 端口，监听所有网络接口
```

## 手动启动

如果启动脚本不可用，可以直接使用 PHP：

```bash
php -S localhost:7789 -t public
```

## 访问应用

启动后，打开浏览器访问：

```
http://localhost:7789
```

如果使用了自定义端口（如 8080）：

```
http://localhost:8080
```

## 停止服务器

在终端按 `Ctrl+C` 停止服务器。

## 后台运行

### Linux/Mac
```bash
# 使用 screen
screen -S ipfsmaster
./start.sh
# 按 Ctrl+A 然后按 D 退出

# 重新连接
screen -r ipfsmaster
```

### Windows
```powershell
Start-Process powershell -ArgumentList "php -S localhost:7789 -t public" -WindowStyle Hidden
```

## 故障排查

### 端口被占用
如果看到 "Address already in use" 错误：
```bash
# 使用其他端口
./start.sh 8080
```

### PHP 命令未找到
确保 PHP 已安装并在 PATH 中：
```bash
php -v
```

如果未安装，请访问：https://www.php.net/downloads

### 数据库权限错误
```bash
chmod 755 database
chmod 644 database/ipfs_master.db
```

## 更多信息

- 完整部署指南：[DEPLOYMENT.md](DEPLOYMENT.md)
- 测试文档：[TEST_RESULTS.md](TEST_RESULTS.md)
- 项目说明：[README.md](README.md)
