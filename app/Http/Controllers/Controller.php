<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Menyediakan $this->authorize(...) yang memakai Policy/Gate.
    use AuthorizesRequests;
}
