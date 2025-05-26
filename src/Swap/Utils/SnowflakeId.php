<?php

namespace Swap\Utils;

class SnowflakeId
{
    private int $epoch = 1731117701; // 起始时间戳
    private int $dataCenterId; // 数据中心ID
    private int $workerId; // 机器（实例）ID
    private int $sequence; // 序列号
    private int $lastTimestamp = -1; // 上一次时间戳

    private static ?SnowflakeId $instance = null;

    public function __construct($dataCenterId, $workerId)
    {
        $this->dataCenterId = $dataCenterId;
        $this->workerId = $workerId;
        $this->sequence = 0;
    }

    public function nextId(): string
    {
        $timestamp = $this->timeGen();
        if ($this->lastTimestamp == $timestamp) {
            $this->sequence = ($this->sequence + 1) & 0xFFFFFFF;
            if ($this->sequence == 0) {
                $timestamp = $this->tilNextMillis($this->lastTimestamp);
            }
        } else {
            $this->sequence = 0;
        }
        $this->lastTimestamp = $timestamp;
        return (($timestamp - $this->epoch) << 22) | ($this->dataCenterId << 17) | ($this->workerId << 12) | $this->sequence;
    }

    private function timeGen()
    {
        return floor(microtime(true) * 1000);
    }

    private function tilNextMillis($lastTimestamp)
    {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }

    private static function toCodePoints($str): array
    {
        $ips = [];
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $ips[] = ord($str[$i]);
        }
        return $ips;
    }

    public static function generateId(): string
    {
        if (null === self::$instance) {
            $ips = self::toCodePoints(gethostbyname(gethostname()));
            $sum = 0;
            foreach ($ips as $value) {
                $sum = $sum + $value;
            }
            $hosts = self::toCodePoints(gethostname());
            $sums = 0;
            foreach ($hosts as $value) {
                $sums = $sums + $value;
            }
            $dataCenterId = intval($sum % 32);
            $workerId = intval($sums % 32);
            self::$instance = new self($dataCenterId, $workerId);
        }
        return self::$instance->nextId();
    }
}
