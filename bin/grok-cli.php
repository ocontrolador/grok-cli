#!/usr/bin/env php
<?php
/*
 * Grok CLI v2.4 - CORRIGIDO: Conteúdo do arquivo enviado + gitAddAndCommit público
 * Autor: André (@SantosDiaX)
 * Data: 10 de novembro de 2025, 19:25 -03
 * Modos: [Expert Mode] [Pro Mode] [Code Mode] [Maximum Truth-Seeking]
 */

require_once __DIR__ . '/../vendor/autoload.php';

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

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

if (!isset($_ENV['GROK_API_KEY']) || !isset($_ENV['GROK_MODEL'])) {
    fwrite(STDERR, "Erro: GROK_API_KEY e GROK_MODEL devem estar no .env\n");
    exit(1);
}

final class GrokCLI extends Application
{
    private const INPUT_PRICE = 0.20;
    private const OUTPUT_PRICE = 0.50;
    private const LOG_DIR = __DIR__ . '/../logs';
    private const API_ENDPOINT = 'https://api.x.ai/v1/chat/completions';

    private Client $http;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        parent::__construct('Grok CLI v2.4 - PHP Security & Refactor Pro', '2.4.0');
        $this->apiKey = $_ENV['GROK_API_KEY'];
        $this->model = $_ENV['GROK_MODEL'] ?? 'grok-4-fast-non-reasoning';
        $this->http = new Client(['timeout' => 180]);

        if (!is_dir(self::LOG_DIR)) mkdir(self::LOG_DIR, 0755, true);

        if (!is_dir(__DIR__ . '/../.git') && !file_exists(__DIR__ . '/../README.md')) {
            $this->initGitProject();
        }
    }

    private function initGitProject(): void
    {
        (new Process(['git', 'init'], __DIR__ . '/..'))->mustRun();

        $root = __DIR__ . '/..';
        if (!file_exists($root . '/README.md')) {
            file_put_contents($root . '/README.md', $this->generateReadme());
        }
        if (!file_exists($root . '/LICENSE')) {
            file_put_contents($root . '/LICENSE', $this->generateMitLicense());
        }
        $this->gitAddAndCommit('chore: initialize Grok CLI project');
    }

    private function generateReadme(): string
    {
        return "# Grok CLI\nInteligência PHP com Segurança, Refatoração e Git\n";
    }

    private function generateMitLicense(): string
    {
        return "MIT License\n\nCopyright (c) " . date('Y') . " André Santos (@SantosDiaX)\n\nPermission is hereby granted...\n";
    }

    // MÉTODO PÚBLICO AGORA
    public function gitAddAndCommit(string $message): void
    {
        $root = __DIR__ . '/..';
        (new Process(['git', 'add', '.'], $root))->mustRun();
        (new Process(['git', 'commit', '-m', $message, '--allow-empty'], $root))->mustRun();
    }

    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new AnalyzeCommand();
        $commands[] = new RefactorCommand();
        $commands[] = new GenerateCommand();
        $commands[] = new TestCommand();
        $commands[] = new DocCommand();
        return $commands;
    }

    private function callGrok(string $prompt, array $context = []): array
    {
        $inputText = $prompt . implode("\n", $context);
        $inputTokens = $this->estimateTokens($inputText);

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

        try {
            $response = $this->http->post(self::API_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            $data = json_decode($response->getBody(), true);
            $output = $data['choices'][0]['message']['content'] ?? '';
            $usage = $data['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0];

            $outputTokens = $usage['completion_tokens'];
            $cost = ($inputTokens * self::INPUT_PRICE + $outputTokens * self::OUTPUT_PRICE) / 1_000_000;

            $this->logUsage($prompt, $output, $inputTokens, $outputTokens, $cost);

            return [
                'response' => $output,
                'tokens_in' => $inputTokens,
                'tokens_out' => $outputTokens,
                'cost' => $cost
            ];
        } catch (\Exception $e) {
            return [
                'response' => "Erro na API Grok: " . $e->getMessage(),
                'tokens_in' => 0,
                'tokens_out' => 0,
                'cost' => 0
            ];
        }
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
Você é um especialista em PHP 8.3+, segurança OWASP, PSR-12, SOLID, Clean Code.
Corrija bugs, vulnerabilidades (XSS, SQLi, RCE, CSRF, path traversal).
Use tipagem estrita, prepared statements, validação, readonly, enums.
Refatore para alta coesão, baixa complexidade.
Retorne apenas código ou análise clara.
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
            'prompt_preview' => substr($prompt, 0, 150) . (strlen($prompt) > 150 ? '...' : '')
        ];

        $filename = self::LOG_DIR . '/grok_usage_' . date('Y-m-d') . '.log';
        file_put_contents($filename, json_encode($log) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function getGrokCaller(): callable
    {
        return fn($p, $c = []) => $this->callGrok($p, $c);
    }
}

// === COMANDO: ANALYZE ===
class AnalyzeCommand extends Command
{
    protected static $defaultName = 'analyze';

    protected function configure(): void
    {
        parent::configure();
        $this->setName('analyze');
        $this->setDescription('Analisa código PHP com foco em segurança e estrutura')
             ->addArgument('path', InputArgument::REQUIRED, 'Caminho do arquivo ou pasta')
             ->addOption('security', 's', InputOption::VALUE_NONE, 'Análise completa de segurança (OWASP)')
             ->addOption('apply', null, InputOption::VALUE_NONE, 'Aplica correções automaticamente');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $security = $input->getOption('security');
        $apply = $input->getOption('apply');
        $files = is_file($path) ? [$path] : $this->findPhpFiles($path);

        if (empty($files)) {
            $output->writeln('<error>Nenhum arquivo PHP encontrado em: ' . $path . '</error>');
            return Command::FAILURE;
        }

        $grok = ($this->getApplication())->getGrokCaller();

        foreach ($files as $file) {
            $code = file_get_contents($file);
            if ($code === false || trim($code) === '') {
                $output->writeln("<error>Arquivo vazio ou ilegível: {$file}</error>");
                continue;
            }

            $relative = str_replace(getcwd() . '/', '', $file);

            $prompt = $security
                ? "ANÁLISE COMPLETA DE SEGURANÇA (OWASP Top 10) + BUGS + SINTAXE + ESTRUTURA\n"
                  . "Arquivo: {$relative}\n"
                  . "Código completo:\n```php\n{$code}\n```\n\n"
                  . "Forneça:\n"
                  . "1. Vulnerabilidades (XSS, SQLi, RCE, etc.)\n"
                  . "2. Erros de sintaxe\n"
                  . "3. Code smells\n"
                  . "4. Sugestões de correção com código refatorado"
                : "ANÁLISE ESTRUTURAL E FUNCIONAL DO CÓDIGO PHP\n"
                  . "Arquivo: {$relative}\n"
                  . "Código:\n```php\n{$code}\n```\n"
                  . "Corrija bugs de sintaxe, aplique PSR-12, tipagem estrita, SOLID.";

            $output->writeln("<info>[ANÁLISE] {$relative}</info>");
            $result = $grok($prompt);  // REMOVIDO contexto desnecessário

            $output->writeln("<comment>Relatório:</comment>\n" . $result['response']);
            $output->writeln("<info>Custo estimado: \${$result['cost']}</info>\n");

            if ($apply) {
                $this->applyFixes($file, $code, $output, $grok);
            }
        }

        return Command::SUCCESS;
    }

    private function applyFixes(string $file, string $originalCode, OutputInterface $output, callable $grok): void
    {
        $prompt = "Com base na análise anterior, retorne APENAS o código PHP corrigido e seguro:\n\n"
                . "Código original:\n```php\n{$originalCode}\n```";

        $result = $grok($prompt);
        preg_match('/```php\n(.*?)\n```/s', $result['response'], $matches);
        $fixed = $matches[1] ?? trim($result['response']);

        if (empty(trim($fixed))) {
            $output->writeln("<error>Nenhum código corrigido retornado pelo Grok.</error>");
            return;
        }

        $backup = $file . '.bak.' . date('YmdHis');
        if (!copy($file, $backup)) {
            $output->writeln("<error>Falha ao criar backup: {$backup}</error>");
            return;
        }

        if (file_put_contents($file, $fixed) === false) {
            $output->writeln("<error>Falha ao salvar arquivo corrigido.</error>");
            return;
        }

        $output->writeln("<info>Correções aplicadas → backup: {$backup}</info>");

        // Usa o método público da aplicação
        $this->getApplication()->gitAddAndCommit("fix: análise aplicada em " . basename($file));
    }

    private function findPhpFiles(string $dir): array
    {
        if (!is_dir($dir)) return [];
        $finder = new Finder();
        $finder->files()->in($dir)->name('*.php')->exclude(['vendor', 'node_modules', 'tests', 'logs']);
        return array_map(fn($f) => $f->getRealPath(), iterator_to_array($finder));
    }
}

// === COMANDO: REFACTOR ===
class RefactorCommand extends Command
{
    protected static $defaultName = 'refactor';

    protected function configure(): void
    {
        parent::configure();
        $this->setName('refactor');
        $this->setDescription('Refatora código com instrução personalizada')
             ->addArgument('path', InputArgument::REQUIRED, 'Arquivo ou pasta')
             ->addArgument('instruction', InputArgument::OPTIONAL, 'Instrução entre aspas', '')
             ->addOption('apply', null, InputOption::VALUE_NONE, 'Aplica refatoração')
             ->addOption('backup', null, InputOption::VALUE_NONE, 'Cria backup')
             ->addOption('security', 's', InputOption::VALUE_NONE, 'Força foco em segurança');
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
            if ($code === false || trim($code) === '') continue;

            $relative = str_replace(getcwd() . '/', '', $file);

            $base = $security
                ? "REFACTOR COM FOCO EM SEGURANÇA (OWASP) + PSR-12 + TIPAGEM ESTRITA:\n"
                : "REFACTOR COM MELHORIAS ESTRUTURAIS E BOAS PRÁTICAS:\n";

            $custom = $instruction ? " Instrução adicional: {$instruction}" : "";
            $prompt = $base . $custom . "\n\nCódigo:\n```php\n{$code}\n```\n\n"
                    . "Retorne APENAS o código refatorado em bloco ```php";

            $output->writeln("<info>[REFATORAÇÃO] {$relative}</info>");
            $result = $grok($prompt);

            preg_match('/```php\n(.*?)\n```/s', $result['response'], $matches);
            $refactored = $matches[1] ?? trim($result['response']);

            if ($apply) {
                if ($backup) {
                    $bak = $file . '.bak.' . date('YmdHis');
                    copy($file, $bak);
                    $output->writeln("<comment>Backup: {$bak}</comment>");
                }
                file_put_contents($file, $refactored);
                $output->writeln("<info>Refatoração aplicada: {$relative}</info>");
                $this->getApplication()->gitAddAndCommit("refactor: {$relative} - " . ($instruction ?: 'melhorias'));
            } else {
                $output->writeln("<comment>Código sugerido:</comment>\n" . $refactored);
            }

            $output->writeln("<info>Custo: \${$result['cost']}</info>\n");
        }

        return Command::SUCCESS;
    }
}

// === COMANDO: GENERATE, TEST, DOC (mantidos, sem alterações críticas) ===
// [GenerateCommand, TestCommand, DocCommand] permanecem iguais ao v2.3

class GenerateCommand extends Command
{
    protected static $defaultName = 'generate';

    protected function configure(): void
    {
        parent::configure();
        $this->setName('generate');
        $this->setDescription('Gera código PHP a partir de instrução')
             ->addArgument('instruction', InputArgument::REQUIRED, 'Instrução em linguagem natural')
             ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Arquivo de saída', 'generated.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instruction = $input->getArgument('instruction');
        $file = $input->getOption('output');

        $grok = ($this->getApplication())->getGrokCaller();
        $prompt = "Gere código PHP 8.3+ completo para: {$instruction}\n"
                . "Use tipagem, PSR-12, classes, traits, enums. Retorne APENAS o código em ```php";

        $result = $grok($prompt);
        preg_match('/```php\n(.*?)\n```/s', $result['response'], $matches);
        $code = $matches[1] ?? $result['response'];

        file_put_contents($file, "<?php\n\n" . $code);
        $output->writeln("<info>Código gerado: {$file}</info>");
        $output->writeln("<info>Custo: \${$result['cost']}</info>");

        return Command::SUCCESS;
    }
}

class TestCommand extends Command
{
    protected static $defaultName = 'test';

    protected function configure(): void
    {
        parent::configure();
        $this->setName('test');
        $this->setDescription('Gera testes unitários PHPUnit')
             ->addArgument('path', InputArgument::REQUIRED, 'Arquivo PHP')
             ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Pasta de testes', 'tests/Unit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        if (!is_file($path)) {
            $output->writeln('<error>Arquivo não encontrado: ' . $path . '</error>');
            return Command::FAILURE;
        }

        $code = file_get_contents($path);
        $class = $this->extractClassName($code);
        $testFile = rtrim($input->getOption('output'), '/') . '/' . $class . 'Test.php';

        $grok = ($this->getApplication())->getGrokCaller();
        $prompt = "Gere testes unitários PHPUnit 10+ com 100% de cobertura para:\n\n```php\n{$code}\n```\n"
                . "Use mocks, assertions completas. Retorne apenas o código em ```php";

        $result = $grok($prompt);
        preg_match('/```php\n(.*?)\n```/s', $result['response'], $matches);
        $testCode = $matches[1] ?? $result['response'];

        if (!is_dir(dirname($testFile))) {
            mkdir(dirname($testFile), 0755, true);
        }

        file_put_contents($testFile, "<?php\n\n" . $testCode);
        $output->writeln("<info>Testes gerados: {$testFile}</info>");
        $output->writeln("<info>Custo: \${$result['cost']}</info>");

        return Command::SUCCESS;
    }

    private function extractClassName(string $code): string
    {
        if (preg_match('/class\s+(\w+)/', $code, $m)) {
            return $m[1];
        }
        return 'GeneratedTest';
    }
}

class DocCommand extends Command
{
    protected static $defaultName = 'doc';

    protected function configure(): void
    {
        parent::configure();
        $this->setName('doc');
        $this->setDescription('Gera documentação PHPDoc ou Markdown')
             ->addArgument('path', InputArgument::REQUIRED, 'Arquivo ou pasta')
             ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Formato: phpdoc|markdown', 'phpdoc');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $format = $input->getOption('format');
        $files = is_file($path) ? [$path] : (new AnalyzeCommand())->findPhpFiles($path);

        $grok = ($this->getApplication())->getGrokCaller();

        foreach ($files as $file) {
            $code = file_get_contents($file);
            $relative = str_replace(getcwd() . '/', '', $file);

            $prompt = $format === 'markdown'
                ? "Gere documentação completa em Markdown para:\n\n```php\n{$code}\n```"
                : "Adicione PHPDoc completo (tipos, params, return, throws) ao código:\n\n```php\n{$code}\n```";

            $result = $grok($prompt);
            $doc = $result['response'];

            $output->writeln("<info>[DOC] {$relative}</info>\n" . $doc);
            $output->writeln("<info>Custo: \${$result['cost']}</info>\n");
        }

        return Command::SUCCESS;
    }
}

// === EXECUÇÃO ===
try {
    (new GrokCLI())->run();
} catch (Throwable $e) {
    fwrite(STDERR, "Erro fatal: " . $e->getMessage() . " | Linha: " . $e->getLine() . " | Arquivo: " . $e->getFile() . PHP_EOL);
    exit(1);
}
