<?php

require_once __DIR__.'/../vendor/autoload.php';
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Psr\Log\LogLevel;
use Silex\Application;

$app = new Silex\Application();

$app['upload_dir'] = '/tmp/uploads/';

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../app.log',
    'monolog.level' => LogLevel::ERROR
));

$app['app.token_authenticator'] = function (Application $app) {
    return new App\Security\TokenAuthenticator($app['security.encoder_factory']);
};

$app->register(new Silex\Provider\SecurityServiceProvider(), [
  'security.firewalls' => [
    'main' => [
        'stateless' => true,
        'guard' => [
            'authenticators' => [
                'app.token_authenticator',
            ],
        ],
        'users' => array(
            'alice' => [
                'ROLE_OWNER',
                password_hash('alice', PASSWORD_BCRYPT, ['cost' => 13])
            ],
            'bob' => [
                'ROLE_OWNER',
                password_hash('bob', PASSWORD_BCRYPT, ['cost' => 13])
            ],
        ),
    ],
  ],
]);

function createFilename (Request $request, $name) {
    return $request->user->getUsername() . '-' . sha1($name);
};

// inject our user object into the incoming request so it's easily available
$app->before(function (Request $request, Application $app) {
    $request->user = $app['security.token_storage']->getToken()->getUser();
});

$app->post('/files', function (Request $request) use ($app) {
    $file = $request->files->get('file');
    if ($file === NULL) {
        return new Response('No file provided', 400);
    }

    $filename = createFilename($request, $file->getClientOriginalName());

    if (!file_exists($app['upload_dir'] . $filename)) {
        $file->move($app['upload_dir'], $filename);
    } else {
        // file already exists, just unlink the uploaded one
        unlink($file->getRealPath());
    }

    return new Response('Success', 200);
});

$app->get('/files/{name}', function (Request $request, $name) use ($app) {
    $filename = createFilename($request, $name);

    if (!file_exists($app['upload_dir'] . $filename)) {
        return new Response('File not found', 404);
    }

    // set the original name as content disposition
    $response = $app->sendFile($app['upload_dir'] . $filename);
    $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $name
    );

    return $response;
});

$app->delete('/files/{name}', function (Request $request, $name) use ($app) {
    $filename = createFilename($request, $name);
    $deleted = unlink($app['upload_dir'] . $filename);

    if ($deleted) {
        return new Response('File deleted', 200);
    } else {
        return new Response('File not found', 404);
    }
});

$app->run();
