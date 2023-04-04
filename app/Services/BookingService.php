<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Exception;
use App\Helper\Helper;
use Illuminate\Support\Facades\Auth;
use App\Libs\Response\GlobalApiResponseCodeBook;
use Illuminate\Support\Facades\DB;

class BookingService extends BaseService
{
    public function all($request)
    {
        try {
            $booking = [];
            $bookings['current'] = Booking::where('client_id', Auth::id())->whereDate('created_at', Carbon::today())->where('status', 'new')->get();
            $bookings['previous'] = Booking::where('client_id', Auth::id())->where('status', 'done')->get();
            $bookings['favourites'] = Auth::user()->FavouriteArtist;

            return Helper::returnRecord(GlobalApiResponseCodeBook::RECORDS_FOUND['outcomeCode'], $bookings);
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("BookingService: getJobHistory", $error);
            return Helper::returnRecord(false, []);
        }
    }

    public function getAvailableArtistTime($id)
    {
        try {
            $available_time = DB::table('schedulers')
                ->whereNotExists(function ($query) use ($id) {
                    $query->select(DB::raw(1))
                        ->from('bookings')
                        ->where('bookings.artist_id', '=', $id)
                        ->whereDate('created_at', Carbon::today())
                        ->whereRaw('schedulers.id = bookings.started_at');
                })
                ->select('id', 'time')
                ->get();

            return Helper::returnRecord(GlobalApiResponseCodeBook::RECORDS_FOUND['outcomeCode'], $available_time);
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("BookingService: getAvailableArtistTime", $error);
            return Helper::returnRecord(false, []);
        }
    }

    public function create($request)
    {
        // dd($request->services_ids);
        // $services = DB::table('services')->where('artist_id', $request->artist_id)
        try {

            $services = DB::table('services')
                ->whereIn('id', $request->services_ids)
                ->pluck('id')->toArray();
            
            $services_ids_not_found = [];
            foreach (array_diff($request->services_ids, $services) as $value)
                $services_ids_not_found[] = $value;

            if (empty($services_ids_not_found) || count($services_ids_not_found) <= 0) {
                $booking = new Booking();
                $booking->artist_id = $request->artist_id;
                // $booking->status = 'new';
                $booking->client_id = Auth::id();
                $booking->started_at = $request->schedule_time_id;
                $booking->save();
                $booking->BookingService()->attach($services);

                return Helper::returnRecord(GlobalApiResponseCodeBook::RECORD_CREATED['outcomeCode'], ['booking_id' => $booking->id]);
            }
             
            return Helper::returnRecord(GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'], $services_ids_not_found);
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("BookingService: getJobHistory", $error);
            return Helper::returnRecord(false, []);
        }
    }
}
