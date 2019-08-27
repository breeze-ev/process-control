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

            public function run()
            {
                return 1;
            }

        }))->listener($this)->start();


        (new Process(new class implements Runable {

            public function run()
            {
                return 2;
            }

        }))->listener($this)->start();

        print_r($this->array);

    }

    public function testMulti(){


        for($i = 0; $i < 8; $i++)
        {
            (new Process(new class($i) implements Runable {

                protected $i;

                public function __construct($i)
                {
                    $this->i = $i;
                }

                public function run()
                {
                    return $this->i;
                }

            }))->listener($this)->start();

        }


        print_r($this->array);

    }

    public function onReceived($message)
    {
        $this->array[] = $message;
    }
}