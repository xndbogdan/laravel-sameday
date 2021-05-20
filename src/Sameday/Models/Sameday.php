<?php

namespace xndbogdan\Sameday\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Sameday extends Model
{
    private $token, $host;
    private $user, $password;
    private $tokenExpiration;

    const PACKAGE_TYPE_PARCEL = 0;
    const PACKAGE_TYPE_ENVELOPE = 1;
    const PACKAGE_TYPE_PARCEL_BIG = 2;

    const AWB_PAYMENT_CLIENT = 1;
    const AWB_PAYMENT_RECEPIENT = 2;
    const AWB_PAYMENT_THIRD_PARTY = 3;
    
    private $routes = [
        'auth' => '/api/authenticate',
        'get-counties' => '/api/geolocation/county',
        'get-cities' => '/api/geolocation/city',
        'awb' => '/api/awb',
        'pickup-points' => '/api/client/pickup-points',
        'get-services' => '/api/client/services',
        'get-awb' => '/api/awb/download',
    ];

    public function __construct() {

    }

    public function isAuthenticated() {
        if(!$this->token) {
            return false;
        }
        return false;
    }

    private function regenerateToken(): void {

        $this->token = Cache::get('sameday-token');

        if($this->token) {
            return;
        }   

        $response = Http::withHeaders([
            'X-AUTH-USERNAME' => $this->user,
            'X-AUTH-PASSWORD' => $this->password,
        ])->post($this->host.$this->routes['auth']);

        if($response->failed()) {
            $response->throw();
        }

        $response = $response->object();

        $this->token = $response->token;

        Cache::put('sameday-token', $response->token, Carbon::now()->diffInSeconds(Carbon::parse($response->expire_at)) - 10);
    }

    public function login(String $user, String $password, String $host): Exception|bool {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        
        $this->regenerateToken();
       
        
        return true;
    }

    public function getCounties(String $name = null, int $page = null, int $countPerPage = null) {

        $this->regenerateToken();
        $response = Http::withHeaders([
            'X-AUTH-TOKEN' => $this->token,
        ])->get($this->host.$this->routes['get-counties'], [
            'name' => $name,
        ]);

        if($response->failed()) {
            $response->throw();
        }

        return $response->object();
    }

    public function getCities(String $name = null, String $county = null, String $postalCode = null, int $page = null, int $countPerPage = null) {
        $this->regenerateToken();
        $response = Http::withHeaders([
            'X-AUTH-TOKEN' => $this->token,
        ])->get($this->host.$this->routes['get-cities'], [
            'name' => $name,
            'county' => $county,
            'postalCode' => $postalCode,
            'page' => $page,
            'countPerPage' => $countPerPage,
        ]);

        if($response->failed()) {
            $response->throw();
        }

        return $response->object();
    }

    public function getPickupPoints(int $page = null, int $countPerPage = null) {
        $this->regenerateToken();
        $response = Http::withHeaders([
            'X-AUTH-TOKEN' => $this->token,
        ])->get($this->host.$this->routes['pickup-points'], [
            'page' => $page,
            'countPerPage' => $countPerPage,
        ]);

        if($response->failed()) {
            $response->throw();
        }
        return $response->object();
    }

    public function getDefaultPickupPoint() {
        $pickupPoints = $this->getPickupPoints();
        foreach($pickupPoints->data as $item) {
            if($item->defaultPickupPoint) {
                return $item;
            }
        }
        return null;
    }

    public function getServices(int $page = null, int $countPerPage = null) {
        $this->regenerateToken();
        $response = Http::withHeaders([
            'X-AUTH-TOKEN' => $this->token,
        ])->get($this->host.$this->routes['get-services'], [
            'page' => $page,
            'countPerPage' => $countPerPage,
        ]);

        if($response->failed()) {
            $response->throw();
        }

        return $response->object();
    }

    public function getDefaultService() {
        $services = $this->getServices();
        foreach($services->data as $service) {
            if($service->defaultServices) {
                return $service;
            }
        }
    }

    public function getCityId(String $name, String $county = null) {
        $this->regenerateToken();
        $response = Http::withHeaders([
            'X-AUTH-TOKEN' => $this->token,
        ])->get($this->host.$this->routes['get-cities'], [
            'name' => $name,
            'county' => $county,
        ]);

        if($response->failed()) {
            $response->throw();
        }
        $response = $response->object();
        if(!isset($response->data[0])) {
            return null;
        }
        return $response->data[0]->id;
    }

    public function getCountyId(String $name) {
        $this->regenerateToken();
        $response = Http::withHeaders([
            'X-AUTH-TOKEN' => $this->token,
        ])->get($this->host.$this->routes['get-counties'], [
            'name' => $name,
        ]);

        if($response->failed()) {
            $response->throw();
        }

        $response = $response->object();
        if(!isset($response->data[0])) {
            return null;
        }
        return $response->data[0]->id;
    }

    public function getPdf(String $awb) {

        if(Storage::disk('awb')->exists($awb.'.pdf')) {
            $pdf = Storage::disk('awb')->get($awb.'.pdf');
            return response()->file(storage_path('awb/'.$awb.'.pdf'));
        }

        $this->regenerateToken();
        $response = Http::withHeaders([
            'X-AUTH-TOKEN' => $this->token,
        ])->get($this->host.$this->routes['get-awb'].'/'.$awb, [
        ]);

        if($response->failed()) {
            $response->throw();
        }
        Storage::disk('awb')->put($awb.'.pdf', $response->body());
        return response()->file(storage_path('awb/'.$awb.'.pdf'));
    }

    public function sendAwb(
        int $pickupPoint, 
        int $packagesType, 
        int $numberOfPackages, 
        int $serviceId,
        array $parcels, 
        float $packageWeight,
        float $insuredValue,
        float $cashOnDelivery,
        int $awbPayment,
        array $awbRecipient,
        int $contactPersonId = null) {
            $this->regenerateToken();
            $response = Http::withHeaders([
                'X-AUTH-TOKEN' => $this->token,
            ])->post($this->host.$this->routes['awb'], [
                'pickupPoint' => $pickupPoint,
                'contactPerson' => $contactPersonId,
                'thirdPartyPickup' => 0,
                'service' => $serviceId,

                'packageType' => $packagesType,
                'packageNumber' => $numberOfPackages,

                'parcels' => $parcels,
                'packageWeight' => $packageWeight,

                'insuredValue' => $insuredValue,
                'cashOnDelivery' => $cashOnDelivery,
                'awbPayment' => $awbPayment,
                'awbRecipient' => $awbRecipient,
            ]);

            if($response->failed()) {
                $response->throw();
            }
    
            return $response->object();

    }

}
