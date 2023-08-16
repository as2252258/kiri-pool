<?php
declare(strict_types=1);

namespace Kiri\Pool;

use Closure;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine;

class PoolItem
{


    /**
     * @var Channel|SplQueue
     */
    private Channel|SplQueue $_items;


    /**
     * @var int
     */
    private int $created = 0;


    /**
     * @param int $maxCreated
     * @param Closure|array $callback
     */
    public function __construct(readonly public int $maxCreated, readonly public Closure|array $callback)
    {
        if (Coroutine::getCid() > -1) {
            $this->_items = new Channel($this->maxCreated);
        } else {
            $this->_items = new SplQueue($this->maxCreated);
        }
    }


    /**
     * @return bool
     */
    public function isClose(): bool
    {
        if ($this->_items instanceof Channel) {
            return $this->_items->errCode == SWOOLE_CHANNEL_CLOSED;
        }
        return false;
    }


    /**
     * @return void
     */
    public function reconnect(): void
    {
        if ($this->_items instanceof Channel && $this->_items->errCode == SWOOLE_CHANNEL_CLOSED) {
            $this->_items = new Channel($this->maxCreated);
        }
    }


    /**
     * @param Channel|SplQueue $items
     */
    public function setItems(Channel|SplQueue $items): void
    {
        $this->_items = $items;
    }


    /**
     * @param mixed $item
     * @return void
     */
    public function push(mixed $item): void
    {
        if (is_null($item)) {
            return;
        }
        $this->_items->push($item);
    }


    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->_items->isEmpty();
    }


    /**
     * @return int
     */
    public function size(): int
    {
        return $this->_items->length();
    }


    /**
     * @return bool
     */
    public function close(): bool
    {
        return $this->_items->close();
    }


    /**
     * @param int $min
     * @return void
     */
    public function tailor(int $min = 0): void
    {
        while ($this->_items->length() > $min) {
            $connection = $this->_items->pop(0.000001);
            if ($connection instanceof StopHeartbeatCheck) {
                $connection->stopHeartbeatCheck();
            }
            $connection = null;
            $this->created -= 1;
        }
    }


    /**
     * @return void
     */
    public function abandon(): void
    {
        $this->created -= 1;
    }


    /**
     * @param int $waite
     * @return mixed
     */
    public function pop(int $waite = 10): mixed
    {
        if ($this->_items->isEmpty() && $this->created < $this->maxCreated) {
            $this->created += 1;
            return call_user_func($this->callback);
        } else {
            return $this->_items->pop($waite);
        }
    }
}
