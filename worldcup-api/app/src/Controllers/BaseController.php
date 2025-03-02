<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\HttpInvalidInputException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class BaseController
 *
 * An abstract base controller providing common functionality for rendering JSON responses.
 *
 * @package App\Controllers
 */
abstract class BaseController
{
    /**
     * BaseController constructor.
     */
    public function __construct() {}

    /**
     * Renders a JSON response with the provided data and status code.
     *
     * @param Response $response The response object to modify.
     * @param array $data The data to encode into JSON.
     * @param int $status_code The HTTP status code for the response (default is 200).
     *
     * @return Response The modified response object with JSON data and the specified status code.
     */
    protected function renderJson(Response $response, array $data, int $status_code = 200): Response
    {
        // var_dump($data);
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        //-- Write JSON data into the response's body.
        $response->getBody()->write($payload);
        return $response->withStatus($status_code)->withAddedHeader(HEADERS_CONTENT_TYPE, APP_MEDIA_TYPE_JSON);
    }
    /**
     * Validate and process pagination parameters
     * @param Request $request HTTP request object for exception throwing
     * @param array $filters Query parameters array
     * @return array [pageCount, pageSize]
     * @throws HttpInvalidInputException
     */
    protected function validatePaginationParams(Request $request, array $filters): array
    {
        $pageCount = isset($filters["page"])
            ? filter_var($filters["page"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
            : 1;

        $pageSize = isset($filters["page_size"])
            ? filter_var($filters["page_size"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 100]])
            : 5;

        if ($pageCount === false) {
            throw new HttpInvalidInputException(
                $request,
                "Page number must be a positive integer."
            );
        }

        if ($pageSize === false) {
            throw new HttpInvalidInputException(
                $request,
                "Page size must be a positive integer between 1 and 100."
            );
        }

        return [$pageCount, $pageSize];
    }
}
