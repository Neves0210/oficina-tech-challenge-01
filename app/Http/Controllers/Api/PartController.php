<?php

namespace App\Http\Controllers\Api;

use App\Domain\Materials\MaterialType;

class PartController extends MaterialController
{
    protected const TYPE = MaterialType::PART;
}
