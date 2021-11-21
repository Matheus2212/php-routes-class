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
 *  */

class API
{
        private $name = "DEFAULT";

        private $middleware = array();

        private $version = 0;

        private $url = null;

        private $requestHeaders = null;

        private $requestBody = null;

        public $URLclass = null;

        private $routes = array();

        private $data = array();

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
                        $url->__construct($this->url);
                        $now = str_replace(preg_replace("/http(s)?\:\/\//", "", $url->getURL()), "", preg_replace("/http(s)?\:\/\//", "", $urlNow));
                        $this->parts = explode("/", $now);
                        $this->URLclass = $url;
                        $this->URLclass->partes = $this->parts;
                }
        }

        public function __set($key, $value)
        {
                $this->$key = $value;
        }

        public function version()
        {
                $this->join(array('name' => $this->name, 'version' => $this->version, 'baseURL' => $this->url));
                return array('name' => $this->name, 'version' => $this->version);
        }

        public function join($array, $status = true)
        {
                $this->data = array_merge($this->data, $array);
                $this->data = array_merge($this->data, array("status" => $status));
                if (isset($this->data['error'])) {
                        $this->data['status'] = false;
                }
                $this->data["time"] = time();
                return $this;
        }

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

        public function route()
        {
                $recursiveRoute = function ($recursiveRoute, &$class, &$allRoutes, $currentRoute, $counter) {
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
                                if (!empty($currentRoute)) {
                                        $recursiveRoute($recursiveRoute, $class, $allRoutes[$operation], array_values($currentRoute), ++$counter);
                                } else {
                                        if (isset($allRoutes[$operation]["middleware"])) {
                                                if (!$allRoutes[$operation]["middleware"]($this, $this->requestHeaders, $this->requestBody)) {
                                                        return false;
                                                }
                                        }
                                        if (isset($allRoutes[$operation]["callback"])) {
                                                $reflection = new ReflectionFunction($allRoutes[$operation]["callback"]);
                                                $totalParams = $reflection->getNumberOfParameters();
                                                if ($totalParams > 1) {
                                                        if (empty($class->requestBody)) {
                                                                $class->join(array("error" => "002", "message" => "No request was sent", 'baseURL' => $this->url, 'route' => $this->URLclass->now()));
                                                        } else {
                                                                $allRoutes[$operation]["callback"]($class, $class->requestBody, $class->requestHeaders);
                                                        }
                                                } else {
                                                        $allRoutes[$operation]["callback"]($class);
                                                }
                                        } else {
                                                $class->join(array("error" => "001", "message" => "This route doesn't exists", 'baseURL' => $this->url, 'route' => $this->URLclass->now()));
                                        }
                                }
                        } else {
                                $class->join(array("error" => "001", "message" => "This route doesn't exists", 'baseURL' => $this->url, 'route' => $this->URLclass->now()));
                        }
                };
                if (!empty($this->middleware)) {
                        foreach ($this->middleware as $callback) {
                                if (!$callback($this, $this->requestHeaders)) {
                                        return false;
                                }
                        }
                }
                $recursiveRoute($recursiveRoute, $this, $this->routes, $this->URLclass->getParts(), 0);
        }

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

        public function response($echo = false)
        {
                $data = $this->APIReturn();
                if ($echo) {
                        echo $data;
                }
                return $data;
        }
}
