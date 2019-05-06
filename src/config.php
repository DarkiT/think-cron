<?php

return [
    'type'  =>      'file',         //支持 file,mysql驱动,当选择数据库来存储定时任务时，请先执行 php thin cron:install 初始化数据库
    'table' =>      'think_cron',   //驱动类型为mysql时存储任务所用的表
    'cache' =>      60,             //为数据库驱动时 查询缓存时间，为减数据库查询压力
    'tasks'   => [],             //定时任务列表
];