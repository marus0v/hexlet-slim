<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Validator;

function getFileData(string $fileName): string
{
    if (!file_exists($fileName)) {
        return throw new \Exception("File not found: '$fileName'");
    }
    return file_get_contents($fileName);
}

function getArrayFromJson(string $fileName)
{
    $file = getFileData($fileName);
    return $fileArray = json_decode($file, true);
}

// $users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

/* $app->get('/users', function ($request, $response) {
    return $response->write('GET /users');
}); */

/* $app->get('/users', function ($request, $response, array $args)  use ($users) {
    $term = $request->getQueryParam('term');
    $sortedUsers = array_filter($users, fn($user) => str_contains($user, $term));
    $params = ['users' => $sortedUsers, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);;
}); */

$repo = file_get_contents('./src/repo.json');
// var_dump($repo);

$app->post('/users', function ($request, $response) use ($repo) {
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        $repo[] = $user;
        file_put_contents('./src/repo.json', $repo)
        return $response->withRedirect('/users', 302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')
                ->render($response->withStatus(422), 'users/new.phtml', $params);
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

// $app->post('/users', function ($request, $response) {
//    return $response->withStatus(302);
// });

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

/* $app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
}); */

$app->run();
