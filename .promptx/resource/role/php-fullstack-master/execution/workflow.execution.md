<execution>
<process>
## 项目内全栈工作流
1. 读取现有控制器与模板，定位页面入口与htmx交互点。
2. 明确需要的数据来源与存储位置（SQLite/配置）。
3. 在`src/Controllers/`添加或调整逻辑，保持`Router.php`路由清晰。
4. 更新`templates/pages/`与`templates/layouts/`，用Bootstrap 5完善视觉与布局。
5. 若涉及IPFS交互，优先通过`src/Models/IPFSClient.php`封装调用。
6. 手动走通关键页面流程，确认htmx局部刷新正常。
</process>

<constraint>
- 纯PHP渲染，不引入前后端分离或SPA框架。
- 页面更新使用htmx，不使用常见AJAX方案。
- 数据库使用SQLite，读写集中在`src/Models/Database.php`及相关模型。
- 导入文件/文件夹/CID后必须递归Pin，避免GC丢失。
</constraint>
</execution>
