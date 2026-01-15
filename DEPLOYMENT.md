# IPFS Master 部署指南

## 环境检查

### PHP 版本与扩展
```bash
# 检查 PHP 版本（需要 7.4+）
php -v

# 检查必需扩展
php -m | grep -E 'sqlite3|curl|json'
```

### IPFS 节点
```bash
# 检查 IPFS 是否运行
ipfs version

# 检查 API 端点
curl http://127.0.0.1:5001/api/v0/version

# 检查网关
curl http://127.0.0.1:8080/ipfs/QmYwAPJzv5CZsnA625s3Xf2nemtYgPpHdWEz79ojWnPbdG
```

## 快速部署（推荐）

### 1. 克隆项目
```bash
git clone https://github.com/linkerlin/IPFSMaster.git
cd IPFSMaster
```

### 2. 设置权限
```bash
# 确保数据库目录可写
chmod 755 database
chmod 644 database/ipfs_master.db  # 如果已存在
```

### 3. 启动服务器

使用 PHP 内置服务器（开发和生产环境均可）：

```bash
# 默认端口 7789
php -S localhost:7789 -t public

# 或自定义端口
php -S localhost:8080 -t public

# 监听所有网络接口（局域网访问）
php -S 0.0.0.0:7789 -t public
```

### 4. 访问应用

打开浏览器访问：
```
http://localhost:7789
```

如果监听 0.0.0.0，可通过局域网 IP 访问：
```
http://192.168.1.100:7789
```

## 生产环境优化
```

```nginx
# Nginx
location / {
    auth_basic "IPFS Master";
    auth_basic_user_file /etc/nginx/.htpasswd;
}
```

### 3. HTTPS（强烈推荐）
```bash
# 使用 Let's Encrypt
certbot --apache -d ipfs.yourdomain.com
# 或
certbot --nginx -d ipfs.yourdomain.com
```

## 性能优化

### PHP OPcache
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### SQLite 优化
```php
// 在 Database.php 中已配置
$this->db->busyTimeout(5000);
```

### 网络优化
```bash
# 增加 PHP-FPM 进程数
# /etc/php/8.1/fpm/pool.d/www.conf
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

## 监控

### 日志位置
- Apache: `/var/log/apache2/ipfsmaster_*.log`
- Nginx: `/var/log/nginx/ipfsmaster_*.log`
- PHP-FPM: `/var/log/php8.1-fpm.log`

### 健康检查脚本
```bash
#!/bin/bash
# check-ipfsmaster.sh

# 检查 Web 服务
if ! curl -s http://localhost/ > /dev/null; then
    echo "Web service down!"
    exit 1
fi

# 检查 IPFS RPC
if ! curl -s http://localhost:5001/api/v0/version > /dev/null; then
    echo "IPFS RPC down!"
    exit 1
fi

echo "All services OK"
exit 0
```

### 使用后台运行（可选）

#### Linux/Mac 使用 screen
```bash
# 安装 screen
sudo apt install screen  # Ubuntu/Debian
# 或
brew install screen      # macOS

# 启动 screen 会话
screen -S ipfsmaster

# 在 screen 中运行服务器
php -S 0.0.0.0:7789 -t public

# 按 Ctrl+A 然后按 D 退出 screen（服务器继续运行）

# 重新连接
screen -r ipfsmaster
```

#### Windows 使用 PowerShell
```powershell
# 使用启动脚本
.\start.ps1

# 自定义端口
.\start.ps1 8080

# 后台运行（隐藏窗口）
Start-Process powershell -ArgumentList "-File start.ps1" -WindowStyle Hidden

# 或创建服务（使用 NSSM）
nssm install IPFSMaster "C:\PHP\php.exe" "-S localhost:7789 -t C:\Path\To\IPFSMaster\public"
nssm start IPFSMaster
```

## 安全建议

### 1. 限制访问（防火墙）
```bash
# 仅允许本地访问
php -S 127.0.0.1:7789 -t public

# 或使用防火墙限制
sudo ufw allow from 192.168.1.0/24 to any port 7789
```

### 2. 环境变量配置
```bash
# 创建 .env 文件（可选）
echo "IPFS_RPC_URL=/ip4/127.0.0.1/tcp/5001" > .env
echo "IPFS_GATEWAY=http://127.0.0.1:8080" >> .env

# 设置文件权限
chmod 600 .env
```

## 性能优化

### PHP 配置调整
```ini
; php.ini 建议配置
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 100M
post_max_size = 100M
```

### 启用 OPcache
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

## 备份策略

### 数据库备份
```bash
#!/bin/bash
# backup-db.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=/var/backups/ipfsmaster
mkdir -p $BACKUP_DIR

# 备份数据库
cp /path/to/IPFSMaster/database/ipfs_master.db \
   $BACKUP_DIR/ipfs_master_$DATE.db

# 保留最近 7 天的备份
find $BACKUP_DIR -name "ipfs_master_*.db" -mtime +7 -delete

echo "Backup completed: ipfs_master_$DATE.db"
```

### 定时备份
```bash
# crontab -e
0 2 * * * /usr/local/bin/backup-db.sh
```

## 故障排查

### 常见问题

#### 1. 无法连接 IPFS RPC
```bash
# 检查 IPFS 是否运行
ipfs id

# 检查 IPFS API 可访问性
curl http://127.0.0.1:5001/api/v0/version

# 在应用设置中配置正确的 RPC 地址
```

#### 2. 数据库权限错误
```bash
# 修复权限
chmod 755 database
chmod 644 database/ipfs_master.db
```

#### 3. PHP 扩展缺失
```bash
# 检查扩展
php -m | grep -E 'sqlite3|curl|json'

# Ubuntu/Debian 安装
sudo apt install php-sqlite3 php-curl

# macOS 安装
brew install php
```

#### 4. 端口被占用
```bash
# 检查端口占用
netstat -an | grep 7789
# 或
lsof -i :7789

# 使用其他端口
php -S localhost:8080 -t public
```

## 升级流程

```bash
# 1. 停止服务器（Ctrl+C）

# 2. 备份数据库
cp database/ipfs_master.db database/ipfs_master.db.backup

# 3. 拉取最新代码
git pull origin main

# 4. 运行测试（可选）
composer install
vendor/bin/phpunit

# 5. 重启服务器
php -S localhost:7789 -t public
```

## 监控和日志

### 查看 PHP 错误日志
```bash
# 查看实时日志
tail -f /var/log/php_errors.log

# 或在项目目录创建日志
php -S localhost:7789 -t public 2>&1 | tee logs/server.log
```

### 监控服务器状态
```bash
# 检查服务器是否运行
curl http://localhost:7789 -I

# 监控资源使用
top -p $(pgrep -f "php -S")
```

## 支持

遇到问题？请访问：
- GitHub Issues: https://github.com/linkerlin/IPFSMaster/issues
- 文档: README.md
- 测试: tests/README.md
