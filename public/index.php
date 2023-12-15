<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

// Подключение сторонних библиотек
use Slim\Factory\AppFactory;
use DI\Container;
use App\Validator;

// СТАРТ СЕССИИ
session_start();

// ПОДКЛЮЧЕНИЕ КОНТЕЙНЕРОВ
$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();

/* $app->get('/users', function ($req, $res) {
    $this->get('flash')->addMessage('success', 'This is a message');
    return $res->withRedirect('/users');
}); */

// СЧИТЫВАНИЕ ДАННЫХ ИЗ JSON
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
$repo = getArrayFromJson('./src/repo.json');

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

// ДОМАШНЯЯ СТРАНИЦА
$app->get('/', function ($request, $response) use ($router) {
    $router->urlFor('users');
    $router->urlFor('user/new');
    return $response->write('Welcome to Slim!');
});

/* $app->get('/users', function ($request, $response) {
    // return $response->write('GET /users');
    return $this->get('renderer')->render($response, 'users/new.phtml');
}); */

/* $app->get('/users', function ($request, $response, array $args)  use ($users) {
    $term = $request->getQueryParam('term');
    $sortedUsers = array_filter($users, fn($user) => str_contains($user, $term));
    $params = ['users' => $sortedUsers, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);;
}); */

// ПОСТРАНИЧНЫЙ ВЫВОД СПИСКА ПОЛЬЗОВАТЕЛЕЙ
$app->get('/users', function ($request, $response) use ($repo) {
    // $allPosts = $repo->all();
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $users = array_slice($repo, ($page - 1) * $per, $per);
    $params = [
    'users' => $users,
    'page' => $page
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
    //            ->get('flash')->addMessage('success', 'This is a message');
})->setName('users');

/* $app->get('/users', function ($request, $response) use ($repo, $user_id) {
    $params = [
        'users' => $repo
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users'); */

// ДОБАВЛЕНИЕ ПОЛЬЗОВАТЕЛЯ
$app->post('/users', function ($request, $response) use ($repo) {
    $validator = new Validator();
    $user_id = count($repo);
    // $user['id'] = $user_id;
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        $user['id'] = $user_id;
        $repo[] = $user;
        file_put_contents('./src/repo.json', json_encode($repo));
        // $this->get('flash')->addMessage('success', 'User has been created');
        return $response->withRedirect('/users', 302)
                        ->get('flash')->addMessage('success', 'User has been created');
    }
    $params = [
        'user' => $user,
        'errors' => $errors,
        'flash' => $flash
    ];
    return $this->get('renderer')
                ->render($response->withStatus(422), 'users/new.phtml', $params);
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'id' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('user/new');

// ИЗМЕНЕНИЕ ПОЛЬЗОВАТЕЛЯ
$app->get('/users/{id}/edit', function ($request, $response, array $args) use ($repo) {
    $id = $args['id'];
    $user = array_filter($repo, fn($user) => $user['id'] == $id)[$id];
    // $user = $repo->find($id);
    $params = [
        'user' => $user,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('editUser');

$app->patch('/users/{id}', function ($request, $response, $args) use ($repo, $router) {
    // $repo = new App\PostRepository();
    $id = $args['id'];
    $user = array_filter($repo, fn($user) => $user['id'] == $id)[$id];
    // $user = $repo->find($id);
    $newUser = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        $user['name'] = $newUser['name'];
        $user['body'] = $newUser['body'];
        $this->get('flash')->addMessage('success', 'User has been updated');
        $repo->save($user);
        $url = $router->urlFor('editUser', ['id' => $user['id']]);
        return $response->withRedirect($url);
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'posts/edit.phtml', $params);
});

// $app->post('/users', function ($request, $response) {
//    return $response->withStatus(302);
// });

/* $app->get('/users/{id}', function ($request, $response, array $args) use ($repo) {
    $id = $args['id'];
    $user = array_filter($repo, fn($user) => $user['id'] == $id);
    // var_dump($user);
    if (!$user) {
        return $response->write('Page not found')
                        ->withStatus(404);
    }
    return $response->write("User {$id} is called {$user[$id]['name']}!");
})->setName('user'); */

$app->get('/posts', function ($request, $response) use ($repo) {
    $allPosts = $repo->all();
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $posts = array_slice($allPosts, ($page - 1) * $per, $per);
    // var_dump($allPosts);
    $params = [
    'posts' => $posts,
    'page' => $page
    ];
    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
})->setName('posts');

$app->get('/users/{id}', function ($request, $response, array $args) use ($repo) {
    $id = $args['id'];
    $user = array_filter($repo, fn($user) => $user['id'] == $id)[$id];
    if (!$user) {
        return $response->write('Page not found')
                        ->withStatus(404);
    }
    $params = [
    'user' => $user
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

/* $app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
}); */

$app->run();
