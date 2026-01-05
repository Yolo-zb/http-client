<?php

namespace Yebindar\Http\Driver;

interface IDriver
{
    public function request(string $method, string $url, array $options = []): mixed;
}