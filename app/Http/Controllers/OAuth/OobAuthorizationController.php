<?php

namespace App\Http\Controllers\OAuth;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class OobAuthorizationController extends ApproveAuthorizationController
{
    /**
     * Approve the authorization request.
     */
    public function approve(Request $request, ResponseInterface $psrResponse): Response
    {
        $authRequest = $this->getAuthRequestFromSession($request);
        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(function () use ($authRequest, $psrResponse) {
            $response = $this->server->completeAuthorizationRequest($authRequest, $psrResponse);

            if ($this->isOutOfBandRequest($authRequest)) {
                $code = $this->extractAuthorizationCode($response);

                return response()->json([
                    'code' => $code,
                    'state' => $authRequest->getState(),
                ]);
            }

            return $this->convertResponse($response);
        }, $authRequest->getGrantTypeId() === 'implicit');
    }

    /**
     * Check if the request is an out-of-band OAuth request.
     *
     * @param  AuthorizationRequest  $authRequest
     * @return bool
     */
    protected function isOutOfBandRequest($authRequest)
    {
        return $authRequest->getRedirectUri() === 'urn:ietf:wg:oauth:2.0:oob';
    }

    /**
     * Extract the authorization code from the PSR-7 response.
     *
     * @param  ResponseInterface  $response
     * @return string
     *
     * @throws OAuthServerException
     */
    protected function extractAuthorizationCode($response)
    {
        $location = $response->getHeader('Location')[0] ?? '';

        if (empty($location)) {
            throw OAuthServerException::serverError('Missing authorization code in response');
        }

        parse_str(parse_url($location, PHP_URL_QUERY), $params);

        if (! isset($params['code'])) {
            throw OAuthServerException::serverError('Invalid authorization code format');
        }

        return $params['code'];
    }
}
