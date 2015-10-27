# File Handler Middleware

**Please note that this package is still a work in progress and that the implementation might change at any time and that the documentation might be out of date**.

[![Build status](https://img.shields.io/travis/phapi/middleware-file-handler.svg?style=flat-square)](https://travis-ci.org/phapi/middleware-file-handler)
[![Code Climate](https://img.shields.io/codeclimate/github/phapi/middleware-file-handler.svg?style=flat-square)](https://codeclimate.com/github/phapi/middleware-file-handler)
[![Test Coverage](https://img.shields.io/codeclimate/coverage/github/phapi/middleware-file-handler.svg?style=flat-square)](https://codeclimate.com/github/phapi/middleware-file-handler/coverage)

File Handler is a middleware preparing the Phapi Framework for handling client request for both uploading and downloading files. It acts like a modified version of a serializer but without the serialization functionality. It registers the supported mime types and makes sure uploaded files are of a mime type that is allowed as well as checking the file size of the uploaded file.

The package also contains an Endpoint that handles both PUT (for uploading files) and GET (for downloading files) requests. The Endpoint depends on [league/flysystem](https://github.com/thephpleague/flysystem) for handling the interaction with the file storage.

## Installation
This middleware is **not** included by default in the [Phapi Framework](https://github.com/phapi/phapi-framework) but if you need to install it it's available to install via [Packagist](https://packagist.org) and [Composer](https://getcomposer.org).

```bash
$ php composer.phar require phapi/middleware-file-handler:1.*
```

## Configuration
The configuration can be a little bit tricky. The [phapi-configuration repo](https://github.com/phapi/phapi-configuration) has all the configuration added in the right order and place. You can use the repo to start a new project or use it as a reference while updating your existing project. Below is a list of what you need to add to your configuration to get the File handler middleware to work.

### configuration/settings.php
Add the following lines to your settings ([example](https://github.com/phapi/phapi-configuration/blob/master/app/configuration/settings.php)) file and configure your list of routes and configuration for each route:

```php
<?php
/*
 * File handler configuration, add an array for each route you want to have.
 * The file handler requires the FlySystem package, link: http://flysystem.thephpleague.com/
 *
 * Each route has the following configuration options:
 *   'route', the route itself (don't forget that the route has to be included in the main route table as well)
 *   'maxFileSize', (optional) the maximum allowed file size
 *   'mimeTypes', an array with the list of allowed mime types
 *   'flySystemAdapter', use the adapter that matches your environment
 */
$container['fileHandlerConfiguration'] = function () {
    return [
        /* Example:
        [
            'route' => '/user/avatar/{id}/{fileName}', // Complete route
            'maxFileSize' => 1024000, // In bytes
            'mimeTypes' => [
                'image/gif',
                'image/png',
                'image/jpg',
                'image/jpeg'
            ],
            'flySystemAdapter' => new League\Flysystem\Adapter\Local('/path/to/file/storage/')
        ]*/
    ];
};

// Create the Fly system file system
$container['flySystem'] = function ($container) {
    return new \League\Flysystem\Filesystem(
        $container['fileHandledConfiguration']['flySystemAdapter']
    );
};
```

### configuration/middleware.php
Add the following two lines to your configuration ([example](https://github.com/phapi/phapi-configuration/blob/master/app/configuration/middleware.php)) to add the middleware to the middleware pipeline:

The first line should be added right after the regular <code>serializers</code> and must be added before the <code>FormatNegotiation</code> middleware.

```php
<?php
$pipeline->pipe(new \Phapi\Middleware\FileHandler\FileReader($container['fileHandlerConfiguration']));
```

The second line must be added between the <code>Route</code> and <code>PostBox</code> middleware.

```php
<?php
$pipeline->pipe(new \Phapi\Middleware\FileHandler\FileUploader($container['fileHandlerConfiguration']));
```

### configuration/routes.php
The last step is to add a new route to your route table ([example](https://github.com/phapi/phapi-configuration/blob/master/app/configuration/routes.php):

```php
<?php

$routes = [
  '/user/avatar/{id}/{fileName}'    => '\\Phapi\\Endpoint\\File',
];
```

## Usage
Some things to consider:
- It's important that you set the set <code>memory_limit</code> high enough to be able to handle large files. Uploading files will NOT be affected by either <code>post_max_size</code> or <code>upload_max_filesize</code> since we are using PUT method instead of POST.
- The included File endpoint expects the route to have a variable last in the string and that variable will be used as the file name. In the example above we have two variables, id and file name, both will be used when the file is saved to storage. If the Flysystem configuration says that a file should be saved to <code>/www/files/</code> the file will be saved to <code>/www/files/{id}/{filename}</code>.
- The File endpoint will save the file to the designated place and that's it. So a good strategy is to create a separate endpoint that gets called before the File endpoint with information about the file. That endpoint should save the information and return the designated url where the file should be PUT.

## Phapi
This middleware is a Phapi package used by the [Phapi Framework](https://github.com/phapi/phapi). The middleware are also [PSR-7](https://github.com/php-fig/http-message) compliant and implements the [Phapi Middleware Contract](https://github.com/phapi/contract).

## License
Serializer JSON is licensed under the MIT License - see the [license.md](https://github.com/phapi/middleware-file-handler/blob/master/license.md) file for details

## Contribute
Contribution, bug fixes etc are [always welcome](https://github.com/phapi/middleware-file-handler/issues/new).
