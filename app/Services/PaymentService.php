<?php

namespace App\Services;

use Exception;
use App\Helper\Helper;
use App\Libs\Response\GlobalApiResponseCodeBook;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\UserPostedService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService extends BaseService
{
    public function getDetails()
    {
        try {
            $bookings = Booking::with([
                'service:id,artist_id,name,price'
            ])
                ->where('artist_id', Auth::id())
                ->get(['id', 'service_id', 'artist_id', 'client_id', 'started_at', 'ended_at', 'status']);

            if ($bookings) {
                return $bookings;
            }
            return intval(GlobalApiResponseCodeBook::RECORD_NOT_EXISTS['outcomeCode']);
        } catch (Exception $e) {

            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("PaymentService: getDetails", $error);
            return false;
        }
    }

    public function getTotalEarning()
    {
        try {
            $data = [
                "total_earning" => Auth::user()->total_balance,
                "pending" => Auth::user()->transections()->with('Booking:id,service_id','Booking.service:id,name')->where('transaction_status',0)->orderBy('created_at', 'desc')->get(['id','booking_id','amount','created_at']),
                "completed" => Auth::user()->transections()->where('transaction_status',1)->orderBy('created_at', 'desc')->get(),
            ];

            return Helper::returnRecord(GlobalApiResponseCodeBook::SUCCESS['outcomeCode'], $data);

        } catch (Exception $e) {

            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("PaymentService: getTotalEarning", $error);
            return false;
        }
    }

    public function sendPayments($request)
    {
        try {

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apisandbox.dev.clover.com/pakms/apikey',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Authorization: Bearer c25dab9e-6d15-e545-bd1f-713c1a10f651'
            ),
            ));

            $response = json_decode(curl_exec($curl));

            curl_close($curl);

            
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://token-sandbox.dev.clover.com/v1/tokens',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "card":{
                    "number":"'.$request->number.'",
                    "exp_month":"'.$request->exp_month.'",
                    "exp_year":"'.$request->exp_year.'",
                    "cvv":"'.$request->cvv.'",
                    "brand":"DISCOVER"
                }
            }',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'apikey:'.$response->apiAccessKey,
                'Content-Type: application/json'
            ),
            ));

            $response_tokenize_card = json_decode(curl_exec($curl));
            
            curl_close($curl);
           
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://scl-sandbox.dev.clover.com/v1/charges',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "amount":'.$request->amount.',
                "currency":"usd",
                "source": "'.$response_tokenize_card->id.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer c25dab9e-6d15-e545-bd1f-713c1a10f651'
            ),
            ));

            $response_make_payment = json_decode(curl_exec($curl));

            curl_close($curl);

            if ($response_make_payment->captured ==  true) {
                DB::begintransaction();
                if(isset($request->booking_id) && $request->booking_id !== ''){
                    $booking = Booking::find($request->booking_id);
                    $booking->status = 'new';
                    $booking->save();

                    $transaction = new Transaction();
                    $transaction->sender_id = $booking->client_id;
                    $transaction->receiver_id = $booking->artist_id;
                    $transaction->payment_method_id = 3;
                    $transaction->booking_id = $booking->id;
                    $transaction->transaction_status = 1;
                    $transaction->save();

                    DB::commit();
                } else {
                    // $user_post_services = UserPostedService::find($request->job_post_id);

                    $transaction = new Transaction();
                    $transaction->sender_id = Auth::id();
                    $transaction->payment_method_id = 3;
                    $transaction->transaction_status = 1;
                    $transaction->save();
                    
                    DB::commit();
                }
                return Helper::returnRecord(GlobalApiResponseCodeBook::SUCCESS['outcomeCode'], $response_make_payment);
            }

        } catch (Exception $e) {

            $error = "Error: Message: " . $e->getMessage() . " File: " . $e->getFile() . " Line #: " . $e->getLine();
            Helper::errorLogs("PaymentService: getTotalEarning", $error);
            return false;
        }
    }
}
