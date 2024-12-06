# snowflake
Webman的雪花id算法生成类


## 安装

- `composer require jisheng100/snowflake`


## 使用

  ~~~
  $id =  \Jisheng100\Snowflake\Snowflake::instance()->generateId();
  ~~~


## 配置

~~~
return [
    'enable' => true,
    'machine_id' => 0,//机器id序号，多台机器需要更改这里
    'machine_id_bits' => 3, //机器id位数，最大值为0-7(2的3次方)
    'process_id_bits' => 7,//进程id位数，最大值为0-127(2的7次方)
    'sequence_bits' => 12, //序列号位数，每微秒可生成的数量4096
];
~~~

其中 `machine_id` 为机器序号，部署多台服务器时，需要更改该配置，取值范围为0~machine_id_bits位的数字
machine_id_bits 为机器id位数
process_id_bits 为进程id位数
sequence_bits   为序列号位数

其中 machine_id_bits+process_id_bits+sequence_bits 总共为22位