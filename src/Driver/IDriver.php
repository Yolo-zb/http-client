<?php

namespace yebindar\Http\Driver;

interface IDriver
{
    public function request(string $method, string $url, array $options = []): mixed;
}