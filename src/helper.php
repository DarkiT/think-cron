<?php
use zishuo\cron\command\Run;
use zishuo\cron\command\Schedule;
use zishuo\cron\command\MySql;

\think\Console::addDefaultCommands([
    Run::class,
    Schedule::class,
    MySql::class,
]);
if (!function_exists('add_xcron')) {

    /**
     * 添加到计划任务
     * @param string $title
     * @param string $task
     * @param array $data
     * @param string $exptime
     * @return bool
     */
    function add_xcron($title, $task, $data = [], $exptime=null)
    {
        return (new MySql)->add($title, $task, $data, $exptime);
    }
}