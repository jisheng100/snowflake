##简介
### Webman 雪花 ID 生成类

使用简单、高效的雪花 ID 算法生成唯一的 64 位整数 ID，支持高并发场景，适合分布式系统中唯一标识的生成需求。

##安装
```
composer require jisheng100/snowflake
```

##使用
### 1. 生成雪花id

```
$id =  \Jisheng100\Snowflake\Snowflake::instance()->generateId();
```
### 2. 解析雪花id

```
$detail = \Jisheng100\Snowflake\Snowflake::instance()->parseId($id);
```
##配置

雪花 ID 算法基于 64 位整数设计，其中：

- 符号位：1 位，始终为 0，保证生成的 ID 为正数。
- 时间戳：41 位，表示毫秒级时间戳，足够支持约 69 年的时间跨度。
- 机器 ID + 进程 ID：共 10 位，用于标识唯一的机器和进程。
- 序列号：12 位，用于在同一毫秒内生成多个唯一 ID，支持每毫秒最多生成 4096 个 ID。

### 配置参数说明
可以根据业务需求自定义以下参数，确保在高并发场景下生成唯一 ID：
```
return [
    'enable' => true,
    'machine_id' => 0,//机器id序号，多台机器需要更改这里
    'machine_id_bits' => 3, //机器id位数，值范围为0-7(2的3次方)
    'process_id_bits' => 7,//进程id位数，值范围为0-127(2的7次方)
    'sequence_bits' => 12, //序列号位数，每微秒可生成的数量4096
];
```

## 注意事项
- **机器 ID 配置**：多台机器需确保 machine_id 值唯一。
- **进程 ID 位数**：需大于等于实际业务所需的最大进程数。例如，如果业务使用了 64 个进程，则 process_id_bits 至少为 6 位。
- **序列号位数**：默认支持每毫秒生成 4096 个 ID，如需更高并发，可调整配置。