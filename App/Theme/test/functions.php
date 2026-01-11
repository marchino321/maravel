<?php

use Core\View\ThemeManager;

ThemeManager::addOnce('head.before', function () {
  return ThemeManager::renderTemplate('template/header.html');
});
