<?php

namespace App\Services;

use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Models\SocialIdentity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\PasswordReset;
use App\Helper\Helper;
use App\Jobs\SendEmailVerificationMail;
use App\Jobs\SendPasswordResetMail;
use App\Models\EmailVerify;
use App\Models\Setting;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Exception;

class AuthService extends BaseService
{
    public function register($request)
    {
        try {
            DB::beginTransaction();
            $user = new User();
            $user->username = $request->username;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->phone_no = $request->phone_no;
            $user->image_url = 'storage/profileImages/default-profile-image.png';
            $user->cv_url = null;
            $user->save();

            $setting = new Setting();
            $setting->user_id = $user->id;
            $setting->private_account = 0;
            $setting->secure_payment = 1;
            $setting->sync_contact_no = 0;
            $setting->app_notification = 1;
            $setting->save();

            $user_role = Role::findByName('user');
            $user_role->users()->attach($user->id);

            $verify_email_token = Str::random(140);
            $email_verify = new EmailVerify;
            $email_verify->email = $request->email;
            $email_verify->token = $verify_email_token;
            $email_verify->save();

            $mail_data = [
                'email' => $request->email,
                'token' => $verify_email_token
            ];

            SendEmailVerificationMail::dispatch($mail_data);

            DB::commit();
            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            dd($e);
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: register", $error);
            return false;
        }
    }

    public function login($request)
    {
        try {
            $credentials = $request->only('email', 'password');

            $user = User::whereHas('roles', function ($q) {
                $q->where('name', 'user');
            })
                ->where('email', '=', $credentials['email'])
                ->with('setting', 'FavouriteArtist')
                ->first();

            if (
                Hash::check($credentials['password'], isset($user->password) ? $user->password : null)
                &&
                $token = $this->guard()->attempt($credentials)
            ) {

                $roles = Auth::user()->roles->pluck('name');
                $data = Auth::user()->toArray();
                unset($data['roles']);

                $data = [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => $this->guard()->factory()->getTTL() * 60,
                    'user' => Auth::user()->only('id', 'username', 'email', 'phone_no', 'address', 'experience', 'cv_url', 'image_url', 'total_balance', 'absolute_cv_url', 'absolute_image_url'),
                    'roles' => $roles,
                    'settings' => Auth::user()->setting->only('user_id', 'private_account', 'secure_payment', 'sync_contact_no', 'app_notification', 'language')
                ];
                return Helper::returnRecord(GlobalApiResponseCodeBook::SUCCESS['outcomeCode'], $data);
            }
            return Helper::returnRecord(GlobalApiResponseCodeBook::INVALID_CREDENTIALS['outcomeCode'], []);
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: login", $error);
            return false;
        }
    }

    public function forgotPassword($request)
    {
        try {
            DB::beginTransaction();
            $password_reset_token = Str::random(140);
            $password_reset = new PasswordReset();
            $password_reset->email = $request->email;
            $password_reset->token = $password_reset_token;
            $password_reset->save();
            $mail_data = [
                "token" => $password_reset_token,
                "email" => $request->email
            ];
            SendPasswordResetMail::dispatch($mail_data);
            $response = [
                "message" => "Email for resetting password has been sent!",
                "token" => $password_reset_token,
                "email" => $request->email
            ];
            DB::commit();
            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: forgotPassword", $error);
            return false;
        }
    }

    public function resetPassword($request, $token, $email)
    {
        try {
            DB::beginTransaction();
            $record = PasswordReset::where('token', $token)
                ->where('email', $email)->latest()->first();
            if ($record) {
                $user = User::where('email', $email)->first();
                $user->password = Hash::make($request->password);
                $user->save();

                PasswordReset::where('token', $token)
                    ->where('email', $email)->latest()->delete();

                $response = [
                    'message' => 'Password has been resetted!',
                ];
                DB::commit();
                return $response;
            }
            return intval(GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode']);
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: resetPassword", $error);
            return false;
        }
    }

    public function logout()
    {
        try {
            Auth::logout();
            return true;
        } catch (Exception $e) {

            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: logout", $error);
            return false;
        }
    }

    public function verifyEmail($token, $email)
    {
        try {
            DB::beginTransaction();
            $record = EmailVerify::where('token', $token)
                ->where('email', $email)->latest()->first();
            if ($record) {
                $user = User::where('email', $email)->first();
                $user->user_verified_at = now();
                $user->save();

                EmailVerify::where('email', $email)->delete();

                $response = [
                    'message' => 'Email has been verified!',
                ];
                DB::commit();
                return $response;
            }
            return intval(GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode']);
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: verifyEmail", $error);
            return false;
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    public function handleProviderCallback($provider)
    {
        try {
            $providerUser = Socialite::driver($provider)->user();
            return $user = $this->findOrCreateUser($providerUser, $provider);

//            $data = Auth::user()->toArray();
//            unset($data['roles']);
//
//            $data = [
//                'access_token' => $token,
//                'token_type' => 'bearer',
//                'expires_in' => $this->guard()->factory()->getTTL() * 60,
//                'user' => Auth::user()->only('id', 'username', 'email', 'phone_no', 'address', 'experience', 'cv_url', 'image_url', 'total_balance', 'absolute_cv_url', 'absolute_image_url'),
//                'roles' => $roles,
//                'settings' => Auth::user()->setting->only('user_id', 'private_account', 'secure_payment', 'sync_contact_no', 'app_notification', 'language')
//            ];
//
//            return $data;

            //return Helper::returnRecord(GlobalApiResponseCodeBook::SUCCESS['outcomeCode'], $data);
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("AuthService: handleProviderCallback", $error);
            return false;
        }
    }

    public function findOrCreateUser($providerUser, $provider)
    {
        $account = SocialIdentity::whereProviderName($provider)
            ->whereProviderId($providerUser->getId())
            ->first();

        if ($account) {
            return $account->user;
        } else {
            $user = User::whereEmail($providerUser->getEmail())->first();

            if (!$user) {

                $user = User::create([
                    'email' => $providerUser->getEmail(),
                    'name' => $providerUser->getName(),
                    'password' => '$2y$10$zzp91bknlK3h3PPh3/xanuZFoE81aIsbn0THkGqZRm2RzCV8f082C',
                    'image_url' => $providerUser->avatar,
                    'user_verified_at' => Carbon::now(),
                ]);

                $admin_role = Role::findByName('user');
                $admin_role->users()->attach($user->id);

                $setting = new Setting();
                $setting->user_id = $user->id;
                $setting->private_account = 0;
                $setting->secure_payment = 1;
                $setting->sync_contact_no = 0;
                $setting->app_notification = 1;
                $setting->save();
            }

            $user->identities()->create([
                'provider_id' => $providerUser->getId(),
                'provider_name' => $provider,
            ]);

            return $user;
        }
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}
