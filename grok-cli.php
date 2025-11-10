#!/usr/bin/env php
<?php
/*
 * Grok CLI v2.0 - Inteligência PHP com Segurança, Git & Refatoração Automatizada
 * Autor: André (@SantosDiaX)
 * Ambiente: Ubuntu 20.04 | PHP 8.3+ | Grok-4-fast-non-reasoning
 * Modos: [Expert Mode] [Advanced Mode] [Code Mode] [Maximum Truth-Seeking] [Pro Mode]
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($_ENV['GROK_API_KEY']) || !isset($_ENV['GROK_MODEL'])) {
    fwrite(STDERR, "Erro: GROK_API_KEY e GROK_MODEL devem estar no .env\n");
    exit(1);
}

final class GrokCLI extends Application
{
    private const INPUT_PRICE = 0.20;
    private const OUTPUT_PRICE = 0.50;
    private const LOG_DIR = __DIR__ . '/logs';
    private const API_ENDPOINT = 'https://api.x.ai/v1/chat/completions';

    private Client $http;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        parent::__construct('Grok CLI v2.0 - PHP Security & Refactor Pro', '2.0.0');
        $this->apiKey = $_ENV['GROK_API_KEY'];
        $this->model = $_ENV['GROK_MODEL'] ?? 'grok-4-fast-non-reasoning';
        $this->http = new Client(['timeout' => 180]);

        if (!is_dir(self::LOG_DIR)) mkdir(self::LOG_DIR, 0755, true);
        if (!is_dir(__DIR__ . '/.git') && !file_exists(__DIR__ . '/README.md')) {
            $this->initGitProject();
        }
    }

    private function initGitProject(): void
    {
        $process = new Process(['git', 'init']);
        $process->run();

        if (!file_exists(__DIR__ . '/README.md')) {
            file_put_contents(__DIR__ . '/README.md', $this->generateReadme());
        }

        if (!file_exists(__DIR__ . '/LICENSE')) {
            file_put_contents(__DIR__ . '/LICENSE', $this->generateMitLicense());
        }

        $this->gitAddAndCommit('chore: init project with Grok CLI, README.md and MIT License');
    }

    private function generateReadme(): string
    {
        return "# Projeto PHP com Grok CLI\n\n"
             . "Gerenciado por IA com análise de segurança, refatoração, testes e documentação.\n"
             . "Última atualização: " . date('Y-m-d H:i') . "\n\n"
             . "## Comandos disponíveis\n"
             . "- `grok analyze` - Analisa código e segurança\n"
             . "- `grok refactor` - Refatora com correção de bugs e melhorias\n"
             . "- `grok test` - Gera testes PHPUnit\n"
             . "- `grok doc` - Gera PHPDoc ou Markdown\n";
    }

    private function generateMitLicense(): string
    {
        return "MIT License\n\n"
             . "Copyright (c) " . date('Y') . " André Santos (@SantosDiaX)\n\n"
             . "Permission is hereby granted, free of charge, to any person obtaining a copy...\n"
             . "(Texto completo da licença MIT - padrão)";
    }

    private function gitAddAndCommit(string $message): void
    {
        (new Process(['git', 'add', '.']))->run();
        (new Process(['git', 'commit', '-m', $message]))->run();
    }

    protected function getDefaultCommands(): array
    {
        return array_merge(parent::getDefaultCommands(), [
            new AnalyzeCommand(),
            new RefactorCommand(),
            new GenerateCommand(),
            new TestCommand(),
            new DocCommand(),
        ]);
    }

    private function callGrok(string $prompt, array $context = []): array
    {
        $inputTokens = $this->estimateTokens($prompt . implode("\n", $context));
        
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt()],
                ...array_map(fn($c) => ['role' => 'user', 'content' => $c], $context),
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.2,
            'max_tokens' => 4000
        ];

        $response = $this->http->post(self::API_ENDPOINT, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => $payload
        ]);

        $data = json_decode($response->getBody(), true);
        $output = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'];

        $outputTokens = $usage['completion_tokens'];
        $cost = ($inputTokens * self::INPUT_PRICE + $outputTokens * self::OUTPUT_PRICE) / 1_000_000;

        $this->logUsage($prompt, $output, $inputTokens, $outputTokens, $cost);

        return [
            'response' => $output,
            'tokens_in' => $inputTokens,
            'tokens_out' => $outputTokens,
            'cost' => $cost
        ];
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
Você é um especialista em PHP 8.3+, segurança OWASP, PSR-12, SOLID, Clean Code e arquitetura defensiva.
Corrija bugs de sintaxe, XSS, SQLi, CSRF, RCE, path traversal, injeção de comando.
Use prepared statements, validação rigorosa, CSP, tipagem estrita, readonly, enums.
Detecte e corrija code smells, complexidade alta, acoplamento.
Refatore para alta coesão, baixa complexidade ciclomática.
Gere código seguro, testável, documentado.
PROMPT;
    }

    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 3.8);
    }

    private function logUsage(string $prompt, string $response, int $in, int $out, float $cost): void
    {
        $log = [
            'timestamp' => date('c'),
            'model' => $this->model,
            'tokens_in' => $in,
            'tokens_out' => $out,
            'cost_usd' => round($cost, 6),
            'prompt' => substr($prompt, 0, 200) . '...'
        ];

        $filename = self::LOG_DIR . '/grok_usage_' . date('Y-m-d') . '.log';
        file_put_contents($filename, json_encode($log) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function getGrokCaller(): callable
    {
        return fn($p, $c = []) => $this->callGrok($p, $c);
    }
}

class AnalyzeCommand extends Command
{
    protected static $defaultName = 'analyze';

    protected function configure(): void
    {
        $this->setDescription('Analisa código com foco em segurança, bugs e estrutura')
             ->addArgument('path', InputArgument::REQUIRED, 'Arquivo ou pasta')
             ->addOption('security', 's', InputOption::VALUE_NONE, 'Análise completa de segurança (OWASP)')
             ->addOption('apply', null, InputOption::VALUE_NONE, 'Aplica correções automáticas após análise');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $apply = $input->getOption('apply');
        $security = $input->getOption('security');
        $files = is_file($path) ? [$path] : $this->findPhpFiles($path);

        if (empty($files)) {
            $output->writeln('<error>Nenhum arquivo PHP encontrado</error>');
            return Command::FAILURE;
        }

        $grok = ($this->getApplication())->getGrokCaller();

        foreach ($files as $file) {
            $code = file_get_contents($file);
            $relative = str_replace(getcwd() . '/', '', $file);

            $prompt = $security
                ? "Análise de SEGURANÇA CRÍTICA (OWASP Top 10) + bugs de sintaxe + estrutura:\n"
                  . "Arquivo: {$relative}\n\nCódigo:\n```php\n{$code}\n```\n\n"
                  . "1. XSS, SQLi, CSRF, RCE, Path Traversal\n"
                  . "2. Validação de entrada/saída\n"
                  . "3. Erros de sintaxe PHP\n"
                  . "4. Code smells e complexidade\n"
                  . "5. Sugestões de correção com código refatorado"
                : "Análise estrutural e funcional do código PHP em {$relative}. "
                  . "Corrija bugs de sintaxe, melhore estrutura, aplique PSR-12.";

            $output->writeln("<info>[ANÁLISE] {$relative}</info>");
            $result = $grok($prompt, ["Arquivo: {$file}"]);

            $output->writeln("<comment>Relatório:</comment>\n" . $result['response']);
            $output->writeln("<info>Custo: \${$result['cost']}</info>");

            if ($apply) {
                $this->applyRefactorFromAnalysis($file, $result['response'], $output, $grok);
            }

            $output->writeln("");
        }

        return Command::SUCCESS;
    }

    private function applyRefactorFromAnalysis(string $file, string $analysis, OutputInterface $output, callable $grok): void
    {
        $prompt = "Com base na análise acima, retorne APENAS o código PHP corrigido e seguro:\n\n"
                . "```php\n" . file_get_contents($file) . "\n```";

        $result = $grok($prompt);
        preg_match('/```php\n(.*?)\n```/s', $result['response'], $matches);
        $fixed = $matches[1] ?? $result['response'];

        copy($file, $file . '.bak.' . date('YmdHis'));
        file_put_contents($file, $fixed);

        $output->writeln("<info>Correções aplicadas com backup: {$file}.bak.*</info>");
        (new GrokCLI())->gitAddAndCommit("fix: segurança e estrutura em " . basename($file));
    }

    private function findPhpFiles(string $dir): array
    {
        if (!is_dir($dir)) return [];
        $finder = new Finder();
        $finder->files()->in($dir)->name('*.php')->exclude(['vendor', 'node_modules', 'tests']);
        return array_map(fn($f) => $f->getRealPath(), iterator_to_array($finder));
    }
}

class RefactorCommand extends Command
{
    protected static $defaultName = 'refactor';

    protected function configure(): void
    {
        $this->setDescription('Refatora código com instrução personalizada')
             ->addArgument('path', InputArgument::REQUIRED, 'Arquivo ou pasta')
             ->addArgument('instruction', InputArgument::OPTIONAL, 'Instrução entre aspas', '')
             ->addOption('apply', null, InputOption::VALUE_NONE, 'Aplica refatoração')
             ->addOption('backup', null, InputOption::VALUE_NONE, 'Cria backup')
             ->addOption('security', 's', InputOption::VALUE_NONE, 'Força análise de segurança');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $instruction = $input->getArgument('instruction');
        $apply = $input->getOption('apply');
        $backup = $input->getOption('backup');
        $security = $input->getOption('security');
        $files = is_file($path) ? [$path] : (new AnalyzeCommand())->findPhpFiles($path);

        $grok = ($this->getApplication())->getGrokCaller();

        foreach ($files as $file) {
            $code = file_get_contents($file);
            $relative = str_replace(getcwd() . '/', '', $file);

            $basePrompt = $security
                ? "Refatore com foco em SEGURANÇA + correção de bugs + PSR-12 + tipagem estrita:\n"
                : "Refatore o código PHP com melhorias estruturais e boas práticas:\n";

            $custom = $instruction ? " Instrução adicional: {$instruction}" : "";
            $prompt = $basePrompt . $custom . "\n\nCódigo:\n```php\n{$code}\n```\n\n"
                    . "Retorne APENAS o código refatorado em bloco ```php";

            $output->writeln("<info>[REFATORAÇÃO] {$relative}</info>");
            $result = $grok($prompt);

            preg_match('/```php\n(.*?)\n```/s', $result['response'], $matches);
            $refactored = $matches[1] ?? $result['response'];

            if ($apply) {
                if ($backup) copy($file, $file . '.bak.' . date('YmdHis'));
                file_put_contents($file, $refactored);
                $output->writeln("<info>Refatoração aplicada: {$relative}</info>");
                (new GrokCLI())->gitAddAndCommit("refactor: {$relative} - " . ($instruction ?: 'melhorias'));
            } else {
                $output->writeln("<comment>Código sugerido:</comment>\n" . trim($refactored));
            }

            $output->writeln("<info>Custo: \${$result['cost']}</info>\n");
        }

        return Command::SUCCESS;
    }
}

// [GenerateCommand, TestCommand, DocCommand permanecem iguais - omitidos por brevidade]
// (Você pode manter os originais do código anterior)

class GenerateCommand extends Command { /* ... mesmo que antes ... */ }
class TestCommand extends Command { /* ... mesmo que antes ... */ }
class DocCommand extends Command { /* ... mesmo que antes ... */ }

// Executa CLI
(new GrokCLI())->run();
