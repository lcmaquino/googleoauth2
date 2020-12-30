<?php

namespace Lcmaquino\GoogleOAuth2;

use Lcmaquino\GoogleOAuth2\GoogleUser;
use Lcmaquino\GoogleOAuth2\HttpClient;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GoogleOAuth2Manager
{
    /**
     * The HTTP Client instance.
     *
     * @var \Lcmaquino\HttpClient
     */
    protected $httpClient;

    /**
     * The client ID.
     *
     * @var string
     */
    protected $clientId;

    /**
     * The client secret.
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * The redirect URL.
     *
     * @var string
     */
    protected $redirectUri;

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * The custom parameters to be sent with the request.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * The type of the encoding in the query.
     *
     * @var int Can be either PHP_QUERY_RFC3986 or PHP_QUERY_RFC1738.
     */
    protected $encodingType = PHP_QUERY_RFC1738;

    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = false;

    /**
     * The cached user instance.
     *
     * @var \Lcmaquino\MiniGoogleClient\GoogleUser|null
     */
    protected $user;

    /**
     * Create a new GoogleClient instance.
     * 
     * @param  array  $config
     * @return void
     */
    public function __construct($config = [])
    {
        $this->clientId = $config['client_id'];
        $this->redirectUri = $config['redirect_uri'];
        $this->clientSecret = $config['client_secret'];
        $this->scopes = [
            'openid',
            'email'
        ];
        $this->httpClient = new HttpClient();
    }

    /**
     * Set the HTTP client instance.
     *
     * @param  \Lcmaquino\MiniGoogleClient\HttpClient  $client
     * @return $this
     */
    protected function setHttpClient(HttpClient $client)
    {
        $this->httpClient = $client;
        return $this;
    }

    /**
     * Get a instance of the HTTP client.
     *
     * @return \Lcmaquino\MiniGoogleClient\HttpClient
     */
    protected function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Set the redirect URL.
     *
     * @param  string  $url
     * @return $this
     */
    public function setRedirectUri($url)
    {
        $this->redirectUri = $url;

        return $this;
    }

    /**
     * get the redirect URI.
     *
     * @param  string  $url
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * Set the scopes of the requested access.
     *
     * @param  array $scopes
     * @return $this
     */
    public function scopes($scopes = [])
    {
        $this->scopes = array_unique($scopes);

        return $this;
    }

    /**
     * Get the current scopes.
     *
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Set the custom parameters of the request.
     *
     * @param  array  $parameters
     * @return $this
     */
    public function with(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get the authentication URL for Google OAuth 2.0 API.
     *
     * @param  string  $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        $base = 'https://accounts.google.com/o/oauth2/auth';

        return $this->buildAuthUrlFromBase($base, $state);
    }

    /**
     * Get the token URL for the Google OAuth 2.0 API.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return 'https://accounts.google.com/o/oauth2/token';
    }

    /**
     * Get the user info URL for the Google OAuth 2.0 API.
     *
     * @return string
     */
    protected function getUserInfoUrl()
    {
        return 'https://www.googleapis.com/oauth2/v3/userinfo';
    }

    /**
     * Get the revoke token URL for the Google OAuth 2.0 API.
     *
     * @return string
     */
    protected function getRevokeTokenUrl()
    {
        return 'https://accounts.google.com/o/oauth2/revoke';
    }

    /**
     * Get the GET fields for the code request.
     *
     * @param  string|null  $state
     * @return array
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->formatScopes($this->getScopes()),
            'response_type' => 'code',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code = '')
    {
        return [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * Get the POST fields for the refresh token request.
     *
     * @param  string  $refresh_token
     * @return array
     */
    protected function getRefreshTokenFields($refresh_token = '')
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
        ];
    }

    /**
     * Get the GET fields for the revoke token request.
     *
     * @param  string  $token
     * @return array
     */
    protected function getRevokeTokenFields($token = '')
    {
        return [
            'token' => $token,
        ];
    }

    /**
     * Build the authentication URL from the given base URL and state.
     *
     * @param  string  $url
     * @param  string  $state
     * @return string
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        $query = http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);
        return $url . '?' . $query;
    }

    /**
     * Format the given scopes.
     *
     * @param  array  $scopes
     * @return string
     */
    protected function formatScopes(array $scopes)
    {
        $scopeSeparator = ' ';
        return implode($scopeSeparator, $scopes);
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     */
    protected function getAccessTokenResponse($code = '')
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), $this->getTokenFields($code));

        return $response;
    }

    /**
     * Get the raw user attributes for the given access token.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserInfoResponse($token)
    {
        $response = $this->getHttpClient()->get($this->getUserInfoUrl(), [
            'access_token' => $token,
        ]);

        return $response;
    }

    /**
     * Get the refresh token response.
     *
     * @param  string  $refresh_token
     * @return array
     */
    protected function getRefreshTokenResponse($refresh_token = '')
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), $this->getRefreshTokenFields($refresh_token));

        return $response;
    }
    
    /**
     * Get the revoke token response.
     *
     * @param  string  $token
     * @return array
     */
    protected function getRevokeTokenResponse($token = '')
    {
        $response = $this->getHttpClient()->get($this->getRevokeTokenUrl(), $this->getRevokeTokenFields($token));

        return $response;
    }

    /**
     * Map the raw user array to a Google User instance.
     *
     * @param  array  $user
     * @return \Lcmaquino\MiniGoogleClient\GoogleUser
     */
    protected function mapUserToObject(array $user)
    {
        return (new GoogleUser)->setRaw($user)->map([
            'sub' => Arr::get($user, 'sub'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
            'emailVerified' => Arr::get($user, 'email_verified'),
            'picture' => Arr::get($user, 'picture'),
        ]);
    }

    /**
     * Determine if the Google Client is operating with state.
     *
     * @return bool
     */
    protected function usesState()
    {
        return ! $this->stateless;
    }

    /**
     * Determine if the Google Client is operating as stateless.
     *
     * @return bool
     */
    protected function isStateless()
    {
        return $this->stateless;
    }

    /**
     * Indicates that the Google Client should operate as stateless.
     *
     * @return $this
     */
    public function stateless()
    {
        $this->stateless = true;

        return $this;
    }

    /**
     * Get the string used for session state.
     *
     * @return string
     */
    protected function getState()
    {
        return Str::random(40);
    }

    /**
     * Determine if the current request / session has a mismatching "state".
     * 
     * @param  Illuminate\Http\Request $request
     * @return bool
     */
    protected function hasInvalidState(Request $request)
    {
        if ($this->isStateless()) {
            return false;
        }

        $state = $request->session()->pull('state');

        return ! (strlen($state) > 0 && $request->input('state') === $state);
    }

    /**
     * Redirect the user from the application to the Google authentication screen.
     *
     * @param  Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(Request $request)
    {
        $state = null;

        if ($this->usesState()) {
            $request->session()->put('state', $state = $this->getState());
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * Get the user coming from Google authentication.
     *
     * @param  Illuminate\Http\Request $request
     * @return \Lcmaquino\MiniGoogleClient\GoogleUser|null
     */
    public function getUserFromAuth(Request $request)
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState($request)) {
            return null;
        }

        $response = $this->getAccessTokenResponse($request->input('code'));
        $token = Arr::get($response, 'access_token');

        if(empty($token)) {
            return null;
        }

        $this->user = $this->getUserFromToken($token);

        return $this->user->setRefreshToken(Arr::get($response, 'refresh_token'))
                    ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * Get a Google User instance from a known access token.
     *
     * @param  string  $token
     * @return \Lcmaquino\MiniGoogleClient\GoogleUser
     */
    public function getUserFromToken($token)
    {
        $response = $this->getUserInfoResponse($token);

        if(empty($response) || isset($response['error'])) {
            return null;
        }

        $user = $this->mapUserToObject($response);

        return $user->setToken($token);
    }

    /**
     * Refresh the user's token and returns the new one.
     * Returns null if the token was not refreshed.
     * 
     * @param  string  $refresh_token
     * @return string|null
     */
    public function refreshUserToken($refresh_token = ''){
        $response = $this->getRefreshTokenResponse($refresh_token);

        return isset($response['access_token']) ? $response['access_token'] : null;
    }

    /**
     * Revoke the user's access token and refresh token (at the same time).
     * The $token parameter can be the access token or the refresh token.
     * 
     * Returns true if the token was revoked.
     * 
     * @param  string  $token
     * @return boolean
     */
    public function revokeToken($token = ''){
        $response = $this->getRevokeTokenResponse($token);
        
        return empty($response);
    }
}