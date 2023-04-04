<?php

namespace App\Services;

use App\Events\JobPostEvent;
use App\Helper\Helper;
use App\Models\User;
use App\Models\UserPostedService;
use Illuminate\Support\Facades\Auth;
use App\Libs\Response\GlobalApiResponseCodeBook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserService extends BaseService
{
    public function getProfileDetails()
    {
        try {
            $user = User::find(Auth::id());
            $data = [
                'username' => $user->username,
                'image_url' => $user->image_url,
                'phone_no' => $user->phone_no,
                'email' => $user->email,
                'address' => $user->address,
                'absolute_image_url' => $user->absolute_image_url
            ];
            return $data;
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("UserService: getProfileDetails", $error);
            return false;
        }
    }

    public function editProfile($request)
    {
        try {
            DB::begintransaction();
            $user = User::find(Auth::id());
            $request->has('username') ? ($user->username = $request->username) : null;
            $request->has('phone_no') ? ($user->phone_no = $request->phone_no) : null;
            $request->has('email') ? ($user->email = $request->email) : null;
            $request->has('password') ? ($user->password = Hash::make($request->password)) : null;
            $request->has('address') ? ($user->address = $request->address) : null;
            
            if (isset($request->image_url)) {
                $path = Helper::storeImageUrl($request, $user);
                $user->image_url = $path;
            }
            $user->save();
            DB::commit();
            return $user;
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("UserService: editProfile", $error);
            return false;
        }
    }

    public function registerAsArtist($request)
    {
        try {
            DB::beginTransaction();
            $artist = new User();
            $artist->username = $request->username;
            $artist->email = $request->email;
            $artist->password = Hash::make($request->password);
            $artist->phone_no = $request->phone_no;
            $artist->address = $request->address;
            $artist->experience = $request->experience;
            $artist->cv_url = Helper::storeCvUrl($request);
            $artist->image_url = 'storage/profileImages/default-profile-image.png';
            $artist->save();
            $artist->assignRole('artist');
            DB::commit();
            return $artist;
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("UserService: registerAsArtist", $error);
            return false;
        }
    }
    
    public function postYourService($request)
    {
        try {
            DB::beginTransaction();
            $user_posted_service = new UserPostedService;
            $user_posted_service->time = $request->time;
            $user_posted_service->date = $request->date;
            $user_posted_service->user_id = Auth::id();
            $user_posted_service->service_id = $request->service_id;
            $user_posted_service->price = $request->price;
            $user_posted_service->location = $request->location;
            $user_posted_service->additional_info = $request->additional_info;
            $user_posted_service->save();
            $message = [
                'time' => $request->time,
                'service' => $request->service,
                'price' => $request->price,
                'location' => $request->location,
                'additional_info' => $request->additional_info
            ];
            event(new JobPostEvent($message));
            DB::commit();
            return $user_posted_service;
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("User:UserService: postYourService", $error);
            return false;
        }
    }
    
    public function getAddresses()
    {
        try {
            $user = User::find(Auth::id());
            if ($user->address) {
                return unserialize($user->address);
            } else {
                return GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'];
            }
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("Artist:UserService: getAddresses", $error);
            return false;
        }
    }
    
    public function saveAddress($request)
    {
        try {
            DB::beginTransaction();
            $addresses = [];
            $user = User::find(Auth::id());
            if ($user->address) {
                $addresses = unserialize($user->address);
                array_push($addresses, $request->address);
                $user->address = serialize($addresses);
                $user->save();
                DB::commit();
                return $user->address;
            } else {
                array_push($addresses, $request->address);
                $user->address = serialize($addresses);
                $user->save();
                DB::commit();
                return $user->address;
            }
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("Artist:UserService: saveAddress", $error);
            return false;
        }
    }
}
