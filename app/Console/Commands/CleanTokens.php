<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Token;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanTokens extends Command
{
    // Define the name and signature of the console command
    protected $signature = 'tokens:clean';
    
    // Define the command description
    protected $description = 'Handle expired tokens and clean up used tokens older than 24 hours';

    public function handle()
    {
        $now = now();  // Get the current time

        // Step 1: Handle expired tokens based on the expiry time
        $this->info('Processing expired tokens...');
        $expiredTokensCount = $this->handleExpiredTokens($now);
        $this->info("Expired tokens processed: $expiredTokensCount");

        // Step 2: Clean up used tokens older than 24 hours
        $this->info('Cleaning up used tokens older than 24 hours...');
        $deletedTokensCount = $this->deleteUsedTokens();
        $this->info("Used tokens deleted: $deletedTokensCount");

        Log::info('Expired and used tokens processed.');
    }

    // Handle expired tokens based on the expiry time
    protected function handleExpiredTokens($now)
    {
        // Fetch tokens that are expired but not yet used
        $tokens = Token::where('expiry', '<', $now)
                       ->where('used', false)
                       ->get();

        $expiredCount = 0;

        foreach ($tokens as $token) {
            // Delete expired tokens
            $token->delete();
            $expiredCount++;
        }

        return $expiredCount;
    }

    // Delete tokens that are marked as "used" and older than 24 hours
    protected function deleteUsedTokens()
    {
        // Find all used tokens that are older than 24 hours
        $tokensCount = Token::where('used', true)
                            ->where('updated_at', '<', Carbon::now()->subDay()) // Older than 24 hours
                            ->delete();

        return $tokensCount; // This returns the number of deleted tokens
    }
}
