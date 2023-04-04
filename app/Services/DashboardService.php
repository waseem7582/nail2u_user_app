<?php

namespace App\Services;

use Exception;
use App\Helper\Helper;
use App\Models\User;
use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\UserPostedService;

class DashboardService extends BaseService
{
    public function getAllArtists()
    { 
        try {
            $artist_data = [];
            $artists = User::with([
                'reviews',
                'jobs' => function ($q) {
                    $q->where("status", "done");
                }    
                // },
                // 'services'
            ])
                ->whereHas("roles", function ($q) {
                    $q->where("name", "artist");
                })
                ->get(['id', 'username', 'image_url', 'cv_url']);
                
            if ($artists) {
                foreach ($artists as $artist) {
                    $data['id'] = $artist->id;
                    $data['username'] = $artist->username;
                    $data['image_url'] = $artist->absolute_image_url;
                    $data['ratings'] = round($artist->reviews->avg('rating'), 2);
                    $data['jobs_done'] = count($artist->jobs);
                    $data['expert'] = '';
                    $data['service_price'] = '';
                    // $artist_services = $artist->services->pluck('id')->toArray();

                    // if (count($artist_services) > 0) {
                    //     for ($i = 0; $i < count($artist->services->toArray()); $i++) {
                    //         $verify = [];
                    //         $count = DB::table('booking_services')->where('service_id', $artist_services[$i])->count();
                    //         $verify[$artist_services[$i]] = $count;
                    //         $maxs = array_keys($verify, max($verify));
                    //     }

                    //     $service = Service::find($maxs[0]);
                    //     $data['expert'] = $service->name;
                    //     $data['service_price'] = $service->price;
                    // }
                    array_push($artist_data, $data);
                }
                return $artist_data;
            }
            return GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'];
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("DashboardService: getAllArtists", $error);
            return false;
        }
    }

    public function getSuggestedArtists()
    {
        try {
            $data = [];
            $artist_data = [];
            $suggested_artists = User::with([
                'reviews',
                'jobs' => function ($q) {
                    $q->where("status", "done");
                }    
                // },
                // 'services'
            ])
                ->whereHas("roles", function ($q) {
                    $q->where("name", "artist");
                })
                ->get(['id', 'username', 'image_url', 'cv_url']);
            if ($suggested_artists) {
                foreach ($suggested_artists as $artist) {
                    $data['id'] = $artist->id;
                    $data['username'] = $artist->username;
                    $data['image_url'] = $artist->absolute_image_url;
                    $data['ratings'] = round($artist->reviews->avg('rating'), 2);
                    $data['jobs_done'] = count($artist->jobs);
                    $data['expert'] = '';
                    $data['service_price'] = '';
                    // $artist_services = $artist->services->pluck('id')->toArray();

                    // if (count($artist_services) > 0) {
                    //     for ($i = 0; $i < count($artist->services->toArray()); $i++) {
                    //         $verify = [];
                    //         $count = DB::table('booking_services')->where('service_id', $artist_services[$i])->count();
                    //         $verify[$artist_services[$i]] = $count;
                    //         $maxs = array_keys($verify, max($verify));
                    //     }

                    //     $service = Service::find($maxs[0]);
                    //     $data['expert'] = $service->name;
                    //     $data['service_price'] = $service->price;
                    // }
                    array_push($artist_data, $data);

                    $artist_data = collect($artist_data)->sortBy('jobs_done')->reverse()->toArray();
                    $artist_data = array_slice($artist_data, 0, 15);
                }
                return $artist_data;
            }
            return GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'];
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("DashboardService: getSuggestedArtists", $error);
            return false;
        }
    }

    public function getNewArtists()
    {
        try {
            $artist_data = [];
            $new_artists = User::with([
                'reviews',
                'jobs' => function ($q) {
                    $q->where("status", "done");
                }
            ])
                ->whereHas("roles", function ($q) {
                    $q->where("name", "artist");
                })
                ->limit(15)
                ->latest()
                ->get(['id', 'username', 'image_url', 'cv_url']);

            if ($new_artists) {
                foreach ($new_artists as $artist) {
                    $data['id'] = $artist->id;
                    $data['username'] = $artist->username;
                    $data['image_url'] = $artist->absolute_image_url;
                    $data['ratings'] = round($artist->reviews->avg('rating'), 2);
                    $data['jobs_done'] = count($artist->jobs);
                    $data['expert'] = '';
                    $data['service_price'] = '';
                    // $artist_services = $artist->services->pluck('id')->toArray();
                    
                    // if (count($artist_services) > 0) {
                    //     for ($i = 0; $i < count($artist->services->toArray()); $i++) {
                    //         $verify = [];
                    //         $count = DB::table('booking_services')->where('service_id', $artist_services[$i])->count();
                    //         $verify[$artist_services[$i]] = $count;
                    //         $maxs = array_keys($verify, max($verify));
                    //     }

                    //     $service = Service::find($maxs[0]);
                    //     $data['expert'] = $service->name;
                    //     $data['service_price'] = $service->price;
                    // }
                    array_push($artist_data, $data);
                }
                return $artist_data;
            }
            return GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'];
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("DashboardService: getNewArtists", $error);
            return false;
        }
    }

    public function getArtistPortfolio($request)
    {
        try {
            $artist_portfolio = User::with([
                "portfolio:id,artist_id,title,image_url",
                // "services:id,artist_id,name",
                "reviews:id,artist_id,rating",
                "jobs" => function ($q) {
                    $q->where("status", "done");
                }
            ])
                ->whereHas("roles", function ($q) {
                    $q->where("name", "artist");
                })
                ->where('id', $request->artist_id)
                ->first(['id', 'username', 'cv_url', 'image_url', 'created_at as working_since']);

            if ($artist_portfolio) {

                $data = [
                    'rating' => round($artist_portfolio->reviews->avg('rating'), 2),
                    'jobs_done' => count($artist_portfolio->jobs),
                    'data' => $artist_portfolio
                ];

                return $data;
            }
            return GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'];
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("DashboardService: getArtistPortfolio", $error);
            return false;
        }
    }

    public function getArtistReviews($request)
    {
        try {
            $reviews = [];
            $record = User::with([
                'reviews'
                // 'services:id,artist_id,name'
            ])
                ->where('id', $request->artist_id)->first();

            foreach ($record->reviews as $review) {
                $client = User::where('id', $review->client_id)->first();
                $data['client_name'] = $client->username;
                $data['date'] = $review['created_at'];
                $data['review'] = $review->review;
                $data['rating'] = $review->rating;
                array_push($reviews, $data);
            };

            $response = [
                'username' => $record->username,
                'image_url' => $record->absolute_image_url,
                'rating' => round($record->reviews->avg('rating'), 2),
                'reviews' => $reviews,
                // 'services' =>  $record->services
                'services' =>  ''
            ];
            return $response;
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("DashboardService: getArtistReviews", $error);
            return false;
        }
    }
    
    public function trackBooking($request)
    {
        try {
            $booking = Booking::select('id', 'artist_id', 'client_id', 'started_at', 'total_price')
                ->with([
                    'BookingService:id,name',
                    'Artist:id,username,image_url,cv_url',
                    'Client:id,address,cv_url,image_url',
                    'Schedule:id,time'
                ])
                ->where('id', $request->booking_id)
                ->first();
            if ($booking) {
                return $booking;
            }
            return GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'];
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("DashboardService: trackBooking", $error);
            return false;
        }
    }

    public function getTrackBooking()
    {
        try {
            $booking = Booking::select('id', 'artist_id', 'client_id', 'started_at', 'total_price', 'created_at')
                ->with([
                    'BookingService:id,name',
                    'Artist:id,username,image_url,cv_url',
                    'Client:id,address,cv_url,image_url',
                    'Schedule:id,time'
                ])
                ->where('client_id', Auth::id())
                ->where('status', 'new')
                ->first();
            
            $job_posts = UserPostedService::select('user_id','service_id', 'date', 'time', 'price', 'location', 'created_at')
                        ->with([
                            'Client:id,username,address,cv_url,image_url',
                            'Service:id,name'
                        ])
                        ->where('user_id', Auth::id())
                        ->first();
            if(isset($booking->created_at) ){
                if(isset($job_posts->created_at)) {
                    if ($booking->created_at > $job_posts->created_at) {
                        return $booking;
                    } else {
                        return $job_posts;
                    }
                } else {
                    return $booking;
                }
                
            } elseif(isset($job_posts->created_at)){
                return $job_posts;
            }
        
            return GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode'];
        } catch (Exception $e) {
            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("DashboardService: trackBooking", $error);
            return false;
        }
    }
}
