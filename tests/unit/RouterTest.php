<?php

namespace JetRouter;

class RouterTest extends \Codeception\Test\Unit{

  protected function _before()
  {
    \WP_Mock::setUp();
  }

  protected function _after()
  {
    \WP_Mock::tearDown();
  }


  /****************************************************************************/
  /* HELPERS                                                                  */
  /****************************************************************************/

  function getRouter($routerArgs = [], $routes = [])
  {
    $router = Router::create($routerArgs);

    foreach($routes as $route){
      $router->addRoute(
        $route['routeHttpMethod'],
        $route['routePath'],
        $route['routeName'],
        $route['routeHandler']
      );
    }

    return $router;
  }


  /****************************************************************************/
  /* DATA PROVIDERS                                                           */
  /****************************************************************************/

  // Namespaces ________________________________________________________________

  function validNamespacesProvider()
  {
    return [
      'with slash on the left' => [ '/cool-namespace' ],
      'with slash on the right' => [ 'your_api/' ],
      'with slashes on both sides' => [ '/another-project/' ],
      'many slashes and symbols' => [ 'abc/def/ghi/jlm/nop/qrs/tuv/wxy/z_-/.' ],
    ];
  }

  function invalidNamespacesProvider()
  {
    return [
      'just spaces' => [ '   ' ],
      'with spaces' => [ 'has spaces inside it' ],
      'with whitespace' => [ ' simple-string ' ],
      'with whitespace on left' => [ ' simple-string' ],
      'with whitespace on right' => [ 'the/namespace ' ],
      'with whitespace on both sides' => [ ' another_string ' ],
      'with illegal symbols' => [ 'namespace!@£$%^' ]
    ];
  }

  // Output Formats ____________________________________________________________

  function validOutputFormatsProvider()
  {
    $outputFormats = [];

    foreach( RequestDispatcher::$outputFormats as $format ){
      $outputFormats[$format] = [$format];
    }

    return $outputFormats;
  }

  function invalidOutputFormatsProvider()
  {
    return [ 
      'simple string' => ['simple_string'], 
      'another example of random string'  => ['ANOTHER_RANDOM_STRING']
    ];
  }

  // Add Route Methods _________________________________________________________

  function addRouteMethodAliasesProvider()
  {
    return [
      'get'     => ['get', 'GET'],
      'head'    => ['head', 'HEAD'],
      'post'    => ['post', 'POST'],
      'put'     => ['put', 'PUT'],
      'patch'   => ['patch', 'PATCH'],
      'delete'  => ['delete', 'DELETE'],
      'options' => ['options', 'OPTIONS']
    ];
  }

  // HTTP Methods ______________________________________________________________

  function validHttpMethodsProvider()
  {
    $validHttpMethods = [];

    foreach(RouteParser::$allowedHttpMethods as $httpMethod){
      $validHttpMethods[$httpMethod] = [$httpMethod];
    }

    return $validHttpMethods;
  }

  function invalidHttpMethodsProvider()
  {
    return [ 
      'test string' => ['TEST'],
      'integer' => [ 29 ], 
      'boolean' => [ true ]
    ];
  }

  function unallowedHttpMethodsProviders()
  {
    $data = [];

    foreach( RouteParser::$allowedHttpMethods as $httpMethod ){
      $unallowedMethods = [ $httpMethod ];

      $data['unallowed ' . $httpMethod] = [
        'allowedHttpMethods' => array_diff( RouteParser::$allowedHttpMethods, $unallowedMethods ),
        'unallowedHttpMethod' => $httpMethod
      ];
    }

    return $data;
  }

  // Route Names _______________________________________________________________

  function validRouteNamesProvider()
  {
    return [
      'simple' => ['someroute'],
      'uppercase' => ['FINALROUTE'],
      'with underscore' => ['casual_route'],
      'with numbers' => ['route66']
    ];
  }

  function invalidRouteNamesProvider()
  {
    return [
      'with spaces' => ['used route'],
      'with hyphens' => ['get-users'],
      'with random symbols' => ['get!@£$']
    ];
  }

  function duplicateRoutesNamesProvider()
  {
    $handler = function(){};

    return [
      'two static routes, no namespace, different methods' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'cool/rainbow',
            'routeName' => 'get_rainbow',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'PATCH',
            'routePath' => 'somewhere/over/the/rainbow',
            'routeName' => 'get_rainbow',
            'routeHandler' => $handler
          ]
        ]
      ],
      'two static routes, with namespace, same methods' => [ 
        'routerArgs' => ['namespace' => 'clouds'],
        'routes' => [
          [
            'routeHttpMethod' => 'POST',
            'routePath' => 'clouds',
            'routeName' => 'create_cloud',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'POST',
            'routePath' => 'sky/clouds',
            'routeName' => 'create_cloud',
            'routeHandler' => $handler
          ]
        ]
      ],
      'two dynamic routes, same param name, same methods' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => 'apps/{id}',
            'routeName' => 'update_app',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => '/system/apps/{id}/',
            'routeName' => 'update_app',
            'routeHandler' => $handler
          ]
        ]
      ],
      'two dynamic routes, different params names' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => 'profile/{id}',
            'routeName' => 'update_profile',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'PATCH',
            'routePath' => 'user-profile/{username}',
            'routeName' => 'update_profile',
            'routeHandler' => $handler
          ]
        ]
      ],
    ];
  }

  // Route Paths _______________________________________________________________

  function invalidRoutePathsProvider()
  {
    $handler = function(){};

    return [
      'route with spaces' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'a route/with spaces',
            'routeName' => 'delete_alphabet_letter',
            'routeHandler' => $handler
          ]
        ]
      ]
    ];
  }

  function routesWithDuplicatePathsProvider()
  {
    $handler = function(){};

    return [
      'two static routes, no namespace' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'netflix/movies/featured',
            'routeName' => 'featured_netflix_movies',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'netflix/movies/featured',
            'routeName' => 'featured_netflix_movies_again',
            'routeHandler' => $handler
          ]
        ]
      ],
      'two static routes, with namespace' => [ 
        'routerArgs' => ['namespace' => 'rotten-tomatoes-api'],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'reviews',
            'routeName' => 'rotten_movies_reviews',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'reviews',
            'routeName' => 'rotten_movies_reviews_one_more_time',
            'routeHandler' => $handler
          ]
        ]
      ],
      'two dynamic routes, same param name' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'DELETE',
            'routePath' => 'raspberrypi-projects/{slug}',
            'routeName' => 'delete_raspberrypi_project',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'DELETE',
            'routePath' => 'raspberrypi-projects/{slug}',
            'routeName' => 'delete_bad_raspberrypi_project',
            'routeHandler' => $handler
          ]
        ]
      ],
      'two dynamic routes, different params names' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => 'chips/{slug}',
            'routeName' => 'update_chip_project',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => 'chips/{name}',
            'routeName' => 'update_the_chip_project',
            'routeHandler' => $handler
          ]
        ]
      ],
    ];
  }

  function dynamicRoutesWithDuplicatePathsWithDiffRegexProvider()
  {
    $handler = function(){};

    return [
      'two dynamic routes, integer and default regex' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'users/{id:i}',
            'routeName' => 'get_user_by_id',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'users/{email}',
            'routeName' => 'get_user_by_email',
            'routeHandler' => $handler
          ]
        ]
      ]
    ];
  }

  function dynamicRoutesWithDuplicateParamsNamesProvider()
  {
    $handler = function(){};

    return [
      'same param name, same default regex' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'users/{username}/{username}',
            'routeName' => 'get_user_by_id',
            'routeHandler' => $handler
          ]
        ]
      ],
      'same param name, same integer regex' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'books/{id:i}/{id:i}',
            'routeName' => 'get_user_by_id',
            'routeHandler' => $handler
          ]
        ]
      ],
      'same param name, different regexes' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'movies/{title:a}/{title:c}',
            'routeName' => 'get_user_by_id',
            'routeHandler' => $handler
          ]
        ]
      ]
    ];
  }


  // Dispatcher ________________________________________________________________

  function requestsToDispatchProvider()
  {
    return [
      'simple static route' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'POST',
            'routePath' => 'comments',
            'routeName' => 'create_comment',
            'routeHandler' => function(){
              return 'comment created';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'POST', 'routePath' => '/comments/'],
        'output' => 'comment created'
      ],
      'simple static route with namespace' => [ 
        'routerArgs' => ['namespace' => 'awesome/api/v3/'],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'photos/latest',
            'routeName' => 'get_latest_photos',
            'routeHandler' => function(){
              return 'so many photos';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'GET', 'routePath' => '/awesome/api/v3/photos/latest'],
        'output' => 'so many photos'
      ],
      'dynamic route with namespace and default param regex' => [ 
        'routerArgs' => ['namespace' => 'social-api'],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'friends/{username}/details',
            'routeName' => 'get_friend_details',
            'routeHandler' => function(){
              return 'friend details output';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'GET', 'routePath' => '/social-api/friends/m@tt!/details/'],
        'output' => 'friend details output'
      ],
      'dynamic route with :i integer regex shortcut param' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'comments/{id:i}',
            'routeName' => 'get_comment',
            'routeHandler' => function(){
              return 'get comment by id output';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'GET', 'routePath' => 'comments/2'],
        'output' => 'get comment by id output'
      ],
      'dynamic route with :a alphanumeric regex shortcut param' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'PATCH',
            'routePath' => 'users/{name:a}',
            'routeName' => 'update_user',
            'routeHandler' => function(){
              return 'user updated';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'PATCH', 'routePath' => 'users/salvatore'],
        'output' => 'user updated'
      ],
      'dynamic route with :s slug regex shortcut param' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'DELETE',
            'routePath' => 'news/{slug:s}',
            'routeName' => 'delete_news',
            'routeHandler' => function(){
              return 'news deleted';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'DELETE', 'routePath' => 'news/new-wordpress-version-released'],
        'output' => 'news deleted'
      ],
      'dynamic route with specific words custom regex param' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'languages/{language:english|italian|french|spanish}',
            'routeName' => 'get_language',
            'routeHandler' => function(){
              return 'lang output';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'GET', 'routePath' => 'languages/italian'],
        'output' => 'lang output'
      ],
      'dynamic route with integer custom regex param' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'posts/{id:[0-9]+}',
            'routeName' => 'get_post',
            'routeHandler' => function(){
              return 'get post by id output';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'GET', 'routePath' => 'posts/25'],
        'output' => 'get post by id output'
      ],
      'dynamic route with lowercase letters and _ - symbols custom regex param' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => 'articles/{slug:[a-z_-]+}',
            'routeName' => 'update_article',
            'routeHandler' => function(){
              return 'get article by slug output';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'PUT', 'routePath' => 'articles/why-jetrouter_router-is-a-great-tool'],
        'output' => 'get article by slug output'
      ],
      'dynamic route with uppercase character custom regex param' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'DELETE',
            'routePath' => 'letters/{char:[A-Z]}',
            'routeName' => 'delete_alphabet_letter',
            'routeHandler' => function(){
              return 'delete alphabet letter output';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'DELETE', 'routePath' => 'letters/A'],
        'output' => 'delete alphabet letter output'
      ]
    ];
  }

  function requestsToNotDispatchProvider()
  {
    $handler = function(){ return 'request is being dispatched!'; };

    return [
      'router with namespace, same request path without namespace' => [ 
        'routerArgs' => ['namespace' => 'awesome_project/api'],
        'routes' => [
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => 'cool-resource',
            'routeName' => 'some_cool_resource_path',
            'routeHandler' => $handler
          ]
        ],
        'request' => ['routeHttpMethod' => 'PUT', 'routePath' => 'cool-resource']
      ],

      'router with namespace, request path more specific than route path' => [ 
        'routerArgs' => ['namespace' => 'photoapp/api'],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'comments',
            'routeName' => 'app_comments',
            'routeHandler' => $handler
          ]
        ],
        'request' => ['routeHttpMethod' => 'GET', 'routePath' => 'photoapp/api/comments/published']
      ],

      'fully matching static route, different HTTP method' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'calendar/sformisano',
            'routeName' => 'get_user_calendar',
            'routeHandler' => $handler
          ]
        ],
        'request' => ['routeHttpMethod' => 'PATCH', 'routePath' => 'calendar/sformisano']
      ],

      'same request path as route, regex not matching' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => 'posts/{id:i}',
            'routeName' => 'update_post',
            'routeHandler' => $handler
          ]
        ],
        'request' => ['routeHttpMethod' => 'PUT', 'routePath' => 'posts/post-title']
      ]
    ];
  }

  function staticRoutesOverridingDynamicRoutesProvider()
  {
    return [
      'GET posts/popular overriding GET posts/{id} (routes added in this order)' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'posts/popular',
            'routeName' => 'popular_posts',
            'routeHandler' => function(){
              return 'get popular posts handler';
            }
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'posts/{id}',
            'routeName' => 'get_post',
            'routeHandler' => function($id){
              return 'get post handler';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'GET', 'routePath' => 'posts/popular'],
        'output' => 'get popular posts handler'
      ],
      'with namespace, PUT users/{username}/pages/{slug} being overridden by PUT users/salvatore/pages/about (routes added in this order)' => [ 
        'routerArgs' => [ 'namespace' => 'some/namespace/' ],
        'routes' => [
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => 'users/{username}/articles/{id}',
            'routeName' => 'update_user_page',
            'routeHandler' => function(){
              return 'get popular posts handler';
            }
          ],
          [
            'routeHttpMethod' => 'PUT',
            'routePath' => 'users/salvatore/pages/about',
            'routeName' => 'update_salvatore_about_page',
            'routeHandler' => function(){
              return 'about page updated';
            }
          ]
        ],
        'request' => ['routeHttpMethod' => 'PUT', 'routePath' => 'some/namespace/users/salvatore/pages/about'],
        'output' => 'about page updated'
      ]
    ];
  }

  function optionalParamsRequestsToDispatchProvider()
  {
    return [
      'Single optional param as last segment' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/comments/{filter}?',
            'routeName' => 'get_comments',
            'routeHandler' => function($filter){
              return "get $filter comments handler output";
            }
          ]
        ],
        'requests' => [
          ['routeHttpMethod' => 'GET', 'routePath' => 'comments'],
          ['routeHttpMethod' => 'GET', 'routePath' => 'comments/deleted'],
        ],
        'outputs' => [ 
          'get  comments handler output',
          'get deleted comments handler output'
        ]
      ],
      'Single optional param between static segments' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'attachments/{type}?/alphabetical/',
            'routeName' => 'get_attachments',
            'routeHandler' => function($type){
              return "get $type attachments handler output";
            }
          ]
        ],
        'requests' => [
          ['routeHttpMethod' => 'GET', 'routePath' => '/attachments/alphabetical'],
          ['routeHttpMethod' => 'GET', 'routePath' => '/attachments/PDF/alphabetical']
        ],
        'outputs' => [
          'get  attachments handler output',
          'get PDF attachments handler output'
        ]
      ],
      'Multiple optional/non-optional params between static segments' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => 'college/{college_name}/{teacher_name}?/students/{class_year}?',
            'routeName' => 'get_students',
            'routeHandler' => function($college_name, $teacher_name, $class_year){
              $output = '';

              if( $college_name ){
                $output .= "college $college_name > ";
              }

              if( $teacher_name ){
                $output .= "teacher $teacher_name > ";
              }

              if( $class_year ){
                $output .= "class year $class_year";
              }
              else{
                $output .= 'all students';
              }

              return $output;
            }
          ]
        ],
        'requests' => [
          ['routeHttpMethod' => 'GET', 'routePath' => 'college/CalTech/students'],
          ['routeHttpMethod' => 'GET', 'routePath' => 'college/Oxford/Stephen-Hawking/students/1980'],
          ['routeHttpMethod' => 'GET', 'routePath' => 'college/MIT/students/2010'],
          ['routeHttpMethod' => 'GET', 'routePath' => 'college/MarvelUniversity/BruceBanner/students/'],
        ],
        'outputs' => [
          'college CalTech > all students',
          'college Oxford > teacher Stephen-Hawking > class year 1980',
          'college MIT > class year 2010',
          'college MarvelUniversity > teacher BruceBanner > all students',
        ]
      ]
    ];
  }

  function optionalParamsRequestsToNotDispatchProvider()
  {
    $handler = function(){
      return 'request has a match and is being dispatched';
    };

    return [
      'Single optional integer param, request not matching integer regex' => [ 
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/circles/{circle:i}?/activities',
            'routeName' => 'circles_activities',
            'routeHandler' => $handler
          ]
        ],
        'requests' => [
          ['routeHttpMethod' => 'GET', 'routePath' => 'circles/close-friends/activities'],
        ]
      ]
    ];
  }

  function respondToInvalidTypesProvider(){
    return [
      ['not an array'],
      [ true ],
      [ false ],
      [ 100 ],
      [ new Router() ]
    ];
  }

  function respondToInvalidHtmlElementTypesProvider(){
    return [
      ['not a callable function'],
      [ true ],
      [ false ],
      [ 88 ],
      [ new Router() ]
    ];
  }

  function validReverseRoutesProvider()
  {
    $handler = function(){};

    return [
      'Valid entries' => [
        'routerArgs' => [],
        'routes' => [
          [
            'routeHttpMethod' => 'POST',
            'routePath' => 'users',
            'routeName' => 'create_user',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/users/{username}',
            'routeName' => 'get_user_by_username',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/users/{username}/comments/{filter}?/',
            'routeName' => 'get_user_comments',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/users/{username}/comments/{filter}?/{page}/',
            'routeName' => 'get_paged_comments',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/schools/{school_name:s}?/teachers/{teacher_name:s}?/students/{year:i}?',
            'routeName' => 'get_students',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '{model:a}/{filter_type:s}/{filter_value:a}',
            'routeName' => 'get_model_by_filter',
            'routeHandler' => $handler
          ]
        ],

        'reverseRoutes' => [
          ['create_user'],
          ['get_user_by_username', 'sformisano'],
          ['get_user_comments', 'matt'],
          ['get_user_comments', 'james', 'published'],
          ['get_paged_comments', 'kris', 'rejected', '3'],
          ['get_paged_comments', 'kris', null, '2'],
          ['get_students', 'mit', 'tony-stark', '2000'],
          ['get_students', 'caltech', null, 1990],
          ['get_students', 'politecnico-milano'],
          ['get_model_by_filter', 'taxonomy', 'type', 'tag'],
        ],
        'reverseRoutesOutputs' => [
          '/users/',
          '/users/sformisano/',
          '/users/matt/comments/',
          '/users/james/comments/published/',
          '/users/kris/comments/rejected/3/',
          '/users/kris/comments/2/',
          '/schools/mit/teachers/tony-stark/students/2000/',
          '/schools/caltech/teachers/students/1990/',
          '/schools/politecnico-milano/teachers/students/',
          '/taxonomy/type/tag/'
        ]
      ]
    ];
  }

  function validReverseRoutesProviderWithNamespace()
  {
    $handler = function(){};

    return [
      'Valid entries' => [
        'routerArgs' => [ 'namespace' => '/jetrouter-api/v3' ],
        'routes' => [
          [
            'routeHttpMethod' => 'POST',
            'routePath' => 'users',
            'routeName' => 'create_user',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/users/{username}',
            'routeName' => 'get_user_by_username',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/users/{username}/comments/{filter}?/',
            'routeName' => 'get_user_comments',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/users/{username}/comments/{filter}?/{page}',
            'routeName' => 'get_paged_comments',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '/schools/{school_name:s}?/teachers/{teacher_name:s}?/students/{year:i}?',
            'routeName' => 'get_students',
            'routeHandler' => $handler
          ],
          [
            'routeHttpMethod' => 'GET',
            'routePath' => '{model:a}/{filter_type:s}/{filter_value:a}',
            'routeName' => 'get_model_by_filter',
            'routeHandler' => $handler
          ]
        ],

        'reverseRoutes' => [
          ['create_user'],
          ['get_user_by_username', 'sformisano'],
          ['get_user_comments', 'matt'],
          ['get_user_comments', 'james', 'published'],
          ['get_paged_comments', 'kris', 'rejected', '3'],
          ['get_paged_comments', 'kris', null, '2'],
          ['get_students', 'mit', 'tony-stark', '2000'],
          ['get_students', 'caltech', null, 1990],
          ['get_students', 'politecnico-milano'],
          ['get_model_by_filter', 'taxonomy', 'type', 'tag'],
        ],
        'reverseRoutesOutputs' => [
          '/jetrouter-api/v3/users/',
          '/jetrouter-api/v3/users/sformisano/',
          '/jetrouter-api/v3/users/matt/comments/',
          '/jetrouter-api/v3/users/james/comments/published/',
          '/jetrouter-api/v3/users/kris/comments/rejected/3/',
          '/jetrouter-api/v3/users/kris/comments/2/',
          '/jetrouter-api/v3/schools/mit/teachers/tony-stark/students/2000/',
          '/jetrouter-api/v3/schools/caltech/teachers/students/1990/',
          '/jetrouter-api/v3/schools/politecnico-milano/teachers/students/',
          '/jetrouter-api/v3/taxonomy/type/tag/'
        ]
      ]
    ];
  }


  /****************************************************************************/
  /* ROUTER INITIALIZATION TESTS                                              */
  /****************************************************************************/

  // WP Integration ____________________________________________________________

  function testWpAddAction()
  {
    $router = new Router();
    \WP_Mock::expectActionAdded('wp_loaded', [ $router, 'run' ], 1, 0);
    $router->init();
  }

  // Namespaces ________________________________________________________________

  /**
   * @dataProvider validNamespacesProvider
   */
  function testValidNamespaces($namespace)
  {
    $router = Router::create(['namespace' => $namespace]);
  }

  /**
   * @dataProvider             invalidNamespacesProvider
   * @expectedException        JetRouter\Exception\InvalidNamespaceException
   * @expectedExceptionMessage is not a valid namespace
   */
  function testInvalidNamespaces($namespace)
  {
    $router = Router::create(['namespace' => $namespace]);
  }

  // Output Formats ____________________________________________________________

  /**
   * @dataProvider validOutputFormatsProvider
   */
  function testValidOutputFormats($outputFormat)
  {
    $router = Router::create(['outputFormat' => $outputFormat]);
  }

  /**
   * @dataProvider             invalidOutputFormatsProvider
   * @expectedException        JetRouter\Exception\InvalidOutputFormatException
   * @expectedExceptionMessage is not a valid output format
   */
  function testInvalidOutputFormats($outputFormat)
  {
    $router = Router::create(['outputFormat' => $outputFormat]);
  }


  /****************************************************************************/
  /* ADDING ROUTES TESTS                                                      */
  /****************************************************************************/

  // HTTP Methods ______________________________________________________________

  /**
   * @dataProvider validHttpMethodsProvider
   */
  function testValidHttpMethods($httpMethod)
  {
    $router = Router::create();
    
    $router->addRoute($httpMethod, 'test', 'test_route', function(){
      return 'valid http method!';
    });

    $this->assertEquals('valid http method!', $router->dispatch($httpMethod, 'test'));
  }
  
  /**
   * @dataProvider             invalidHttpMethodsProvider
   * @expectedException        JetRouter\Exception\InvalidHttpMethodException
   * @expectedExceptionMessage is not a valid HTTP method
   */
  function testInvalidHttpMethods($httpMethod)
  {
    $router = Router::create();
    $router->addRoute($httpMethod, 'somewhere', 'sw_route', function(){});
  }

  /**
   * @dataProvider addRouteMethodAliasesProvider
   */
  function testAddRouteMethodAliases($httpMethodAlias, $httpMethod)
  {
    $router = Router::create();

    $router->$httpMethodAlias('best/resource', 'best_resource', function(){
      return 'best resource return';
    });

    $this->assertEquals(
      'best resource return',
      $router->dispatch($httpMethod, 'best/resource')
    );
  }

  // Route Names _______________________________________________________________

  /**
   * @dataProvider validRouteNamesProvider
   */  
  function testValidRouteNames($routeName)
  {
    $router = Router::create();

    $router->addRoute( 'GET', '/something', $routeName, function(){
      return 'valid route name!';
    });

    $this->assertEquals(
      'valid route name!',
      $router->dispatch('GET', '/something')
    );
  }

  /**
   * @dataProvider             invalidRouteNamesProvider
   * @expectedException        JetRouter\Exception\InvalidRouteException
   * @expectedExceptionMessage is not a valid route name
   */
  function testInvalidRouteNames($routeName)
  {
    $router = Router::create();

    $router->addRoute('GET', '/something', $routeName, function(){});
  }

  /**
   * @dataProvider             duplicateRoutesNamesProvider
   * @expectedException        JetRouter\Exception\InvalidRouteException
   * @expectedExceptionMessage A route named
   */
  function testDuplicateRouteNames($routerArgs, $routes){
    $router = $this->getRouter($routerArgs, $routes);
  }

  // Route Paths _______________________________________________________________

  /**
   * @dataProvider             invalidRoutePathsProvider
   * @expectedException        JetRouter\Exception\InvalidRouteException
   * @expectedExceptionMessage is not a valid route path
   */
  function testInvalidRoutePaths($routerArgs, $routes){
    $router = $this->getRouter($routerArgs, $routes);
  }

  /**
   * @dataProvider             routesWithDuplicatePathsProvider
   * @expectedException        JetRouter\Exception\InvalidRouteException
   * @expectedExceptionMessage Cannot register two routes matching
   */
  function testDuplicateRoutePaths($routerArgs, $routes){
    $router = Router::create($routerArgs);

    foreach($routes as $route){
      $router->addRoute(
        $route['routeHttpMethod'],
        $route['routePath'],
        $route['routeName'],
        $route['routeHandler']
      );
    }
  }

  /**
   * @dataProvider dynamicRoutesWithDuplicatePathsWithDiffRegexProvider
   */
  function testDuplicateRoutePathsWithDiffRegex($routerArgs, $routes)
  {
    $router = $this->getRouter($routerArgs, $routes);
  }

  /**
   * @dataProvider             dynamicRoutesWithDuplicateParamsNamesProvider
   * @expectedException        JetRouter\Exception\InvalidRouteException
   * @expectedExceptionMessage found more than once in route
   */
  function testDynamicRoutesWithDuplicateParamsNames($routerArgs, $routes)
  {
    $router = $this->getRouter($routerArgs, $routes);
  }


  /****************************************************************************/
  /* DISPATCHER TESTS                                                         */
  /****************************************************************************/

  /**
   * @dataProvider requestsToDispatchProvider
   */
  function testRequestsToDispatch($routerArgs, $routes, $request, $output)
  {
    $router = $this->getRouter($routerArgs, $routes);

    $this->assertEquals( 
      $output,
      $router->dispatch($request['routeHttpMethod'], $request['routePath'])
    );
  }

  /**
   * @dataProvider requestsToNotDispatchProvider
   */
  function testRequestsToNotDispatch($routerArgs, $routes, $request)
  {
    $router = Router::create($routerArgs);

    foreach($routes as $route){
      $router->addRoute(
        $route['routeHttpMethod'],
        $route['routePath'],
        $route['routeName'],
        $route['routeHandler']
      );
    }
    
    $this->assertEquals( 
      RequestDispatcher::NOT_DISPATCHED,
      $router->dispatch($request['routeHttpMethod'], $request['routePath'])
    );
  }

  /**
   * @dataProvider unallowedHttpMethodsProviders
   */
  function testRequestWithUnallowedHttpMethods($allowedHttpMethods, $unallowedHttpMethod)
  {
    $router = Router::create();

    $path = '/somewhere/over/the/rainbow/';
    $handler = function(){};

    foreach($allowedHttpMethods as $httpMethod){
      $name = 'test_' . $httpMethod;
      $router->addRoute($httpMethod, $path, $name, $handler);
    }

    $this->assertEquals( 
      RouteStore::NOT_FOUND,
      $router->dispatch($unallowedHttpMethod, $path)
    );
  }

  /**
   * @dataProvider staticRoutesOverridingDynamicRoutesProvider
   */
  function testStaticRouteOverridingDynamicRoute($routerArgs, $routes, $request, $output)
  {
    $router = $this->getRouter($routerArgs, $routes);

    $this->assertEquals( 
      $output,
      $router->dispatch($request['routeHttpMethod'], $request['routePath'])
    );
  }

  /**
   * @dataProvider optionalParamsRequestsToDispatchProvider
   */
  function testOptionalParamsRequestsToDispatch($routerArgs, $routes, $requests, $outputs)
  {
    $router = $this->getRouter($routerArgs, $routes);

    $i = 0;

    foreach($requests as $request){
      $this->assertEquals( 
        $outputs[$i],
        $router->dispatch($request['routeHttpMethod'], $request['routePath'])
      );

      $i++;
    }
  }

  /**
   * @dataProvider optionalParamsRequestsToNotDispatchProvider
   */
  function testOptionalParamsRequestsToNotDispatch($routerArgs, $routes, $requests)
  {
    $router = $this->getRouter($routerArgs, $routes);

    foreach($requests as $request){
      $this->assertEquals( 
        RouteStore::NOT_FOUND,
        $router->dispatch($request['routeHttpMethod'], $request['routePath'])
      );
    }
  }

  /**
   * @dataProvider             respondToInvalidTypesProvider
   * @expectedException        JetRouter\Exception\InvalidRouteHandlerException
   * @expectedExceptionMessage handler output property must be an array.
   */
  function testInvalidHandlerRespondToTypes($respondToValue)
  {
    $router = Router::create();

    $router->get('foo', 'get_foo', function() use($respondToValue){
      return ['respond_to' => $respondToValue];
    });

    $router->dispatch('GET', 'foo');
  }

  /**
   * @expectedException        JetRouter\Exception\InvalidRouteHandlerException
   * @expectedExceptionMessage Missing json output from respond_to route handler.
   */
  function testHandlerRespondToWithoutJsonKey()
  {
    $router = Router::create();

    $router->get('foo', 'get_foo', function(){
      return ['respond_to' => [ 'html' => '', 'something-else' => '', 'but-no-json!' => true ] ];
    });

    $router->dispatch('GET', 'foo');
  }

  /**
   * @expectedException        JetRouter\Exception\InvalidRouteHandlerException
   * @expectedExceptionMessage Missing html callback from respond_to route handler.
   */
  function testHandlerRespondToWithoutHtmlKey()
  {
    $router = Router::create();

    $router->get('foo', 'get_foo', function(){
      return ['respond_to' => [ 'json' => '', 'something-else' => '', 'but-no-html!' => true ] ];
    });

    $router->dispatch('GET', 'foo');
  }

  /**
   * @dataProvider             respondToInvalidHtmlElementTypesProvider
   * @expectedException        JetRouter\Exception\InvalidRouteHandlerException
   * @expectedExceptionMessage The html property of the respond_to route handler needs to be callable.
   */
  function testHandlerRespondToWithInvalidHtmlTypes($htmlValue)
  {
    $router = Router::create();

    $router->get('foo', 'get_foo', function() use($htmlValue){
      return ['respond_to' => [ 'json' => 'can be anything really', 'html' => $htmlValue ] ];
    });

    $router->dispatch('GET', 'foo');
  }

  function testValidHandlerRespondToOutput()
  {
    $router = Router::create();

    $router->get('foo', 'get_foo', function(){
      return ['respond_to' => [ 
        'json' => 'can be anything really',
        'html' => function(){
          return 'works fine';
        }
      ]];
    });

    $this->assertEquals(
      'works fine',
      $router->dispatch('GET', 'foo')
    );
  }

  function testHandlerReturningRespondToJsonBasedOnOutputFormatConfigValue()
  {
    $router = Router::create(['outputFormat' => 'json']);

    \WP_Mock::wpFunction( 'wp_send_json', array(
      'times' => 1,
      'return' => 'wp_send_json output',
    ) );

     $router->get('movies', 'get_movies', function(){
      return ['respond_to' => [ 
        'json' => null,
        'html' => function(){
          return 'movies list view loaded';
        }
      ]];
    });

    $this->assertEquals(
      'wp_send_json output',
      $router->dispatch('GET', 'movies')
    );
  }

  function testHandlerReturningRespondToJsonToXmlHttpRequest()
  {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

    \WP_Mock::wpFunction( 'wp_send_json', array(
      'times' => 1,
      'return' => 'json data output',
    ) );

    $router = Router::create();

    $router->get('users', 'get_users', function(){
      return ['respond_to' => [ 
        'json' => 'irrelevant data as wp_send_json return data is mocked above',
        'html' => function(){
          return 'users view loaded';
        }
      ]];
    });

    $this->assertEquals(
      'json data output',
      $router->dispatch('GET', 'users')
    );
  }

  function testHandlerReturningRespondToJsonToRequestWithJsonGetParam()
  {
    \WP_Mock::wpFunction( 'wp_send_json', array(
      'times' => 1,
      'return' => 'here are your files in json format',
    ) );

    // we're testing against definition itself, no need for truthy values
    $_GET['json'] = '';

    $router = Router::create();

    $router->get('files', 'get_files', function(){
      return ['respond_to' => [ 
        'json' => 'irrelevant data as wp_send_json return data is mocked above',
        'html' => function(){
          return 'files list view';
        }
      ]];
    });

    $this->assertEquals(
      'here are your files in json format',
      $router->dispatch('GET', 'files')
    );
  }

  function testHandlerReturningRespondToHtmlByDefault()
  {
    $router = Router::create();

    $router->get('laptops', 'get_laptops', function(){
      return ['respond_to' => [ 
        'json' => 'json data',
        'html' => function(){
          return 'laptops list view';
        }
      ]];
    });

    $this->assertEquals(
      'laptops list view',
      $router->dispatch('GET', 'laptops')
    );
  }

  function testHandlerReturningRespondToHtmlWithOutputFormatOverridingXmlHttpRequest()
  {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

    $router = Router::create(['outputFormat' => 'html']);

    $router->get('laptops', 'get_laptops', function(){
      return ['respond_to' => [ 
        'json' => 'json respond to data',
        'html' => function(){
          return 'laptops list view';
        }
      ]];
    });

    $this->assertEquals(
      'laptops list view',
      $router->dispatch('GET', 'laptops')
    );
  }

  
  /****************************************************************************/
  /* REVERSE ROUTER TESTS                                                     */
  /****************************************************************************/

  /**
   * @dataProvider validReverseRoutesProvider
   */
  function testValidReverseRoutes($routerArgs, $routes, $reverseRoutes, $reverseRoutesOutputs)
  {
    $router = $this->getRouter($routerArgs, $routes);

    $i = 0;

    foreach($reverseRoutes as $reverseRoute){
      $this->assertEquals(
        $reverseRoutesOutputs[$i],
        call_user_func_array( [$router, 'getThePath'], $reverseRoute )
      );

      $i++;
    }
  }

  /**
   * @dataProvider validReverseRoutesProviderWithNamespace
   */
  function testValidReverseRoutesWithNamespace($routerArgs, $routes, $reverseRoutes, $reverseRoutesOutputs)
  {
    $router = $this->getRouter($routerArgs, $routes);

    $i = 0;

    foreach($reverseRoutes as $reverseRoute){
      $this->assertEquals(
        $reverseRoutesOutputs[$i],
        call_user_func_array( [$router, 'getThePath'], $reverseRoute )
      );

      $i++;
    }
  }

  /**
   * @expectedException        JetRouter\Exception\InvalidRouteException
   * @expectedExceptionMessage Missing required parameter
   */
  function testMissingRequiredParameterInReverseRouter()
  {
    $router = Router::create();

    $router->delete('movies/{movie_name:s}', 'delete_movie', function(){});
    $router->thePath('delete_movie');
  }

  /**
   * @expectedException        JetRouter\Exception\InvalidRouteException
   * @expectedExceptionMessage Invalid parameter
   */
  function testInvalidParameterInReverseRouter()
  {
    $router = Router::create();

    $router->get('guitarists/{id:i}', 'get_guitarist', function(){});
    $router->thePath('get_guitarist', 'yngwie-malmsteen');
  }

  /**
   * @expectedException        JetRouter\Exception\InvalidRouteException
   * @expectedExceptionMessage Too many parameters for route
   */
  function testTooManyParametersReverseRouterException()
  {
    $router = Router::create();

    $router->put('tv-shows/{show_name:s}', 'update_tv_show', function(){});
    $router->thePath('update_tv_show', 'game-of-thrones', 'season-2');
  }


  // test namespace is in the output
  // test no route with name 
  // test missing parameter/s

  // fixme test with routes with same path existing (static and dynamic) the
  // right handler is selected depending on the method

  // check if param custom regex is valid at addRoute time


  // fixme we may wanna look into same dynamic routes with optional and non optional param but equal in everything else,
  // and how they work / how the routes behaves with this case
  // 
  // fixme test default param value in dynamic routes with optional params (right now it does not seem to work if you set a default in the param definition in the callback, e.g. function($foo = 'bar'){
  // foo is null!
  // })
  // 
  // fixme maybe we should have all router instances add their routes to a class static property so one can know if different router instances have conflicting routes
}