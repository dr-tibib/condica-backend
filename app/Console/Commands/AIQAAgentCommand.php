<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Domain;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Workplace;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Data\Tool;
use Gemini\Enums\Role;
use Gemini\Enums\DataType;
use Gemini\Data\Content;
use Gemini\Data\Part;
use Gemini\Data\FunctionDeclaration;
use Gemini\Data\Schema;
use Gemini\Data\FunctionResponse;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Chrome\ChromeOptions;

class AIQAAgentCommand extends Command
{
    protected $signature = 'ai:qa-agent {--scenario=all} {--model=gemini-2.5-flash}';
    protected $description = 'Autonomous QA agent using Gemini and Dusk (MySQL isolated)';

    private ?Browser $browser = null;
    private string $reportPath;
    private array $history = [];
    private string $baseUrl;
    private string $tenantId;

    public function handle(): int
    {
        $this->tenantId = 'ai' . now()->format('His');
        $this->baseUrl = "http://" . $this->tenantId . ".localhost:8000";
        $this->reportPath = base_path('tests/AI/reports/run_' . now()->format('Ymd_His'));
        File::ensureDirectoryExists($this->reportPath);

        $this->info("Initializing OakSoft AI QA Agent (Model: " . $this->option('model') . ")...");
        $this->info("Test Tenant ID: " . $this->tenantId);
        
        config(['gemini.request_timeout' => 120]);

        try {
            // 1. Prepare Environment (using isolated SQLite tenant)
            $this->prepareEnvironment();
            
            // 2. Start Browser
            $this->startBrowser();

            // 3. Run Scenarios
            $scenarios = $this->getScenarios();
            foreach ($scenarios as $id => $description) {
                if ($this->option('scenario') !== 'all' && $this->option('scenario') !== $id) continue;
                $this->runScenario($id, $description);
            }

            $this->generateFinalReport();
        } catch (\Exception $e) {
            $this->error("Critical Failure: " . $e->getMessage());
            $this->info($e->getTraceAsString());
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $this->cleanup();
            if ($this->browser) {
                $this->browser->quit();
            }
        }

        return 0;
    }

    private function prepareEnvironment(): void
    {
        $this->info("Preparing isolated SQLite environment...");
        
        // Clear logs
        File::put(base_path('storage/logs/laravel.log'), '');

        // Create Tenant in central DB
        $tenant = Tenant::create(['id' => $this->tenantId]);
        Domain::create([
            'domain' => $this->tenantId . '.localhost', 
            'tenant_id' => $this->tenantId
        ]);

        tenancy()->initialize($tenant);
        
        Artisan::call('tenants:migrate', ['--force' => true]);
        $this->seedTenantData();
    }

    private function cleanup(): void
    {
        $this->info("Cleaning up test tenant...");
        if (isset($this->tenantId)) {
            $tenant = Tenant::find($this->tenantId);
            if ($tenant) {
                $tenant->delete(); // This also deletes the database if configured
            }
        }
    }

    private function startBrowser()
    {
        $this->info("Connecting to Chromedriver...");
        
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless',
            '--window-size=1280,1080',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--ignore-certificate-errors'
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $port = 9515;
        $connection = @fsockopen('localhost', $port);
        if (!$connection) {
            $this->info("Starting Chromedriver binary...");
            $binary = base_path('vendor/laravel/dusk/bin/chromedriver-linux');
            exec("$binary --port=$port > /dev/null 2>&1 &");
            sleep(2);
        } else {
            fclose($connection);
        }

        $driver = RemoteWebDriver::create("http://localhost:$port", $capabilities);
        $this->browser = new Browser($driver);
        Browser::$storeScreenshotsAt = base_path('tests/Browser/screenshots');
    }

    private function seedTenantData(): void
    {
        $wp = Workplace::create([
            'name' => 'Oak Soft Padureni',
            'address' => 'Padureni 123',
            'late_start_threshold' => '16:00'
        ]);

        Employee::create([
            'first_name' => 'Laszlo',
            'last_name' => 'Olah',
            'workplace_enter_code' => '001',
            'workplace_id' => $wp->id,
            'personal_numeric_code' => '1790116141052'
        ]);

        LeaveType::create(['name' => 'Vacation', 'code' => 'V']);
        Vehicle::create(['name' => 'Dacia Logan', 'license_plate' => 'BV01HID']);
    }

    private function getScenarios(): array
    {
        return [
            'checkin' => 'Test a regular check-in flow for employee 001. Navigate to / and enter code 001.',
            'delegation' => 'Test starting a multi-day delegation. Click Delegație, enter 001, pick a place and a vehicle.',
            'late_start' => 'Mock threshold to 00:00 and verify Late Start screen appears when entering code.'
        ];
    }

    private function runScenario(string $id, string $description)
    {
        $this->warn("\n--- Running Scenario: $id ---");
        $this->info($description);

        $systemPrompt = $this->getSystemPrompt();
        $this->history = [
            new Content([new Part($systemPrompt)], Role::USER),
            new Content([new Part("Scenario to test: $description. Use tools to execute and verify.")], Role::USER),
        ];

        $maxSteps = 20;
        $tool = new Tool(functionDeclarations: $this->getFunctionDeclarations());
        $model = Gemini::generativeModel($this->option('model'))->withTool($tool);
        $chat = $model->startChat(history: $this->history);

        for ($i = 0; $i < $maxSteps; $i++) {
            try {
                $response = $chat->sendMessage("Continue execution.");
                $content = $response->candidates[0]->content;
                $part = $content->parts[0];

                if ($part->functionCall) {
                    $result = $this->callTool($part->functionCall->name, (array) $part->functionCall->args);
                    $funcResponsePart = new Part(functionResponse: new FunctionResponse(
                        name: $part->functionCall->name,
                        response: ['result' => $result]
                    ));
                    $chat->history[] = new Content([$funcResponsePart], Role::MODEL);
                    $this->info("Tool: {$part->functionCall->name}(" . json_encode($part->functionCall->args) . ") -> " . substr(json_encode($result), 0, 50) . "...");
                } else {
                    $conclusion = $part->text;
                    $this->info("AI Conclusion: " . $conclusion);
                    File::put($this->reportPath . "/scenario_$id.md", "## Scenario: $id\n\n### Goal\n$description\n\n### AI Conclusion\n$conclusion\n");
                    break;
                }
            } catch (\Exception $e) {
                $this->error("Gemini Error: " . $e->getMessage());
                break;
            }
        }
    }

    private function getFunctionDeclarations(): array
    {
        return [
            new FunctionDeclaration(
                name: 'navigate',
                description: 'Navigate to a URL relative to ' . $this->baseUrl,
                parameters: new Schema(type: DataType::OBJECT, properties: [
                    'path' => new Schema(type: DataType::STRING, description: 'The URL path')
                ], required: ['path'])
            ),
            new FunctionDeclaration(
                name: 'set_local_storage',
                description: 'Set a value in the browser localStorage.',
                parameters: new Schema(type: DataType::OBJECT, properties: [
                    'key' => new Schema(type: DataType::STRING),
                    'value' => new Schema(type: DataType::STRING)
                ], required: ['key', 'value'])
            ),
            new FunctionDeclaration(
                name: 'type',
                description: 'Type text into a CSS selector.',
                parameters: new Schema(type: DataType::OBJECT, properties: [
                    'selector' => new Schema(type: DataType::STRING),
                    'text' => new Schema(type: DataType::STRING)
                ], required: ['selector', 'text'])
            ),
            new FunctionDeclaration(
                name: 'press_button',
                description: 'Press a button by its visible text.',
                parameters: new Schema(type: DataType::OBJECT, properties: [
                    'text' => new Schema(type: DataType::STRING)
                ], required: ['text'])
            ),
            new FunctionDeclaration(
                name: 'wait_for_text',
                description: 'Wait for specific text to appear on the page.',
                parameters: new Schema(type: DataType::OBJECT, properties: [
                    'text' => new Schema(type: DataType::STRING)
                ], required: ['text'])
            ),
            new FunctionDeclaration(
                name: 'screenshot',
                description: 'Take a screenshot.',
                parameters: new Schema(type: DataType::OBJECT, properties: [
                    'name' => new Schema(type: DataType::STRING)
                ], required: ['name'])
            ),
            new FunctionDeclaration(
                name: 'read_logs',
                description: 'Read the last 20 lines of the Laravel log.',
                parameters: new Schema(type: DataType::OBJECT, properties: [])
            ),
            new FunctionDeclaration(
                name: 'setup_scenario',
                description: 'Set the backend state for employee 001.',
                parameters: new Schema(type: DataType::OBJECT, properties: [
                    'scenario' => new Schema(type: DataType::STRING)
                ], required: ['scenario'])
            )
        ];
    }

    private function callTool(string $name, array $args): string
    {
        try {
            switch ($name) {
                case 'navigate':
                    $this->browser->visit($this->baseUrl . $args['path']);
                    $this->browser->pause(1500);
                    return "Navigated to " . $args['path'];
                case 'set_local_storage':
                    $this->browser->script("localStorage.setItem('{$args['key']}', '{$args['value']}');");
                    return "LocalStorage set: {$args['key']} = {$args['value']}";
                case 'type':
                    $this->browser->waitFor($args['selector'], 5);
                    $this->browser->click($args['selector']);
                    $this->browser->keys($args['selector'], $args['text']);
                    $this->browser->pause(500);
                    return "Typed into " . $args['selector'];
                case 'press_button':
                    $this->browser->press($args['text']);
                    $this->browser->pause(1500);
                    return "Pressed button with text: " . $args['text'];
                case 'wait_for_text':
                    $this->browser->waitForText($args['text'], 5);
                    return "Text found: " . $args['text'];
                case 'screenshot':
                    $this->browser->screenshot($args['name']);
                    $src = base_path('tests/Browser/screenshots/' . $args['name'] . '.png');
                    $dest = $this->reportPath . '/' . $args['name'] . '.png';
                    if (File::exists($src)) {
                        File::copy($src, $dest);
                        return "Screenshot saved.";
                    }
                    return "Error: Screenshot failed.";
                case 'read_logs':
                    $log = base_path('storage/logs/laravel.log');
                    if (File::exists($log)) {
                        $lines = explode("\n", File::get($log));
                        return implode("\n", array_slice($lines, -20));
                    }
                    return "Log file not found.";
                case 'setup_scenario':
                    Artisan::call('kiosk:qa', [
                        'employee_code' => '001',
                        'scenario' => $args['scenario'],
                        'tenant_id' => $this->tenantId
                    ]);
                    return "Backend state set: " . $args['scenario'];
                default:
                    return "Unknown tool";
            }
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    private function getSystemPrompt(): string
    {
        $manifesto = File::get(base_path('GEMINI.md'));
        
        return "You are the OakSoft AI QA Agent, powered by Gemini. 
        Your goal is to perform autonomous end-to-end QA on the Condica Kiosk frontend.
        
        CONTEXT:
        $manifesto
        
        KNOWLEDGE:
        - The app is a React SPA.
        - To access the Kiosk Home, you MUST set 'kiosk_workplace_id' to '1' in localStorage. 
        - Use tool 'set_local_storage' with key='kiosk_workplace_id' and value='1' AFTER navigating to the page once.
        - After setting localStorage, you MUST navigate again to '/' to see the Home screen.
        - Code input is an <input> element.
        - To submit the code, use tool 'press_button' with text='OK'.
        - Codes are numeric. '001' is for Laszlo Olah.
        - Successful check-in results in message 'Checked in successfully.'
        
        GUIDELINES:
        1. Always call setup_scenario first.
        2. Bypass login: navigate('/') -> set_local_storage -> navigate('/').
        3. After entering code '001' and pressing 'OK', expect the text 'Checked in successfully.'
        4. Take screenshots of key moments.
        5. Summarize findings at the end.
        
        URL: {$this->baseUrl}
        
        Be meticulous and verify success messages.";
    }

    private function generateFinalReport(): void
    {
        $this->info("Generating final summary...");
        $summary = "# Condica AI QA Run Summary\n";
        $summary .= "Generated on: " . now()->toDateTimeString() . "\n";
        $summary .= "Model used: " . $this->option('model') . "\n";
        $summary .= "Tenant ID: " . $this->tenantId . "\n\n";
        
        foreach (File::files($this->reportPath) as $file) {
            if ($file->getExtension() === 'md' && $file->getFilename() !== 'summary.md') {
                $summary .= File::get($file->getPathname()) . "\n\n";
            }
        }

        File::put($this->reportPath . '/summary.md', $summary);
    }
}
