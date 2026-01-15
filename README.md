# IPFS Master

![IPFS](https://img.shields.io/badge/IPFS-Compatible-65c2cb?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4+-777bb4?style=flat-square)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

**IPFS大师** - 一个美观、功能强大的本地IPFS节点管理Web应用。

## ✨ 特性

- 🎨 **华丽界面** - 基于Bootstrap 5设计，渐变色彩，现代化UI
- ⚡ **纯PHP实现** - 无需Node.js，轻量级部署
- 🗄️ **SQLite数据库** - 零配置，开箱即用
- 🔄 **htmx驱动** - 无刷新页面更新，流畅体验
- 📌 **自动递归Pin** - 智能固定所有关联内容，防止GC回收
- 🌐 **灵活配置** - 支持自定义RPC和Gateway地址
- 📁 **文件管理** - 上传文件、导入CID、浏览内容
- 🔍 **内容浏览器** - 查看IPFS对象结构和链接

## 📋 系统要求

- PHP 7.4 或更高版本
- PHP扩展: `sqlite3`, `curl`, `json`
- Apache/Nginx Web服务器（推荐Apache with mod_rewrite）
- IPFS Kubo节点（本地或远程）

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

### 2. 部署IPFS Master

```bash
# 克隆仓库
git clone https://github.com/linkerlin/IPFSMaster.git
cd IPFSMaster

# 配置Web服务器指向public目录
# Apache示例配置见下文
```

### 3. Apache配置示例

```apache
<VirtualHost *:80>
    ServerName ipfs-master.local
    DocumentRoot /path/to/IPFSMaster/public
    
    <Directory /path/to/IPFSMaster/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ipfs-master-error.log
    CustomLog ${APACHE_LOG_DIR}/ipfs-master-access.log combined
</VirtualHost>
```

### 4. 使用PHP内置服务器（开发环境）

```bash
cd public
php -S localhost:8000
```

然后访问 http://localhost:8000

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
- 从IPFS节点同步Pin列表
- 浏览Pin的内容结构

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
