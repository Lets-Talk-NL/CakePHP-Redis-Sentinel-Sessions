<?php

namespace Letstalk\CakephpRedisSentinelSessions\Http\Session;

use Cake\Cache\Engine\RedisEngine;
use InvalidArgumentException;
use RedisSentinel;
use SessionHandlerInterface;

/**
 * Store sessions using Redis Sentinel and the Cake Redis Cache Engine for CakePHP 3.x
 */
class Cake3RedisSentinelSession implements SessionHandlerInterface
{
    protected RedisEngine $redis;

    /**
     * @param array $config The configuration to use for this engine
     *                      It requires the keys 'host' and 'port'. 'database' is optional.
     * @throws InvalidArgumentException if the config is invalid
     */
    public function __construct(array $config = [])
    {
        if (empty($config['host']) || empty($config['port'])) {
            throw new InvalidArgumentException('The Sentinel host and port are required');
        }
        $sentinel = new RedisSentinel($config['host'], $config['port']);
        [$host, $port] = $sentinel->getMasterAddrByName('mymaster');
        $this->redis = new RedisEngine();
        $this->redis->init([
            'host'     => $host,
            'port'     => $port,
            'database' => $config['database'] ?? 0,
            'prefix'   => $config['prefix'] ?? 'session_',
        ]);

    }

    /**
     * @inheritDoc
     */
    public function open($savePath, $name): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function read($id): string
    {
        $value = $this->redis->read($id);

        if (empty($value)) {
            return '';
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function write($id, $data): bool
    {
        if (!$id) {
            return false;
        }

        return $this->redis->write($id, $data);
    }

    /**
     * @inheritDoc
     */
    public function destroy($id): bool
    {
        $this->redis->delete($id);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function gc($maxlifetime): bool
    {
        return true;
    }
}
