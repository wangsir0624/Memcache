<?php
use Wangjian\Memcache\MyMemcache;

include __DIR__.'/../../../autoload.php';

$memcache = new MyMemcache();
$memcache->connect('127.0.0.1', 11211);
$memcache->set('test1', 12);
$memcache->set('test2', 12);
$memcache->set('test3', 12);
$result = $memcache->getVersion();
var_dump($result);
