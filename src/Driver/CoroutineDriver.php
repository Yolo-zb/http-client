<?php declare(strict_types=1);


namespace Yebindar\Http\Driver;

use Swoole\Coroutine\System;
use Swoole\Coroutine\Http\Client;
use Yebindar\Http\Exception\RequestException;

class CoroutineDriver implements IDriver
{

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     * @return string|bool
     * @throws RequestException
     */
    public function request(string $method, string $url, array $options = []): string|bool
    {
        $settings = ['timeout' => $options['timeout'] ?? 10];

        $uri    = parse_url($url);
        $ssl    = $uri['scheme'] === 'https';
        $client = new Client($uri['host'], $uri['port'] ?? ($ssl ? 443 : 80), $ssl);

        if (isset($options['headers']) && is_array($options['headers'])) {
            $client->setHeaders($options['headers']);
        }

        if (!$savePath = $options['save_path'] ?? null) {
            $settings['write_stream'] = $savePath;
        }

        if (isset($options['json'])) {

            $json = $options['json'];

            // 数组转 JSON 字符串
            if (is_array($options['json'])) {
                $json = json_encode($options['json'], JSON_UNESCAPED_UNICODE);
            }

            $client->setData($json);
        }

        $query = http_build_query($options['query'] ?? []);

        $client->set($settings);
        $client->setMethod($method);
        $path = ($uri['path'] ?? '/') . $query ? '?' . $query : '';
        $client->execute($path);
        $status = $client->statusCode;
        $client->close();

        if ($status != 200) {
            $this->handleFailure($client, $status, $savePath);
        }

        // 如果成功且是下载模式，返回 true；否则返回内存中的 body
        return !empty($savePath) ? true : $client->body;
    }

    /**
     * @param Client $client
     * @param int $status
     * @param string|null $path
     * @return void
     * @throws RequestException
     */
    protected function handleFailure(Client $client, int $status, ?string $path): void
    {
        // 普通请求失败，返回错误信息给到上游，让上游处理
        if (!$path) {
            throw new RequestException(
                'Request External Api Failed',
                $status,
                json_decode($client->getBody(), true)
            );
        }

        // 文件流请求失败，读取文件内容，删除文件，返回错误信息给到上游，让上游处理
        if (file_exists($path)) {
            $content = System::readFile($path);
            $resp    = json_decode($content, true);
            unlink($path);
            throw new RequestException(
                'Request External Api Failed',
                $status,
                $resp
            );
        }

        throw new RequestException(
            'Request External Api Failed',
            $status,
        );
    }
}