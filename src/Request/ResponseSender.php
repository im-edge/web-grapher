<?php

namespace IMEdge\Web\Grapher\Request;

use Icinga\Web\Response;
use IMEdge\Json\JsonString;
use IMEdge\Web\Grapher\GraphRendering\ErrorImage;
use Psr\Http\Message\ServerRequestInterface;

use function gmdate;
use function strlen;
use function time;

class ResponseSender
{
    protected Response $response;
    protected bool $useXhr = false;
    protected ServerRequestInterface $request;
    protected ?int $expiration;

    public function __construct(Response $response, ServerRequestInterface $request, ?int $expiration = 0)
    {
        $this->response = $response;
        $this->request = $request;
        $this->expiration = $expiration;
    }

    public function useXhr($xhr = true)
    {
        $this->useXhr = $xhr;
    }

    public function sendAsJson($props)
    {
        $response = $this->response;
        $compression = new ResponseCompression($this->response, $this->request);
        $body = $compression->compress(JsonString::encode($props));
        $response->setHeader('Content-Type', 'application/json', true);
        $response->setHeader('Content-Length', strlen($body), true);
        if ($this->expiration) {
            $response->setHeader(
                'Cache-Control',
                //sprintf('public, max-age=%d, stale-while-revalidate=%d', $this->expiration, $this->expiration * 10),
                sprintf('public, max-age=%d', $this->expiration),
                true
            );
        }

        $this->response->setBody($body);
    }

    /**
     * @param \Error|string $error
     * @param int $width
     * @param int $height
     * @return void
     */
    public function sendError($error, int $width, int $height)
    {
        $image = new ErrorImage($error);
        // $image->showStackTrace();
        $this->sendNoCacheHeaders();
        if ($this->useXhr) {
            $this->sendAsJson($image->renderToJson($width, $height));
        } else {
            $this->sendImage($image->render($width, $height), 'png');
        }
    }

    public function sendImage(string $image, string $type, ?string $downloadFilename = null)
    {
        $this->sendCacheHeaders();
        $this->response->setHeader('Content-Type', $type, true);
        if ($downloadFilename) {
            $this->response
                ->setHeader('Content-Description', 'File Transfer', true)
                ->setHeader('Content-Disposition', "attachment; filename=\"$downloadFilename\"", true);
        }

        $this->response->setBody($image);
    }

    protected function sendCacheHeaders()
    {
        $secondsToCache = 3600;
        $ts = gmdate("D, d M Y H:i:s", time() + $secondsToCache) . " GMT";
        $this->response
            ->setHeader('Expires', $ts, true)
            ->setHeader('Pragma', 'cache', true)
            ->setHeader('Cache-Control', "public,max-age=$secondsToCache", true);
    }

    protected function sendNoCacheHeaders()
    {
        $this->response
            ->setHeader('Pragma', 'no-cache', true)
            ->setHeader('Cache-Control', 'no-store', true);
    }
}
