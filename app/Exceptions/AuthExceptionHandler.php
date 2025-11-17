<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class AuthExceptionHandler
{
    public static function handle(UnauthorizedHttpException $e, Request $request)
    {
        $prev = $e->getPrevious();

        if ($prev instanceof TokenBlacklistedException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token blacklisted'
            ], 401);
        }

        if ($prev instanceof TokenExpiredException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expired'
            ], 401);
        }

        if ($prev instanceof TokenInvalidException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token'
            ], 401);
        }

        if ($prev instanceof JWTException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not provided'
            ], 401);
        }

        // Default
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 401);
    }
}
