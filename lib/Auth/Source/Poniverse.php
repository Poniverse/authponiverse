<?php

use League\OAuth2\Client\Provider\AbstractProvider;

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/oauth/libextinc/OAuth.php');

/**
 * Authenticate using Poniverse.
 *
 * @author Adam Lavin, Poniverse.
 */
class sspmod_authponiverse_Auth_Source_Poniverse extends SimpleSAML_Auth_Source
{

    /**
     * The string used to identify our states.
     */
    const STAGE_INIT = 'authponiverse:init';

    /**
     * The key of the AuthId field in the state.
     */
    const AUTHID = 'authponiverse:AuthId';

    const URL = 'https://poniverse.net';

    private $key;

    private $secret;

    /**
     * @var \Poniverse\Lib\Client
     */
    private $client;

    /**
     * Constructor for this authentication source.
     *
     * @param array $info Information about this authentication source.
     * @param array $config Configuration.
     */
    public function __construct($info, $config)
    {
        assert('is_array($info)');
        assert('is_array($config)');

        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        if (!array_key_exists('key', $config))
            throw new Exception('Poniverse authentication source is not properly configured: missing [key]');

        $this->key = $config['key'];

        if (!array_key_exists('secret', $config))
            throw new Exception('Poniverse authentication source is not properly configured: missing [secret]');

        $this->secret = $config['secret'];

        $this->client = new \Poniverse\Lib\Client(
            $this->key,
            $this->secret,
            new GuzzleHttp\Client()
        );
    }

    /**
     * Log-in using Poniverse platform
     * Documentation at: http://developer.poniverse.com/docs/DOC-1008
     *
     * @param array &$state Information about the current authentication.
     */
    public function authenticate(&$state)
    {
        assert('is_array($state)');

        // We are going to need the authId in order to retrieve this authentication source later
        $state[self::AUTHID] = $this->authId;
        $stateID = SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);

        $linkback = SimpleSAML\Module::getModuleURL('authponiverse/linkback.php');

        SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);

        //?response_type=code&client_id=%s&redirect_uri=%s&state=%s';

        $provider = $this->client->getOAuthProvider([
            'redirectUri' => $linkback,
        ]);

        $provider->authorize([
            'state' => $stateID
        ]);
    }


    public function finalStep(&$state)
    {
        $linkback = SimpleSAML\Module::getModuleURL('authponiverse/linkback.php');

        $provider = $this->client->getOAuthProvider([
            'redirectUri' => $linkback,
        ]);

        $provider->apiDomain = 'https://poniverse.net';
        // Replace the request token with an access token (via GET method)
        $accessToken = $provider->getAccessToken('authorization_code', ['code' => $state['code']]);


        SimpleSAML\Logger::debug("Got an access token from the OAuth service provider [" .
            $accessToken->getToken() . "] with the refresh token [" . $accessToken->getRefreshToken() . "]");

        $request = $provider->getAuthenticatedRequest(AbstractProvider::METHOD_GET, 'https://api.poniverse.net/v1/users/me', $accessToken);

        $userdata = $provider->getResponse($request);

        $attributes = array();
        foreach ($userdata AS $key => $value) {
            if (is_string($value))
                $attributes['poniverse.' . $key] = array((string)$value);

        }

        // TODO: pass accessToken: key, secret + expiry as attributes?

        if (array_key_exists('id', $userdata)) {
            $attributes['poniverse_targetedID'] = array('https://poniverse.net!' . $userdata['id']);
            $attributes['poniverse_user'] = array($userdata['id'] . '@poniverse.net');
        }

        SimpleSAML\Logger::debug('Poniverse Returned Attributes: ' . implode(", ", array_keys($attributes)));

        $state['Attributes'] = $attributes;
    }
}
