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

        // Step 1: Handle expired tokens
        $this->info('Processing expired tokens...');
        $expiredTokensCount = $this->deleteExpiredTokens($now);
        $this->info("Expired tokens deleted: $expiredTokensCount");

        // Step 2: Handle used tokens older than 24 hours
        $this->info('Processing used tokens older than 24 hours...');
        $deletedTokensCount = $this->deleteUsedTokens();
        $this->info("Used tokens deleted: $deletedTokensCount");

        Log::info('Expired and used tokens processed.', [
            'expiredTokens' => $expiredTokensCount,
            'deletedTokens' => $deletedTokensCount,
        ]);
    }

    /**
     * Delete tokens that are expired and not yet used.
     *
     * @param  \Carbon\Carbon  $now
     * @return int  Number of deleted expired tokens
     */
    protected function deleteExpiredTokens($now)
    {
        // Fetch tokens that are expired but not yet used
        $tokens = Token::where('expiry', '<', $now)
                       ->where('used', false)
                       ->get();

        Log::info("Expired tokens being deleted:", ['tokens' => $tokens->pluck('id')]);

        // Delete the tokens and return the count
        $expiredCount = $tokens->count();
        foreach ($tokens as $token) {
            $token->delete();
        }

        return $expiredCount;
    }

    /**
     * Delete tokens that are marked as used and older than 24 hours.
     *
     * @return int  Number of deleted used tokens
     */
    protected function deleteUsedTokens()
    {
        // Fetch tokens that are marked as used and older than 24 hours
        $tokens = Token::where('used', true)
                       ->where('updated_at', '<', Carbon::now()->subDay())
                       ->get();

        Log::info("Used tokens being deleted:", ['tokens' => $tokens->pluck('id')]);

        // Delete the tokens and return the count
        $deletedCount = $tokens->count();
        foreach ($tokens as $token) {
            $token->delete();
        }

        return $deletedCount;
    }
}
