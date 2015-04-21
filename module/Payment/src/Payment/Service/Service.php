<?php
namespace Payment\Service;

use Application\Service\ApplicationSetting as SettingService;

class Service
{
    /**
     * Rounding a cost
     *
     * @param float|integer $cost
     * @return integer|float
     */
    public static function roundingCost($cost)
    {
        switch (SettingService::getSetting('payment_type_rounding')) {
            case 'type_round' :
                return round($cost);

            case 'type_ceil' :
                return ceil($cost);

            case 'type_floor' :
                return floor($cost);

            default :
                return $cost;
        }
    }
}