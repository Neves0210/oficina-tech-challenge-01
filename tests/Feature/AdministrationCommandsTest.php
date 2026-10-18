<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AdministrationCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_created_with_a_hashed_password(): void
    {
        $this->artisan('app:create-admin')->expectsQuestion('Nome', 'Administrador')
            ->expectsQuestion('E-mail', 'admin@example.test')
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'senha-local-de-teste')
            ->assertSuccessful();
        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('senha-local-de-teste', $user->password));
    }

    public function test_weak_password_does_not_create_an_admin(): void
    {
        $this->artisan('app:create-admin')->expectsQuestion('Nome', 'Administrador')
            ->expectsQuestion('E-mail', 'admin@example.test')
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'curta')
            ->expectsOutput('A senha deve ter pelo menos 12 caracteres.')
            ->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_real_console_input_works_from_a_windows_path_with_accents(): void
    {
        if (PHP_OS_FAMILY !== 'Windows' || ! preg_match('/[^\x00-\x7F]/', base_path())) {
            $this->markTestSkipped('Regressão específica de caminhos com acentos no Windows.');
        }

        $script = <<<'PHP'
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        exit($app->handleCommand(new Symfony\Component\Console\Input\ArgvInput(['artisan', 'app:create-admin'])));
        PHP;

        $process = new Process([PHP_BINARY, '-r', $script], base_path(), [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
            'CACHE_STORE' => 'array',
        ]);
        $process->setInput("Administrador\nadmin@example.test\ncurta\n");
        $process->run();

        $output = $process->getOutput().$process->getErrorOutput();
        $this->assertStringNotContainsString('Fatal error', $output);
        $this->assertStringContainsString('Senha (mínimo 12 caracteres)', $output);
        $this->assertSame(1, $process->getExitCode());
    }

    public function test_jwt_key_generation_preserves_an_existing_key(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'oficina-env-');
        try {
            file_put_contents($path, "APP_ENV=testing\nJWT_SECRET=\n");
            app()->useEnvironmentPath(dirname($path))->loadEnvironmentFrom(basename($path));
            $this->artisan('jwt:secret')->assertSuccessful();
            $generated = file_get_contents($path);
            $this->assertMatchesRegularExpression('/^JWT_SECRET=[a-f0-9]{64}$/m', $generated);
            $this->artisan('jwt:secret')->assertFailed();
            $this->assertSame($generated, file_get_contents($path));
        } finally {
            unlink($path);
        }
    }
}
