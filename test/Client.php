<?php
define('VENDOR_PATH', __DIR__ . '/../vendor/autoload.php');

require_once VENDOR_PATH;

ini_set('date.timezone', 'Asia/Shanghai');

$addressArray = [
    'tcp://127.0.0.1:9501@user1:passwd1',
    'tcp://127.0.0.1:9501@user1:passwd2'
];

$TestClient = \Rpc\RpcClient::instance('test', 'TestService');
$TestClient = $TestClient->address($addressArray);
$retSync    = $TestClient->test('test_1');


$retAsync = $TestClient->asend_test('test_2');
$retAsync = $TestClient->arecv_test('test_2');


var_dump($retSync);
var_dump($retAsync);


$userClient = \Rpc\RpcClient::instance('test', 'UserService');
$userClient = $userClient->address($addressArray);
$retSync    = $userClient->test('test_1');

var_dump($retSync);