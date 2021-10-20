<?php
define('VENDOR_PATH', __DIR__ . '/../vendor/autoload.php');

require_once VENDOR_PATH;

ini_set('date.timezone', 'Asia/Shanghai');


$config = [
    'service_path'  => "Test\\Service",
    'process_count' => 10,
    'account'       => [
        'user1' => 'passwd1'
    ]
];


(new \Rpc\RpcServer())->setConfig($config)->createWorker()->run();