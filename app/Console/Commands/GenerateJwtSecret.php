<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateJwtSecret extends Command
{
    protected $signature = 'jwt:secret';

    protected $description = 'Gera a chave JWT no arquivo .env local';

    public function handle(): int
    {
        $path = app()->environmentFilePath();
        if (! is_file($path)) {
            $this->error('Crie o arquivo .env a partir do .env.example.');

            return self::FAILURE;
        }
        $content = file_get_contents($path);
        if (preg_match('/^JWT_SECRET=.+$/m', $content)) {
            $this->error('JWT_SECRET já está configurado. A chave existente foi preservada.');

            return self::FAILURE;
        }
        $line = 'JWT_SECRET='.bin2hex(random_bytes(32));
        $content = preg_match('/^JWT_SECRET=.*$/m', $content)
            ? preg_replace('/^JWT_SECRET=.*$/m', $line, $content)
            : rtrim($content).PHP_EOL.$line.PHP_EOL;
        if (file_put_contents($path, $content) === false) {
            $this->error('Não foi possível gravar o arquivo .env.');

            return self::FAILURE;
        }
        $this->info('Chave JWT gerada. Se houver configuração em cache, execute config:clear.');

        return self::SUCCESS;
    }
}
