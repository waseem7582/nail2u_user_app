<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequests\EditProfileRequest;
use App\Http\Requests\UserRequests\RegisterAsArtistRequest;
use App\Http\Requests\UserRequests\SaveAddressRequest;
use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Libs\Response\GlobalApiResponse;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(UserService $UserService, GlobalApiResponse $GlobalApiResponse)
    {
        $this->user_service = $UserService;
        $this->global_api_response = $GlobalApiResponse;
    }

    public function getProfileDetails()
    {
        $profile_details = $this->user_service->getProfileDetails();

        if (!$profile_details)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "User profile details did not fetched!", $profile_details));

        return ($this->global_api_response->success(1, "User profile details fetched successfully!", $profile_details));
    }

    public function editProfile(EditProfileRequest $request)   
    {
        $edit_profile = $this->user_service->editProfile($request);

        if (!$edit_profile)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "User profile did not edited!", $edit_profile));

        return ($this->global_api_response->success(1, "User profile edited successfully!", $edit_profile));
    }

    public function registerAsArtist(RegisterAsArtistRequest $request)
    {
        $register = $this->user_service->registerAsArtist($request);

        if (!$register)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "User did not registered!", $register));

        return ($this->global_api_response->success(1, "Artist registered successfully!", $register));
    }
    
    public function postYourService(Request $request)
    {
        $post_service = $this->user_service->postYourService($request);

        if (!$post_service)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "User's service did not posted!", $post_service));

        return ($this->global_api_response->success(1, "User posted his service successfully!", $post_service));
    }
    
    public function getAddresses()
    {
        $get_addresses = $this->user_service->getAddresses();

        if ($get_addresses == GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'])
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::RECORD_NOT_EXISTS, "User addresses not found!", []));

        if (!$get_addresses)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "User addresses did not fetched!", $get_addresses));

        return ($this->global_api_response->success(count($get_addresses), "User addresses fetched successfully!", $get_addresses));
    }
    
    public function saveAddress(SaveAddressRequest $request)
    {
        $save_address = $this->user_service->saveAddress($request);

        if (!$save_address)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "User address did not saved!", $save_address));

        return ($this->global_api_response->success(1, "User address saved successfully!", $save_address));
    }
}