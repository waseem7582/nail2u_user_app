<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Libs\Response\GlobalApiResponse;
use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Services\SettingService;

class SettingController extends Controller
{
    public function __construct(SettingService $AuthService, GlobalApiResponse $GlobalApiResponse)
    {
        $this->services_service = $AuthService;
        $this->global_api_response = $GlobalApiResponse;
    }

    public function update(UpdateSettingsRequest $request)
    {
        $update_settings = $this->services_service->update($request);

        if (!$update_settings)
            return ($this->global_api_response->error(GlobalApiResponseCodeBook::INTERNAL_SERVER_ERROR, "Setting did not updated!", $update_settings));

        return ($this->global_api_response->success(1, "Setting updated successfully!", $update_settings['record']));
    }
}
