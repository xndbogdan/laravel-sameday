<?php

namespace xndbogdan\Sameday\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;

class Sameday extends Model {
    public static function isAuthenticated() {
        return app()->sameday->isAuthenticated();
    }

    public static function login(String $user, String $password, String $host): Exception|bool {
        return app()->sameday->login($user, $password, $host);
    }

    public static function getCounties(String $name = null, int $page = null, int $countPerPage = null) {
        return app()->sameday->getCounties($name, $page, $countPerPage);
    }

    public static function getCities(String $name = null, String $county = null, String $postalCode = null, int $page = null, int $countPerPage = null) {
        return app()->sameday->getCities($name, $county, $postalCode, $page, $countPerPage);
    }

    public static function getPickupPoints(int $page = null, int $countPerPage = null) {
        return app()->sameday->getPickupPoints($page, $countPerPage);
    }

    public static function getDefaultPickupPoint() {
        return app()->sameday->getDefaultPickupPoint();
    }

    public static function getServices(int $page = null, int $countPerPage = null) {
        return app()->sameday->getServices($page, $countPerPage);
    }

    public static function getDefaultService() { 
        return app()->sameday->getDefaultService();
    }

    public static function getCityId(String $name, String $county = null) {
        return app()->sameday->getCityId($name, $county);
    }

    public static function getCountyId(String $name) {
        return app()->sameday->getCountyId($name);
    }

    public static function getPdf(String $awb) {
        return app()->sameday->getPdf($awb);
    }

    public static function sendAwb(
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
          return app()->sameday->sendAwb($pickupPoint, $packagesType, $numberOfPackages, $serviceId, $parcels, $packageWeight, $insuredValue, $cashOnDelivery, $awbPayment, $awbRecipient, $contactPersonId);
    }
}