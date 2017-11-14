<?php
/**
 * The base class that instantiates and initialises all the moving pieces of
 * the router, and then provides the api for adding routes and reverse routing.
 * 
 * @package    JetRouter
 * @subpackage Router
 */

namespace JetRouter;

class Router
{

  /*** PROPERTIES ***/

  private static $defaults = [
    'namespace' => '',
    'outputFormat' => 'auto'
  ];

  private $routeStore;
  private $reverseRouter;
  private $requestDispatcher;

  /*** STATIC METHODS ***/

  public static function create($args = [])
  {
    $config = array_merge(self::$defaults, $args);
    $router = new self();
    $router->init($config);

    return $router;
  }

  /*** PUBLIC METHODS ***/

  /**
   * Merges passed arguments with router defaults, initializes the other
   * classes adding them as object properties, and finally hooks into WordPress
   * so request can pass through this router before being handed back over.
   *
   * @param   array  $args The router arguments
   * 
   * @return  string  The object's $httpMethod property
   */
  public function init($args = [])
  {
    $config = array_merge(self::$defaults, $args);

    $this->routeStore = new RouteStore($config['namespace']);
    $this->reverseRouter = new ReverseRouter($this->routeStore);
    $this->requestDispatcher = new RequestDispatcher($config['outputFormat']);

    add_action('wp_loaded', [$this, 'run'], 1, 0);
  }

  // Dispatcher

  /**
   * Tries to dispatch the current http request. If the request cannot be dispatched
   * it returns false. When the request matches a route and is successfully dispatched 
   * it exists to avoid WordPress also attempting to dispatch the current http request. 
   *
   * @return  boolean|void  Returns false if the request is not dispatched, exits if the request is dispatched
   */
  public function run()
  {
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $result = $this->dispatch($httpMethod, $path);

    if($result === RequestDispatcher::NOT_DISPATCHED){
      return false;       
    }

    exit;
  }

  /**
   * Dispatches the HTTP request or returns a status code if no routes matches.
   * Look at the RequestDispatcher 'dispatch' method for details as this is
   * just a wrapper for that method.
   *
   * @param  string  $httpMethod  The http method
   * @param  string  $path        The path
   *
   * @return mixed  ( description_of_the_return_value )
   */
  public function dispatch($httpMethod, $path)
  {
    return $this->requestDispatcher->dispatch(
      $this->routeStore,
      $httpMethod,
      $path
    );
  }

  // Route store


  /**
   * Adds a route in the RouteStore object.
   * 
   * Look at the RouteStore 'addRoute' method for details as this is just
   * a wrapper for that method.
   *
   * @param  string    $httpMethod  The route's http method
   * @param  string    $routePath   The route's route path
   * @param  string    $routeName   The route's route name
   * @param  callback  $handler     The route's handler
   */
  public function addRoute($httpMethod, $routePath, $routeName, $handler)
  {
    $this->routeStore->addRoute($httpMethod, $routePath, $routeName, $handler);
  }


  /**
   * Alias method for addRoute method specifying 'GET' as http method
   *
   * @param  string    $routePath  The route's path
   * @param  string    $routeName  The route's name
   * @param  callback  $handler    The route's handler
   */
  public function get($routePath, $routeName, $handler)
  {
    $this->addRoute('GET', $routePath, $routeName, $handler);
  }

  /**
   * Alias method for addRoute method specifying 'HEAD' as http method
   *
   * @param  string    $routePath  The route's path
   * @param  string    $routeName  The route's name
   * @param  callback  $handler    The route's handler
   */
  public function head($routePath, $routeName, $handler)
  {
    $this->addRoute('HEAD', $routePath, $routeName, $handler);
  }

  /**
   * Alias method for addRoute method specifying 'POST' as http method
   *
   * @param  string    $routePath  The route's path
   * @param  string    $routeName  The route's name
   * @param  callback  $handler    The route's handler
   */
  public function post($routePath, $routeName, $handler)
  {
    $this->addRoute('POST', $routePath, $routeName, $handler);
  }

  /**
   * Alias method for addRoute method specifying 'PUT' as http method
   *
   * @param  string    $routePath  The route's path
   * @param  string    $routeName  The route's name
   * @param  callback  $handler    The route's handler
   */
  public function put($routePath, $routeName, $handler)
  {
    $this->addRoute('PUT', $routePath, $routeName, $handler);
  }

  /**
   * Alias method for addRoute method specifying 'PATCH' as http method
   *
   * @param  string    $routePath  The route's path
   * @param  string    $routeName  The route's name
   * @param  callback  $handler    The route's handler
   */
  public function patch($routePath, $routeName, $handler)
  {
    $this->addRoute('PATCH', $routePath, $routeName, $handler);
  }

  /**
   * Alias method for addRoute method specifying 'DELETE' as http method
   *
   * @param  string    $routePath  The route's path
   * @param  string    $routeName  The route's name
   * @param  callback  $handler    The route's handler
   */
  public function delete($routePath, $routeName, $handler)
  {
    $this->addRoute('DELETE', $routePath, $routeName, $handler);
  }

  /**
   * Alias method for addRoute method specifying 'OPTIONS' as http method
   *
   * @param  string    $routePath  The route's path
   * @param  string    $routeName  The route's name
   * @param  callback  $handler    The route's handler
   */
  public function options($routePath, $routeName, $handler)
  {
    $this->addRoute('OPTIONS', $routePath, $routeName, $handler);
  }

  // Reverse router
  
  /**
   * Returns a full route path by pulling the route data from the RouteStore object 
   * and then (for dynamic routes) passing this method's parameters as route params.
   * 
   * @return  string  The full route path
   */
  public function getThePath()
  {
    return call_user_func_array( [$this->reverseRouter, 'getThePath'], func_get_args() );
  }

  /**
   * Prints a full route path by pulling the route data from the RouteStore object 
   * and then (for dynamic routes) passing this method's parameters as route params.
   */
  public function thePath()
  {
    echo call_user_func_array( [$this, 'getThePath'], func_get_args() );
  }
  
}