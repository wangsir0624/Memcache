<?php
namespace Wangjian\Memcache\Connection;

use Wangjian\Memcache\Protocol\MemcacheProtocol;

class Connection implements ConnectionInterface {
    protected $stream;

    protected $recv_buffer = '';

    protected $recv_buffer_size = 1048576;

    public function __construct($stream) {
        $this->stream = $stream;
        stream_set_read_buffer($this->stream, $this->recv_buffer_size);
    }

    public function send($buffer) {
        if($buffer) {
            $len = strlen($buffer);
            $writeLen = 0;
            while(($bytes = fwrite($this->stream, substr($buffer, $writeLen), $len-$writeLen)) != false) {
                $writeLen += $bytes;

                if($writeLen >= $len) {
                    return $len;
                }
            }
        }

        return 0;
    }

    public function handleMessage() {
        $this->recv_buffer .= fread($this->stream, $this->recv_buffer_size);

        $package_size = MemcacheProtocol::input($this->recv_buffer, $this);

        if($package_size != 0) {
            $buffer = substr($this->recv_buffer, 0, $package_size);
            $this->recv_buffer = substr($this->recv_buffer, $package_size);

            return MemcacheProtocol::decode($buffer, $this);
        } else {
            return $this->handleMessage();
        }
    }

    public function close() {
        fclose($this->stream);
    }

    /**
     * get the client address, including IP and port
     * @return string
     */
    public function getRemoteAddress() {
        return stream_socket_get_name($this->stream, true);
    }

    /**
     * get the client IP
     * @return string
     */
    public function getRemoteIp() {
        return substr($this->getRemoteAddress(), 0, strpos($this->getRemoteAddress(), ':'));
    }

    /**
     * get the client port
     * @return string
     */
    public function getRemotePort() {
        return substr($this->getRemoteAddress(), strpos($this->getRemoteAddress(), ':')+1);
    }
}