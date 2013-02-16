<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

require 'vendor/autoload.php';

$app = new Silex\Application();

$app->get('/login', function (Request $request) {
    $session = $request->getSession();

    $username = $request->server->get('PHP_AUTH_USER');
    $password = $request->server->get('PHP_AUTH_PW');

    if ('igor' === $username && 'password' === $password) {
        $session->set('user', array('username' => $username));
        return new RedirectResponse('/account');
    }

    $response = new Response();
    $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'site_login'));
    $response->setStatusCode(401, 'Please sign in.');
    return $response;
});

$app->get('/account', function (Request $request) {
    $session = $request->getSession();

    if (null === $user = $session->get('user')) {
        return new RedirectResponse('/login');
    }

    return "Welcome {$user['username']}!";
});

$stack = (new Stack\Stack())
    ->push('Stack\Session');

$app = $stack->resolve($app);

$request = Request::createFromGlobals();
$response = $app->handle($request)->send();
$app->terminate($request, $response);
