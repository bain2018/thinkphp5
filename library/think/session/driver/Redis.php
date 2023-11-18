<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\session\driver;

use SessionHandler;
use think\Exception;

class Redis extends SessionHandler
{
    /** @var \Redis */
    protected $handler = null;
    protected $config  = [
        'host'         => '127.0.0.1', // redis主机
        'port'         => 6379, // redis端口
        'password'     => '', // 密码
        'select'       => 0, // 操作库
        'expire'       => 3600, // 有效期(秒)
        'timeout'      => 0, // 超时时间(秒)
        'persistent'   => true, // 是否长连接
        'session_name' => '', // sessionkey前缀
    ];

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 打开Session
     * @access public
     * @param string $path
     * @param string $name
     * @return bool
     * @throws Exception
     * @throws \RedisException
     */
    public function open(string $path, string $name): bool
    {
        // 检测php环境
        if (!extension_loaded('redis')) {
            throw new Exception('not support:redis');
        }
        $this->handler = new \Redis;

        // 建立连接
        $func = $this->config['persistent'] ? 'pconnect' : 'connect';
        $this->handler->$func($this->config['host'], $this->config['port'], $this->config['timeout']);

        if ('' != $this->config['password']) {
            $this->handler->auth($this->config['password']);
        }

        if (0 != $this->config['select']) {
            $this->handler->select($this->config['select']);
        }

        return true;
    }

    /**
     * 关闭Session
     * @access public
     */
    public function close():bool
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->handler->close();
        $this->handler = null;
        return true;
    }

    /**
     * 读取Session
     * @access public
     * @param string $id
     * @return string
     */
    public function read(string $id): string|false
    {
        return (string) $this->handler->get($this->config['session_name'] . $id);
    }

    /**
     * 写入Session
     * @access public
     * @param string $id
     * @param String $data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        if ($this->config['expire'] > 0) {
            return $this->handler->setex($this->config['session_name'] . $id, $this->config['expire'], $data);
        } else {
            return $this->handler->set($this->config['session_name'] . $id, $data);
        }
    }

    /**
     * 删除Session
     * @access public
     * @param string $id
     * @return bool
     * @throws \RedisException
     */
    public function destroy(string $id): bool
    {
        return $this->handler->del($this->config['session_name'] . $id) > 0;
    }

    /**
     * Session 垃圾回收
     * @access public
     * @param int $max_lifetime
     * @return int|false
     */
    public function gc(int $max_lifetime): int|false
    {
        return true;
    }
}
