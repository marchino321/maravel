<?php

namespace App\Plugins\HeaderMenu;

use Core\View\MenuManager;
use App\Debug;

class HeaderMenuManager extends MenuManager
{
    public function __construct(array $userRoles = [], array $defaultMenu = [])
    {
        parent::__construct($userRoles, $defaultMenu);

        Debug::log("📌 HeaderMenuManager inizializzato", "PLUGIN");
    }
}