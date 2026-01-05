<?php declare(strict_types=1);


namespace yebindar\Http\Driver;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use yebindar\Http\Exception\RequestException;

class GuzzleDriver implements IDriver
{
    private ?Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws RequestException
     */
    public function request(string $method, string $url, array $options = []): mixed
    {
        $guzzleOptions = [
            'timeout'     => $options['timeout'] ?? 10,
            'http_errors' => false, // 让它不要直接抛异常，由我们 handleFailure 处理
        ];

        if (isset($options['headers']) && is_array($options['headers'])) {
            $guzzleOptions['headers'] = $options['headers'];
        }

        // 处理 Body
        if (isset($options['json']) && is_array($options['json'])) {
            $guzzleOptions['json'] = $options['json'];
        }

        // 处理流式下载
        if (isset($options['sink']) && is_string($options['sink'])) {
            $guzzleOptions['sink'] = $options['sink'];
        }

        $response = $this->client->request($method, $url, $guzzleOptions);

        // 如果状态码不是 200，走我们之前写的处理流程
        if ($response->getStatusCode() !== 200) {
            $this->handleFailure($response, $options['sink'] ?? null);
        }

        return !empty($options['sink']) ? true : (string)$response->getBody();
    }

    /**
     * @param ResponseInterface $response
     * @param string|null $path
     * @return void
     * @throws RequestException
     */
    protected function handleFailure(ResponseInterface $response, ?string $path): void
    {
        // 普通请求失败，返回错误信息给到上游，让上游处理
        if (!$path) {
            throw new RequestException(
                'Request External Api Failed',
                $response->getStatusCode(),
                json_decode($response->getBody()->getContents(), true)
            );
        }

        // 文件流请求失败，读取文件内容，删除文件，返回错误信息给到上游，让上游处理
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $resp    = json_decode($content, true);
            unlink($path);
            throw new RequestException(
                'Request External Api Failed',
                $response->getStatusCode(),
                $resp
            );
        }

        throw new RequestException(
            'Request External Api Failed',
            $response->getStatusCode(),
        );
    }
}