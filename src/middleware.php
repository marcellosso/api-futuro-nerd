<?php
// Application middleware
$app->add(new \Tuupola\Middleware\Cors([
    'origin'         => ['*'],
    'methods'        => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'headers.allow'  => ["Origin", "Content-Type", "Authorization", "Accept", "ignoreLoadingBar", "X-Requested-With", "Access-Control-Allow-Origin"],
    'headers.expose' => [],
    'credentials'    => false,
    'cache'          => 0,
]));

// $app->add(function($request, $response, $next) {
//     $route = $request->getAttribute("route");

//     $methods = [];

//     if (!empty($route)) {
//         $pattern = $route->getPattern();

//         foreach ($this->router->getRoutes() as $route) {
//             if ($pattern === $route->getPattern()) {
//                 $methods = array_merge_recursive($methods, $route->getMethods());
//             }
//         }
//         //Methods holds all of the HTTP Verbs that a particular route handles.
//     } else {
//         $methods[] = $request->getMethod();
//     }

//     $response = $next($request, $response);


//     return $response->withHeader("Access-Control-Allow-Methods", implode(",", $methods));
// });