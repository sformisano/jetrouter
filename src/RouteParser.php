<?php
/**
 * Serves as a common abstract base class for the StaticRouteParser and the 
 * DynamicRouteParser classes. It has all the common basic validation methods
 * and the getters and setters methods.
 * 
 * @package    JetRouter
 * @subpackage Router
 */

namespace JetRouter;

abstract class RouteParser
{

  /*** CONST ***/

  /**
   * The route name regex (standard alphanumeric plus the "_" character).
   */
  const ROUTE_NAME_REGEX = '~^[a-zA-Z0-9_]+$~';


  /*** STATIC PROPERTIES ***/

  /**
   * The allowed HTTP Methods
   */
  public static $allowedHttpMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];


  /*** STATIC METHODS ***/

  /**
   * Validates the HTTP Method param value by looking for it in the $allowedHttpMethods array property defined in this class.
   *
   * @param  string  $httpMethod  The http method
   *
   * @throws Exception\InvalidHttpMethodException  If the http method param value is not in the $allowedHttpMethods array property defined in this class
   */
  protected static function validateHttpMethod($httpMethod)
  {
     if( ! in_array($httpMethod, self::$allowedHttpMethods, true) ){
      throw new Exception\InvalidHttpMethodException(
        "'$httpMethod' is not a valid HTTP method."
      );
    }
  }
  
  /**
   * Validates the route name with the ROUTE_NAME_REGEX constant defined in this class
   *
   * @param  string  $name   The route name
   *
   * @throws Exception\InvalidRouteException  If the route name does not match the ROUTE_NAME_REGEX constant defined in this class
   */
  private static function validateName($name)
  {
    if( ! preg_match( self::ROUTE_NAME_REGEX, $name ) ){
      throw new Exception\InvalidRouteException(
        "'$name' is not a valid route name.", 1
      );
    }
  }

  /**
   * Validates the route handler param by making sure it's callable
   *
   * @param  callback  $handler  The route handler param
   *
   * @throws Exception\InvalidRouteHandlerException  If the handler param is not callable
   */
  protected static function validateHandler($handler)
  {
    if( ! is_callable($handler) ){
      throw new Exception\InvalidRouteHandlerException(
        "Invalid handler for '" . $this->name . "' route."
      );
    }
  }


  /*** PROPERTIES ***/

  protected $httpMethod;
  protected $path;
  protected $name;
  protected $handler;


  /*** PUBLIC METHODS ***/

  /**
   * Initializes the object by setting the http method, path, name and handler properties
   *
   * @param  string    $httpMethod  The route http method
   * @param  string    $path        The route path
   * @param  string    $name        The route name
   * @param  callback  $handler     The route handler
   */
  public function __construct($httpMethod, $path, $name, $handler)
  {
    $this->setHttpMethod($httpMethod);
    $this->setPath($path);
    $this->setName($name);
    $this->setHandler($handler);
  }

  /**
   * Returns the route http method
   *
   * @return  string  The object's $httpMethod property
   */
  public function getHttpMethod()
  {
    return $this->httpMethod;
  }

  /**
   * Returns the route's path
   *
   * @return  string  The object's $path property
   */
  public function getPath()
  {
    return $this->path;
  }

  /**
   * Returns the route's name
   *
   * @return  string  The object's $name property
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Returns the route's handler
   *
   * @return  callback  The object's $handler property
   */
  public function getHandler()
  {
    return $this->handler;
  }


  /*** PROTECTED METHODS ***/

  /**
   * Validates the route's http method and then sets it as an object property
   *
   * @param  string  $httpMethod  The route's http method
   */
  protected function setHttpMethod($httpMethod)
  {
    self::validateHttpMethod($httpMethod);
    $this->httpMethod = $httpMethod;
  }

  /**
   * Validates the route's name and then sets it as an object property
   *
   * @param  string  $name   The route's name
   */
  protected function setName($name)
  {
    self::validateName($name);
    $this->name = $name;
  }

  /**
   * Validates the route's handler and then sets it as an object property
   *
   * @param  callback  $handler   The route's handler
   */
  protected function setHandler($handler)
  {
    self::validateHandler($handler);
    $this->handler = $handler;
  }

}