<?php

use App\Models\Branch;
use App\Services\BranchManagerProvisioningService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(BranchManagerProvisioningService::class)->provisionAllMissing();
    }

    public function down(): void
    {
        // Non-destructive: manager accounts are not removed.
    }
};
