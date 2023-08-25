<?php


namespace Kiri\Pool;


use Exception;
use Kiri\Di\Context;


/**
 * Class Pool
 * @package Kiri\Pool
 */
class Pool implements PoolInterface
{

    /** @var array<PoolItem> */
    protected array $_connections = [];


    /**
     * @param $name
     * @param $retain_number
     * @throws Exception
     */
    public function flush($name, $retain_number): void
    {
        if ($this->hasChannel($name)) {
            if ($retain_number == 0) {
                $this->close($name);
                return;
            }
            $this->channel($name)->tailor($retain_number);
        }
    }


    /**
     * @param PoolItem $channel
     * @param $retain_number
     */
    protected function pop(PoolItem $channel, $retain_number): void
    {
        $channel->tailor($retain_number);
    }


    /**
     * @param $name
     * @param int $max
     * @param callable $closure
     */
    public function created($name, int $max, callable $closure): void
    {
        if (!isset($this->_connections[$name])) {
            $this->_connections[$name] = new PoolItem($max, $closure);
        }
    }


    /**
     * @param $name
     * @return PoolItem
     * @throws Exception
     */
    public function channel($name): PoolItem
    {
        if (!isset($this->_connections[$name])) {
            throw new Exception('Channel is not exists.');
        }
        $channel = $this->_connections[$name];
        if ($channel->isClose()) {
            $channel->reconnect();
        }
        return $channel;
    }


    /**
     * @param $name
     * @return bool
     */
    public function hasChannel($name): bool
    {
        return isset($this->_connections[$name]) && $this->_connections[$name] instanceof PoolItem;
    }


    /**
     * @param string $name
     * @return void
     * @throws Exception
     */
    public function abandon(string $name): void
    {
        $this->channel($name)->abandon();
    }


    /**
     * @param string $name
     * @param int $waite_time
     * @return array
     * @throws Exception
     */
    public function get(string $name, int $waite_time = 3): mixed
    {
        return $this->channel($name)->pop($waite_time);
    }


    /**
     * @param $name
     * @return bool
     * @throws Exception
     */
    public function isNull($name): bool
    {
        return $this->channel($name)->isEmpty();
    }


    /**
     * @param string $name
     * @param mixed $client
     * @return bool
     * 检查连接可靠性
     */
    public function checkCanUse(string $name, mixed $client): bool
    {
        return true;
    }


    /**
     * @param string $name
     * @return bool
     */
    public function hasItem(string $name): bool
    {
        $channel = $this->_connections[$name] ?? null;
        if ($channel === null) {
            return false;
        }
        return !$channel->isEmpty();
    }


    /**
     * @param string $name
     * @return int
     */
    public function size(string $name): int
    {
        $channel = $this->_connections[$name] ?? null;
        if ($channel === null) {
            return 0;
        }
        return $channel->size();
    }


    /**
     * @param string $name
     * @param mixed $data
     * @throws Exception
     */
    public function push(string $name, mixed $data): void
    {
        $this->channel($name)->push($data);
    }


    /**
     * @param $name
     * @param int $time
     * @return array
     * @throws Exception
     */
    public function waite($name, int $time = 30): mixed
    {
        return $this->channel($name)->pop($time);
    }


    /**
     * @param string $name
     * @throws Exception
     */
    public function close(string $name): void
    {
        if (!isset($this->_connections[$name])) {
            return;
        }
        if (Context::inCoroutine()) {
            $this->_connections[$name]->close();
        }
    }


    /**
     * return pool queue lists
     * @return PoolItem[]
     */
    protected function channels(): array
    {
        return $this->_connections;
    }


    /**
     * @param string $name
     * @param mixed $client
     * @return void
     * @throws Exception
     */
    public function release(string $name, mixed $client): void
    {
        // TODO: Implement release() method.
        $this->channel($name)->push($client);
    }
}
