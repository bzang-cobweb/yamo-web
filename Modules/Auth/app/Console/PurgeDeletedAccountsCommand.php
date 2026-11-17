<?php

namespace Modules\Auth\Console;

use Illuminate\Console\Command;
use Modules\Auth\Services\AccountDeletionService;

/**
 * Purge définitive des comptes supprimés depuis plus que le délai réglé
 * (`AUTH_ACCOUNT_DELETION_GRACE_DAYS`). Planifiée chaque jour.
 */
class PurgeDeletedAccountsCommand extends Command
{
    protected $signature = 'auth:purge-deleted-accounts';

    protected $description = 'Purge les comptes supprimés depuis plus que le délai de restauration';

    public function handle(AccountDeletionService $service): int
    {
        $this->info(__('auth::form.delete_account.purged', ['count' => $service->purgeExpired()]));

        return self::SUCCESS;
    }
}
