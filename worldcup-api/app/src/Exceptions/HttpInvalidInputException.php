<?php

namespace App\Exceptions;

use Slim\Exception\HttpSpecializedException;

/**
 * Class HttpInvalidInputException
 *
 * Represents an exception thrown when the request contains invalid parameters.
 * Extends the HttpSpecializedException class from Slim.
 *
 * @package App\Exceptions
 */
class HttpInvalidInputException extends HttpSpecializedException
{
    /**
     * @var int The HTTP status code for the exception (400 - Bad Request).
     */
    protected $code = 400;

    /**
     * @var string The message associated with the exception.
     */
    protected $message = 'Bad Request.';

    /**
     * @var string The title of the exception.
     */
    protected string $title = 'Invalid Input';

    /**
     * @var string A description of the exception, explaining the cause.
     */
    protected string $description = 'The request contains invalid parameters.';
}
