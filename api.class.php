<?php

/** 
 * Class created by Matheus Marques https://github.com/Matheus2212
 * 2021-06-12 -> First version of the API class
 * 2021-06-18 -> Updated ALL methods for the class
 * 2021-06-19 -> Defined methods for routes and route groups for the class
 * 2021-06-20 -> removed all own methods and made the API class work together with the Friendly URL class
 * 2021-06-22 -> Fully integrated API class with Friendly URL class (now its a dependency)
 *  */

class API
{

        private $version = 0;

        private $url = null;

        private $request = null;

        private $URLclass = null;

        private $routes = array();

        private $data = array();

        private $mode = "";

        public function __construct($url, $version)
        {
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT');

                if ($url && is_object($url)) {
                        $this->version = $version;
                        $this->url = $url->getURL() . "v" . explode(".", $this->version)[0] . "/";
                        $now = str_replace(preg_replace("/http(s)?\:\/\//", "", $url->getURL()), "", preg_replace("/http(s)?\:\/\//", "", $url->agora()));
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
                $this->join(array('version' => $this->version));
                return $this->version;
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
                sort($parts);
                $route = $parts[0];
                $this->routes[$route] = array("callback" => $callback);
                if (isset($parts[1])) {
                        $this->routes[$route]["url"] = $parts[1];
                }
        }

        public function route()
        {
                $operation = $this->URLclass->get(0);
                if (isset($this->routes[$operation])) {
                        $reflection = new ReflectionFunction($this->routes[$operation]["callback"]);
                        $totalParams = $reflection->getNumberOfParameters();
                        if ($totalParams > 1) {
                                if ($this->request === null) {
                                        $this->join(array("error" => "002", "message" => "No request was sent"));
                                } else {
                                        $this->routes[$operation]["callback"]($this, $this->request, true);
                                }
                        } else {
                                $this->routes[$operation]["callback"]($this);
                        }
                } else {
                        $this->join(array("error" => "001", "message" => "This route doesn't exists"));
                }
        }

        private function APIReturn()
        {
                switch ($this->mode) {
                        case "json":
                                function recursive_json($data)
                                {
                                        if (is_array($data)) {
                                                return array_map("recursive_json", $data);
                                        } else {
                                                return utf8_encode($data);
                                        }
                                }

                                return json_encode(recursive_json($this->data));
                                break;
                }
        }

        public function type($type)
        {
                switch ($type) {
                        case "json":
                                header("Content-type: application/json");
                                $this->request = json_decode(file_get_contents('php://input'), true);
                                $this->mode = "json";
                                break;
                }
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
