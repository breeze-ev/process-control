<?php
/**
 * Created by PhpStorm, Author: 刘迎春.
 * User: Breeze
 * Date: 2019-08-26
 * Time: 13:53
 */

use Breeze\ProcessControl\MessageListener;
use Breeze\ProcessControl\Process;
use Breeze\ProcessControl\Runable;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase implements MessageListener
{

    protected $array;

    public function testProcess()
    {

        (new Process(new class implements Runable {

            public function run($max, $current)
            {
                return 1;
            }

        }))->listener($this)->start();


        (new Process(new class implements Runable {

            public function run($max, $current)
            {
                return 2;
            }

        }))->listener($this)->start();


    }

    public function testMulti(){


        (new Process(new class implements Runable {

            public function run($max, $i)
            {
                return $i;
            }

        }))->listener($this)->start(3);


        print_r($this->array);

    }

    public function onReceived($message)
    {
        $this->array[] = $message;
    }
}