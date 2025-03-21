<?php

namespace Jisheng100\Snowflake;

use Webman\App;
use Webman\Config;

class Snowflake
{
    private $machineId;        // 机器 ID，用于标识当前服务节点
    private $processId;        // 进程 ID，用于标识当前节点的进程
    private $sequence = 0;     // 序列号，用于区分同一毫秒内生成的多个 ID
    private $lastTimestamp = -1; // 上一次生成 ID 的时间戳（毫秒）

    // 起始时间戳，作为 ID 的时间部分的基准时间（2023-01-01 00:00:00 UTC）
    private const EPOCH = 1672531200000;

    // 最大值计算
    private $maxMachineId;    // 最大机器 ID 值，由 machine_id_bits 决定
    private $maxProcessId;    // 最大进程 ID 值，由 process_id_bits 决定
    private $maxSequence;     // 最大序列号值，由 sequence_bits 决定

    // 位移计算
    private $processIdShift;  // 序列号位数左移，用于进程 ID
    private $machineIdShift;  // 进程 ID 和序列号位数左移，用于机器 ID
    private $timestampShift;  // 机器 ID 和进程 ID 位数左移，用于时间戳

    protected static $instance = null; // 单例模式实例

    /**
     * 获取单例实例
     *
     * @return Snowflake
     */
    public static function instance()
    {
        if (self::$instance === null) {
            // 从配置中读取 machine_id，默认为 0
            $machineId = (int)Config::get('plugin.jisheng100.snowflake.app.machine_id');
            // 获取当前 Worker 进程 ID，默认为 0
            $processId = App::worker() ? (App::worker()->id ?: 0) : 0;

            // 创建 Snowflake 实例
            self::$instance = new self($machineId, $processId);
        }
        return self::$instance;
    }

    /**
     * 构造函数，初始化 Snowflake 配置和参数
     *
     * @param int $machineId 机器 ID
     * @param int $processId 进程 ID
     * @throws \InvalidArgumentException
     */
    private function __construct(int $machineId, int $processId)
    {
        // 从配置中加载位数配置
        $config = Config::get('plugin.jisheng100.snowflake.app', [
            'machine_id_bits' => 3,  // 机器 ID 占用位数
            'process_id_bits' => 7,  // 进程 ID 占用位数
            'sequence_bits' => 12,  // 序列号占用位数
        ]);

        // 判断位数总和是否超过 22 位
        $totalBits = $config['machine_id_bits'] + $config['process_id_bits'] + $config['sequence_bits'];
        if ($totalBits > 22) {
            throw new \InvalidArgumentException("Invalid configuration: total bits (machine_id_bits + process_id_bits + sequence_bits) cannot exceed 22. Current total: $totalBits.");
        }

        // 计算最大值
        $this->maxMachineId = (1 << $config['machine_id_bits']) - 1;
        $this->maxProcessId = (1 << $config['process_id_bits']) - 1;
        $this->maxSequence = (1 << $config['sequence_bits']) - 1;

        // 计算位移值
        $this->processIdShift = $config['sequence_bits'];
        $this->machineIdShift = $config['sequence_bits'] + $config['process_id_bits'];
        $this->timestampShift = $this->machineIdShift + $config['machine_id_bits'];

        // 验证 machineId 和 processId 是否在有效范围内
        if ($machineId < 0 || $machineId > $this->maxMachineId) {
            throw new \InvalidArgumentException("Machine ID must be between 0 and " . $this->maxMachineId);
        }
        if ($processId < 0 || $processId > $this->maxProcessId) {
            throw new \InvalidArgumentException("Process ID must be between 0 and " . $this->maxProcessId);
        }

        $this->machineId = $machineId;
        $this->processId = $processId;
    }

    /**
     * 生成唯一 ID
     *
     * @return int
     * @throws \RuntimeException
     */
    public function generateId(): int
    {
        $timestamp = $this->getCurrentTimestamp();

        // 检查时钟是否倒退
        if ($timestamp < $this->lastTimestamp) {
            throw new \RuntimeException("Clock moved backwards. Refusing to generate ID.");
        }

        if ($timestamp === $this->lastTimestamp) {
            // 同一毫秒内，递增序列号
            $this->sequence = ($this->sequence + 1) & $this->maxSequence;
            if ($this->sequence === 0) {
                // 如果序列号溢出，等待下一毫秒
                $timestamp = $this->waitUntilNextMillis($this->lastTimestamp);
            }
        } else {
            // 不同毫秒，重置序列号
            $this->sequence = 0;
        }

        $this->lastTimestamp = $timestamp;

        // 按位组合生成 ID
        return (($timestamp - self::EPOCH) << $this->timestampShift) |
            ($this->machineId << $this->machineIdShift) |
            ($this->processId << $this->processIdShift) |
            $this->sequence;
    }

    /**
     * 获取当前时间戳（毫秒）
     *
     * @return int
     */
    private function getCurrentTimestamp(): int
    {
        return (int)(microtime(true) * 1000);
    }

    /**
     * 等待直到下一毫秒
     *
     * @param int $lastTimestamp 上一次的时间戳
     * @return int
     */
    private function waitUntilNextMillis(int $lastTimestamp): int
    {
        $timestamp = $this->getCurrentTimestamp();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->getCurrentTimestamp();
        }
        return $timestamp;
    }

    /**
     * 解析雪花 ID
     *
     * @param int $id 雪花 ID
     * @return array 包含时间戳、机器 ID、进程 ID 和序列号的数组
     */
    public function parseId(int $id): array
    {
        $timestamp = ($id >> $this->timestampShift) + self::EPOCH;
        $machineId = ($id >> $this->machineIdShift) & $this->maxMachineId;
        $processId = ($id >> $this->processIdShift) & $this->maxProcessId;
        $sequence = $id & $this->maxSequence;

        return compact('timestamp', 'machineId', 'processId', 'sequence');
    }
}
