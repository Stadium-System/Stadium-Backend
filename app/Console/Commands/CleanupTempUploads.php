<?php

namespace App\Console\Commands;

use App\Models\TempUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CleanupTempUploads extends Command
{
    protected $signature = 'temp-uploads:cleanup {--hours=24}';
    protected $description = 'Cleanup temporary uploads older than specified hours';

    public function handle()
    {
        $hours = $this->option('hours');
        $this->info("Cleaning up temporary uploads older than {$hours} hours...");
        
        // Get media items that are older than specified hours
        $oldMedia = Media::where('created_at', '<', now()->subHours($hours))
            ->whereHasMorph('model', [TempUpload::class])
            ->get();
            
        $count = $oldMedia->count();
        
        if ($count === 0) {
            $this->info("No old temporary uploads found.");
            return Command::SUCCESS;
        }
        
        $this->info("Found {$count} old temporary uploads to remove.");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($oldMedia as $media) {
            $media->delete(); // This will also remove the file
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        // Clean up any orphaned temp upload records
        $orphanCount = TempUpload::whereDoesntHave('media')->delete();
        $this->info("Removed {$orphanCount} orphaned temporary upload records.");
        
        $this->info("Cleanup completed successfully.");
        
        return Command::SUCCESS;
    }
}