# ProcessControl

## 安装方法




此包为PHP多进程编程包，风格类似Java多线程，主进程开启子进程，子进程可向主进程通信(基于msg_queue)

**需要 posix 和 pcntl扩展**


```
composer require breeze-ev/process-control
```


## 实例

* 单开子线程

```PHP
(new Process(new class implements Runable {

    public function run()
    {
    
       // 子线程中运行
       echo  'hello world';
    }

}))->start();

```


* 多开子线程

```PHP
for($i = 0; $i < 8; $i++)
{

    // 主线程向子线程传递数据

    (new Process(new class($i) implements Runable {

        protected $i;

        public function __construct($i)
        {
            $this->i = $i;
        }

        public function run()
        {
            // 子线程中运行
            echo $this->i;
        }

    }))->start();

}
```

* 子线程向主线程发送数据


```PHP
(new Process(new class implements Runable {

    public function run()
    {
        // 子线程中运行
        return 'hello world';
    }

}))->listener(new class implements MessageListener {

    public function onReceived($message)
    {
        // 主线程中运行
        echo $message;
    }

})->start();
```

