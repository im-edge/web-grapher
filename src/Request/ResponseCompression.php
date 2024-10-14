<?php

namespace IMEdge\Web\Grapher\Request;

use Icinga\Web\Response;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function gzcompress;

class ResponseCompression
{
    protected Response $response;
    protected ServerRequestInterface $request;
    protected ?array $acceptedEncodings = null;

    public function __construct(Response $response, ServerRequestInterface $request)
    {
        $this->response = $response;
        $this->request = $request;
    }

    public function compress(string $body): string
    {
        $response = $this->response;
        if ($this->supportsEncoding('gzip')) {
            $response->setHeader('Content-Encoding', 'gzip', true);
            $body = gzcompress($body, -1, ZLIB_ENCODING_GZIP);
        // } elseif ($this->supportsEncoding('compress')) { // TODO: can we support this?
        } elseif ($this->supportsEncoding('deflate')) {
            $response->setHeader('Content-Encoding', 'deflate', true);
            $body = gzcompress($body, -1, ZLIB_ENCODING_DEFLATE);
        }
        if ($body === false) {
            throw new RuntimeException('Failed to compress body');
        }

        return $body;
    }

    protected function listAcceptedEncodings(): array
    {
        if ($this->acceptedEncodings === null) {
            // gzip, deflate, br
            $encodings = [];
            $header = $this->request->getHeaderLine('Accept-Encoding');
            $parts = preg_split('/\s*,\s*/', $header, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parts as $part) {
                if (strpos($part, ';') === false) {
                    $encodings[] = $part;
                } else {
                    $attributes = [];
                    $attributesString = explode(';', $part);
                    $part = array_shift($attributesString);
                    foreach ($attributesString as $attribute) {
                        $pos = strpos($attribute, '=');
                        if ($pos === false) {
                            $attributes[$attribute] = true;
                        } else {
                            $name = substr($attribute, 0, $pos);
                            $value = substr($attribute, $pos + 1);
                            $attributes[$name] = $value;
                        }
                    }
                    if (isset($attributes['q'])) {
                        if ((float) $attributes['q'] > 0) {
                            $encodings[] = $part;
                        }
                    } else {
                        $encodings[] = $part;
                    }
                }
            }

            $this->acceptedEncodings = $encodings;
        }

        return $this->acceptedEncodings;
    }

    protected function supportsEncoding(string $encoding): bool
    {
        return in_array($encoding, $this->listAcceptedEncodings());
    }
}
