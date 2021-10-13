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
        $password = sha1($request["password"]);
        $data = db::fetch("SELECT * FROM tb_users WHERE email = '$email' AND password='$password'");
        if (db::empty($data)) {
                $api->join(array("cerror" => 'custom_01', "message" => "User doesn't exists"), false);
        } else {
                unset($data['password'], $data['id']);
                if ($data["active"] == "n") {
                        $api->join(array_merge($data, array("cerror" => 'custom_02', "message" => "User is not active")));
                } else {
                        db::update(array('last_logged' => db::date()), "tb_users", array("token" => $data["token"]));
                        $api->join($data);
                }
        }
});

$api->route($url->agora(true));

$api->response('echo');
