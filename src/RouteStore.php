<?php
/**
 * Stores routes data and provids methods to retrieve them
 * 
 * @package    JetRouter
 * @subpackage Router
 */

namespace JetRouter;

class RouteStore
{

  /**
   * The namespace regex (standard alphanumeric plus the "_", "-", "." and "/" characters).
   * it also does not allow consecutive "/" characters
   */
  const NAMESPACE_REGEX = '~(?!.*(\/)\1)^([a-zA-Z0-9_\-\.\/])+$~';

  const CHUNK_SIZE = 10;

  /**
   * The status code returned when a route is not found.
   */
  const NOT_FOUND = 0;


  /*** STATIC METHODS ***/

  /**
   * Determines if the route path passed as argument is a static route path.
   *
   * @param  string  $routePath  The route path
   *
   * @return boolean  True if the route passed as argument is a static route path, False otherwise.
   */
  public static function isStaticRoutePath($routePath)
  {
    return strpos($routePath, '{') === false && strpos($routePath, '}') === false;
  }


  /*** PROPERTIES ***/

  private $namespace = '';
  private $staticRoutes = [];
  private $dynamicRoutesData = [];
  private $dynamicRoutes = [];


  /*** PUBLIC METHODS ***/

  /**
   * Parses the namespace and then sets it as object property
   *
   * @param  string  $namespace  The router namespace
   */
  public function __construct($namespace)
  {
    $this->namespace = $this->parseNamespace($namespace);
  }

  /**
   * Adds a route to this object.
   * 
   * Makes sure the route name is unique, then prepends the router namespace
   * to the route path and finally it calls the appropriate add route method
   * depending on route type (static/dynamic).
   *
   * @param  string    $httpMethod  The route's http method
   * @param  string    $routePath   The route's path
   * @param  string    $routeName   The route's name
   * @param  callback  $handler     The handler
   */
  public function addRoute($httpMethod, $routePath, $routeName, $handler)
  {
    $this->validateRouteNameUniqueness($routeName);
    $routePath = $this->preparePath($routePath);

    $add = self::isStaticRoutePath($routePath) ? 'addStaticRoute' : 'addDynamicRoute';

    $this->$add($httpMethod, $routePath, $routeName, $handler);
  }

  /**
   * Looks for a route by request http method and request path.
   * 
   * Tries to establish if it should look for the route at all. If it should, it
   * looks for a static route first, then for a dynamic one if no static route was 
   * found. If a static or dynamic route is found, that route is returned, otherwise
   * the RouteStore::NOT_FOUND status code is returned.
   *
   * @param  string  $requestHttpMethod  The request http method
   * @param  string  $requestPath        The request path
   *
   * @return integer|array  Returns the RouteStore::NOT_FOUND status code (int) if not found, The route array if a route is found
   */
  public function findRouteByRequestMethodAndPath($requestHttpMethod, $requestPath)
  {
    $requestPath = trim($requestPath, ' /');
    
    if( ! $this->shouldLookForRoute($requestPath) ){
      return self::NOT_FOUND;
    }

    $route = $this->findStaticRoute($requestHttpMethod, $requestPath);

    if( ! $route ){
      $route = $this->findDynamicRoute($requestHttpMethod, $requestPath);

      if( ! $route ){
        $route = self::NOT_FOUND;
      }
    }

    return $route;
  }

  /**
   * Looks for a static route by name.
   * 
   * Alias method for getRouteByName method specifying static routes as the
   * routes to be searched.
   *
   * @param  string  $routeName  The route name
   *
   * @return integer|array  Returns the RouteStore::NOT_FOUND status code (int) if not found, The route array if a route is found
   */
  public function getStaticRouteByName($routeName)
  {
    return $this->getRouteByName(
      $this->staticRoutes,
      $routeName
    );
  }

  /**
   * Looks for a dynamc route by name.
   * 
   * Alias method for getRouteByName method specifying dynamc routes as the
   * routes to be searched.
   *
   * @param  string  $routeName  The route name
   *
   * @return integer|array  Returns the RouteStore::NOT_FOUND status code (int) if not found, The route array if a route is found
   */
  public function getDynamicRouteByName($routeName, $args = [])
  {
    return $this->getRouteByName(
      $this->dynamicRoutesData,
      $routeName,
      $args
    );
  }


  /*** PRIVATE METHODS ***/

  /**
   * Parses the router namespace.
   * 
   * Makes sure that if the namespace is not empty it matches the regex 
   * specified in the NAMESPACE_REGEX constant of this class. Trims whitespace
   * and leading/trailing forward slashes. 
   *
   * @param  string  $namespace  The namespace
   *
   * @throws Exception\InvalidNamespaceException  If the namespace is not empty and does not match the namespace regex
   *
   * @return  string  The parsed router namespace
   */
  private function parseNamespace($namespace)
  {
    if( 
      ! empty($namespace) && 
      ! preg_match(self::NAMESPACE_REGEX, $namespace)
    ){
      throw new Exception\InvalidNamespaceException(
        "'$namespace' is not a valid namespace",
        1
      );
    }

    return trim($namespace, ' /');
  }

  /**
   * Makes sure the route name passed as argument is not used for another route already.
   * 
   * Looks for static and dynamic routes already using the route name passed as 
   * argument, throws an Exception if it finds one. Returns not found status code otherwise.
   *
   * @param  $routeName  The route's name
   *
   * @throws Exception\InvalidRouteException  If a route with the route name passed as argument is found
   *
   * @return integer|void  Returns the RouteStore::NOT_FOUND status code (int) if no route is found, void otherwise
   */
  private function validateRouteNameUniqueness($routeName)
  {
    $route = $this->getStaticRouteByName($routeName);

    if($route === self::NOT_FOUND){      
      $route = $this->getDynamicRouteByName($routeName);

      if($route === self::NOT_FOUND){
        return self::NOT_FOUND;
      }
    }

    throw new Exception\InvalidRouteException(
      "A route named '$routeName' already exists."
    );
  }

  /**
   * Adds a static route to this object.
   * 
   * Parses the route data passed to this method as arguments through the
   * StaticRouteParser class, then makes sure no conflicting route already exists
   * and finally it adds the route data to the staticRoutes property of this object.
   *
   * @param  string    $httpMethod  The route's http method
   * @param  string    $routePath   The route's path
   * @param  string    $routeName   The route's name
   * @param  callback  $handler     The route's handler
   *
   * @throws Exception\InvalidRouteException  If a route with the same http method and path already exists
   */
  private function addStaticRoute($httpMethod, $routePath, $routeName, $handler)
  {
    $route = new StaticRouteParser($httpMethod, $routePath, $routeName, $handler);
    $httpMethod = $route->getHttpMethod();
    $routePath = $route->getPath();
    $routeName = $route->getName();
    $handler = $route->getHandler();

    if ( isset( $this->staticRoutes[$routePath][$httpMethod]) ){
      throw new Exception\InvalidRouteException(
        "Cannot register two routes matching '$routePath' for method '$httpMethod'"
      );
    }

    $this->staticRoutes[$routePath][$httpMethod] = [
      'routeName' => $routeName,
      'routeHandler' => $handler,
      'routeArgs' => []
    ];
  }

  /**
   * Adds a dynamic route.
   *
   * @param      <type>                           $httpMethod  The http method
   * @param      <type>                           $routePath   The route path
   * @param      <type>                           $routeName   The route name
   * @param      <type>                           $handler     The handler
   *
   * @throws     Exception\InvalidRouteException  (description)
   */
  private function addDynamicRoute($httpMethod, $routePath, $routeName, $handler)
  {
    $route = new DynamicRouteParser($httpMethod, $routePath, $routeName, $handler);

    $regex = $route->getRegex();
    $paramsNames = $route->getParamsNames();
    $segments = $route->getSegments();

    if( isset( $this->dynamicRoutesData[$regex][$httpMethod] ) ){
       throw new Exception\InvalidRouteException("Cannot register two routes matching '$regex' for method '$httpMethod'");
    }

    $this->dynamicRoutesData[$regex][$httpMethod] = [
      'routeName' => $routeName,
      'routeHandler' => $handler,
      'routeArgs' => $paramsNames,
      'routeSegments' => $segments
    ];
  }

  /**
   * Parses the dynamic route data and generates the dynamic routes.
   * 
   * Explanation of the group position based, non chunked dispatching 
   * implementation adopted by this router:
   * 
   * The router splits the dynamic routes data array in chunks whose size is 
   * defined in the CHUNK_SIZE constant of this class. It then iterates
   * over the chunks to merge all the regexes in the chunk into a single
   * regex containing the regex expressions in a single OR group.
   * 
   * The PCRE regex "?|" non-capturing group type is used to avoid ending
   * up with a massive amount of unneeded capturing groups (bad performance).
   * However, doing this also means losing separate group numbers which would 
   * have allowed us to know which of the regexes in the OR group matched.
   * 
   * This is solved by adding dummy groups to each individual route, making the
   * matches size of each regex unique.
   * 
   * This unique number is also then used to map each of the regexes in the 
   * OR group to its route data (handlers) in the routeMap array, i.e. the 
   * unique number is used as index in the routeMap array.
   * 
   * This regexes OR merge and the subsequent procedure outline above 
   * is repeated for each chunk of routes.
   * 
   * @return  boolean  Returns False if the dynamic route data array is empty
   */
  private function generateDynamicRoutes()
  {
    $dynamicRoutesN = count($this->dynamicRoutesData);

    if( ! $dynamicRoutesN ){
      return false;
    }

    $partsN = max(1, round( $dynamicRoutesN / self::CHUNK_SIZE ) );
    $chunkSize = ceil($dynamicRoutesN / $partsN);
    $chunks = array_chunk( $this->dynamicRoutesData, $chunkSize, true);
    $dynamicRoutes = [];

    foreach($chunks as $chunk){
      $routeMap = [];
      $regexes = [];
      $groupsN = 0;

      foreach($chunk as $regex => $routes){
        $firstRoute = reset($routes);
        $variablesN = count($firstRoute['routeArgs']);
        $groupsN = max($groupsN, $variablesN);

        $regexes[] = $regex . str_repeat('()', $groupsN - $variablesN);

        foreach ($routes as $httpMethod => $route) {
          $routeMap[$groupsN + 1][$httpMethod] = $route;
        }

        $groupsN++;
      }

      $regex = '~^(?|' . implode('|', $regexes) . ')$~';

      $dynamicRoutes[] = [ 'routeRegex' => $regex, 'routeMap' => $routeMap ];
    }

    $this->dynamicRoutes = $dynamicRoutes;
  }

  /**
   * Looks for a static route by http method and request path
   *
   * @param  string  $httpMethod   The http method
   * @param  string  $requestPath  The request path
   *
   * @return array|boolean  Returns the route array if the route is found, False otherwise
   */
  private function findStaticRoute($httpMethod, $requestPath)
  {
    if( ! isset($this->staticRoutes[$requestPath][$httpMethod]) ){
      return false;
    }

    return $this->staticRoutes[$requestPath][$httpMethod];
  }

  /**
   * Looks for a dynamic route by http method and request path.
   * 
   * Generates the dynamic routes chunks from the dynamic routes data. This is
   * done only at "find" time to avoid doing this work if a static route matched
   * the request we are trying to route.
   * 
   * If both route regex match and http method handler are found, arguments are
   * extracted from the route path, added to the route array, and the route array
   * is then returned.
   *
   * @param  string  $httpMethod   The http method
   * @param  string  $requestPath  The request path
   *
   * @return array|boolean  Returns the route array if the route is found, False otherwise
   */
  private function findDynamicRoute($httpMethod, $requestPath)
  {
    $this->generateDynamicRoutes();

    foreach($this->dynamicRoutes as $data){
      if ( ! preg_match( $data['routeRegex'], $requestPath, $matches ) ){
        continue;
      }

      $matchesN = count($matches);

      while( ! isset( $data['routeMap'][$matchesN++] ) );

      $routes = $data['routeMap'][$matchesN - 1];

      foreach(array_keys($routes[$httpMethod]['routeArgs']) as $i => $varName){
        if( ! isset($matches[$i + 1]) || $matches[$i + 1] === '' ){
          unset($routes[$httpMethod]['routeArgs'][$i]);
          continue;
        }

        $routes[$httpMethod]['routeArgs'][$varName] = $matches[$i + 1];
      }

      return $routes[$httpMethod];
    }

    return false;
  }

  /**
   * Looks for a route by name.
   * 
   * Loops over the routes passed as argument and if one with the same name is
   * found, it does some parsing and it returnes the route data array.
   *
   * @param  array   $routes     The routes array
   * @param  string  $routeName  The name of the route to find
   * @param  array   $args       The arguments to pass to the route data array (dynamic routes only)
   *
   * @return  array|integer   The route data array if a route is found, RouteStore::NOT_FOUND status code (int) otherwise
   */
  private function getRouteByName($routes, $routeName, $args = null)
  {
    foreach($routes as $routePath => $httpHandlers){
      foreach($httpHandlers as $httpHandler){
        if( $httpHandler['routeName'] === $routeName ){
          $route = [ 
            'routeName' => $routeName, 
            'routePath' => $routePath,
            'routeArgs' => $args
          ];

          // only defined in dynamic routes
          if( isset($httpHandler['routeSegments']) ){
            $routeSegments = $httpHandler['routeSegments'];
            $route['routeSegments'] = $routeSegments;
          }

          return $route;
        }
      }
    }

    return self::NOT_FOUND;
  }

  /**
   * Determines if the RouteStore object needs to look for a route matching 
   * the request path passed as argument.
   *
   * @param  string  $requestPath  The request path
   *
   * @return boolean  The boolean result
   */
  private function shouldLookForRoute($requestPath)
  {
    return ! $this->namespace || 0 === strpos($requestPath, $this->namespace);
  }

  /**
   * Returns the route path passed as argument with the router namespace prepended to it
   *
   * @param  string  $routePath  The route path
   *
   * @return string  The route path with the prepended router namespace
   */
  private function preparePath($routePath)
  {
    $routePath = trim($routePath, ' /');

    if( ! $this->namespace ){
      return $routePath;
    }
    
    return $this->namespace . '/' . $routePath;
  }
  
}