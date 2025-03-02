<?php

namespace App\Exceptions;

use Slim\Exception\HttpSpecializedException;

/**
 * Class HttpNotFoundException
 *
 * Represents an exception thrown when the requested resource could not be found on the server.
 * Extends the HttpSpecializedException class from Slim.
 *
 * @package App\Exceptions
 */
class HttpNotFoundException extends HttpSpecializedException
{
    /**
     * @var int The HTTP status code for the exception (404 - Not Found).
     */
    protected $code = 404;

    /**
     * @var string The message associated with the exception.
     */
    protected $message = "Not Found";

    /**
     * @var string The title of the exception.
     */
    protected string $title = "Resource Not Found";

    /**
     * @var string A description of the exception, explaining the cause.
     */
    protected string $description = "The requested resource could not be found on the server.";
}
