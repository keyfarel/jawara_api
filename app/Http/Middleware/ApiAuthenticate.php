<?php

namespace App\Http\Middleware;

use Closure;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;

class ApiAuthenticate
{
    public function handle($request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        try {
            JWTAuth::parseToken()->authenticate();
        }
        catch (TokenExpiredException $e) {
            return response()->json(['status' => 'error', 'message' => 'Token expired'], 401);
        } catch (TokenBlacklistedException $e) {
            return response()->json(['status' => 'error', 'message' => 'Token blacklisted'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], 401);
        } catch (JWTException $e) {
            return response()->json(['status' => 'error', 'message' => 'Token not provided'], 401);
        }

        return $next($request);
    }
}
