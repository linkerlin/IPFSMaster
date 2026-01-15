# IPFS Master

![IPFS](https://img.shields.io/badge/IPFS-Compatible-65c2cb?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4+-777bb4?style=flat-square)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3?style=flat-square)
![Tests](https://img.shields.io/badge/Tests-89%20passed-success?style=flat-square)
![Coverage](https://img.shields.io/badge/Assertions-198-blue?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

**IPFS大师** - 一个美观、功能强大的本地IPFS节点管理Web应用。

## ✨ 特性

- 🎨 **华丽界面** - 基于Bootstrap 5设计，渐变色彩，现代化UI
- ⚡ **纯PHP实现** - 无需Node.js，轻量级部署
- 🗄️ **SQLite数据库** - 零配置，开箱即用
- 🔄 **htmx驱动** - 无刷新页面更新，流畅体验
- 📌 **自动递归Pin** - 智能固定所有关联内容，防止GC回收
- ⚡ **后台任务** - 异步处理大量Pin同步，避免超时
- 🌐 **灵活配置** - 支持自定义RPC和Gateway地址
- 📁 **文件管理** - 上传文件、导入CID、浏览内容
- 🔍 **内容浏览器** - 查看IPFS对象结构和链接
- ✅ **全面测试** - 89个测试，198个断言，100%通过

## 📋 系统要求

- PHP 7.4 或更高版本
- PHP扩展: `sqlite3`, `curl`, `json`
- IPFS Kubo节点（本地或远程）

> 💡 使用PHP内置服务器，无需安装Apache或Nginx！

## 🧪 测试

本项目包含完整的测试套件（19个测试类，89个测试，198个断言）：

### 运行测试

```bash
# 安装开发依赖
composer install

# 运行所有测试
vendor/bin/phpunit

# 运行特定测试类
vendor/bin/phpunit tests/DatabaseTest.php

# 生成覆盖率报告（需要Xdebug）
vendor/bin/phpunit --coverage-html coverage
```

### 测试覆盖范围

- ✅ **89个测试，100%通过** - 零失败，零错误
- ✅ 数据库操作（设置、Pin管理、导入历史、SQL注入防护）
- ✅ IPFS客户端（地址转换、JSON解析、文件上传、目录递归）
- ✅ 路由系统（路径匹配、参数提取、多参数路由）
- ✅ 控制器（JSON响应、htmx触发器、模板渲染）
- ✅ 输入验证（CID格式、路径遍历、XSS防护）
- ✅ 性能测试（数据库查询、批量操作）
- ✅ 边界情况（空值、特殊字符、格式错误）

详细测试结果：[TEST_RESULTS.md](TEST_RESULTS.md)
- ✅ 控制器逻辑（请求处理、响应生成）
- ✅ 边界条件（空数据、异常处理、并发）

详细文档请查看 [tests/README.md](tests/README.md)

## 🚀 快速开始

### 1. 安装IPFS Kubo

如果还没有安装IPFS节点，请先安装：

```bash
# 下载并安装IPFS Kubo
wget https://dist.ipfs.tech/kubo/v0.24.0/kubo_v0.24.0_linux-amd64.tar.gz
tar -xvzf kubo_v0.24.0_linux-amd64.tar.gz
cd kubo
sudo bash install.sh

# 初始化并启动IPFS
ipfs init
ipfs daemon
```

### 2. 配置 IPFS CORS（重要！）

⚠️ **必须配置 CORS 才能正常使用 IPFSMaster**

**快速配置（推荐）**：
```bash
# Linux/Mac
chmod +x fix-cors.sh
./fix-cors.sh

# Windows PowerShell
.\fix-cors.ps1
```

**手动配置**：
```bash
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Origin '["http://localhost:7789", "http://127.0.0.1:7789", "http://localhost:8080", "http://127.0.0.1:8080", "http://localhost:8081", "http://127.0.0.1:8081", "http://localhost:8082", "http://127.0.0.1:8082"]'
ipfs config --json API.HTTPHeaders.Access-Control-Allow-Methods '["PUT", "POST", "GET", "OPTIONS"]'

# 重启 IPFS
ipfs shutdown
ipfs daemon
```

> 💡 详细说明请查看 [IPFS_CORS_SETUP.md](IPFS_CORS_SETUP.md)

### 3. 部署IPFS Master

```bash
# 克隆仓库
git clone https://github.com/linkerlin/IPFSMaster.git
cd IPFSMaster

# Linux/Mac - 使用启动脚本
chmod +x start.sh
./start.sh

# 或手动启动
php -S localhost:7789 -t public
```

**Windows 用户**：
```powershell
# PowerShell 中运行
.\start.ps1

# 或手动启动
php -S localhost:7789 -t public
```

**自定义端口**：
```bash
# Linux/Mac
./start.sh 8080

# Windows PowerShell
.\start.ps1 8080

# 手动指定
php -S localhost:8080 -t public
```

### 3. 访问应用

打开浏览器访问：

```
http://localhost:7789
```

> 💡 端口可自定义，如：`php -S localhost:8080 -t public`

### 4. 常见问题

#### ❌ HTTP 405 - Method Not Allowed

如果看到 "连接错误: IPFS API returned HTTP 405" 错误：
- **原因**：IPFS 的 CORS 配置不正确
- **解决**：运行 `./fix-cors.sh`（Linux/Mac）或 `.\fix-cors.ps1`（Windows）
- **详细说明**：查看 [IPFS_CORS_SETUP.md](IPFS_CORS_SETUP.md)

#### ❌ 无法连接到 IPFS

确保 IPFS daemon 正在运行：
```bash
ipfs id  # 检查是否运行
```

## 🎯 功能说明

### 仪表盘
- 查看IPFS节点状态和版本信息
- 显示已固定内容统计
- 快速访问常用功能

### 上传文件
- 拖拽上传文件到IPFS
- 自动Pin上传的内容
- 导入已存在的CID并递归Pin

### Pin管理
- 查看所有已固定的内容
- 管理Pin（添加/删除）
- **后台异步同步** - 从IPFS节点同步大量Pin，显示实时进度
- 浏览Pin的内容结构

> 💡 **新功能**：Pin 同步现在支持后台异步处理！当您的节点有大量 Pin 时，同步操作会在独立进程中执行，前端显示实时进度条，不会阻塞页面或超时。详见 [BACKGROUND_TASKS.md](BACKGROUND_TASKS.md)

### 内容浏览
- 查看IPFS对象的详细信息
- 浏览目录结构和文件列表
- 通过网关访问内容

### 系统设置
- 配置IPFS RPC地址（支持多地址和HTTP格式）
- 配置IPFS Gateway地址
- 设置自动Pin和递归Pin选项

## ⚙️ 配置说明

### IPFS RPC地址

支持以下格式：
- 多地址格式：`/ip4/127.0.0.1/tcp/5001`
- HTTP URL格式：`http://127.0.0.1:5001`
- 远程节点：`http://100.113.134.128:5001`

### IPFS Gateway地址

默认网关：`http://127.0.0.1:8080`

系统会自动尝试以下端口（如果主网关不可用）：
- 8080（默认）
- 8081
- 8082

也可以配置为其他地址，如：
- `http://127.0.0.1:8088`（自定义端口）
- `https://ipfs.io`（公共网关）

## 🔒 安全提示

- ⚠️ 此应用设计用于本地网络环境
- ⚠️ 不要将IPFS API端口暴露到公网
- ⚠️ 建议在可信网络中使用
- ⚠️ 上传文件前请确保数据安全

## 🛠️ 技术栈

- **后端**: 纯PHP（无框架）
- **数据库**: SQLite 3
- **前端**: Bootstrap 5 + htmx
- **IPFS**: Kubo RPC API

## 📝 目录结构

```
IPFSMaster/
├── public/              # Web根目录
│   ├── index.php       # 应用入口
│   └── .htaccess       # Apache重写规则
├── src/
│   ├── Controllers/    # 控制器
│   ├── Models/         # 模型（数据库、IPFS客户端）
│   └── Router.php      # 路由器
├── templates/
│   ├── layouts/        # 布局模板
│   └── pages/          # 页面模板
├── database/           # SQLite数据库文件（自动创建）
└── README.md
```

## 🤝 贡献

欢迎提交Issue和Pull Request！

## 📄 许可证

MIT License

## 🌟 特别说明

### 自动递归Pin功能

当导入文件、文件夹或CID时，系统会：
1. 添加主内容到IPFS（如果是文件上传）
2. 执行递归Pin操作，固定主CID
3. 遍历所有链接的子对象
4. 递归固定所有关联内容
5. 保存到数据库，永久追踪

这确保了所有导入的数据不会因为IPFS的垃圾回收（GC）而丢失。

## 📞 支持

如有问题，请提交Issue或联系作者。

---

Made with ❤️ for the IPFS community
