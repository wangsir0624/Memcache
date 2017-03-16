<?php
namespace Wangjian\Memcache\Connection;

use Wangjian\Memcache\Protocol\MemcacheProtocol;

class OneBalancer implements BalancerInterface {
    protected $connection;

    public function addConnection(ConnectionInterface $connection) {
        $this->connection = $connection;
    }

    public function hashConnection($key) {
        return $this->connection;
    }

    public function getStats() {
        $this->connection->send("stats\r\n");

        $respond = $this->connection->handleMessage();

        if($respond == MemcacheProtocol::STATS) {
            return MemcacheProtocol::getData();
        } else {
            return false;
        }
    }

    public function close() {
        $this->connection->close();
    }

    public function flush() {
        $this->connection->send("flush_all\r\n");

        $respond = $this->connection->handleMessage();

        if($respond == MemcacheProtocol::OK) {
            return true;
        } else {
            return false;
        }
    }

    public function getVersion() {
        $this->connection->send("version\r\n");

        $respond = $this->connection->handleMessage();

        if($respond == MemcacheProtocol::VERSION) {
            return MemcacheProtocol::getData();
        } else {
            return false;
        }
    }
}