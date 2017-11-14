<?php
/**
 * Builds a full route path by accessing the route data contained in the RouteStore
 * object and then (for dynamic routes) adding the arguments passed to this object.
 * 
 * @package    JetRouter
 * @subpackage Router
 */

namespace JetRouter;

class ReverseRouter
{

  /*** PROPERTIES ***/

  private $routeStore;


  /*** PUBLIC METHODS ***/

  /**
   * Adds a $routeStore object to this object as property.
   *
   * @param  RouteStore  $routeStore  The RouteStore object with the routes data
   */
  public function __construct($routeStore)
  {
    $this->routeStore = $routeStore;
  }

  /**
   * Looks for a route with the name passed as argument. If the route exists, it returns the absolute route path.
   * 
   * Looks for a static route first. If no static route exists, it looks for a
   * dynamic one. If no dynamic route is found either, an exception is thrown.
   *
   * @param  string $routeName  The name of the route
   *
   * @throws Exception\MissingRouteException If there is no route with the name we passed as argument.
   *
   * @return string  The route path
   */
  public function getThePath($routeName)
  {
    $routePath = $this->getStaticRoutePath($routeName);

    if( $routePath === RouteStore::NOT_FOUND ){
      $routePath = call_user_func_array(
        [$this, 'getDynamicRoutePath'],
        func_get_args()
      );

      if( $routePath === RouteStore::NOT_FOUND ){
         throw new Exception\MissingRouteException("There is no route named '$routeName'.");
      }
    }

    return '/' . $routePath . '/';
  }


  /*** PRIVATE METHODS ***/

  /**
   * Tries to find a static route by name in the RouteStore object.
   * Returns the full route path if the static route is found.
   *
   * @param  string  $routeName  The static route name
   *
   * @return integer|string  Returns the RouteStore::NOT_FOUND status code (int) if no static route is found, or the full route path (string) if the route is found.
   */
  private function getStaticRoutePath($routeName)
  {
    $matchingStaticRoute = $this->routeStore->getStaticRouteByName($routeName);
    
    if( $matchingStaticRoute === RouteStore::NOT_FOUND ){
      return RouteStore::NOT_FOUND;
    }

    return $matchingStaticRoute['routePath'];
  }

  /**
   * Tries to find a dynamic route by name in the RouteStore object.
   * Returns the full route path if the dynamic route is found.
   * 
   * When the dynamic route is found, this method iterates on the route segments.
   * The static segments are simply added to the path array, whereas the params
   * are then matched against the arguments passed to this method, so if the regex
   * defined on the param matches the argument, the argument is added as next segment
   * to the path array. This process leads to an array with all the required path
   * segments which are then imploded to form the returned url path.
   *
   * @param  string  $routeName  The route name
   *
   * @throws Exception\InvalidRouteException  If there are missing required parameters, invalid parameters, too many parameters or invalid segments in the route data
   *
   * @return integer|string  Returns the RouteStore::NOT_FOUND status code (int) if the route is not found, or the full dynamic route path (string) if the route is found.
   */
  private function getDynamicRoutePath($routeName)
  {
    $argsValues = func_get_args();
    array_splice($argsValues, 0, 1);

    $matchingDynamicRoute = $this->routeStore->getDynamicRouteByName($routeName, $argsValues);

    if( $matchingDynamicRoute === RouteStore::NOT_FOUND ){
      return RouteStore::NOT_FOUND;
    }

    $pathArr = [];
    $pathArrIndex = -1; // to start at index 0 since ++ is at loop top
    $argIndex = -1; // to start at index 0 since ++ is at dynamic block top

    foreach($matchingDynamicRoute['routeSegments'] as $segment){
      $pathArrIndex++;

      // static part
      if( is_string($segment) ){
        $pathArr[$pathArrIndex] = $segment;
        continue;
      }

      // dynamic part
      if( is_array($segment) ){
        $argIndex++;

        if( ! isset( $argsValues[$argIndex] ) ){
          if( $segment['optional'] ){
            $n = 1;

            // optional param does not exist, remove previous '/'
            unset($pathArr[$pathArrIndex - 1]);

            // optional param is not the last param, remove next '/'
            if( isset($matchingDynamicRoute['routeSegments'][$pathArrIndex + 1]) ){
              $n++;
              unset($matchingDynamicRoute['routeSegments'][$pathArrIndex + 1]);
            }

            $pathArrIndex = $pathArrIndex - $n;
            continue;
          }

          throw new Exception\InvalidRouteException(
            "Missing required parameter '{$segment['paramName']}' for '{$matchingDynamicRoute['routeName']}' route."
          );
        }

        if( ! preg_match($segment['paramRegex'], $argsValues[$argIndex]) ){
          throw new Exception\InvalidRouteException(
            "Invalid parameter '{$segment['paramName']}' for '{$matchingDynamicRoute['routeName']}' route."
          );
        }

        $pathArr[$pathArrIndex] = $argsValues[$argIndex];
        continue;
      }

      throw new Exception\InvalidRouteException("Invalid route segment.");
    }

    $argsPassed = count($argsValues);
    $argsNeeded = $argIndex + 1;

    if( $argsPassed > $argsNeeded ){
      throw new Exception\InvalidRouteException("Too many parameters for route $routeName");
    }

    return implode('', $pathArr);
  }

}