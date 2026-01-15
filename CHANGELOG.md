# 更新日志（Changelog）

## [1.1.0] - 2026-01-15

### 重大变更 🚀
- **移除 Apache 依赖** - 不再需要 Apache 或 Nginx
- **PHP 内置服务器支持** - 使用 `php -S localhost:7789 -t public` 一键启动
- **简化部署流程** - 零配置，开箱即用

### 新增功能 ✨
- 添加 `start.sh` 启动脚本（Linux/Mac）
- 添加 `start.ps1` 启动脚本（Windows PowerShell）
- 添加 `QUICKSTART.md` 快速启动指南
- 支持自定义端口和网络接口

### 移除内容 ⛔
- 删除 `public/.htaccess`（Apache 专用）
- 移除 Apache 配置示例（DEPLOYMENT.md）
- 移除 Nginx 配置示例（DEPLOYMENT.md）

### 文档更新 📝
- 更新 `README.md` - PHP 内置服务器说明
- 更新 `DEPLOYMENT.md` - 简化部署步骤
- 更新 `PROJECT_COMPLETE.md` - 强调零依赖特性

### 技术细节
- 路由器（Router）完全兼容 PHP 内置服务器
- 所有测试通过（89 tests, 198 assertions）
- 无需额外配置即可运行

---

## [1.0.0] - 2026-01-15

### 初始版本 🎉
- 完整的 IPFS 节点管理功能
- 文件上传和 CID 导入
- 自动递归 Pin 管理
- 内容浏览器
- 设置管理
- 89 个测试，100% 通过
- 完整文档

### 核心功能
- 仪表板（实时节点状态）
- 文件/文件夹上传
- Pin 列表管理
- CID 内容浏览
- IPFS RPC/网关配置

### 技术栈
- PHP 7.4+
- SQLite 3
- Bootstrap 5.3
- htmx
- PHPUnit 9.6

---

## 升级指南

### 从 1.0.0 升级到 1.1.0

1. **备份数据库**（可选）
   ```bash
   cp database/ipfs_master.db database/ipfs_master.db.backup
   ```

2. **拉取最新代码**
   ```bash
   git pull origin main
   ```

3. **删除旧的 .htaccess**（如果存在）
   ```bash
   rm public/.htaccess
   ```

4. **使用新的启动方式**
   ```bash
   # Linux/Mac
   ./start.sh
   
   # Windows
   start.bat
   
   # 或手动
   php -S localhost:7789 -t public
   ```

5. **测试应用**
   访问 http://localhost:7789

### 注意事项
- ⚠️ 不再支持 Apache/Nginx 部署方式
- ✅ 所有功能保持不变，仅部署方式简化
- ✅ 数据库结构无变化，无需迁移
- ✅ 现有 Pin 和设置保持不变

---

## 兼容性

### PHP 版本
- 最低要求：PHP 7.4
- 推荐版本：PHP 8.0+
- 测试版本：PHP 8.3

### 操作系统
- ✅ Windows 10/11
- ✅ Linux (Ubuntu, Debian, CentOS, etc.)
- ✅ macOS

### IPFS Kubo
- 兼容版本：0.20.0+
- 推荐版本：0.24.0+

---

## 已知问题

暂无已知问题。

如发现问题，请在 GitHub Issues 报告：
https://github.com/linkerlin/IPFSMaster/issues

---

## 贡献者

感谢所有为本项目做出贡献的开发者！

---

## 许可证

MIT License - 详见 LICENSE 文件
