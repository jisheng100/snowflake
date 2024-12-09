<?php
/**
 *机器 ID 配置：多台机器需确保 machine_id 值唯一。
 *进程 ID 位数：需大于等于实际业务所需的最大进程数。例如，如果业务使用了 64 个进程，则 process_id_bits 至少为 6 位。
 *序列号位数：默认支持每毫秒生成 4096 个 ID，如需更高并发，可调整配置。
 */
return [
    'enable' => true,
    'machine_id' => 0,            // 机器 ID，多台机器需配置唯一值
    'machine_id_bits' => 3,       // 机器 ID 位数，范围 0-7（2^3=8）
    'process_id_bits' => 7,       // 进程 ID 位数，范围 0-127（2^7=128）
    'sequence_bits' => 12,        // 序列号位数，每毫秒最多生成 4096（2^12）个 ID
];