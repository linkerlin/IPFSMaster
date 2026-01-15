# IPFSMaster 测试套件

## 运行测试

### 安装依赖
```bash
composer install
```

### 运行所有测试
```bash
vendor/bin/phpunit
```

### 运行特定测试类
```bash
vendor/bin/phpunit tests/DatabaseTest.php
vendor/bin/phpunit tests/IPFSClientTest.php
```

### 生成覆盖率报告（需要 Xdebug）
```bash
vendor/bin/phpunit --coverage-html coverage
```

## 测试覆盖范围

### 单元测试（基础）
- **DatabaseTest.php**: 数据库设置读取、默认配置
- **IPFSClientTest.php**: 多地址转换、JSON-lines 解析、空目录处理
- **RouterTest.php**: 路由注册、路径匹配、参数提取
- **UploadControllerTest.php**: 控制器基类方法（POST 检测、htmx 检测、参数读取）
- **SettingsControllerTest.php**: 设置控制器基础功能
- **BrowseControllerTest.php**: 浏览控制器存在性
- **HomeControllerTest.php**: 首页控制器方法存在性
- **PinControllerTest.php**: Pin 列表读取

### 单元测试（进阶）
- **ControllerRenderTest.php**: 控制器渲染、JSON 响应、htmx 触发、参数获取
- **IPFSClientAdvancedTest.php**: RPC/网关 URL 获取、多地址转换变体、目录扫描、JSON 解析边界
- **RouterAdvancedTest.php**: 多参数路由、复杂路径匹配、查询字符串处理、控制器/回调处理

### 集成测试
- **DatabaseIntegrationTest.php**: Pin 插入/读取/删除、导入历史记录、设置更新
- **DatabaseEdgeCaseTest.php**: 重复插入、NULL 处理、批量操作、特殊字符、时间戳更新

### 边界测试
- **EdgeCaseTest.php**: IPFS 客户端配置、单例模式、路由规范化、URL 生成、空数据处理、嵌套目录
- **ValidationTest.php**: CID 格式验证、文件名清理、HTML 转义、URL 验证、整数验证、路径验证
- **ErrorHandlingTest.php**: 数据库错误、空结果、NULL 值、无效配置、JSON 解析错误

## 测试策略

### 隔离性、`edge_`、`error_` 前缀，便于清理
- setUp/tearDown 自动清理测试数据
- 反射机制测试私有/受保护方法

### 覆盖场景（19 个测试类）
1. **正常流程**: 标准操作流程（8 个基础单元测试）
2. **边界条件**: 空输入、大量数据、特殊字符（3 个边界测试）
3. **异常处理**: 缺失配置、网络错误、格式错误（ErrorHandlingTest）
4. **集成验证**: 多模块协作、数据库事务（2 个集成测试）
5. **输入验证**: CID、URL、路径、类型格式验证（ValidationTest）
6. **进阶功能**: 复杂路由、批量操作、性能基准（3 个进阶测试）
7. **安全测试**: XSS、SQL 注入、路径遍历（SecurityTest）
8. **性能测试**: 查询速度、解析效率、批量处理（PerformanceTest）式错误
4. **集成验证**: 多模块协作、数据库事务

## 扩展建议

### 需要 Mock 的场景
- IPFS RPC 调用（避免依赖真实节点）
- 文件上传测试（使用临时文件）
- 网络请求测试（Mock cURL）

### 未来增强
- 添加性能基准测试
- 增加并发安全性测试
- 添加 UI 集成测试（使用 Selenium/Panther）
- 测试 htmx 交互行为
