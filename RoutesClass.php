<?php

/** 
 * Class created by Matheus Marques https://github.com/Matheus2212
 * 2021-06-12 -> First version of the API class
 * 2021-06-18 -> Updated ALL methods for the class
 * 2021-06-19 -> Defined methods for routes and route groups for the class
 * 2021-06-20 -> removed all own methods and made the API class work together with the Friendly URL class
 * 2021-06-22 -> Fully integrated API class with Friendly URL class (now its a dependency)
 * 2021-10-28 -> Updated Class. It now can be used on production
 * 2021-11-09 -> Added $middleware and $requestHeaders as variable
 * 2022-03-29 -> refactor: Improved route method, to call if from another route. Added some documentation on the class file.
 * 2022-04-04 -> refactor: Improved middleware method, to get data while on the route function, sending the class as reference and parameter
 * 2022-09-25 -> refactor: Renamed class to use with PHPUnit
 *  */

class ROUTE
{
        // Application name
        private $name = "DEFAULT";

        // Middlewares array of callbacks
        private $middleware = array();

        // Application version
        private $version = 0;

        // Application URL
        private $url = null;

        // Headers array
        private $requestHeaders = null;

        // Body array
        private $requestBody = null;

        // URL Class instance (this class could extends the friendly URLs one, but let's keep it somehow independant)
        public $URLclass = null;

        // All routes array
        private $routes = array();

        // Data array, from the route callback
        private $data = array();

        // Constructor method. Will define almost everything the API needs to be ready to work.
        public function __construct($url, $info)
        {
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT');
                header("Content-type: application/json");
                $this->requestBody = json_decode(file_get_contents('php://input'), true);
                if ($this->requestBody == null) {
                        $this->requestBody = array();
                }
                if ($url && is_object($url)) {
                        $urlNow = $url->now();
                        $this->requestHeaders = getallheaders();
                        $this->name = $info['name'];
                        if (isset($info['version'])) {
                                $this->version = $info['version'];
                                $this->url = $url->getURL() . "v" . $this->version . "/";
                        } else {
                                $this->url = $url->getURL();
                        }
                        if (substr($urlNow, -1) !== "/") {
                                $urlNow = $urlNow . "/";
                        }
                        $url->__construct($this->url, $url->__get("get"));
                        $now = str_replace(preg_replace("/http(s)?\:\/\//", "", $url->getURL()), "", preg_replace("/http(s)?\:\/\//", "", $urlNow));
                        $this->parts = explode("/", $now);
                        $this->URLclass = $url;
                        $this->URLclass->partes = $this->parts;
                }
        }

        /**
         * @param $key = Property
         * @param $value = Property value
         */
        public function __set($key, $value)
        {
                $this->$key = $value;
        }

        /**
         * @return $array returns data inside $data array
         * 
         */
        public function getData()
        {
                return $this->data;
        }

        /**
         * @return $array returns data inside $data array
         * 
         */
        public function getBaseURL()
        {
                return $this->url;
        }

        /**
         * @return array Returns the Application version
         */
        public function version()
        {
                $this->join(array('name' => $this->name, 'version' => $this->version, 'baseURL' => $this->getBaseURL()));
                return array('name' => $this->name, 'version' => $this->version);
        }

        /**
         * @param array $array Data to be joined on response
         * @param bool $status Define if the response will have a true or false output status
         */
        public function join($array, $status = true)
        {
                $this->data = array_merge($this->data, $array);
                $this->data = array_merge($this->data, array("status" => ($status ? "true" : "false")));
                if (isset($this->data['error'])) {
                        $this->data['status'] = "false";
                }
                $this->data["time"] = time();
                return $this;
        }

        /**
         * @param string $route Defines the route
         * @param function $callback Defines the callback for that route
         */
        public function define($route, $callback)
        {
                $parts = explode("/", $route);
                $parts = array_filter($parts, function ($item) {
                        if (trim($item) === "") {
                                return false;
                        } else {
                                return true;
                        }
                });
                $parts = array_values($parts);
                foreach ($parts as $key => $value) {
                        $parts[$key] = "/" . $parts[$key];
                }
                $recursiveRoute = function ($recursiveRoute, $previous = "", &$arrayParent, $arrayTarget, $callback) {
                        $previous = ($previous == "" ? (isset($arrayTarget[0]) ? $arrayTarget[0] : "") : $previous);
                        $position = (isset($arrayTarget[0]) ? $arrayTarget[0] : "");
                        if (!array_key_exists($position, $arrayParent)) {
                                $arrayParent[$position] = array();
                        }
                        unset($arrayTarget[0]);
                        if (empty($arrayTarget)) {
                                $arrayParent[$position]['callback'] = $callback;
                        } else {
                                $recursiveRoute($recursiveRoute, $position, $arrayParent[$position], array_values($arrayTarget), $callback);
                        }
                };
                $recursiveRoute($recursiveRoute, "", $this->routes, $parts, $callback);
        }

        /**
         * @param function $callback Defines the callback BEFORE the ROUTE
         * @param array $routesArray Defines WHICH ROUTES the callback will be applied.
         */
        public function middleware($callback, $routesArray)
        {
                if (is_array($routesArray)) {
                        foreach ($routesArray as $route) {
                                $parts = explode("/", $route);
                                $parts = array_filter($parts, function ($item) {
                                        if (trim($item) === "") {
                                                return false;
                                        } else {
                                                return true;
                                        }
                                });
                                $parts = array_values($parts);
                                foreach ($parts as $key => $value) {
                                        $parts[$key] = "/" . $parts[$key];
                                }
                                $recursiveRoute = function ($recursiveRoute, $previous = "", &$arrayParent, $arrayTarget, $callback) {
                                        $previous = ($previous == "" ? (isset($arrayTarget[0]) ? $arrayTarget[0] : "") : $previous);
                                        $position = (isset($arrayTarget[0]) ? $arrayTarget[0] : "");
                                        if (!array_key_exists($position, $arrayParent)) {
                                                $arrayParent[$position] = array();
                                        }
                                        unset($arrayTarget[0]);
                                        if (empty($arrayTarget)) {
                                                $arrayParent[$position]['middleware'] = $callback;
                                        } else {
                                                $recursiveRoute($recursiveRoute, $position, $arrayParent[$position], array_values($arrayTarget), $callback);
                                        }
                                };
                                $recursiveRoute($recursiveRoute, "", $this->routes, $parts, $callback);
                        }
                } else {
                        if (is_string($routesArray)) {
                                if ($routesArray == "*") {
                                        $this->middleware[] = $callback;
                                }
                        }
                }
        }

        /**
         * @param string $route Is the route you want to call or it gets automatically
         * @param array $requestBody Is the data you want to send to the route
         * @param array $requestHeaders is the data you want to send to the route
         */
        public function route($route = false, $requestBody = false, $requestHeaders = false)
        {
                $recursiveRoute = function ($recursiveRoute, &$class, &$allRoutes, $currentRoute, $counter, $requestBody = false, $requestHeaders = false) {
                        $operation = isset($currentRoute[0]) ? $currentRoute[0] : "";
                        unset($currentRoute[0]);
                        if (!isset($allRoutes[$operation])) {
                                foreach (array_keys($allRoutes) as $value) {
                                        if (preg_match("/\{\{/", $value)) {
                                                $class->requestBody = array_merge($class->requestBody, array(preg_replace("/(?:\/\{\{)(.*?)(?:\}\})/", "$1", $value) => preg_replace("/\//", "", $class->URLclass->get($counter))));
                                                $operation = $value;
                                        }
                                }
                        }
                        if (isset($allRoutes[$operation])) {
                                if (isset($allRoutes[$operation]["middleware"])) {
                                        if (!$allRoutes[$operation]["middleware"]($class, ($requestHeaders ? array_merge($class->requestHeaders, $requestHeaders) : $class->requestHeaders), ($requestBody ? array_merge($class->requestBody, $requestBody) : $class->requestBody))) {
                                                return false;
                                        }
                                }
                                if (!empty($currentRoute)) {
                                        $recursiveRoute($recursiveRoute, $class, $allRoutes[$operation], array_values($currentRoute), ++$counter, $requestBody, $requestHeaders);
                                } else {
                                        if (isset($allRoutes[$operation]["middleware"])) {
                                                if (!$allRoutes[$operation]["middleware"]($class, ($requestHeaders ? array_merge($class->requestHeaders, $requestHeaders) : $class->requestHeaders), ($requestBody ? array_merge($class->requestBody, $requestBody) : $class->requestBody))) {
                                                        return false;
                                                }
                                        }
                                        if (isset($allRoutes[$operation]["callback"])) {
                                                $reflection = new ReflectionFunction($allRoutes[$operation]["callback"]);
                                                $totalParams = $reflection->getNumberOfParameters();
                                                if ($totalParams > 1) {
                                                        if (empty($class->requestBody) && !$requestBody) {
                                                                $class->join(array("message" => "No request was sent", 'baseURL' => $class->url, 'route' => $class->URLclass->now()));
                                                        } else {
                                                                $allRoutes[$operation]["callback"]($class, ($requestBody ? array_merge($class->requestBody, $requestBody) : $class->requestBody), ($requestHeaders ? array_merge($class->requestHeaders, $requestHeaders) : $class->requestHeaders));
                                                        }
                                                } else {
                                                        $allRoutes[$operation]["callback"]($class);
                                                }
                                        } else {
                                                $class->join(array("message" => "This route doesn't exists", 'baseURL' => $class->url, 'route' => $class->URLclass->now()));
                                        }
                                }
                        } else {
                                $class->join(array("message" => "This route doesn't exists", 'baseURL' => $class->url, 'route' => $class->URLclass->now()));
                        }
                };
                if (!empty($this->middleware)) {
                        foreach ($this->middleware as $callback) {
                                if (!$callback($this, $this->requestHeaders)) {
                                        return false;
                                }
                        }
                }
                if ($route) {
                        $parts = array_map(function ($part) {
                                return ($part !== "" ? "/" . $part : false);
                        }, explode("/", $route));
                        unset($parts[0]);
                        $parts = array_values($parts);
                }
                $recursiveRoute($recursiveRoute, $this, $this->routes, (isset($parts) ? $parts : $this->URLclass->getParts()), 0, $requestBody, $requestHeaders);
        }

        /**
         * @return string Return a JSON array with all data on the Route
         */
        private function APIReturn()
        {
                function recursive_json($data)
                {
                        if (is_array($data)) {
                                return array_map("recursive_json", $data);
                        } else {
                                return utf8_encode($data);
                        }
                }

                return json_encode(recursive_json($this->data));
        }

        /**
         * @param bool $echo Will define it the class itself will echo the output
         * @return string will output all data managed in the request
         */
        public function response($echo = false)
        {
                $data = $this->APIReturn();
                if ($echo) {
                        echo $data;
                }
                return $data;
        }
}
