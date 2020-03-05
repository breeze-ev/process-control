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


    public function __construct($maxWorkers = 5)
    {
        set_time_limit(0);
        $this->maxWorkers = $maxWorkers;
        $this->pid = posix_getpid();
        $this->pgid = posix_getpgid($this->pid);
        $this->ppid = posix_getppid();

        $this->makeQueue($this->pgid);

    }


    public function worker(int $numWorkers, Closure $closure)
    {
        $workers = [];

        for($i = 0; $i < $numWorkers; $i++)
        {
            $workers[$i]['closure'] = $closure;
        }

        $this->workers = array_merge($this->workers, $workers);
        return $this;
    }


    public function addWorker(Closure $closure)
    {

        $this->workers[] = ['closure' => $closure];
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
            $this->deleteQueue();
            foreach ($workers as $key => $worker)
            {
                $worker = $worker['closure'];
                $message = $worker($key, $this->pid, $this->ppid);
                $this->workers[$key]['message'] = $message;
            }

        }else{

            foreach ($workers as $key => $worker) {

                $this->fork(function($pid, $cpid) use ($key, &$execute, $max){
                    $this->workers[$key]['status'] = 0;
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



        do {

            $pid = pcntl_waitpid(0, $status);
            if(pcntl_wifexited($status))
            {
                $code = pcntl_wexitstatus($status);
                $this->workers[$code]['status'] = 1;
            }

        } while ($pid != -1);


        if($max >= 2 && $this->isSupportMulti())
        {
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
        $pcntlStatus = in_array('pcntl', get_loaded_extensions());
        $posixStatus = in_array('posix', get_loaded_extensions());
        if($pcntlStatus && $posixStatus)
        {
            return true;
        }
        return false;
    }

}
