<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Helper\TerminalInputHelper;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Cria uma conta administrativa sem expor a senha no histórico do terminal';

    public function handle(): int
    {
        // O Symfony muda a code page durante a leitura no Windows. Carregar antes
        // evita autoload com o caminho acentuado interpretado na codificação OEM.
        if (PHP_OS_FAMILY === 'Windows') {
            class_exists(TerminalInputHelper::class);
        }

        $data = [
            'name' => $this->ask('Nome'),
            'email' => $this->ask('E-mail'),
            'password' => $this->secret('Senha (mínimo 12 caracteres)'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:255'],
        ], [
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto.',
            'max' => 'O campo :attribute deve ter no máximo :max caracteres.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe uma conta com esse e-mail.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
        ], [
            'name' => 'nome',
            'email' => 'e-mail',
            'password' => 'senha',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->is_admin = true;
        $user->save();
        $this->info('Conta administrativa criada.');

        return self::SUCCESS;
    }
}
