<?php
//符号位：1 位
//时间戳：41 位
//机器 ID：3 位
//进程 ID：7 位
//序列号：12 位
//共计64为
return [
    'enable' => true,
    'machine_id' => 0,//机器id序号，多台机器需要更改这里
    'machine_id_bits' => 3, //机器id位数，最大值为0-7(2的3次方)
    'process_id_bits' => 7,//进程id位数，最大值为0-127(2的7次方)
    'sequence_bits' => 12, //序列号位数，每微秒可生成的数量4096
];