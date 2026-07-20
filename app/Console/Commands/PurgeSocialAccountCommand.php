<?php

namespace App\Console\Commands;

use App\Models\OauthToken;
use App\Models\SocialAccount;
use Illuminate\Console\Command;

class PurgeSocialAccountCommand extends Command
{
    protected $signature = 'social:purge {external_id : Facebook page external_id or "cmopage" alias} {--brand= : Limit purge to a brand id}';

    protected $description = 'Permanently remove social account row(s) so OAuth can reconnect cleanly';

    public function handle(): int
    {
        $externalId = $this->argument('external_id');

        if (strtolower($externalId) === 'cmopage') {
            $externalId = '1214353265090839';
        }

        $query = SocialAccount::withTrashed()->where('external_id', $externalId);

        if ($brandId = $this->option('brand')) {
            $query->where('brand_id', (int) $brandId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('No matching social_accounts rows found.');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            OauthToken::query()->where('social_account_id', $account->id)->delete();
            $account->forceDelete();
            $this->line("Removed id={$account->id} brand_id={$account->brand_id} name={$account->account_name}");
        }

        $this->info('Done. Reconnect the page from Social accounts.');

        return self::SUCCESS;
    }
}
