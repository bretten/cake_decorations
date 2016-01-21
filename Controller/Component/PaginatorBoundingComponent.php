<?php
App::uses('Component', 'Controller');

/**
 * Provides support functionality to the standard PaginatorComponent
 *
 * Makes sure the PaginatorComponent won't try to query a page that doesn't exist by
 * performing an initial query that counts the number of rows and determining the
 * maximum page count
 *
 * @author https://github.com/bretten
 */
class PaginatorBoundingComponent extends Component {

    /**
     * Settings
     *
     * @var array
     */
    public $settings = array(
        'limit' => 20
    );

    /**
     * Request object
     *
     * @var CakeRequest
     */
    public $request;

    /**
     * A reference to the Controller's PaginatorComponent
     *
     * @var PaginatorComponent
     */
    public $Paginator;

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
     * @param Controller $controller
     * @return void
     */
    public function initialize(Controller $controller) {
        $this->request = $controller->request;
        $this->Paginator = $controller->Paginator;
    }

    /**
     * Sets the query limit of the Paginator query
     *
     * @param int $limit The object result limit
     * @throws CakeException
     */
    public function setLimit($limit = 0) {
        if (is_int($limit)) {
            $this->settings['limit'] = $limit;
        } else {
            throw new CakeException('The limit must be an integer.');
        }
    }

    /**
     * Checks to make sure that the current query's page does not exceed the actual number of pages
     *
     * @param Model $queryModel The model the query will be carried out on
     * @param array $query The query/settings that will be passed to the PaginatorComponent
     * @return void
     */
    public function checkBounds(Model $queryModel, $query = array()) {
        // Make sure that the current page does not exceed the actual number of pages
        if (isset($this->request->params['named']['page'])) {
            // Get the number of rows in the query
            $count = $queryModel->find('count', $query);
            // Determine the number of pages based off the number of rows
            $pageCount = ceil($count / $this->settings['limit']);
            // If the current page exceeds the maximum page count, change the page to the last page
            if ($this->request->params['named']['page'] > $pageCount) {
                $this->request->params['named']['page'] = $pageCount;
            }
        }
    }

}
