<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\HandlesApiQuery;

abstract class ApiController extends Controller
{
    use ApiResponses, HandlesApiQuery;
}
