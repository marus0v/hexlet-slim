<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);

/* $faker = \Faker\Factory::create();
$faker->seed(1234);

$domains = [];
for ($i = 0; $i < 10; $i++) {
    $domains[] = $faker->domainName;
}

$phones = [];
for ($i = 0; $i < 10; $i++) {
    $phones[] = $faker->phoneNumber;
} */

// $app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

/* $app->get('/users', function ($request, $response) {
    return $response->write('GET /users');
}); */

$app->get('/users', function ($request, $response, array $args)  use ($users) {
    $term = $request->getQueryParam('term');
    foreach ($users as $user) {
        if (str_contains($user, $term)) {
            $sortedUsers[] = $user;
        }
    }
    // $sortedUsers = array_filter($users, fn($user) => str_contains($user, $term) )
    $params = ['users' => $sortedUsers, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);;
});

$app->post('/users', function ($request, $response) {
    return $response->write('POST /users');
});

// $app->post('/users', function ($request, $response) {
//    return $response->withStatus(302);
// });

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->run();
