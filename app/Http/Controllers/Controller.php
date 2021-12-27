<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public const PER_PAGE           = 5;
    public const DEFAULT_SORT_FIELD = 'id';
    public const DEFAULT_SORT_ORDER = 'desc';

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
