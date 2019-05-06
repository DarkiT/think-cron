<?php
namespace zishuo\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Cache;
use think\Process;


class Schedule extends Command
{
    protected $daemon = false;
    protected $memory = 128;
    protected function configure()
    {
        $this->setName('xcron:schedule')
            ->addArgument('action', Argument::OPTIONAL, "Run command",false)
            ->addOption('memory', null, Option::VALUE_OPTIONAL, 'The memory limit in megabytes', 128)
            ->addOption('daemon', null, Option::VALUE_NONE, 'Run the worker in daemon mode')
            ->setDescription('Daemon running crontab tasks');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');
        $this->memory = $input->getOption('memory');
        
        if ($input->getOption('daemon')) {
            $this->daemon = true;
        }
        
        $this->output = $output;
        
        switch ($action)
        {
            case "start":
                $this->runProcess($this->memory);
                break;
            case "stop":
                $this->stopProcess();
                break;
            case "state":
                $this->checkRunProcess();
                break;
            case "reset":
                $this->resetProcess();
                break;
            default:
                $this->printHelpMessage();
        }
    }
    
    /**
     * @param \Think\Process $process
     * @param  int           $memory
     */
    protected function runProcess()
    {
        if($this->daemon){
            $this->output->writeln("<info>Crontab is started successfully</info>");
            $process = $this->makeProcess($this->getName(),'start',$this->memory,"&");
            $process->start();
            if($process->isRunning()){
                return true;
            }
            return false;
        }else{
            if ($pid=$this->getCronStatus()) {
                $this->output->info("Crontab daemon {$pid} created successfully!");
                $this->stop();
            }
            $this->output->writeln("<info>Crontab is started successfully</info>");
            $process = $this->makeProcess();
            while (true) {
                $process->start();
                if ($this->memoryExceeded($this->memory)) {
                    $this->stop();
                }
                if($process->isRunning()){
                    Cache::set($this->getName(), $this->checkProcess(),60);
                }
                $process->wait();
                sleep(60);
            }
        }
    }
    
    protected function checkRunProcess()
    {
        if (($pid = $this->checkProcess()) > 0) {
            $this->output->info("Crontab daemon {$pid} is runing.");
        } else {
            $this->output->info('The crontab daemon is not running.');
        }
    }
    
    protected function stopProcess()
    {
        if (($pid = $this->checkProcess()) > 0) {
            $this->closeProcess($pid);
            $this->output->info("Crontab daemon {$pid} closed successfully.");
        } else {
            $this->output->info('The crontab daemon is not running.');
        }
    }
    
    protected function resetProcess()
    {
        if (($pid = $this->checkProcess()) > 0) {
            $this->closeProcess($pid);
            $this->output->info("Crontab daemon {$pid} closed successfully!");
        }
        $this->daemon = true;
        $this->runProcess();
        if ($pid = $this->checkProcess()) {
            $this->output->info("Crontab daemon {$pid} created successfully!");
        } else {
            $this->output->error('Crontab daemon creation failed, try again later!');
        }
    }
    /**
     * @param string $task
     * @param string $connector
     * @param int    $memory
     * @param int    $timeout
     * @return Process
     */
    protected function makeProcess($task='xcron:run',$connector=null, $memory = 128,$daemon=null)
    {
        $command = array_filter([
            PHP_BINARY,
            'think',
            $task,
            $connector,
            "--memory={$memory}",
            $daemon,
        ], function ($value) {
            return !is_null($value);
        });
        return new Process(implode($command," "),env('ROOT_PATH'), null, null,null);
    }
    
    /**
     * 获取进程的PID
     *
     * @return int|null
     */
    protected function getCronStatus()
    {
        return Cache::get($this->getName());
    }

    /**
     * 检查内存是否超出
     * @param  int $memoryLimit
     * @return bool
     */
    protected function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * 停止执行任务的守护进程.
     * @return void
     */
    protected function stop()
    {
        die;
    }
    
    /**
     * 检查进程是否存在
     * @return boolean|integer
     */
    protected function checkProcess()
    {
        if ($this->isWin()) {
            $command = 'wmic process where name="php.exe" get processid,CommandLine';
            $process = new Process($command);
            $process->run();
            if (!$process->isSuccessful()) {
                return false;
            }
            foreach (explode("\n", trim($process->getOutput())) as $line) if (stripos($line, $this->getName()." start") !== false) {
                list(, , , $pid) = explode(' ', preg_replace('|\s+|', ' ', $line));
                if ($pid > 0) return $pid;
            }
        } else {
            $command = "ps aux|grep -v grep|grep \"xcron:schedule start\"| awk '{print $2}' |xargs";
            $process = new Process($command);
            $process->run();
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
            return false;
        }
    }

    /**
     * 关闭任务进程
     * @param integer $pid 进程号
     * @return boolean
     */
    protected function closeProcess($pid)
    {
        if ($this->isWin()) {
            $command = "wmic process {$pid} call terminate";
        } else {
            $command = "kill -9 {$pid}";
        }
        $process = new Process($command);
        $process->run();
        if ($process->isSuccessful()) {
            Cache::rm($this->getName());
            return true;
        }
        return false;
    }

    /**
     * 判断系统类型
     * @return boolean
     */
    protected function isWin()
    {
        // '\\' === DIRECTORY_SEPARATOR
        return PATH_SEPARATOR === ';';
    }
    
    protected function printHelpMessage()
    {
        $msg = "<highlight>* Usage: php think {$this->getName()} {start|stop|reset|state} [option]</highlight>";
        $this->output->writeln($msg);
    }
}