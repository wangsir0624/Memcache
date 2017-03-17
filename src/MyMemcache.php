<?php
namespace Wangjian\Memcache;

use RuntimeException;
use Wangjian\Memcache\Connection\OneBalancer;
use Wangjian\Memcache\Connection\MoreBalancer;
use Wangjian\Memcache\Connection\Connection;
use Wangjian\Memcache\Protocol\MemcacheProtocol;

class MyMemcache {
    const NOT_CONNECTED = 1;

    const CONNECTED = 2;

    const CLOSED = 4;

    protected $balancer;

    protected $status = self::NOT_CONNECTED;

    public function connect($ip, $port, $timeout=5) {
        if($this->status == self::CONNECTED) {
            throw new RuntimeException("the memcache client is alread connected");
        }

        $this->balancer = new OneBalancer();
        $this->balancer->addConnection(self::createConnection($ip, $port, $timeout));
        $this->status = self::CONNECTED;
    }

    public function addServer($ip, $port, $weight = 1, $timeout = 5) {
        if($this->balancer instanceof OneBalancer) {
            throw new RuntimeException("the memcache client is alread connected");
        }

        if(!($this->balancer instanceof MoreBalancer)) {
            $this->balancer = new MoreBalancer($this);
            $this->status = self::CONNECTED;
        }

        $this->balancer->addConnectionConfig($ip, $port, $weight, $timeout);
    }

    public function close() {
        if($this->status == self::CONNECTED) {
            $this->balancer->close();
        }

        $this->status = self::CLOSED;
    }

    public function add($key, $value, $flag = 0, $expire = 0) {
        if($this->status != self::CONNECTED) {
            return false;
        }

        $connection = $this->balancer->hashConnection($key);

        $connection->send("add $key $flag $expire ".strlen($value)."\r\n$value\r\n");
        $respond = $connection->handleMessage();

        if($respond == MemcacheProtocol::STORED) {
            return true;
        } else {
            return false;
        }
    }

    public function set($key, $value, $flag = 0, $expire = 0) {
        if($this->status != self::CONNECTED) {
            return false;
        }

        $connection = $this->balancer->hashConnection($key);

        $connection->send("set $key $flag $expire ".strlen($value)."\r\n$value\r\n");
        $respond = $connection->handleMessage();

        if($respond == MemcacheProtocol::STORED) {
            return true;
        } else {
            return false;
        }
    }

    public function replace($key, $value, $flag = 0, $expire = 0) {
        if($this->status != self::CONNECTED) {
            return false;
        }

        $connection = $this->balancer->hashConnection($key);

        $connection->send("replace $key $flag $expire ".strlen($value)."\r\n$value\r\n");
        $respond = $connection->handleMessage();

        if($respond == MemcacheProtocol::STORED) {
            return true;
        } else {
            return false;
        }
    }

    public function get($key, &$flag = array()) {
        if($this->status != self::CONNECTED) {
            return false;
        }

        $return_array = false;
        if(is_array($key)) {
            $return_array = true;
            $key = implode(' ', $key);
        }

        $connection = $this->balancer->hashConnection($key);

        $connection->send("get $key \r\n");
        $respond = $connection->handleMessage();

        if($respond == MemcacheProtocol::VALUE) {
            $data = MemcacheProtocol::getData();

            $value = array();
            foreach($data as $index => $item) {
                $value[$index] = $item['value'];
                $flag[$index] = $item['flag'];
            }

            if(!$return_array) {
                $flag = array_pop($flag);
                return array_pop($value);
            }

            return $value;
        } else {
            return false;
        }
    }

    public function increment($key, $by = 1) {
        if($this->status != self::CONNECTED) {
            return false;
        }

        $connection = $this->balancer->hashConnection($key);

        $connection->send("incr $key $by\r\n");
        $respond = $connection->handleMessage();

        if($respond == MemcacheProtocol::OK) {
            return (int)MemcacheProtocol::getData();
        } else {
            return false;
        }
    }

    public function decrement($key, $by = 1) {
        if($this->status != self::CONNECTED) {
            return false;
        }

        $connection = $this->balancer->hashConnection($key);

        $connection->send("decr $key $by\r\n");
        $respond = $connection->handleMessage();

        if($respond == MemcacheProtocol::OK) {
            return (int)MemcacheProtocol::getData();
        } else {
            return false;
        }
    }

    public function delete($key, $timeout = 0) {
        if($this->status != self::CONNECTED) {
            return false;
        }

        $connection = $this->balancer->hashConnection($key);

        $connection->send("delete $key $timeout\r\n");
        $respond = $connection->handleMessage();

        if($respond == MemcacheProtocol::DELETED) {
            return true;
        } else {
            return false;
        }
    }

    public function flush() {
        if($this->status != self::CONNECTED) {
            return false;
        }

        return $this->balancer->flush();
    }

    public function getStats() {
        if($this->status != self::CONNECTED) {
            return false;
        }

        return $this->balancer->getStatus();
    }

    public function getVersion() {
        if($this->status != self::CONNECTED) {
            return false;
        }

        return $this->balancer->getVersion();
    }

    public static function createConnection($ip, $port, $timeout) {
        $stream = stream_socket_client("tcp://$ip:$port", $errno, $errstr, $timeout);

        if(!$stream) {
            throw new RuntimeException("create socket failed($errno): $errstr");
        }

        return new Connection($stream);
    }
}