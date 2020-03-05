<?php
/**
 * Created by PhpStorm, Author: 刘迎春.
 * User: Breeze
 * Date: 2019-08-26
 * Time: 13:53
 */

use Breeze\ProcessControl\Master;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{

    protected $array;

    public function testProcess()
    {

        $master = new Master(5);
        $master->worker(10, function($num, $pid, $ppid){


            echo '第' . $num . '个子进程开启 pid:' . $pid . ' 父进程id:' . $ppid . PHP_EOL;

            sleep(rand(1,3));


            echo '第' . $num . '个子进程退出' . PHP_EOL;


            return $num;

        })->start();


        print_r($master->result());


        $this->assertIsArray($master->result());


    }

    public function testAdd()
    {
        $master = new Master(5);

        for ($i = 0; $i <= 10; $i++)
        {
            $master->addWorker(function($num, $pid, $ppid) use ($i){

                echo '第' . $num . '个子进程开启 pid:' . $pid . ' 父进程id:' . $ppid . PHP_EOL;

                sleep(rand(1,3));


                echo '第' . $num . '个子进程退出' . PHP_EOL;

                return $i;
            });
        }

        $master->start();

        $result = $master->result();

        print_r($result);

        $this->assertIsArray($result);


    }

    public function testSingleProcess()
    {

        $master = new Master(1);

        for ($i = 0; $i <= 1; $i++)
        {
            $master->addWorker(function($num, $pid, $ppid) use ($i){

                echo '第' . $num . '个子进程开启 pid:' . $pid . ' 父进程id:' . $ppid . PHP_EOL;
                sleep(10);
                echo '第' . $num . '个子进程退出' . PHP_EOL;
                return $i;
            });
        }

        $master->start();

        $result = $master->result();

        print_r($result);

        $this->assertIsArray($result);

    }

}