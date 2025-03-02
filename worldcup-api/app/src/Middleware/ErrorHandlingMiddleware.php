<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpException;
use Nyholm\Psr7\Response as SlimResponse;
use Nyholm\Psr7\Stream;

class ErrorHandlingMiddleware
{
    public function __invoke(Request $request, Response $response, callable $next)
    {
        try {
            return $next($request, $response);
        } catch (HttpException $e) {
            $statusCode = $e->getCode();
            $error = [
                'error' => [
                    'code' => $statusCode,
                    'message' => $e->getMessage()
                ]
            ];
            $response = (new SlimResponse())
                ->withStatus($statusCode)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(new Stream(fopen('php://temp', 'r+')));
            $response->getBody()->write(json_encode($error));
            return $response;
        } catch (\Exception $e) {
            $error = [
                'error' => [
                    'code' => 500,
                    'message' => 'An unexpected error occurred.'
                ]
            ];
            $response = (new SlimResponse())
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(new Stream(fopen('php://temp', 'r+')));
            $response->getBody()->write(json_encode($error));
            return $response;
        }
    }
}
