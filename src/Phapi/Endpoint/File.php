<?php


namespace Phapi\Endpoint;

use Phapi\Endpoint;
use Phapi\Exception\InternalServerError;
use Zend\Diactoros\Stream;

/**
 * Class File
 *
 * @category
 * @package  Phapi\Endpoint
 * @author   Peter Ahinko <peter@avero.se>
 * @license  See license.md
 * @link     http://www.avero.se
 */
class File extends Endpoint
{

    /**
     * Get file
     *
     * @return array
     * @throws InternalServerError
     */
    public function get()
    {
        // Build file name based on (URI) arguments
        $filename = implode('/', func_get_args());

        // Get the file system
        $fileSystem = $this->container['flySystem'];

        // Get the file content and write it to file
        if (!$fileSystem->has($filename)) {
            throw new InternalServerError('Could not read file from storage.');
        }

        // Get file content
        $fileContent = $fileSystem->read($filename);

        // Get mime type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);

        // Write content to body
        $body = new Stream('php://memory', 'w+');
        $body->write($fileContent);

        // Set headers
        $this->response = $this->response
            ->withBody($body)
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Length', strval(strlen($fileContent)));

        return [];
    }

    /**
     * Upload file to the API
     *
     * @return array
     * @throws InternalServerError
     */
    public function put()
    {
        // Build file name based on (URI) arguments
        $filename = implode('/', func_get_args());

        // Get the file system
        $fileSystem = $this->container['flySystem'];

        // Get the file content and write it to file
        if (!$fileSystem->put($filename, $this->request->getBody()->getContents())) {
            throw new InternalServerError('Could not save file to storage.');
        }

        // Change status code on the response to a 201 CREATED:
        $this->response = $this->response->withStatus(201);

        return [
            '_links' => [
                'rel' => 'self',
                'href' => $this->request->getUri()->getPath()
            ]
        ];
    }
}
