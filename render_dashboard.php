<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::query()->first();
if (!$user) {
    $user = App\Models\User::query()->create([
        'name' => 'Temp User',
        'username' => 'tempuser',
        'email' => 'tempuser@example.com',
        'password' => bcrypt('password12345'),
        'role' => 'user',
        'is_admin' => false,
    ]);
}

Illuminate\Support\Facades\Auth::login($user);
$request = Illuminate\Http\Request::create('/dashboard', 'GET');
$request->setUserResolver(fn () => $user);
$request->headers->set('Accept', 'text/html');

$response = app(App\Http\Controllers\DashboardController::class)->index($request);
$content = method_exists($response, 'render') ? $response->render() : $response->getContent();
file_put_contents(__DIR__ . '/dashboard_rendered.html', $content);
echo strlen($content) . "\n";
