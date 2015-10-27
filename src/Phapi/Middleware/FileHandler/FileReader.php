<?php


namespace Phapi\Middleware\FileHandler;

use Phapi\Contract\Di\Container;
use Phapi\Contract\Middleware\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class File
 *
 * @category
 * @package  Phapi\Middleware\Serializer\File
 * @author   Peter Ahinko <peter@avero.se>
 * @license  See license.md
 * @link     http://www.avero.se
 */
class FileReader implements Middleware
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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        // Loop through the configuration
        foreach ($this->config as $config) {

            // Remove all variables from the route in the file configuration
            $route = preg_replace('/{(.*)/i', '', $config['route']);

            // Do a really simple match by checking if the start of the route in the file
            // configuration matches the start of the requested uri. We have to do this since
            // the real routing hasn't been done yet.
            if (strpos($request->getUri()->getPath(), $route) === 0 &&
                $request->getMethod() === 'GET'
            ) {
                // Clear the list of supported mime types and register the file
                // mime types to the list of supported mime types
                $this->container['acceptTypes'] = $config['mimeTypes'];

                // Add the configuration to the DI container so that the endpoint
                // can get it and use it when handling the file.
                $this->container['fileHandledConfiguration'] = $config;
            }
        }

        // Call the next middleware
        return $next($request, $response, $next);
    }
}
