<knowledge>
## Project: IPFS Master (IPFSMaster)
- **定位**：本地IPFS Kubo节点的管理Web App，纯PHP实现。
- **技术栈**：PHP 7.4+、SQLite、Bootstrap 5、htmx。
- **默认RPC**：/ip4/127.0.0.1/tcp/5001（也支持http://100.113.134.128:5001/）。
- **默认网关**：http://127.0.0.1:8080，失败自动尝试8081/8082，可配置为http://127.0.0.1:8088。
- **目录约定**：
  - 入口：`public/index.php`
  - 控制器：`src/Controllers/`
  - 路由：`src/Router.php`
  - 模型：`src/Models/`（含`Database.php`、`IPFSClient.php`）
  - 模板：`templates/layouts/`、`templates/pages/`
  - 数据库文件：`database/`（SQLite）
- **关键功能**：上传/导入CID后自动递归Pin并入库，避免GC回收。
</knowledge>
