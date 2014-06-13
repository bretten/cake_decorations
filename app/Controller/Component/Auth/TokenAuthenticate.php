<?php

App::uses('BaseAuthenticate', 'Controller/Component/Auth');

/**
 * Token-based authentication transmitted via HTTP headers.
 *
 * @author https://github.com/bretten
 */
class TokenAuthenticate extends BaseAuthenticate {

/**
 * Settings
 *
 * @var array
 */
    public $settings = array(
        'userModel' => 'User',
        'passwordField' => 'password',
        'tokenField' => 'token',
        'authHeader' => 'Authorization'
    );

/**
 * Constructor
 *
 * @param ComponentCollection $collection
 * @param array $settings
 */
    public function __construct(ComponentCollection $collection, $settings = array()) {
        $this->_Collection = $collection;
        $this->settings = array_merge($this->settings, $settings);
        if (!empty($this->components)) {
            $this->_componentMap = ComponentCollection::normalizeObjectArray($this->components);
        }
    }

/**
 * Authenticate a User by checking the request for the token.
 *
 * @param CakeRequest $request
 * @param CakeResponse $response
 * @return mixed
 */
    public function authenticate(CakeRequest $request, CakeResponse $response) {
        return $this->getUser($request);
    }

/**
 * Handles the case where authentication fails.
 *
 * @param CakeRequest $request
 * @param CakeResponse $response
 * @return mixed|void
 * @throws UnauthorizedException
 */
    public function unauthenticated(CakeRequest $request, CakeResponse $response) {
        $Exception = new UnauthorizedException();
        throw $Exception;
    }

/**
 * Examines the request and gets the data used for authentication.
 *
 * @param CakeRequest $request
 * @return bool|mixed
 */
    public function getUser(CakeRequest $request) {
        // Get the headers
        $headers = apache_request_headers();

        // Get the token
        $token = base64_decode($headers[$this->settings['authHeader']]);

        // Get the User data
        $result = ClassRegistry::init($this->settings['userModel'])->find('first', array(
            'conditions' => array(
                $this->settings['userModel'] . "." . $this->settings['tokenField'] => $token
            ),
            'recursive' => -1,
            'callbacks' => false
        ));

        // Remove the password
        if (isset($result[$this->settings['userModel']][$this->settings['passwordField']])) {
            unset($result[$this->settings['userModel']][$this->settings['passwordField']]);
        }

        return $result[$this->settings['userModel']];
    }

} 