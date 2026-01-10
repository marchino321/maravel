<?php

namespace Core\Classi;

use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
class Soldi
{
    public static function SalvaSoldi($val)
    {
        if ($val) {
            if (is_numeric($val)) {
                return $val;
            } else {
                preg_match('/^([\$]|EUR|€)\s*([0-9,\s]*\.?[0-9]{0,2})?+/', $val, $regs);
                if (isset($regs[2])) {
                    return $regs[2];
                } else {
                    return false;
                }
            }
        } else {
            return 0.00;
        }
    }
    public static function MostraSoldi($val)
    {

        if ($val != '') {
            $arrotondamento = $val;
            $nmr =  number_format($arrotondamento, 2, ',', '.');
            $mst = '€ ' . $nmr;
            return $mst;
        }
    }
    public static function SalvaSoldiStringa($val)
    {
        $sol = \str_replace(['.', ','], ['', '.'], $val);
        return $sol;
    }
}
