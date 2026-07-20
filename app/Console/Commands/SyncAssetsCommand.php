<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncAssetsCommand extends Command
{
    protected $signature = 'cmo:sync-assets';

    protected $description = 'Copy CSS/JS from resources/ to public/ (optional, for local edits)';

    public function handle(): int
    {
        $copies = [
            'resources/css/cmo-design.css' => 'public/css/cmo-design.css',
            'resources/css/landing.css' => 'public/css/landing.css',
            'resources/css/app.css' => 'public/css/app.css',
            'node_modules/bootstrap/dist/css/bootstrap.min.css' => 'public/css/bootstrap.min.css',
            'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js' => 'public/js/bootstrap.bundle.min.js',
        ];

        foreach ($copies as $from => $to) {
            if (! file_exists(base_path($from))) {
                $this->warn("Skip (missing): {$from}");

                continue;
            }

            if (! is_dir(dirname(base_path($to)))) {
                mkdir(dirname(base_path($to)), 0755, true);
            }

            $content = file_get_contents(base_path($from));
            if (str_ends_with($from, 'app.css')) {
                $content = preg_replace('/@import[^;]+;\s*/', '', $content);
            }

            file_put_contents(base_path($to), $content);
            $this->line("Synced → {$to}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
