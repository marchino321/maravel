<?php

use Core\View\ThemeManager;
use Core\Classi\Flash;

//Flash::AddMex("Tema Crystal Light Caricato", Flash::SUCCESS, "Tema");

/**
 * ===============================
 *  CSS TEMA CRYSTAL-LIGHT (override dopo i base)
 * ===============================
 */
ThemeManager::addCss(
  '/App/Theme/' . ThemeManager::$theme . '/assets/css/style.css',
  'theme-style',
  ['my-css']
);


// JS specifico del tema
ThemeManager::addJsFooter(
  '/App/Theme/' . ThemeManager::$theme . '/assets/js/main.js',
  'theme-script',
  ['app-js']
);

/**
 * ===============================
 *  HEAD (meta tags, viewport)
 * ===============================
 */
ThemeManager::addOnce('head.before', function () {
  return ThemeManager::renderTemplate('includes/header.html');
});

/**
 * ===============================
 * NAVBAR (top navigation bar)
 * ===============================
 */
ThemeManager::addOnce('body.before', function () {
  return ThemeManager::renderTemplate('includes/navbar.html');
});

/**
 * ===============================
 * SIDEBAR (left side menu)
 * ===============================
 */
ThemeManager::addOnce('body.before', function () {
  return ThemeManager::renderTemplate('includes/sidebar.html');
});

/**
 * ===============================
 *  FOOTER (HTML footer - dentro content-page, dopo content)
 * ===============================
 */
ThemeManager::addOnce('body.after', function () {
  return ThemeManager::renderTemplate('includes/footer.html');
});
