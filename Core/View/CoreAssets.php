<?php

namespace Core\View;

final class CoreAssets
{
  public static function register(): void
  {
    /**
     * ===============================
     *  ASSETS CSS BASE (dal framework) - VERSIONE LIGHT
     * ===============================
     */
    ThemeManager::addCss('/App/public/css/bootstrap.min.css', 'bs-css');
    ThemeManager::addCss('/App/public/css/app.min.css', 'app-default-stylesheet', ['bs-css']);
    ThemeManager::addCss('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', 'sweetalert2-css');
    ThemeManager::addCss('/App/public/libs/toastr/build/toastr.min.css', 'toastr-css');
    ThemeManager::addCss('/App/public/libs/select2/css/select2.min.css', 'select2-css');
    ThemeManager::addCss('/App/public/css/icons.min.css', 'icons-css');
    ThemeManager::addCss('https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css', 'bs-time-picker-css');
    ThemeManager::addCss('/App/public/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'bs-data-table-css');
    ThemeManager::addCss('/App/public/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'bs-button-table-css');
    ThemeManager::addCss('/App/public/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css', 'bs-select-table-css');
    ThemeManager::addCss('/App/public/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css', 'bs-data-picker-css');
    ThemeManager::addCss('https://cdn.jsdelivr.net/npm/pretty-checkbox@3.0/dist/pretty-checkbox.min.css', 'pretty-checkbox-css');
    ThemeManager::addCss('/App/public/css/myStyle.css', 'my-css');


    /**
     * ===============================
     *  HEAD JS
     * ===============================
     */
    ThemeManager::addJsHead('/App/public/js/vendor.js', 'vendor-js', ['jQuery']);
    ThemeManager::addJsHead('https://code.jquery.com/jquery-3.7.1.min.js', 'jQuery');
    ThemeManager::addJsHead('/App/public/libs/toastr/build/toastr.min.js', 'toastr-js', ['vendor-js']);
    ThemeManager::addJsHead('/App/public/js/Classes/AjaxHelper.js', 'ajax-helper-js', ['jQuery', 'toastr-js']);

    /**
     * ===============================
     *  FOOTER JS
     * ===============================
     */
    ThemeManager::addJsFooter('/App/public/libs/tippy.js/tippy.all.min.js', 'tippy-js');
    ThemeManager::addJsFooter('/App/public/LibCustom/TableCustom/dataTableJquery.js', 'table-custom-js', ['vendor-js']);
    ThemeManager::addJsFooter('/App/public/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js', 'table-bs-jQuery-js', ['table-custom-js']);
    ThemeManager::addJsFooter('/App/public/js/Funzioni/Soldi.js', 'soldi-custom-js', ['vendor-js']);
    ThemeManager::addJsFooter('/App/public/js/app.js', 'app-js', ['vendor-js', 'soldi-custom-js']);
    ThemeManager::addJsFooter('https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js', 'time-picker-js', ['vendor-js']);
    ThemeManager::addJsFooter('/App/public/libs/select2/js/select2.min.js', 'select2-js');
    ThemeManager::addJsFooter('/App/public/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js', 'ds-data-picker-js');
    ThemeManager::addJsFooter('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js', 'sweetalert2-js', ['vendor-js']);
    ThemeManager::addJsFooter('/App/public/js/Funzioni/StandardFunction.js', 'standard-fn-js', ['toastr-js', 'app-js']);
  }
}
