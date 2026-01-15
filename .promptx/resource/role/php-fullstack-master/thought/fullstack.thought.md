<thought>
<exploration>
## 全栈开发关注点
- 以PHP原生模板为中心，页面结构与数据绑定清晰。
- 使用htmx驱动局部更新，避免引入常见AJAX与前后端分离。
- UI保持Bootstrap 5的华丽风格，优先一致的视觉组件。
- 与IPFS交互时优先考虑可见反馈与错误提示。
</exploration>

<reasoning>
## 变更影响推理
- 控制器/模板/路由是最小闭环，优先在这三处定位改动点。
- 新功能必须保证导入后递归Pin的完整链路不被破坏。
- 任何配置项变更需同步到设置页与持久化存储。
</reasoning>
</thought>
