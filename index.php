<?php

require "./config.php";
//require "../php-database-class/db.class.php";
require "../php-friendly-urls-class/url.class.php"; // you can find this class in other repository
require "./RoutesClass.php";

$url = new URL($url);

$api = new ROUTE($url, $APIversion);



$api->define("/version/{{id}}/more", function ($api, $request) {
        $api->join($request);
});

$api->define("/version/check/more", function ($api) {
        $api->version();
});

$api->define("/version/", function ($api) {
        $api->version();
});

$api->define("/version/check/more/this", function ($api) {
        $api->version();
});

$api->define("/", function ($api) {
        $api->version();
});
/*$api->define("/version", function ($api) {
        $api->version();
});

$api->define("/login", function ($api, $request) {
        $email = $request["email"];
});

$api->define("/login/check", function ($api) {
        echo "ops";
});

$api->define("/login/{{id}}", function ($api) {
        echo "opa, esse tem parametro";
});

$api->define("/", function ($api) {
});*/

$api->route($url->now(true));

$api->response('echo');
