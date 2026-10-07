<?php

namespace App\Console\Commands;

use App\Services\Ledger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('charges:refresh-status')]
#[Description('Actualiza el estado de los cobros según su antigüedad (por vencer, vencido, moroso)')]
class RefreshChargeStatuses extends Command
{
    public function handle(Ledger $ledger): int
    {
        $this->info("Cobros actualizados: {$ledger->refreshStatuses()}");

        return self::SUCCESS;
    }
}
