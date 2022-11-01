<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;

class DeleteExpiredFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:delete_expired_files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removing expired files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $files = File::where('expires_at', '<', date('Y-m-d'))->get();

        foreach ($files as $file) {
            $file->remove();
        }

        return Command::SUCCESS;
    }
}
