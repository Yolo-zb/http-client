<?php declare(strict_types=1);


namespace yebindar\Http\Exception;


class RequestException extends \Exception
{
    private array $responseArr;

    /**
     * @param string $message
     * @param int $code
     * @param array $responseArr
     */
    public function __construct(string $message = '', int $code = 0, array $responseArr = [])
    {
        parent::__construct($message, $code);

        $this->responseArr = $responseArr;
    }

    public function getResponseArr(): array
    {
        return $this->responseArr;
    }
}