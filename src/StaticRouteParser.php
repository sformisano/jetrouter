<?php
/**
 * Parses a static route with static path specific validations.
 * The rest is handled by the common base RouteParser class it inherits from.
 * 
 * @package    JetRouter
 * @subpackage Router
 */

namespace JetRouter;

class StaticRouteParser extends RouteParser
{

  /**
   * Parses the route string allowing alphanumeric characters and the
   * "+", "_", "-" and "/" characters
   * it also does not allow consecutive "/" characters
   */
  const STATIC_ROUTE_PATH_REGEX = '~(?!.*(\/)\1)^([a-zA-Za-zA-Z0-9_\-\/])+$~';


  /*** STATIC METHODS ****/

  /**
   * Validates the route path passed as argument through the static route path regex
   *
   * @param  string  $path  The route path
   *
   * @throws Exception\InvalidRouteException  If the path does not match the regex
   */
  public static function validateStaticPath($path)
  {
    if( ! preg_match(self::STATIC_ROUTE_PATH_REGEX, $path) ){
      throw new Exception\InvalidRouteException(
        "'$path' is not a valid route path.",
        1
      );
    }
  }


  /*** PROTECTED METHODS ***/

  /**
   * Validates the route path passed as argument and then sets it as object property
   *
   * @param  string  $path  The route path
   */
  protected function setPath($path)
  {
    $this->validateStaticPath($path);
    $this->path = $path;
  }

}