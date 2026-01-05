<?php declare(strict_types=1);


namespace yebindar\Http;


use yebindar\Http\Driver\GuzzleDriver;
use yebindar\Http\Driver\CoroutineDriver;

class Factory
{
    public static function create()
    {
        // 在协程环境下使用swoole协程驱动
        if (extension_loaded('swoole') && \Swoole\Coroutine::getuid() > 0) {
            return new CoroutineDriver();
        }

        // FPM环境或处于非协程环境下，使用guzzle驱动
        return new GuzzleDriver();
    }
}