<?php
namespace SoftDD\ProcessPool;

class ProcessPool
{
    protected $process;
    protected $workerList = [];
    protected $resultData = [];
    protected $taskList = [];
    protected $processNum ;
    protected $params ;
    protected $debug = false;
    protected  $processCallback;
    protected  $collectionCallback;
    public function __construct($taskList,$processNum,$processCallback,$params=[],$debug=false)
    {
        $this->processNum = $processNum;
        $this->params = $params;
        $this->taskList =$taskList;
        $this->debug =$debug;
        $this->processCallback =$processCallback;
        $this->process = new \Swoole\Process(array($this, 'run'), false, 2);
        $this->process->start();
//        $this->process->wait();
       // \swoole_process::wait();
        \Swoole\Process::wait();

        \Swoole\Process::signal(SIGCHLD, function($sig) {
            //必须为false，非阻塞模式
            while($ret =  \Swoole\Process::wait(false)) {
                echo "PID={$ret['pid']}\n";
            }
        });

        $this->log("全部完成\n");
    }
    public function getData()
    {
        return $this->resultData;
    }
    public function log($data)
    {
        if ($this->debug){
            if (is_string($data))
            {
                echo($data);
            }
            else{
                print_r($data);
            }
        }
    }
    public function run(){
        $params = $this->params;
        for ($i=0;$i<$this->processNum;$i++){
            echo 'hi';
            $process = new  \Swoole\Process(function ($worker)use($params){
                $this->log($worker->pid."开始运行");
                \Swoole\Event::add($worker->pipe, function($pipe)use($worker,$params){
                    $task = $worker->read();
                    $this->log($worker->pid . ' getData: ' . $task." at ".time()."\n");
                    if($task == 'exit'){
                        $worker->exit();
                    }
                    if (is_callable($this->processCallback)){
                        try {
                            $data = call_user_func_array($this->processCallback,[$task,$params]);
                        }catch (\Throwable $e){
                            $this->log($e);
                        }

                    }else{
                        echo '进程回调不可用 sleep 2';
                        $data=[];
                        var_dump($this->processCallback);
                        sleep(2);
                    }
                    //告诉主进程处理完成
                    //在子进程内调用write，父进程可以调用read接收此数据
                    $this->log($worker->pid."done at ".time());
                    $worker->write(json_encode($data));
                });
            }, false, 2);
            $pid = $process->start();
            $this->workerList[$pid] = $process;
        }
        //全部初始化完毕后，进行绑定
        foreach ($this->workerList as $pid =>$process){
            $params = $this->params;
            \Swoole\Event::add($process->pipe, function ($pipe) use ($process,$params){
                $data = $process->read();
                $this->log('get from '.$process->pid . ' '.$data);
//                $this->resultData[] = json_decode($data,true);
                $d = array_shift($this->taskList);
                if ($d) {
                    $this->log ("任务 $d 写入 ".$process->pid);
                    $process->write($d);
                }else{
                    $this->log ("任务处理完毕 关闭".$process->pid."\n");
                    $process->write("exit");
                    //$process->exit();//$process->pid;
                    unset($this->workerList[$process->pid]);
                    if (count($this->workerList) == 0){
                        $this->log( '可以退出了');
                        $this->process->exit();
                    }
                }
            });
        }
        //下发数据

        foreach ($this->workerList as $pid => $process){
            if ($d = array_shift($this->taskList)){
                $this->log ($d.'==>'.$process->pid."\n");
                $process->write($d);
            }else{
                $this->log ("\n任务结束 ".$pid."\n");
                unset($this->workerList[$pid]);
                $process->write('exit');
                //$process->exit();
            }
        }
    }
}
