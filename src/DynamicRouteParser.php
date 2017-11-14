<?php
/**
 * Parses a dynamic route and generates the data required for route matching and reverse routing.
 * 
 * @package    JetRouter
 * @subpackage Router
 */

namespace JetRouter;

class DynamicRouteParser extends RouteParser
{

  /*** CONST ***/

  /**
   * Matches a string without mismatching nor nested curly braces, e.g. 
   * /this/{will/not/{work}
   * {neither}/will/this}
   * /not/{cool{at-all}}
   */
  const BALANCING_CURLY_BRACES_REGEX = '~^[^{}]*(?:\{[^}{}]*\}[^{}]*)*$~';

  /**
   * Matches params in a route (parts surrounded by curly braces, content is
   * ignored, hence the "loose" naming). Example:
   * /some/{param1}/route/{param2}/ matches "{param1}" and "{param2}"
   */
  const LOOSE_PARAMS_REGEX = '~\s*\{.+\}\s*~U';

  /**
   * Parses route strings of the following form to capture params:
   *
   * "/user/{name}[/{id:[0-9]+}]"
   */
  const CAPTURE_PARAMS_REGEX = '~\{([a-zA-Z0-9_]*)(?::([^{]+(?:\{.*?\})?))?\}\??~x';

  /**
   * One or more characters that is not a '/'
   */
  const DEFAULT_PARAM_VALUE_REGEX = '[^/]+';


  /*** STATIC METHODS ***/

  /**
   * Validates curly braces positioning in path
   *
   * Uses a regex to make sure no nested nor mismatching curly braces are in the
   * route path passed as parameter.
   * 
   * @param  string $path The dynamic route path
   * 
   * @throws Exception\InvalidRouteException If mismatching or nested curly braces are found in $path
   */
  private static function validateParamsCurlyBraces($path)
  {
    if( ! preg_match(self::BALANCING_CURLY_BRACES_REGEX, $path) ){
      throw new Exception\InvalidRouteException(
        "Mismatching curly braces in '$path' route.",
        1
      );
    }
  }

  /**
   * Validates the static parts of a dynamic route path
   * 
   * Replaces params with a string so they can be ignored (they are validated 
   * elsewhere) and validates the remaining static parts of the path
   * e.g. comments/{id}/popular > comments/param/popular
   *
   * @param string $path The dynamic route path
   */
  private static function validateStaticPathParts($path)
  {
    StaticRouteParser::validateStaticPath(
      preg_replace(
        self::LOOSE_PARAMS_REGEX, 'param', 
        str_replace('}?', '}', $path) // accounts for optional parameters
      )
    );
  }

  /**
   * Captures data about the dynamic route path parameter
   * 
   * Uses a regex to capture params data (name, position in path, regex)
   *
   * @param  string $path The dynamic route path
   * 
   * @return array The array with the params data
   */
  private static function captureParamsData($path)
  {
    preg_match_all(
      self::CAPTURE_PARAMS_REGEX,
      $path,
      $paramsData,
      PREG_OFFSET_CAPTURE | PREG_SET_ORDER
    );

    return $paramsData;
  }

  /**
   * Validates the params in the dynamic route path parameter
   * 
   * Compares the number of {} pairs found with the loose params regex, with the
   * number of parameters captured with the full capture params regex. If there
   * is a delta then one of the {} pairs is wrapping an invalid param definition
   * that the capture params regex failed to parse correctly.
   *
   * @param   integer  $looseParamsN  The number of loose params 
   * @param   integer  $paramsDataN   The number of captured params
   * @param   string   $path          The dynamic route path
   *
   * @throws  Exception\InvalidRouteException If there are more loose params than params data items
   */
  private static function validateParamsN($looseParamsN, $paramsDataN, $path)
  {
    $invalidParamsN = abs( $looseParamsN - $paramsDataN );

    if( $invalidParamsN ){
      throw new Exception\InvalidRouteException(
        sprintf( 
          _n( 
            '%s invalid parameter', 
            '%s invalid parameters', 
            $invalidParamsN
          ),
          $invalidParamsN
        ) . " found in '$path' route.",
        1
      );
    }
  }

  /**
   * Validates a regex
   * 
   * Simply tests a regex with @preg_match (@ to avoid warnings) against null.
   * If === false then the regex is broken.
   *
   * @param   string  $regex  The regular expression
   *
   * @throws  Exception\InvalidRouteException If the regex is invalid
   */
  private static function validateRegex($regex)
  {
    if( @preg_match($regex, null) === false ){
      throw new Exception\InvalidRouteException("'$regex' is not a valid regex.");
    }
  }


  /*** PROPERTIES ***/

  private $paramsNames = [];
  private $regex;
  private $regexParts = [];
  private $regexShortcuts = [
    ':i}' => ':[0-9]+}',           // integer
    ':a}' => ':[a-zA-Z0-9]+}',     // alphanumeric
    ':s}' => ':[a-zA-Z0-9_\-\.]+}' // alphanumeric, "_", "-" and ".""
  ];
  private $segments = [];
  private $segmentsCounter = 0;
  private $regexPartsCounter = 0;


  /*** PUBLIC METHODS ***/

  /**
   * Gets the regular expression.
   *
   * @return  string  The full dynamic route regex.
   */
  public function getRegex()
  {
    return $this->regex;
  }

  /**
   * Gets the dynamic route params names.
   *
   * @return  array  The dynamic route params names.
   */
  public function getParamsNames()
  {
    return $this->paramsNames;
  }

  /**
   * Gets the segments.
   *
   * @return  array  The dynamic route segments.
   */
  public function getSegments()
  {
    return $this->segments;
  }


  /*** PRIVATE METHODS ***/

  /**
   * Parses the dynamic route path param and collects params data.
   *
   * @param  string  $path  The dynamic route path
   */
  protected function setPath($path)
  {
    // replace regex shortcuts with regex
    $this->path = $path = strtr($path, $this->regexShortcuts);

    self::validateStaticPathParts($path);
    self::validateParamsCurlyBraces($path);

    $paramsData = self::captureParamsData($path);
    $looseParamsN = preg_match_all(self::LOOSE_PARAMS_REGEX, $path);

    self::validateParamsN($looseParamsN, count($paramsData), $path);

    $this->parseParamsData($paramsData);
  }

  /**
   * Parses the dynamic route params data
   * 
   * Uses the params data obtained from the dynamic route path to build a
   * full route regex, which will be used to match requests to routes, and a
   * route path segments array, which will be used for reverse routing.
   *
   * @param  array  $paramsData  The dynamic route params data
   */
  private function parseParamsData($paramsData)
  {
    $this->setParamsNames($paramsData);
    
    $prevParamEndPos = 0;

    foreach($paramsData as $paramData){
      $param = $paramData[0][0]; // e.g. "{foo:[0-9]+}" (regex shortcut translated into regex)
      $paramStartPosInRoute = $paramData[0][1]; // strpos of {foobar} in route string
      $paramName = $paramData[1][0]; // e.g. "foobar"

      // e.g. "[0-9]+"
      $paramRegex = (isset($paramData[2]) ? $paramData[2][0] : self::DEFAULT_PARAM_VALUE_REGEX);

      $optional = substr($param, -1) === '?'; // e.g. "test/{foo}?"

      // Find static segments between current and previous param in route
      $this->maybeAddStaticSegments(
        $prevParamEndPos,
        $paramStartPosInRoute - $prevParamEndPos
      );

      $this->addParamSegment($paramName, $paramRegex, $optional);

      /*
       * These are imploded into the full route regex (i.e. used to match route
       * to request path), whereas $paramRegex is used to match the single param
       */
      $this->addRoutePathRegexPart($paramRegex, $optional);

      $prevParamEndPos = $paramStartPosInRoute + strlen($param);

      /* 
       * Note on why the segments and regex parts require separate counters.
       *
       * The regex parts array is used to build the full regex path used to match
       * requests to routes, whereas the segments array is used for reverse routing.
       * 
       * In the full route regex, optional parameters replace the previous "/" 
       * symbol and include it in the optional param regex, so if the parameter 
       * does not exist there won't be an extra unneeded "/" symbol in the regex.
       *
       * None of this happens for the segments array, which means that for every 
       * optional parameter added to the dynamic route path the regex parts array 
       * will have one less element than the segments array.
       * 
       * Example: the route "/users/{username}/comments/{filter}?"
       * creates a segments array with 7 elements:
       * ["users", "/", username param data array, "/", "comments", "/", "filter param data array"]
       * 
       * That same route will create a regex parts array with one less element,
       * because the "/" symbol before the optional filter parameter will be
       * removed as separate array element and added in the filter param regex
       */
      $this->segmentsCounter++;
      $this->regexPartsCounter++;
    }

    // Find any static segments after the final param
    $this->maybeAddStaticSegments(
      $prevParamEndPos,
      strlen($this->path) - $prevParamEndPos
    );

    $this->regex = implode('', $this->regexParts);
  }

  /**
   * Sets the params names array as a property of the object.
   *
   * @param  array  $paramsData  The dynamice route params data
   *
   * @throws Exception\InvalidRouteException  If the same param name is used more than once
   */
  private function setParamsNames($paramsData)
  {
    foreach($paramsData as $paramData){
      $paramName = $paramData[1][0];

      if( array_key_exists($paramName, $this->paramsNames) ){
        throw new Exception\InvalidRouteException(
          "Parameter '$paramName' found more than once in route.",
          1
        );
      }

      $this->paramsNames[$paramName] = null;
    }
  }

  /**
   * Adds a param segment to the segments array property of the object.
   *
   * @param  string  $paramName   The param name
   * @param  string  $paramRegex  The param regex the argument will have to match
   * @param  boolean $optional    Is the param optional?
   */
  private function addParamSegment($paramName, $paramRegex, $optional)
  {
    $this->segments[$this->segmentsCounter] = [
      'paramName' => $paramName,
      'paramRegex' => '~^(' . $paramRegex . ')$~',
      'optional' => $optional
    ];
  }

  /**
   * Adds the param regex to the regexParts property of the object.
   * 
   * If the param is optional and there's a previous "/" symbol before this new
   * param regex, this new param regex will replace that "/" symbol in the array
   * (the regex array index counter, i.e. the regexPartsCounter object property,
   * is reduced by 1 so this param regex will be placed at the array index where 
   * the previous "/" symbol was) and the param regex will be changed
   * to incorporate the "/" symbol. This is to avoid having an extra "/" symbol
   * if the argument for this optional parameter does not exist (either in 
   * request matching or reverse routing).
   *
   * @param  string  $paramRegex  The param regex the argument will have to match
   * @param  boolean $optional    Is the param optional?
   */
  private function addRoutePathRegexPart($paramRegex, $optional)
  {
    $routeRegexPart = '(' . $paramRegex . ')';
    self::validateRegex($routeRegexPart);

    if($optional){
      $prevSegmentPos = $this->regexPartsCounter - 1;

      if(
        isset($this->regexParts[$prevSegmentPos]) && 
        $this->regexParts[$prevSegmentPos] === '/'
      ){
        $this->regexPartsCounter--;
        $routeRegexPart = '(?:/' . $routeRegexPart . ')';
      }

      $routeRegexPart .= '?';
    }

    $this->regexParts[$this->regexPartsCounter] = $routeRegexPart;
  }

  /**
   * Adds static segments in the specified dynamic route path substr if there's any
   * 
   * Looks for any static segments in the dynamic route path substring specified
   * with the $start and $end params. If it finds any it adds them to the segments
   * and the regexParts properties of the object.
   *
   * @param  integer  $start  The start position of the dynamic route path substring
   * @param  integer  $end    The end position of the dynamic route path substring
   */
  private function maybeAddStaticSegments($start, $end)
  {
    $staticSegments =  preg_split(
      '~(/)~u', 
      substr($this->path, $start, $end), 
      0, 
      PREG_SPLIT_DELIM_CAPTURE
    );

    foreach($staticSegments as $segment){
      if($segment){
        // static, i.e. just the string
        $this->segments[$this->segmentsCounter] = $segment;

        $this->regexParts[] = preg_quote($segment, '~');

        $this->segmentsCounter++;
        $this->regexPartsCounter++;
      }
    }
  }

}