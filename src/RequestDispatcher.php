<?php
/**
 * Takes an HTTP Request's data and tries to match it to a route.
 * If a match is found it runs the matching route's handler and returns the 
 * output in a format decided either by config or request type.
 * 
 * @package    JetRouter
 * @subpackage Router
 */

namespace JetRouter;

class RequestDispatcher
{

  /*** CONST ***/

  /**
   * The status code returned when a route is not dispatched,
   * used to allow WordPress to take routing over.
   */
  const NOT_DISPATCHED = 0;


  /*** STATIC PROPERTIES ***/

  public static $outputFormats = ['auto', 'html', 'json'];


  /*** STATIC METHODS ***/

  /**
   * Determines if the passed parameter is a respond_to array.
   * 
   * The respond_to array is a feature of the route handler, allowing to determine
   * different behaviour and output for html and json. For json one can simply
   * return data in json format, whereas html runs the callback and does whatever
   * it needs to do (e.g. redirects etc.).
   * 
   * This is inspired by the Ruby on Rails respond_to block feature.
   *
   * @param      <type>   $output  The output
   *
   * @return     boolean  True if respond to, False otherwise.
   */
  private static function isRespondTo($output)
  {
    return (
      is_array($output) && 
      array_key_exists('respond_to', $output)
    );
  }


  /*** PROPERTIES ***/

  private $outputFormat;


  /*** PUBLIC METHODS ***/

  /**
   * Initializes the object and sets the output format
   *
   * @param  string  $outputFormat  The output format
   */
  public function __construct($outputFormat)
  {
    $this->outputFormat = $this->parseOutputFormat($outputFormat);
  }

  /**
   * Dispatches the HTTP request or returns a status code if no routes matches
   *
   * Tries to find a route in the route store by http method and request path.
   * Returns a NOT_DISPATCHED status code if no route matches.
   *
   * If a match is found, the route handler is called and the appropriate output
   * is printed/returned depending on request type and config.
   *
   * @param  RouteStore  $routeStore         The route store object
   * @param  string      $requestHttpMethod  The request http method
   * @param  string      $requestPath        The request path
   *
   * @return integer|mixed|string|mixed  Returns the RouteStore::NOT_FOUND status code (int), mixed if it prints json or returning $output, and finally if $output is a callback returns its returned value
   */
  public function dispatch($routeStore, $requestHttpMethod, $requestPath)
  {
    $route = $routeStore->findRouteByRequestMethodAndPath($requestHttpMethod, $requestPath);

    if($route === RouteStore::NOT_FOUND){
      // route does not exist, fallback to WordPress routing
      return self::NOT_DISPATCHED;
    }

    $output = $this->parseHandlerOutput(
      call_user_func_array($route['routeHandler'], array_values($route['routeArgs']))
    );

    if( $this->shouldReturnJson() ){
      return wp_send_json($output);
    }

    if( is_callable($output) ){
      return call_user_func($output);
    }

    return $output;
  } 


  /*** PRIVATE METHODS ***/

  /**
   * Parses the output format parameter.
   * 
   * Makes sure the output format parameter has one of the predefined values set
   * in the $outputFormats static property set in this class.
   *
   * @param   string  $outputFormat  The output format
   *
   * @throws  Exception\InvalidOutputFormatException  If the output format is invalid
   *
   * @return  string The output format value
   */
  private function parseOutputFormat($outputFormat)
  {
    if( ! in_array($outputFormat, self::$outputFormats, true) ){
      throw new Exception\InvalidOutputFormatException(
        "'$outputFormat' is not a valid output format.",
        1
      );
    }

    return $outputFormat;
  }

  /**
   * Parses and returns the route handler output.
   * 
   * Checks that the output is a respond_to array. If it's not it simply returns 
   * the output as it was passed in.
   * 
   * If it is, it validates it and then it returns the appropriate output format
   * depending on config or request type.
   *
   * @param  mixed  $output  The output of the route handler
   *
   * @return mixed  The parsed output of the route handler
   */
  private function parseHandlerOutput($output)
  {
    if( ! self::isRespondTo($output) ){
      return $output;
    }

    $this->validateRespondTo($output['respond_to']);
    $type = $this->shouldReturnJson() ? 'json' : 'html';

    return $output['respond_to'][$type];
  }

  /**
   * Determines if the route handler respond_to block should return the json data
   * 
   * Looks for a "json" get parameter, if it's missing it checks for conditions
   * that indicate the request we're handling is an ajax request, or finally it
   * simply looks for the output format not being set to html.
   * 
   * If any of these condition is met it returns true
   *
   * @return  boolean  The should return json boolean
   */
  private function shouldReturnJson()
  {
    return (
      isset($_GET['json']) ||
      $this->outputFormat === 'json' ||
      (
        ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 
        strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' &&
        $this->outputFormat !== 'html'
      )
    );
  }

  /**
   * Validates the respond_to array
   *
   * @param  array  $respondTo  The respond_to array
   *
   * @throws Exception\InvalidRouteHandlerException  If the respond_to is not an array, or missing the 'json' or 'html' keys, or if 'html' property is not callable
   */
  private function validateRespondTo($respondTo)
  {
    if( ! is_array($respondTo) ){
      throw new Exception\InvalidRouteHandlerException(
        'The "respond_to" handler output property must be an array.'
      );
    }

    if( ! array_key_exists('json', $respondTo) ){
      throw new Exception\InvalidRouteHandlerException(
        'Missing json output from respond_to route handler.'
      );
    }

    if( ! array_key_exists('html', $respondTo) ){
      throw new Exception\InvalidRouteHandlerException(
        'Missing html callback from respond_to route handler.'
      );
    }

    if( ! is_callable($respondTo['html']) ){
      throw new Exception\InvalidRouteHandlerException(
        "The html property of the respond_to route handler needs to be callable."
      );
    }
  }

}
