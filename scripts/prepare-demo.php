<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local') || DB::connection()->getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'oficina') {
    fwrite(STDERR, "Use somente no ambiente Docker local com o banco oficina.\n");
    exit(1);
}
$password = bin2hex(random_bytes(18));
$email = 'demo.'.bin2hex(random_bytes(6)).'@example.test';
$user = new User;
$user->name = 'Administrador de demonstração';
$user->email = $email;
$user->password = Hash::make($password);
$user->is_admin = true;
$user->save();
$values = [];
foreach (['base_url' => getenv('APP_URL'), 'admin_email' => $email, 'admin_password' => $password] as $key => $value) {
    $values[] = ['key' => $key, 'value' => $value, 'type' => $key === 'admin_password' ? 'secret' : 'default', 'enabled' => true];
}
file_put_contents(__DIR__.'/../build/postman-local.environment.json', json_encode([
    'name' => 'Oficina local - somente neste computador', 'values' => $values, '_postman_variable_scope' => 'environment',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
echo "Administrador criado. Importe build/postman-local.environment.json no Postman.\nNão publique esse arquivo: contém a senha local.\n";
