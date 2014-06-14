<?php

App::uses('Component', 'Controller');

/**
 * Stateless and token-based authentication
 *
 * This authentication component is used by specifying which Controller actions will use stateless authentication so
 * that a whole Controller does not have to rely on it.  It is meant to be used along with the normal AuthComponent.
 *
 * @author https://github.com/bretten
 */
class StatelessAuthComponent extends Component {

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
 * Components
 *
 * @var array
 */
    public $components = array('Auth');

/**
 * A mapping of Controller actions that will use stateless authentication
 *
 * @var array
 */
    public $map;

/**
 * Request object
 *
 * @var CakeRequest
 */
    public $request;

/**
 * Response object
 *
 * @var CakeResponse
 */
    public $response;

/**
 * The authenticated User
 *
 * @var array
 */
    protected static $_user = array();

/**
 * Constructor
 *
 * @param ComponentCollection $collection
 * @param array $settings
 */
    public function __construct(ComponentCollection $collection, $settings = array()) {
        $this->_Collection = $collection;
        $this->settings = array_merge($this->settings, $settings);
        $this->_set($settings);
        if (!empty($this->components)) {
            $this->_componentMap = ComponentCollection::normalizeObjectArray($this->components);
        }
    }

/**
 * Called before the Controller's beforeFilter
 *
 * Checks if the current Controller action is in the map.  If it is, this components stateless authentication will be used.
 *
 * @param Controller $controller
 */
    public function initialize(Controller $controller) {
        // Get the request and response
        $this->request = $controller->request;
        $this->response = $controller->response;

        // Check if this Controller and the current Controller action is in the map
        if (array_key_exists($this->request->params['controller'], $this->map)
            && in_array($this->request->params['action'], $this->map[$this->request->params['controller']])
        ) {
            // Make the action accessible without being logged in
            $this->Auth->allow($this->request->params['action']);
            // Get the headers
            $headers = apache_request_headers();
            // Authenticate
            if (!isset($headers[$this->settings['authHeader']]) || !$this->authenticate($headers[$this->settings['authHeader']])) {
                $this->unauthenticated();
            }
        }
    }

/**
 * Authenticates a User by checking their token
 *
 * @param $auth
 * @return bool
 */
    public function authenticate($auth) {
        $token = base64_decode($auth);

        $result = ClassRegistry::init($this->settings['userModel'])->find('first', array(
            'conditions' => array(
                $this->settings['userModel'] . "." . $this->settings['tokenField'] => $token
            ),
            'recursive' => -1,
            'callbacks' => false
        ));

        if (isset($result[$this->settings['userModel']][$this->settings['passwordField']])) {
            unset($result[$this->settings['userModel']][$this->settings['passwordField']]);
        }

        self::$_user = $result[$this->settings['userModel']];

        return (boolean)$result;
    }

/**
 * Handles an unauthenticated request
 *
 * @throws UnauthorizedException
 */
    public function unauthenticated() {
        $Exception = new UnauthorizedException();
        throw $Exception;
    }

/**
 * Returns information about the authenticated user
 *
 * @param null $key
 * @return array|mixed|null
 */
    public static function user($key = null) {
        if (!empty(self::$_user)) {
            $user = self::$_user;
        } else {
            return null;
        }
        if ($key === null) {
            return $user;
        }
        return Hash::get($user, $key);
    }

} 