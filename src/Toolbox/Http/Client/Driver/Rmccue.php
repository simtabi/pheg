<?php

/**
 * JBZoo Toolbox - Http-OpenAiClient
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Http-OpenAiClient
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/Http-Client
 */

declare(strict_types=1);

namespace JBZoo\HttpClient\Driver;

use JBZoo\HttpClient\Exception;
use JBZoo\HttpClient\HttpCodes;
use JBZoo\HttpClient\Options;
use JBZoo\HttpClient\Request;
use JBZoo\HttpClient\Response;
use Requests;

/**
 * Class Rmccue
 * @package JBZoo\HttpClient\Driver
 */
final class Rmccue extends AbstractDriver
{
    private const INVALID_CODE_LINE = HttpCodes::BAD_REQUEST;

    /**
     * @inheritDoc
     */
    public function request(Request $request): Response
    {
        $options = $request->getOptions();

        /**
         * @psalm-suppress PossiblyInvalidArgument
         * @phan-suppress  PhanPartialTypeMismatchArgument
         */
        $httpResult = Requests::request(
            $request->getUri(),
            $request->getHeaders(),
            $request->getArgs(), // @phpstan-ignore-line @phan-suppress-current-line PhanPartialTypeMismatchArgument
            $request->getMethod(),
            self::getDriverOptions($options)
        );

        if ($httpResult->status_code >= self::INVALID_CODE_LINE && $options->allowException()) {
            throw new Exception($httpResult->body, (int)$httpResult->status_code);
        }

        return (new Response())
            ->setCode((int)$httpResult->status_code)
            /** @phan-suppress-next-line PhanPossiblyNonClassMethodCall */
            ->setHeaders($httpResult->headers->getAll())
            ->setBody($httpResult->body)
            ->setRequest($request);
    }

    /**
     * @inheritDoc
     */
    public function multiRequest(array $requestList): array
    {
        $requestResults = [];
        foreach ($requestList as $name => $request) {
            $requestResults[$name] = [
                'url'     => $request->getUri(),
                'data'    => $request->getArgs(),
                'type'    => $request->getMethod(),
                'headers' => $request->getHeaders(),
                'options' => self::getDriverOptions($request->getOptions()),
            ];
        }

        $httpResults = Requests::request_multiple($requestResults);

        $result = [];
        foreach ($httpResults as $name => $httpResult) {
            $result[$name] = (new Response())
                ->setCode((int)$httpResult->status_code)
                ->setHeaders($httpResult->headers->getAll())
                ->setBody($httpResult->body)
                ->setRequest($requestList[$name]);
        }

        return $result;
    }

    /**
     * @param Options $options
     * @return array
     */
    private static function getDriverOptions(Options $options): array
    {
        return [
            'timeout'          => $options->getTimeout(),
            'verify'           => $options->isVerify(),
            'follow_redirects' => $options->isAllowRedirects(),
            'redirects'        => $options->getMaxRedirects(),
            'useragent'        => $options->getUserAgent('Rmccue'),
            'auth'             => $options->getAuth() ?: false,
        ];
    }
}
