<?php

require "./config.php";
require "./db.class.php";
require "./url.class.php";
require "./api.class.php";

$url = new URL($url);

$api = new API($url, $version);

$api->type("json");

$api->define("/version", function ($api) {
        $api->version();
});

$api->define("/login", function ($api, $request) {
        $email = $request["email"];
});

$api->route($url->agora(true));

$api->response('echo');
