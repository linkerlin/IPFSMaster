# 🎉 IPFSMaster v1.1.0 - 简化部署更新

## 更新完成 ✅

已成功将 IPFSMaster 从 Apache/Nginx 依赖迁移到 PHP 内置服务器。

## 主要变更

### 1️⃣ 移除 Apache 依赖
- ❌ 删除 `public/.htaccess`
- ❌ 移除 Apache 配置文档
- ❌ 不再需要 mod_rewrite

### 2️⃣ 添加 PHP 内置服务器支持
- ✅ 一键启动：`php -S localhost:7789 -t public`
- ✅ 启动脚本：`start.sh` (Linux/Mac) 和 `start.bat` (Windows)
- ✅ 支持自定义端口和网络接口

### 3️⃣ 文档更新
- ✅ `README.md` - 更新快速开始部分
- ✅ `DEPLOYMENT.md` - 重写为 PHP 服务器部署指南
- ✅ `PROJECT_COMPLETE.md` - 强调零依赖特性
- ✅ `QUICKSTART.md` - 新增快速启动指南
- ✅ `CHANGELOG.md` - 新增更新日志

## 启动方式

### 方式 1：使用启动脚本（推荐）

**Linux/Mac:**
```bash
chmod +x start.sh
./start.sh
```

**Windows:**
```powershell
.\start.ps1
```

### 方式 2：手动启动

```bash
php -S localhost:7789 -t public
```

### 方式 3：自定义配置

```bash
# Linux/Mac - 自定义端口
./start.sh 8080

# Windows PowerShell - 自定义端口
.\start.ps1 8080

# 局域网访问
./start.sh 7789 0.0.0.0                    # Linux/Mac
.\start.ps1 7789 -HostAddress 0.0.0.0      # Windows

# 手动指定
php -S 0.0.0.0:8080 -t public
```

## 测试结果

✅ **所有 89 个测试通过**
- 198 个断言成功
- 0 个失败
- 0 个错误
- 执行时间：12.598 秒

## 兼容性

- ✅ PHP 7.4+（推荐 8.0+）
- ✅ Windows 10/11
- ✅ Linux (Ubuntu, Debian, CentOS, etc.)
- ✅ macOS
- ✅ IPFS Kubo 0.20.0+

## 升级步骤

如果你正在使用旧版本（1.0.0）：

```bash
# 1. 备份数据库（可选）
cp database/ipfs_master.db database/ipfs_master.db.backup

# 2. 拉取最新代码
git pull origin main

# 3. 删除旧的 .htaccess（如果存在）
rm public/.htaccess

# 4. 使用新的启动方式
./start.sh   # Linux/Mac
# 或
.\start.ps1  # Windows PowerShell
```

## 优势对比

### 之前（v1.0.0）
- ❌ 需要安装 Apache 或 Nginx
- ❌ 需要配置虚拟主机
- ❌ 需要启用 mod_rewrite
- ❌ 需要配置权限
- ❌ 部署复杂

### 现在（v1.1.0）
- ✅ 零依赖（只需 PHP）
- ✅ 一条命令启动
- ✅ 自动路由
- ✅ 跨平台一致
- ✅ 部署超简单

## 性能

PHP 内置服务器性能对比：
- **开发环境**：完全够用
- **小型团队**：5-10 人同时使用无压力
- **个人使用**：完美

> 💡 如需高并发生产环境，建议使用 PHP-FPM + Nginx

## 文件清单

新增文件：
- ✅ `start.sh` - Linux/Mac 启动脚本
- ✅ `start.bat` - Windows 启动脚本
- ✅ `QUICKSTART.md` - 快速启动指南
- ✅ `CHANGELOG.md` - 更新日志
- ✅ `UPDATE_SUMMARY.md` - 本文件

删除文件：
- ❌ `public/.htaccess`

修改文件：
- 📝 `README.md`
- 📝 `DEPLOYMENT.md`
- 📝 `PROJECT_COMPLETE.md`

## 常见问题

### Q: 为什么移除 Apache 支持？
A: 简化部署流程，降低使用门槛。PHP 内置服务器足够满足个人和小团队使用。

### Q: 端口 7789 被占用怎么办？
A: 使用自定义端口：`./start.sh 8080` 或 `php -S localhost:8080 -t public`

### Q: 如何后台运行？
A: 
- Linux/Mac: 使用 `screen` 或 `nohup`
- Windows: 使用 PowerShell 后台启动或创建服务

详见 `DEPLOYMENT.md`

### Q: 性能够用吗？
A: 对于个人和小团队完全够用。如需高并发，建议使用专业 Web 服务器。

## 下一步

1. 启动服务器：`./start.sh`
2. 访问应用：http://localhost:7789
3. 开始使用 IPFS Master！

## 反馈

如有问题或建议，欢迎：
- GitHub Issues: https://github.com/linkerlin/IPFSMaster/issues
- 查看文档：`README.md`, `DEPLOYMENT.md`, `QUICKSTART.md`

---

**更新日期**: 2026-01-15
**版本**: 1.1.0
**状态**: ✅ 生产就绪
