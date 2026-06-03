<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Services\CoreProfileUserProvisioningService;

class CoreProfileUserObserver
{
    public function saved(Student|Lecturer|Employee $profile): void
    {
        if ($profile->wasChanged('user_id') && filled($profile->user_id)) {
            return;
        }

        app(CoreProfileUserProvisioningService::class)->provisionFor($profile);
    }
}
