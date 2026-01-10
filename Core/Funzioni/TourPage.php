<?php

use Core\Helpers\AssetHelper;
use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
function GetTourPage($script = "")
{
    AssetHelper::addCss( "/App/public/libs/hopscotch/css/hopscotch.min.css", "tour-main-css");
    $html = "";
    AssetHelper::addJs("/App/public/libs/hopscotch/js/hopscotch.min.js", 'tour-main-js');
    AssetHelper::addJs( $script, 'tour-custom-js');
    return $html;
}
