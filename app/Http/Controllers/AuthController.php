<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequests\ForgotPasswordRequest;
use App\Http\Requests\AuthRequests\ResetPasswordRequest;
use App\Http\Requests\AuthRequests\LoginRequest;
use App\Http\Requests\AuthRequests\RegisterRequest;
use App\Libs\Response\GlobalApiResponse;
use App\Libs\Response\GlobalApiResponseCodeBook;
use Laravel\Socialite\Facades\Socialite;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(AuthService $AuthService, GlobalApiResponse $GlobalApiResponse)
    {
        $this->auth_service = $AuthService;
        $this->global_api_response = $GlobalApiResponse;
    }

    public function register(RegisterRequest $request)
    {
        $register = $this->auth_service->register($request);
        dd('waseem');
        if (!$register)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "User did not registered!", $register));

        return ($this->global_api_response->success(1, "Artist registered successfully!", $register));
    }

    public function login(LoginRequest $request)
    {
        $login = $this->auth_service->login($request);

        if ($login['outcomeCode'] === GlobalApiResponseCodeBook::INVALID_CREDENTIALS['outcomeCode'])
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INVALID_CREDENTIALS, "Your email or password is invalid!", []));

        if ($login['outcomeCode'] === GlobalApiResponseCodeBook::EMAIL_NOT_VERIFIED['outcomeCode'])
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::EMAIL_NOT_VERIFIED, "Your email is not verified!", $login['record']));

        if (!$login)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Login not successful!", $login['record']));

        return ($this->global_api_response->success(1, "Login successfully!", $login['record']));
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $forgot_password = $this->auth_service->forgotPassword($request);

        if (!$forgot_password)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Email for sending resetting password did not sent!", $forgot_password));

        return ($this->global_api_response->success(1, "Email for sending resetting password sent successfully!", $forgot_password));
    }

    public function resetPassword(ResetPasswordRequest $request, string $token, string $email)
    {
        $reset_password = $this->auth_service->resetPassword($request, $token, $email);

        if ($reset_password == GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'])
            return $this->global_api_response->error(GlobalApiResponseCodeBook::RECORD_NOT_EXISTS, "Record not found for resetting password!", []);

        if (!$reset_password)
            return $this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Password didn't reset!", $reset_password);

        return $this->global_api_response->success(1, "Password has been reset successfully!", $reset_password);
    }

    public function logout()
    {
        $logout = $this->auth_service->logout();

        if ($logout === GlobalApiResponseCodeBook::NOT_AUTHORIZED['outcomeCode'])
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::NOT_AUTHORIZED, "User is not authorized to logout!", $logout));

        if (!$logout)
            return (new GlobalApiResponse())->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Logout not successful!", $logout);

        return (new GlobalApiResponse())->success(1, "User logout successfully!", $logout);
    }

    public function verifyEmail(string $token, string $email)
    {
        $verify_email = $this->auth_service->verifyEmail($token, $email);

        if ($verify_email == GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode']) {
            return $this->global_api_response->error(GlobalApiResponseCodeBook::RECORD_NOT_EXISTS, "Record not found for email verification!", []);
        }

        if (!$verify_email) {
            return $this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Email didn't verified!", $verify_email);
        }

        return $this->global_api_response->success(1, "Email verified successfully!", $verify_email);
    }

    public function redirectToProvider($provider)
    {
        $user = Socialite::driver($provider)->redirect();
        return $user;
    }

    public function handleProviderCallback($provider)
    {
        return $this->auth_service->handleProviderCallback($provider);
    }
}
