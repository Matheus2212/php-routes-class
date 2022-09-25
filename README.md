# php Routes class

A REST PHP Routes class.

It uses the express (node.js) route concepts, in a very simple way.

This class needs the [Friendly URL](https://github.com/Matheus2212/php-friendly-urls-class) class as dependency and .htaccess file.

---

## How to use

### version()

Displays the API current Version

### join($array, $status = true)

It merges an array with the response of the API. $status set the response status to true or false

### define($route, $callback)

It sets a new Route within a callback. You can set dynamic routes like this:

```php
// URL = /version/1487
$api->define("/route/{{id}}", function ($api,$request) {
        $api->join($request); // you will see on the response an "id" position with "1487" as its value
});
```

### route()

It basically calls the API, to execute whatever that route should do.

### response($echo = false)

It will send the response. if $echo == true, will print it on the screen, otherwise will return as a JSON object
