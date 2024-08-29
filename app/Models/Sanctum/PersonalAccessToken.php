<?php

namespace App\Models\Sanctum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
    ];



    /**
     * Get the token from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public static function getTokenFromRequest(\Illuminate\Http\Request $request)
    {
        if (is_callable(\Laravel\Sanctum\Sanctum::$accessTokenRetrievalCallback)) {
            return (string) (\Laravel\Sanctum\Sanctum::$accessTokenRetrievalCallback)($request);
        }

        return $request->bearerToken();
    }


    public static function getAccessToken($token)
    {

        $model = \Laravel\Sanctum\Sanctum::$personalAccessTokenModel;

        return $model::findToken($token);
    }

    /**
     * Determine if the provided access token is valid.
     *
     * @param  mixed  $accessToken
     * @return bool
     */
    public static function isValidAccessToken($accessToken): bool
    {
        if (! $accessToken) {
            return false;
        }

        $isValid = (! $accessToken->expires_at || \Illuminate\Support\Carbon::parse($accessToken->expires_at)->gt(now()));

        if (is_callable(\Laravel\Sanctum\Sanctum::$accessTokenAuthenticationCallback)) {
            $isValid = (bool) (\Laravel\Sanctum\Sanctum::$accessTokenAuthenticationCallback)($accessToken, $isValid);
        }

        return $isValid;
    }
}
