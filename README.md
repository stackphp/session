# Stack/Session

Session stack middleware.

Enables the request session for subsequent middlewares.

## Example

Here's an example giving a silex app access to the session using stack/stack:

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpFoundation\RedirectResponse;

    $app = new Silex\Application();

    $app->get('/login', function (Request $request) {
        $session = $request->getSession();

        $username = $request->server->get('PHP_AUTH_USER');
        $password = $request->server->get('PHP_AUTH_PW');

        if ('igor' === $username && 'password' === $password) {
            $session->set('user', array('username' => $username));
            return new RedirectResponse('/account');
        }

        return new Response('Please sign in.', 401, [
            'WWW-Authenticate' => sprintf('Basic realm="%s"', 'site_login'),
        ]);
    });

    $app->get('/account', function (Request $request) {
        $session = $request->getSession();

        if (null === $user = $session->get('user')) {
            return new RedirectResponse('/login');
        }

        return sprintf('Welcome %s!', $user['username']);
    });

    $stack = (new Stack\Builder())
        ->push('Stack\Session');

    $app = $stack->resolve($app);

## Options

The following options can be used to configure stack/session:

* **session.storage.save_path** (optional): The path for the
  NativeFileSessionHandler, defaults to the value of `sys_get_temp_dir()`.

* **session.storage.options** (optional): An array of options that is passed to
  the constructor of the session.storage service.

  In case of the default NativeSessionStorage, the possible options are listed
  on [the PHP manual's session configuration page](http://php.net/session.configuration).

* **session.default_locale** (optional): The default locale, defaults to `en`.

* **session.cookie_params** (optional): Override parameter values for the session cookie
  as listed on [the PHP manual's session_get_cookie_params page](http://www.php.net/manual/en/function.session-get-cookie-params.php).
  (allowed keys: `lifetime`, `path`, `domain`, `secure`, and `httponly`)

## Usage

The session middleware enables the `Session` object on the request. You can
access it through the `Request` object:

    $session = $request->getSession();

    $session->start();
    $foo = $session->get('foo');
    $session->set('foo', 'bar');

## Silex SessionServiceProvider

Note that this middleware is a replacement for the silex
SessionServiceProvider. If you want to use it with silex, you might want to
define the `session` service as follows:

    $app['session'] = $app->share(function ($app) {
        return $app['request']->getSession();
    });

This is only needed if you have services that depend on the `session` service.

## Inspiration

This middleware is based on the silex SessionServiceProvider.
