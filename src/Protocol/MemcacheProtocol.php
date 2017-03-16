<?php
namespace Wangjian\Memcache\Protocol;

use Wangjian\Memcache\Connection\ConnectionInterface;

class MemcacheProtocol implements ProtocolInterface {
    const OK = 1;

    const STORED = 2;

    const NOT_STORED = 4;

    const DELETED = 8;

    const NOT_FOUND = 16;

    const ERROR = 32;

    const CLIENT_ERROR = 64;

    const SERVER_ERROR = 128;

    const VALUE = 256;

    const STATS = 512;

    const END = 1024;

    const VERSION = 2048;

    protected static $data;

    /**
     * get the websocket frame length.
     * @param $buffer
     * @param ConnectionInterface $connection
     * @return int return the frame length when the buffer is ready. Notice: when the buffer is not ready and should wait for more data, returns 0
     */
    public static function input($buffer, ConnectionInterface $connection) {
        if(substr($buffer, 0 ,4) == 'STAT') {
            if(($pos = strpos($buffer, 'END')) !== false) {
                $len = $pos + 5;

                if($len > strlen($buffer)) {
                    return 0;
                } else {
                    return $len;
                }
            } else {
                return 0;
            }
        } else if(substr($buffer, 0, 5) == 'VALUE') {
            if(($pos = strpos($buffer, 'END')) !== false) {
                $len = $pos + 5;

                if($len > strlen($buffer)) {
                    return 0;
                } else {
                    return $len;
                }
            } else {
                return 0;
            }
        } else if(substr($buffer, 0, 6) == 'STORED') {
            if(($pos = strpos($buffer, "\r\n")) !== false) {
                $len = $pos + 2;

                if($len > strlen($buffer)) {
                    return 0;
                } else {
                    return $len;
                }
            } else {
                return 0;
            }
        } else if(substr($buffer, 0, 10) == 'NOT_STORED') {
            if(($pos = strpos($buffer, "\r\n")) !== false) {
                $len = $pos + 2;

                if($len > strlen($buffer)) {
                    return 0;
                } else {
                    return $len;
                }
            } else {
                return 0;
            }
        } else if(substr($buffer, 0, 7) == 'DELETED') {
            if(($pos = strpos($buffer, "\r\n")) !== false) {
                $len = $pos + 2;

                if($len > strlen($buffer)) {
                    return 0;
                } else {
                    return $len;
                }
            } else {
                return 0;
            }
        } else if(substr($buffer, 0, 9) == 'NOT_FOUND') {
            if(($pos = strpos($buffer, "\r\n")) !== false) {
                $len = $pos + 2;

                if($len > strlen($buffer)) {
                    return 0;
                } else {
                    return $len;
                }
            } else {
                return 0;
            }
        } else if(substr($buffer, 0, 3) == 'END') {
            if (($pos = strpos($buffer, "\r\n")) !== false) {
                $len = $pos + 2;

                if ($len > strlen($buffer)) {
                    return 0;
                } else {
                    return $len;
                }
            } else {
                return 0;
            }
        } else if(substr($buffer, 0, 7) == 'VERSION') {
            if (($pos = strpos($buffer, "\r\n")) !== false) {
                $len = $pos + 2;

                if ($len > strlen($buffer)) {
                    return 0;
                } else {
                    return $len;
                }
            } else {
                return 0;
            }
        } else {
            if (($pos = strpos($buffer, "\r\n")) !== false) {
                $len = $pos + 2;

                if ($len > strlen($buffer)) {
                    return 0;
                } else {
                    return $len;
                }
            } else {
                return 0;
            }
        }
    }

    /**
     * websocket encode
     * @param $buffer
     * @param ConnectionInterface $connection
     * @return string  returns the encoded buffer
     */
    public static function encode($buffer, ConnectionInterface $connection) {
        return $buffer;
    }

    /**
     * websocket decode
     * @param $buffer
     * @param ConnectionInterface $connection
     * @return string  returns the original data
     */
    public static function decode($buffer, ConnectionInterface $connection) {
        if(substr($buffer, 0, 4) == 'STAT') {
            $stats = array();

            $lines = explode("\r\n", $buffer);
            foreach($lines as $line) {
                if(substr($line, 0, 4) == 'STAT') {
                    list(, $key, $value) = explode(' ', $line);
                    $stats[$key] = $value;
                }
            }

            self::$data = $stats;
            return self::STATS;
        } else if(substr($buffer, 0, 5) == 'VALUE') {
            $lines = explode("\r\n", $buffer);
            $lines = array_slice($lines, 0, count($lines)-2);

            $data = array();
            foreach($lines as $index => $line) {
                if(($index % 2) == 0) {
                    list(, $key, $flag, $bytes) = explode(' ', $line);
                } else {
                    $data[$key] = array('value' => $line, 'flag' => $flag, 'bytes' => $bytes);
                }
            }

            self::$data = $data;
            return self::VALUE;
        } else if(substr($buffer, 0, 6) == 'STORED') {
            return self::STORED;
        } else if(substr($buffer, 0, 10) == 'NOT_STORED') {
            return self::NOT_STORED;
        } else if(substr($buffer, 0, 7) == 'DELETED') {
            return self::DELETED;
        } else if(substr($buffer, 0, 9) == 'NOT_FOUND') {
            return self::NOT_FOUND;
        } else if(substr($buffer, 0, 3) == 'END') {
            return self::END;
        } else if(substr($buffer, 0, 7) == 'VERSION') {
            list(, $version) = explode(' ', trim($buffer, "\r\n"));

            self::$data = $version;
            return self::VERSION;
        } else {
            list($value, ) = explode("\r\n", $buffer);

            self::$data = $value;
            return self::OK;
        }
    }

    public static function getData() {
        return self::$data;
    }
}