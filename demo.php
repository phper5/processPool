<?php

use SoftDD\ProcessPool\ProcessPool;


$taskList = [2, 5, 8, 4, 1, 1, 3];
$processNum = 20;
$params = ['param1' => 1, 'param2' => 2];
$process = new ProcessPool($taskList, $processNum, function ($task, $params) {
    var_dump($task);
    var_dump($params);
}, $params, 1);

