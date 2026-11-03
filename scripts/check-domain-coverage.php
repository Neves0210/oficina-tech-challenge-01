<?php

// Clover emits absolute paths; normalize separators for both Windows and Unix.
$path = $argv[1] ?? __DIR__.'/../build/coverage/clover.xml';
if (! is_file($path) || ! ($report = simplexml_load_file($path))) {
    fwrite(STDERR, "Relatório Clover ausente ou inválido.\n");
    exit(1);
}
$domains = [];
foreach ($report->xpath('//file') as $file) {
    $name = str_replace('\\', '/', (string) $file['name']);
    if (! preg_match('~/app/Domain/([^/]+)/~', $name, $match)) {
        continue;
    }
    $domain = $match[1];
    $domains[$domain] ??= ['lines' => 0, 'covered' => 0];
    foreach ($file->line as $line) {
        if ((string) $line['type'] === 'stmt') {
            $domains[$domain]['lines']++;
            $domains[$domain]['covered'] += (int) $line['count'] > 0 ? 1 : 0;
        }
    }
}
if ($domains === []) {
    fwrite(STDERR, "Nenhum domínio encontrado no relatório Clover.\n");
    exit(1);
}
ksort($domains);
$failed = false;
foreach ($domains as $domain => $metrics) {
    if ($metrics['lines'] === 0) {
        echo "{$domain}: sem linhas executáveis (contratos).\n";

        continue;
    }
    $percentage = 100 * $metrics['covered'] / $metrics['lines'];
    printf("%s: %d/%d linhas (%.2f%%).\n", $domain, $metrics['covered'], $metrics['lines'], $percentage);
    $failed = $failed || $percentage < 80;
}
exit($failed ? 1 : 0);
