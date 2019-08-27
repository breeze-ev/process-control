<?php
/**
 * Created by PhpStorm, Author: 刘迎春.
 * User: Breeze
 * Date: 2019-08-26
 * Time: 13:40
 */

namespace Breeze\ProcessControl;


class Process
{

    protected $runable;
    protected $queue;
    protected $listener;
    const MessageFlag = 'breeze\process_control';

    public function __construct(Runable $runable)
    {
        $key = ftok( __FILE__, 'a');
        // 然后使用msg_get_queue创建一个消息队列
        $queue = msg_get_queue($key);
        $this->queue = $queue;

        $this->runable = $runable;
    }

    public function listener(MessageListener $listener)
    {
        $this->listener = $listener;
        return $this;
    }

    public function start()
    {

        $pid = pcntl_fork(); // 创建子进程

        if($pid == -1) {
            die('fork error');
        }
        elseif ($pid === 0) {

            // 子进程处理逻辑
            try{

                $message = $this->runable->run();
                if($message !== null){
                    $this->sendMessage($message);
                }else{
                    $this->sendMessage(self::MessageFlag);
                }

            }catch (\Exception $exception){
                //自杀自己的进程

            } finally{

                exit;
            }

        }

        // 父进程处理逻辑

        // 父进程
        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
        }

        if($this->listener instanceof MessageListener){
            $this->handleMessage($this->listener);
        }
    }



    protected function sendMessage($message)
    {
        msg_send($this->queue, 1, $message);
    }

    protected function handleMessage(MessageListener $listener)
    {
        msg_receive($this->queue, 0, $type, 1024, $s);
        if($s !== self::MessageFlag){
            $listener->onReceived($s);
        }

        msg_remove_queue($this->queue);
    }

}