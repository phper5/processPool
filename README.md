# softdd/processpool
基于swoole的封装。
## Installation
```bash
composer require softdd/processpool
```
## Usage
同时维护了任务池和进程池。
### 参数列表

- $taskList 所有的任务列表
- $processNum 进程数量
- $processCallback  子进程的业务逻辑
- $params 额外传递的参数
- $debug是否开启dbug 输出调试信息

会启用固定个数的进程，给任务池分配任务给进程，在进程处理完任务后，整理结果，再次分配任务。直至所有任务处理完毕。

```$taskList``` 所有的任务列表 为数组结构，每个子任务必须是字符串，请序列化或者encode。弹出任务用的```array_pop```。

$processNum 固定的进程数量，当进程数量大于任务数量时，会取任务数量。

在 $processCallback 中实现每个进程的处理逻辑， 
```php
    //$task 任务数据
    //$params 额外的参数
    call_user_func_array($this->processCallback,[$task,$params];
```
不提供返回值，请在```$processCallback```中更改数据，或者调用接口来记录结果。

对全局变量的数据请使用```$params```,如 任务运行环境，语言等，或者其他通用的数据。
```$params```中的数据会提供给```processCallback```

### demo
```php
$taskList = [2, 5, 8, 4, 1, 1, 3];
$processNum = 20;
$params = ['param1' => 1, 'param2' => 2];
$process = new ProcessPool($taskList, $processNum, function ($task, $params) {
    var_dump($task);
    var_dump($params);
}, $params, 1);
```

