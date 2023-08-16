<?php

namespace Kiri\Pool;

interface PoolInterface
{


    /**
     * @param $name
     * @param int $max
     * @param callable $closure
     * @return void
     */
    public function created($name, int $max, callable $closure): void;


    /**
     * @param string $name
     * @param int $waite_time
     * @return mixed
     */
    public function get(string $name, int $waite_time): mixed;


    /**
     * @param string $name
     * @return void
     */
    public function abandon(string $name): void;


    /**
     * @param string $name
     * @param mixed $data
     * @return void
     */
    public function push(string $name, mixed $data): void;


    /**
     * @param string $name
     * @param mixed $client
     * @return void
     */
    public function release(string $name, mixed $client): void;


    /**
     * @param string $name
     * @return void
     */
    public function close(string $name): void;


    /**
     * @param string $name
     * @param int $retain_number
     * @return void
     */
    public function flush(string $name, int $retain_number): void;

}