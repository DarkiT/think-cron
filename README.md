# think-cron 计划任务
## 项目目标：
项目中往往因为有非常多的计划任务而变的难以维护。且每次都需要麻烦运维，也不是一件很好的事情。看了很多大神写的计划任务工具，都不太符合自己的需求。因此萌发了“使用单一入口管理项目中的计划任务”的想法,抽空看了下thinkphp相关扩展的实现方法，依葫芦画瓢实现了本扩展。本扩展支持配置文件和mysql方式管理任务列表。

## 安装方法
```
composer require zishuo/think-cron
```

## 使用方法

### 创建任务类

```
<?php

namespace app\task;

use zishuo\cron\Task;

class DemoTask extends Task
{

    public function configure()
    {
        $this->exptime = "*/1 * * * *"; 
        //设置任务的周期，每分钟执行一次，使用标准的Crontab语法,当配置文件中设置了执行周期将优先配置文件中的设置
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        //...具体的任务执行
        $time = date('Y-m-d H:i:s');
        $this->statusDesc = $time;
        return true;
    }
}

```

### 配置
配置文件位于 application/extra/xcron.php


```
return [
    'type'  =>    'file',       //支持 file,mysql驱动
    'table' =>    'think_cron', //驱动类型为mysql时存储任务所用的表
    'tasks'   => [             //为文件存储时定时任务列表格式
        'demo'  =>[
            'title'     =>  '测试',
            'task'      =>  \app\task\DemoTask::class,
            'data'      =>  [],
            'exptime'       =>  '* * * * *'
        ]
    ]
];
```
使用mysql存储定时任务时，请将 type 设置为 mysql，然后控制台执行 php think xcron:install 初始化数据库，或者手动创建如下数据表
```
CREATE TABLE `think_cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '任务状态',
  `count` int(11) NOT NULL DEFAULT '0' COMMENT '执行次数',
  `title` char(50) DEFAULT NULL COMMENT '任务名称',
  `exptime` char(200) NOT NULL DEFAULT '* * * * *' COMMENT '任务周期',
  `task` varchar(500) DEFAULT NULL COMMENT '任务命令',
  `data` longtext COMMENT '附加参数',
  `status_desc` varchar(1000) DEFAULT NULL COMMENT '上次执行结果',
  `last_time` datetime DEFAULT NULL COMMENT '最后执行时间',
  `next_time` datetime DEFAULT NULL COMMENT '下次执行时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
### 创建计划任务
```
/**
 * 添加到计划任务
 * @param string $title 任务名
 * @param string $task  类名
 * @param array $data   参数 数组格式
 * @param string $exptime 任务执行周期,使用标准的Crontab语法,默认60秒
 * @return bool
 */
 add_xcron($title, $task, $data = [], $exptime=null);
```
### 创建计划任务例子
```
$data = ['name' => 'thinkphp'];
add_xcron('test', \app\task\DemoTask::class, $data, '*/5 * * * *');
```

### 任务监听

#### 两种方法：

> 方法一 

##### 起一个常驻进程，或者配合supervisor使用 (推荐)
~~~
php think xcron:schedule start --daemon
~~~

##### 创建 supervisor 
```
[program:php]
command= /usr/bin/php think xcron:schedule start; 被监控进程
directory=/home/wwwroot/shabi.in
process_name=%(program_name)s
numprocs=1 ;启动几个进程 别改 扩展限制了一个进程运行
autostart=true ;随着supervisord的启动而启动
autorestart=true ;自动启动
startsecs=1 ;程序重启时候停留在runing状态的秒数
startretries=10 ;启动失败时的最多重试次数
redirect_stderr=true ;重定向stderr到stdout
stdout_logfile=/root/supervisor.log ;stdout文件
```

> 方法二

#### 在系统的计划任务里添加
~~~
* * * * * php /path/to/think xcron:run >> /dev/null 2>&1
~~~

## 特别鸣谢
- 使用了[yunwuxin/think-cron](https://packagist.org/packages/yunwuxin/think-cron/ "创建自定义指令")项目中的部分代码

## 写在最后
- 代码中有很多不成熟的地方，期待您的issue。最好能fork，将您的想法贡献出来。让这个项目更适应更多的场景。
- 邮箱：i@shabi.in