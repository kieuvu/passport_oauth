<?php

namespace Kieuvu\PassportOauth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Passport\Client as OClient;
use GuzzleHttp\Client;
use Kieuvu\PassportOauth\Requests\LoginRequest;
use Kieuvu\PassportOauth\Requests\RegisterRequest;

class PassportController extends Controller
{
    /**
     * Registration user
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return response()->json([
            'status' => true,
            'details' => [
                'user' => $user
            ]
        ], 200);
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(LoginRequest $request)
    {
        $credentials = request(['email', 'password']);
        if (Auth::attempt($credentials)) {
            $oClient = OClient::where('password_client', 1)->first();
            $result =  $this->getTokenAndRefreshToken($oClient, $request->email, $request->password);
            return response()->json([
                'status' => true,
                'details' => [
                    'credentials' => $result
                ]
            ], 200);
        }
        return response()->json([
            'status' => false,
            'details' => [
                "credentials" => [
                    "Wrong username or password."
                ]
            ],
        ], 401);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $tokenId = $request->user()->token()->id;
        $tokenRepository = app('Laravel\Passport\TokenRepository');
        $refreshTokenRepository = app('Laravel\Passport\RefreshTokenRepository');
        $tokenRepository->revokeAccessToken($tokenId);
        $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($tokenId);
        $request->session()->flush();
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function getTokenAndRefreshToken(OClient $oClient, $email, $password)
    {
        $oClient = OClient::where('password_client', 1)->first();
        $http = new Client;
        $response = $http->request('POST', env("APP_URL") . '/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $oClient->id,
                'client_secret' => $oClient->secret,
                'username' => $email,
                'password' => $password,
                'scope' => '',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function refreshToken(Request $request)
    {
        $refresh_token = $request->header('Refreshtoken');
        $oClient = OClient::where('password_client', 1)->first();
        $http = new Client;

        try {
            $response = $http->request('POST', env("APP_URL") . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refresh_token,
                    'client_id' => $oClient->id,
                    'client_secret' => $oClient->secret,
                    'scope' => '',
                ],
            ]);
            $result = json_decode($response->getBody(), true);
            $result['details'] = ['user' => auth()->user()];
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json("Unauthorized", 401);
        }
    }
}
