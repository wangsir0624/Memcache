<?php
use Wangjian\Memcache\MyMemcache;
use Wangjian\Memcache\Connection\MoreBalancer;

include __DIR__.'/../../../autoload.php';

$memcache = new MyMemcache();
$memcache->addServer('127.0.0.1', 11211);
$memcache->addServer('127.0.0.1', 11212);
$memcache->addServer('127.0.0.1', 11213);

echo $memcache->get('fdafda');

