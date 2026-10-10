<?php
// Populate HTTP_AUTHORIZATION and define BEARER_TOKEN constant for APIs
if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } else {
        if (function_exists('apache_request_headers')) {
            $h = apache_request_headers();
            if (!empty($h['Authorization'])) $_SERVER['HTTP_AUTHORIZATION'] = $h['Authorization'];
            elseif (!empty($h['authorization'])) $_SERVER['HTTP_AUTHORIZATION'] = $h['authorization'];
        }
    }
}

if (!defined('BEARER_TOKEN')) {
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
        define('BEARER_TOKEN', $m[1]);
    } else {
        define('BEARER_TOKEN', null);
    }
}

?>
