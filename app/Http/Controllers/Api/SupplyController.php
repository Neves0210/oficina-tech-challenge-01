<?php

namespace App\Http\Controllers\Api;

use App\Domain\Materials\MaterialType;

class SupplyController extends MaterialController
{
    protected const TYPE = MaterialType::SUPPLY;
}
