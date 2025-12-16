<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateAllMigrationsFile extends Command
{
    protected $signature = 'generate:allmigrations';

    protected $description = 'Generate AllMigrations.php containing all migrations content in order';

    public function handle()
    {
        $migrationPath = database_path('migrations');
        $outputPath = base_path('Migrations.all.php');

        $files = File::allFiles($migrationPath);

        $migrations = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $migrations[] = $file;
            }
        }

        usort($migrations, function ($a, $b) {
            return strcmp($a->getFilename(), $b->getFilename());
        });

        $content = "<?php\n\n// === All Migrations Content ===\n\n";

        foreach ($migrations as $file) {
            $filename = $file->getFilename();
            $fileContent = File::get($file->getRealPath());

            $content .= "// ===============================\n";
            $content .= "// $filename\n";
            $content .= "// ===============================\n\n";
            $content .= $fileContent . "\n\n";
        }

        File::put($outputPath, $content);

        $this->info("AllMigrations.php generated successfully at the project root.");
    }
}
