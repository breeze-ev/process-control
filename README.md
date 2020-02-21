# ProcessControl


此包为PHP多进程编程包，主进程开启多子进程，子进程可向主进程通信(基于msg_queue)

## 安装方法



**需要 pcntl 和 posix 扩展**


```
composer require breeze-ev/process-control
```


## 实例

* 多开子进程 1

并发进程控制数为5， 共开10个进程处理业务

```PHP
$master = new Master(5);
$master->worker(10, function($num, $pid, $ppid){


    echo '第' . $num . '个子进程开启 pid:' . $pid . ' 父进程id:' . $ppid . PHP_EOL;

    sleep(rand(1,3));


    echo '第' . $num . '个子进程退出' . PHP_EOL;

})->start();

print_r($master->result());

```


* 多开子进程 2

```PHP
$master = new Master(5);

for ($i = 0; $i <= 10; $i++)
{
    $master->addWorker(function($num, $pid, $ppid) use ($i){

        echo '第' . $num . '个子进程开启 pid:' . $pid . ' 父进程id:' . $ppid . PHP_EOL;

        sleep(rand(1,3));


        echo '第' . $num . '个子进程退出' . PHP_EOL;

    });
}

$master->start();

$result = $master->result();

print_r($result);
```

* 子进程向主进程发送数据


```PHP
$master = new Master(5);

for ($i = 0; $i <= 10; $i++)
{
    $master->addWorker(function($num, $pid, $ppid) use ($i){

        echo '第' . $num . '个子进程开启 pid:' . $pid . ' 父进程id:' . $ppid . PHP_EOL;

        sleep(rand(1,3));


        echo '第' . $num . '个子进程退出' . PHP_EOL;

        return $i; // 数据返回给主进程
    });
}

$master->start();

$result = $master->result(); // 主进程数据可查看所有子进程返回的 message

print_r($result);
```

