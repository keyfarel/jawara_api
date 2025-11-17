<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class ForceJsonAuth extends Middleware
{
    protected function unauthenticated($request, array $guards)
    {
        abort(response()->json([
            'status' => 'error',
            'message' => 'Unauthenticated'
        ], 401));
    }
}
