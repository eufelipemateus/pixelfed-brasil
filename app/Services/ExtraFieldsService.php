<?php

namespace App\Services;

use App\Profile;
use App\Status;



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
