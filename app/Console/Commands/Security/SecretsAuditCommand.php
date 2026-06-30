<?php

declare(strict_types=1);

namespace App\Console\Commands\Security;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SecretsAuditCommand extends Command
{
    protected $signature = 'secrets:audit';
    protected $description = 'Scan tracked code and config files for hardcoded secrets/API keys';

    protected array $secretPatterns = [
        'OpenAI API Key' => '/sk-[a-zA-Z0-9-]{32,}/i',
        'Stripe Key' => '/sk_live_[a-zA-Z0-9]{24,}/i',
        'Slack Webhook' => '/https:\/\/hooks\.slack\.com\/services\/[T|B][A-Z0-9]{8}\/[B][A-Z0-9]{8}\/[a-zA-Z0-9]{24}/i',
        'AWS Access Key ID' => '/(?i)(aws_access_key_id|aws_key_id|aws_access_key)\s*[:=>\s]+[\'"](AKIA[0-9A-Z]{16})[\'"]/i',
        'AWS Secret Key' => '/(?i)(aws_secret_access_key|aws_secret_key|aws_secret|aws_key)\s*[:=>\s]+[\'"]([A-Za-z0-9\/+]{40})[\'"]/i',
        'Generic Hardcoded API Key' => '/(api[-_]?key|secret[-_]?token)\s*[:=>\s]+[\'"][a-zA-Z0-9]{20,}[\'"]/i',
    ];

    public function handle(): int
    {
        $this->info('🔒 Running Secrets and API Key Audit...');
        $this->line(str_repeat('=', 50));

        $directories = [
            app_path(),
            config_path(),
            base_path('routes'),
            base_path('database/seeders'),
        ];

        $leaksFound = 0;

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                continue;
            }

            $files = File::allFiles($dir);

            foreach ($files as $file) {
                // Skip binaries, archives, lock files, and node_modules
                if (in_array($file->getExtension(), ['png', 'jpg', 'zip', 'gz', 'sqlite'], true)) {
                    continue;
                }

                $content = File::get($file->getRealPath());

                foreach ($this->secretPatterns as $name => $pattern) {
                    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                        foreach ($matches[0] as $match) {
                            $offset = $match[1];
                            $secretVal = $match[0];
                            
                            // Get line number
                            $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;
                            
                            // Mask secret for safety
                            $masked = substr($secretVal, 0, 4) . '...' . substr($secretVal, -4);

                            $this->error("❌ Leaked {$name} detected!");
                            $this->line("   • File: " . $file->getRelativePathname());
                            $this->line("   • Line: {$lineNumber}");
                            $this->line("   • Value: {$masked}");
                            $this->newLine();
                            
                            $leaksFound++;
                        }
                    }
                }
            }
        }

        // Special check for .env.example
        $envExamplePath = base_path('.env.example');
        if (File::exists($envExamplePath)) {
            $lines = file($envExamplePath);
            foreach ($lines as $i => $line) {
                if (preg_match('/=\s*(?![\s#])([a-zA-Z0-9]{15,})/i', $line, $matches)) {
                    // Exclude standard placeholders like "local", "localhost", "utf8mb4"
                    $val = trim($matches[1]);
                    $ignored = ['localhost', 'mysql', 'sqlite', 'redis', 'memcached', 'pusher', 'utf8mb4', 'utf8mb4_unicode_ci', 'laravel', 'mailpit', 'smtp', '127.0.0.1'];
                    if (!in_array(strtolower($val), $ignored, true)) {
                        $this->warn("⚠️ Possible default secret placeholder in .env.example:");
                        $this->line("   • Line " . ($i + 1) . ": {$line}");
                        $leaksFound++;
                    }
                }
            }
        }

        $this->line(str_repeat('=', 50));
        if ($leaksFound > 0) {
            $this->error("❌ Audit Completed: {$leaksFound} potential secret leaks detected!");
            return 1;
        }

        $this->info('✅ Audit Completed: No hardcoded secrets or API keys found in tracked directories.');
        return 0;
    }
}
