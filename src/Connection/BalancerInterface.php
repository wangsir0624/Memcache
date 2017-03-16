<?php
namespace Wangjian\Memcache\Connection;

interface BalancerInterface {
    public function addConnection(ConnectionInterface $connection);

    public function hashConnection($key);

    public function close();
}