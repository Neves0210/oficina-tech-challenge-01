<?php

// Execute somente no banco exclusivo de testes; nunca limpa o banco da oficina.
use App\Domain\Materials\MaterialType;
use App\Domain\Materials\MovementDirection;
use App\Domain\Materials\Quantity;
use App\Domain\Orders\BudgetDecision;
use App\Domain\Orders\OrderStatus;
use App\Domain\Shared\BusinessRuleViolation;
use App\Infrastructure\Persistence\Customer;
use App\Infrastructure\Persistence\EloquentBudgetRepository;
use App\Infrastructure\Persistence\EloquentExecutionRepository;
use App\Infrastructure\Persistence\EloquentMaterialRepository;
use App\Infrastructure\Persistence\EloquentOrderRepository;
use App\Infrastructure\Persistence\Material;
use App\Infrastructure\Persistence\OrderBudget;
use App\Infrastructure\Persistence\ServiceOrder;
use App\Infrastructure\Persistence\StockMovement;
use App\Infrastructure\Persistence\Vehicle;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'oficina_testing') {
    fwrite(STDERR, "Exige APP_ENV=testing, DB_CONNECTION=mysql e DB_DATABASE=oficina_testing.\n");
    exit(1);
}

if (($argv[1] ?? '') === '--worker') {
    $connection = DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
    $started = microtime(true);
    echo "READY\n";
    flush();
    try {
        app(EloquentExecutionRepository::class)->start((int) $argv[2], (int) $argv[3]);
        $result = 'started';
    } catch (BusinessRuleViolation $exception) {
        $result = 'insufficient_stock';
    }
    echo json_encode(['result' => $result, 'connection' => $connection, 'started' => $started, 'finished' => microtime(true)], JSON_THROW_ON_ERROR)."\n";
    exit(0);
}

function verify(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

Artisan::call('migrate', ['--force' => true]);
$reports = [];
for ($round = 1; $round <= 3; $round++) {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = Customer::firstOrCreate(['document' => '52998224725'], ['name' => 'Concorrência', 'document_type' => 'CPF']);
    $vehicle = Vehicle::firstOrCreate(['plate' => 'CON1234'], ['customer_id' => $customer->id, 'brand' => 'Fiat', 'model' => 'Uno', 'year' => 2014]);
    $materials = app(EloquentMaterialRepository::class);
    $material = $materials->save(MaterialType::SUPPLY, ['name' => 'Insumo concorrência '.$round, 'unit' => 'L', 'price_cents' => 100, 'active' => true]);
    $materials->move(MaterialType::SUPPLY, $material['id'], MovementDirection::IN, new Quantity('0.5'), 'Preparação do teste', $admin->id);
    $orders = [];
    foreach ([1, 2] as $index) {
        $repository = app(EloquentOrderRepository::class);
        $order = $repository->open([
            'customer_document' => $customer->document, 'vehicle_plate' => $vehicle->plate,
            'complaint' => 'Teste de disputa '.$index,
            'items' => [['kind' => 'SUPPLY', 'catalog_id' => $material['id'], 'quantity' => '0.5']],
        ], $admin->id);
        $repository->diagnose($order['id'], 'Consumo de meio litro', $admin->id);
        $budget = app(EloquentBudgetRepository::class)->present($order['id'], $admin->id);
        OrderBudget::findOrFail($budget['id'])->update(['decision' => BudgetDecision::APPROVED, 'decided_at' => now()]);
        $orders[] = $order['id'];
    }

    // Mantém o saldo bloqueado até os dois processos independentes iniciarem.
    DB::beginTransaction();
    Material::whereKey($material['id'])->lockForUpdate()->firstOrFail();
    $workers = [];
    try {
        foreach ($orders as $orderId) {
            $process = proc_open([PHP_BINARY, __FILE__, '--worker', (string) $orderId, (string) $admin->id], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            verify(is_resource($process), 'Não foi possível iniciar o processo concorrente.');
            stream_set_timeout($pipes[1], 20);
            $workers[] = ['process' => $process, 'pipes' => $pipes];
        }
        foreach ($workers as $worker) {
            verify(trim((string) fgets($worker['pipes'][1])) === 'READY', 'Worker não iniciou dentro do prazo.');
        }
        usleep(500000);
        DB::commit();
        $results = [];
        foreach ($workers as $worker) {
            $output = stream_get_contents($worker['pipes'][1]);
            $error = stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][1]);
            fclose($worker['pipes'][2]);
            verify(proc_close($worker['process']) === 0, 'Worker falhou: '.$error);
            $results[] = json_decode(trim($output), true, flags: JSON_THROW_ON_ERROR);
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($workers as $worker) {
            if (is_resource($worker['process'])) {
                proc_terminate($worker['process']);
                proc_close($worker['process']);
            }
        }
    }
    $outcomes = array_column($results, 'result');
    sort($outcomes);
    verify($outcomes === ['insufficient_stock', 'started'], 'Esperado exatamente um sucesso e uma rejeição.');
    verify($results[0]['connection'] !== $results[1]['connection'], 'As conexões devem ser independentes.');
    verify(max(array_column($results, 'started')) < min(array_column($results, 'finished')), 'As execuções devem se sobrepor.');
    foreach ($results as $result) {
        verify($result['finished'] - $result['started'] >= 0.45, 'Worker deve aguardar o bloqueio real do saldo.');
    }
    verify(Material::findOrFail($material['id'])->stock_quantity === '0.000', 'Saldo final deve ser zero.');
    verify(StockMovement::where('material_id', $material['id'])->where('direction', 'OUT')->count() === 1, 'Deve haver uma única baixa.');
    $rows = ServiceOrder::whereIn('id', $orders)->get();
    verify($rows->where('status', OrderStatus::EXECUTING)->count() === 1, 'Uma OS deve iniciar.');
    $loser = $rows->firstWhere('status', OrderStatus::WAITING);
    verify($loser !== null && $loser->started_at === null && $loser->history()->count() === 3, 'OS rejeitada deve permanecer intacta.');
    $reports[] = ['round' => $round, 'independent_connections' => true, 'overlapping_executions' => true, 'outcomes' => $outcomes, 'final_stock' => '0.000', 'out_movements' => 1, 'loser_rolled_back' => true];
}
echo json_encode(['database' => 'MySQL', 'version' => DB::selectOne('SELECT VERSION() AS version')->version, 'rounds' => $reports, 'passed' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
