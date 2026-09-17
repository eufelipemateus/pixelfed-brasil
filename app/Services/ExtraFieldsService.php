<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Status;



class ExtraFieldsService
{
    public function getAccountExtraFields(Profile $profile): array
    {
        return [];
    }

    public function getStatusExtraFields(Status $status): array
    {
        return [];
    }

}
