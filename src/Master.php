<?php
/**
 * Created by PhpStorm, Author: 刘迎春.
 * User: Breeze
 * Date: 2020/2/21
 * Time: 11:50 上午
 */

namespace Breeze\ProcessControl;

use Closure;

class Master
{

    protected $maxWorkers;

    protected $ppid; // 父进程id

    protected $pgid; // 进程组id

    protected $pid; // 进程id

    protected $workers = [];

    protected $queue;

    protected $isSupportMulti;


    public function __construct($maxWorkers = 5)
    {
        set_time_limit(0);
        $this->maxWorkers = $maxWorkers;

        if($this->isSupportMulti())
        {
            $this->pid = posix_getpid(); // 多进程时使用posix增加子进程获取的准确性
            $this->pgid = posix_getpgid($this->pid);
            $this->ppid = posix_getppid();

            // 多进程时才开启消息队列
            if($maxWorkers >= 2)
            {
                $this->makeQueue($this->pgid);
            }

        }else{
            $this->ppid = 0; // 无
            $this->pid = getmypid(); // 单进程时使用默认的方法
            $this->pgid = 0; // 无
        }
    }


    public function worker(int $numWorkers, Closure $closure)
    {
        $workers = [];

        for($i = 0; $i < $numWorkers; $i++)
        {
            $workers[$i]['closure'] = $closure;
            $workers[$i]['status'] = 0;
            $workers[$i]['message'] = null;
        }

        $this->workers = array_merge($this->workers, $workers);
        return $this;
    }


    public function addWorker(Closure $closure)
    {

        $this->workers[] = [
            'closure' => $closure,
            'status' => 0,
            'message' => null
        ];
        return $this;
    }


    public function start()
    {
        $max = $this->maxWorkers;
        $workers = $this->workers;
        $execute = 1;


        // 最大为1时，或系统不支持扩展时采用主进程阻塞运行，提高兼容性
        if($max == 1 || $this->isNotSupportMulti())
        {
            foreach ($workers as $key => $worker)
            {
                $worker = $worker['closure'];
                $message = $worker($key, $this->pid, $this->ppid);
                $this->workers[$key]['message'] = $message;
                $this->workers[$key]['status'] = 1;
            }

        }else{

            foreach ($workers as $key => $worker) {

                $this->fork(function($pid, $cpid) use ($key, &$execute, $max){
                    $execute++;
                    if ($execute > $max){
                        $pid = pcntl_waitpid(0, $status);
                        if($pid != -1) {
                            $code = pcntl_wexitstatus($status);
                            $this->workers[$code]['status'] = 1;
                        }
                        $execute--;
                    }
                }, function($pid, $ppid) use ($key, $worker){
                    $worker = $worker['closure'];
                    $message = $worker($key, $pid, $ppid);
                    $data = json_encode(['key' => $key, 'message' => $message]);
                    //将一条消息加入消息队列
                    msg_send($this->queue, 1, $data);
                    exit($key);

                });
            }

        }


        if($max >= 2 && $this->isSupportMulti())
        {

            do {
                $pid = pcntl_waitpid(0, $status);
                if(pcntl_wifexited($status))
                {
                    $code = pcntl_wexitstatus($status);
                    $this->workers[$code]['status'] = 1;
                }

            } while ($pid != -1);

            $this->massage();
        }
    }


    protected function massage()
    {
        $queue = $this->queue;

        $c = count($this->workers);

        for($i = 0; $i < $c; $i++)
        {
            msg_receive($queue, 1, $message_type, 1024, $data, true);
            $array = json_decode($data, true);
            $this->workers[$array['key']]['message'] = $array['message'];
            unset($data);
        }

        $this->deleteQueue();
    }


    protected function fork(Closure $parent, Closure $child)
    {

        $pid = pcntl_fork();


        if($pid == -1)
        {
            die('进程开启失败');

        }

        if($pid == 0)
        {
            $child(posix_getpid(), posix_getppid());
            exit;
        }

        if($pid > 0)
        {
            $parent(posix_getpid(), $pid);
        }

    }


    public function result()
    {
        return $this->workers;
    }


    protected function makeQueue($pgid)
    {

        //产生一个消息队列
        $msg_queue = msg_get_queue($pgid, 0666);
        $this->queue = $msg_queue;

    }


    protected function deleteQueue()
    {
        msg_remove_queue($this->queue);
    }

    public function isNotSupportMulti()
    {
        return !$this->isSupportMulti();
    }

    public function isSupportMulti()
    {
        if($this->isSupportMulti === null)
        {
            $pcntlStatus = in_array('pcntl', get_loaded_extensions());
            $posixStatus = in_array('posix', get_loaded_extensions());

            if($pcntlStatus && $posixStatus)
            {
                $this->isSupportMulti = true;
            }else{
                $this->isSupportMulti = false;
            }
        }
        return $this->isSupportMulti;
    }

}
