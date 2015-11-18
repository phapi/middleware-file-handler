<?php


namespace Phapi\Middleware\FileHandler;

use Phapi\Contract\Di\Container;
use Phapi\Contract\Middleware\Middleware;
use Phapi\Exception\RequestEntityTooLarge;
use Phapi\Exception\UnsupportedMediaType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * File Uploader
 *
 * Middleware responsible for preparing the framework to handle a file upload.
 * The middleware also checks that the mime type is allowed and that the file size
 * is within the allowed parameter.
 *
 * @category Phapi
 * @package  Phapi\Middleware\FileHandler
 * @author   Peter Ahinko <peter@ahinko.se>
 * @license  MIT (http://opensource.org/licenses/MIT)
 * @link     https://github.com/phapi/middleware-file-handler
 */
class FileUploader implements Middleware
{

    /**
     * Dependency Injection Container
     *
     * @var Container
     */
    protected $container;

    /**
     * Configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor setting the configuration
     *
     * @param array $fileConfig
     */
    public function __construct(array $fileConfig = [])
    {
        $this->config = $fileConfig;
    }

    /**
     * Set dependency injection container
     *
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Invoke, checks if the matched route is listed in the configuration for the file serializer. If it is,
     * unset all registered mime types and add the mime types from the configuration for the file serializer.
     * Next check for real file mime type and make sure both the mime type and file size is within allowed
     * values
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return ResponseInterface
     * @throws RequestEntityTooLarge
     * @throws UnsupportedMediaType
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        // Loop through the configuration
        foreach ($this->config as $config) {
            // Match the matched route (not endpoint) with the configuration
            if ($request->getAttribute('routeRoute', null) === $config['route'] &&
                $request->getMethod() === 'PUT'
            ) {
                // Clear the list of supported mime types and register the file
                // mime types to the list of supported mime types
                $this->container['contentTypes'] = $config['mimeTypes'];

                // Add the configuration to the DI container so that the endpoint
                // can get it and use it when handling the file.
                $this->container['fileHandledConfiguration'] = $config;

                // Get the file content
                $fileContent = $request->getBody()->getContents();

                // Check for base64 info in the content (HTML5 FileReader API)
                if (preg_match('/base64,(.*)/', $fileContent, $match)) {
                    $fileContent = $match[1];
                }

                // Check if the files real content type matches the list of supported mime types
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($fileContent);

                // Check if file content is of allowed mime type
                if ($mimeType !== false && !in_array($mimeType, $config['mimeTypes'])) {
                    throw new UnsupportedMediaType(
                        'Uploaded file is of an unsupported mime type ' . $mimeType .
                        '. Supported types are ' . implode(', ', $config['mimeTypes'])
                    );
                }

                // Check file size
                $fileSize = strlen($request->getBody()->getContents());
                if (isset($config['maxFileSize']) && $fileSize > $config['maxFileSize']) {
                    throw new RequestEntityTooLarge(
                        'The uploaded file is to large (' . $this->formatBytes($fileSize) . ').
                        Max allowed size are: ' . $this->formatBytes($config['maxFileSize'])
                    );
                }
            }
        }

        // Call next middleware
        return $next($request, $response, $next);
    }

    /**
     * Format bytes to a more readable unit
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
