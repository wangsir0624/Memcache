<?php
namespace Wangjian\Memcache\Connection;

use Wangjian\Memcache\MyMemcache;

class MoreBalancer implements BalancerInterface {
    const CONNECTION_REPL_NUM = 3000;

    protected $connection_configs = array();

    protected $connections;

    protected $connections_repl;

    protected $memcache;

    protected $initialized = false;

    protected $total_weights = 0;

    public function __construct(MyMemcache $memcache) {
        $this->memcache = $memcache;
    }

    public function addConnection(ConnectionInterface $connection) {
        $this->addConnectionConfig($connection->getRemoteIp(), $connection->getRemotePort());
        $this->connections[$connection->getRemoteAddress()] = $connection;
    }

    public function addConnectionConfig($ip, $port, $weight=1, $timeout=5) {
        $this->initialized = false;
        $this->connection_configs["$ip:$port"] = compact(array('ip', 'port', 'weight', 'timeout'));
        $this->total_weights += $weight;
    }

    public function hashConnection($key) {
        if(!$this->initialized) {
            $this->initialize();
        }

        $hash = self::hash($key);
        $connection_key = '';
        foreach($this->connections_repl as $key => $repl) {
            if($hash <= $key) {
                $connection_key = $repl;
                break;
            }
        }

        if(empty($connection_key)) {
            reset($this->connections_repl);
            $connection_key = current($this->connections_repl);
        }

        $connection = @$this->connections[$connection_key];
        if(empty($connection)) {
            $connection_config = $this->connection_configs[$connection_key];
            $connection = MyMemcache::createConnection($connection_config['ip'], $connection_config['port'], $connection_config['timeout']);
            $this->connections[$connection->getRemoteAddress()] = $connection;
        }

        return $connection;
    }

    public function initialize() {
        foreach($this->connection_configs as $config) {
            $repl_nums = ceil($config['weight'] * self::CONNECTION_REPL_NUM/$this->total_weights);

            for($i = 1; $i <= $repl_nums; $i++) {
                $this->connections_repl[self::hash("$config[ip]:$config[port]#$i")] = "$config[ip]:$config[port]";
            }
        }

        ksort($this->connections_repl);

        $this->initialized = true;
    }

    public function close() {
        foreach($this->connections as $connection) {
            $connection->close();
        }
    }

    protected static function hash($key) {
        return abs(crc32($key));
    }
}