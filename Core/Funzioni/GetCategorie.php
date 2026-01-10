<?php

use Core\Classi\Chiamate;
use Core\Helpers\AssetHelper;
use App\Config;
use App\Debug;
use Core\Classi\Flash;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

/**
 * =====================
 *   SCUOLE
 * =====================
 */
function BuildCategorieScuole($editMode = true)
{
    $c = new Chiamate();
    $scuole = $c->seleziona("tbl_scuole", "eliminataScuola", 0);
    if (empty($scuole)) return '';

    $html = "<input type=\"hidden\" id=\"idClasseStudente\" name=\"idClasseStudente\" value=\"0\">
             <div class='jstree jstree-1 jstree-default' id='TreeScuole'><ul>";

    foreach ($scuole as $s) {
        $html .= '<li id="scuola_' . $s['idScuolaAutoIncrement'] . '" data-jstree=\'{"type":"folder"}\'>';
        $html .= '<span class="azioni"><a href="#">' . $s['nomeScuola'] . '</a>';

        if ($editMode) {
            $html .= ' <a href="javascript:void(0)" onclick="AggiungiCategoriaScuola(' . $s['idScuolaAutoIncrement'] . ', \'' . htmlspecialchars($s['nomeScuola']) . '\')">
                       <i class="fas fa-plus text-success"></i></a>';
        }
        $html .= "</span>";

        $classi = $c->seleziona("tbl_classi", "idScuolaClasse", $s['idScuolaAutoIncrement']);
        if (!empty($classi)) {
            $html .= "<ul>";
            foreach ($classi as $cl) {
                $html .= '<li id="classe_' . $cl['idClasseAutoIncrement'] . '" class="classi" data-jstree=\'{"type":"file"}\'>' .
                    $cl['nomeClasse'] . '</li>';
            }
            $html .= "</ul>";
        }
        $html .= "</li>";
    }

    $html .= '</ul></div>';
    if ($editMode) {
        $html .= '<div class="col-md-12 mt-2 mb-3">
                    <a href="javascript:void(0)" class="text-warning" onclick="AggiungiScuola()">Aggiungi Scuola</a>
                  </div>';
    }
    return $html;
}

function GetCategorieScuoleHtml($editMode = true)
{
    GetHeaderScuoleCategoria();
    return BuildCategorieScuole($editMode);
}

function GetCategorieScuoleAjax($editMode = true)
{
    $html = GetCategorieScuoleHtml($editMode);
    return json_encode([
        'success' => true,
        'data'    => $html,
        'flash'   => Flash::GetMex(),
        'logs'    => Debug::renderAjaxLogs("AJAX"),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function GetHeaderScuoleCategoria()
{
    AssetHelper::addCss("/App/public/libs/treeview/style.css", "treeview-css");
    AssetHelper::addJs('/App/public/libs/jstree/jstree.min.js', 'jstree-scuole-js');
    AssetHelper::addJs('/App/public/js/CustomScript/categorieScuole.js', 'tree-scuole-js');
}


/**
 * =====================
 *   LISTINI
 * =====================
 */
function BuildCategorieListini($parent = 0, $valore = '', $editMode = true)
{
    $c = new Chiamate();
    $categorie = $c->seleziona("tel_categorie_listino_prezzi", "parent_id", $parent, "ORDER BY categoria_name");
    if (empty($categorie)) return '';

    $html = '<ul>';
    foreach ($categorie as $cat) {
        $figli = $c->seleziona("tel_categorie_listino_prezzi", "parent_id", $cat['idCategoriaListinoPrezzo'], "ORDER BY categoria_name");
        $icona = empty($figli) ? "data-jstree='{\"type\":\"file\"}'" : "data-jstree='{\"type\":\"folder\"}'";

        $html .= '<li ' . $icona . ' id="IdCategoria_' . $cat['idCategoriaListinoPrezzo'] . '" nomecategoria="' . htmlspecialchars($cat['categoria_name']) . '">';
        $html .= htmlspecialchars($cat['categoria_name']);

        if ($editMode) {
            $html .= ' <span class="azioni">';
            $html .= ' <a href="javascript:void(0)" onclick="AggiungiCategoria(' . $cat['idCategoriaListinoPrezzo'] . ', \'' . htmlspecialchars($cat['categoria_name']) . '\')"><i class="fas fa-plus text-success"></i></a> ';
            $html .= ' <a href="javascript:void(0)" onclick="ModificaCategoriaListini(' . $cat['idCategoriaListinoPrezzo'] . ', \'' . htmlspecialchars($cat['categoria_name']) . '\')"><i class="fas fa-pen text-info"></i></a> ';
            $html .= ' <a href="javascript:void(0)" onclick="return confirm(\'Eliminare?\')"><i class="fas fa-trash text-danger"></i></a>';
            $html .= '</span>';
        }

        $html .= BuildCategorieListini($cat['idCategoriaListinoPrezzo'], "", $editMode);
        $html .= '</li>';
    }
    $html .= '</ul>';

    if ($parent === 0 && $editMode) {
        $html .= '</div><div class="col-md-12 mt-3 mb-2">
                    <a href="javascript:void(0)" class="text-warning" onclick="AggiungiCategoria(0, \'\')">Aggiungi Categoria Padre</a>
                  </div>';
    }

    return $html;
}

function GetCategorieListiniHtml($parent = 0, $valore = '', $editMode = true)
{
    GetHeaderListiniCategoria();
    $html = '<input type="hidden" name="category_id" id="category_id" value="' . $valore . '">';
    $html .= '<div id="TreeListini" class="jstree jstree-1 jstree-default">';
    $html .= BuildCategorieListini($parent, $valore, $editMode);
    $html .= '</div>';
    return $html;
}

function GetCategorieListiniAjax($parent = 0, $valore = '', $editMode = true)
{
    $html = GetCategorieListiniHtml($parent, $valore, $editMode);
    return json_encode([
        'success' => true,
        'data'    => $html,
        'flash'   => Flash::GetMex(),
        'logs'    => Debug::renderAjaxLogs("AJAX"),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function GetHeaderListiniCategoria()
{
    AssetHelper::addCss('/App/public/libs/treeview/style.css', 'treeview-css');
    AssetHelper::addJs('/App/public/libs/jstree/jstree.min.js', 'jstree-min-js');
    AssetHelper::addJs('/App/public/js/CustomScript/categorieListini.js', 'tree-lstini-js');
}


/**
 * =====================
 *   SERVIZI
 * =====================
 */
function BuildCategorieServizi($parent = 0, $valore = '', $editMode = true)
{
    $c = new Chiamate();
    $categorie = $c->seleziona("tbl_categorie_servizi", "parentID", $parent, "ORDER BY nome_categorie_servizi");
    if (empty($categorie)) return '';

    $html = '<ul>';
    foreach ($categorie as $cat) {
        $figli = $c->seleziona("tbl_categorie_servizi", "parentID", $cat['idCategoria'], "ORDER BY nome_categorie_servizi");
        $icona = empty($figli) ? "data-jstree='{\"type\":\"file\"}'" : "data-jstree='{\"type\":\"folder\"}'";

        $html .= '<li ' . $icona . ' id="IdCategoria_' . $cat['idCategoria'] . '" nomecategoria="' . htmlspecialchars($cat['nome_categorie_servizi']) . '">';
        $html .= htmlspecialchars($cat['nome_categorie_servizi']);

        if ($editMode) {
            $html .= ' <span class="azioni">';
            $html .= ' <a href="javascript:void(0)" onclick="AggiungiCategoriaServizi(' . $cat['idCategoria'] . ', \'' . htmlspecialchars($cat['nome_categorie_servizi']) . '\')"><i class="fas fa-plus text-success"></i></a> ';
            $html .= ' <a href="javascript:void(0)" onclick="ModificaCategoriaServizi(' . $cat['idCategoria'] . ', \'' . htmlspecialchars($cat['nome_categorie_servizi']) . '\')"><i class="fas fa-pen text-info"></i></a> ';
            $html .= ' <a href="javascript:void(0)" onclick="return confirm(\'Eliminare?\')"><i class="fas fa-trash text-danger"></i></a>';
            $html .= '</span>';
        }

        $html .= BuildCategorieServizi($cat['idCategoria'], "", $editMode);
        $html .= '</li>';
    }
    $html .= '</ul>';

    if ($parent === 0 && $editMode) {
        $html .= '</div><div class="col-md-12 mt-3 mb-2">
                    <a href="javascript:void(0)" class="text-warning" onclick="AggiungiCategoriaServizi(0, \'\')">Aggiungi Categoria Padre</a>
                  </div><div>';
    }

    return $html;
}

function GetCategorieServiziHtml($parent = 0, $valore = '', $editMode = true)
{
    AddFileHeaderServizi();
    $html = '<input type="hidden" name="category_id_servizio" id="category_id_servizio" value="' . $valore . '">';
    $html .= '<div id="TreeServizi" class="jstree jstree-1 jstree-default">';
    $html .= BuildCategorieServizi($parent, $valore, $editMode);
    $html .= '</div>';
    return $html;
}

function GetCategorieServiziAjax($parent = 0, $valore = '', $editMode = true)
{
    $html = GetCategorieServiziHtml($parent, $valore, $editMode);
    return json_encode([
        'success' => true,
        'data'    => $html,
        'flash'   => Flash::GetMex(),
        'logs'    => Debug::renderAjaxLogs("AJAX"),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function AddFileHeaderServizi()
{
    AssetHelper::addCss('/App/public/libs/treeview/style.css', 'treeview-css');
    AssetHelper::addJs('/App/public/libs/jstree/jstree.min.js', 'jstree-min-js');
    AssetHelper::addJs('/App/public/js/CustomScript/categorieServizi.js', 'main-servizi-js');
}
function BuildCategorieStrumenti($parent = 0, $valore = '', $editMode = true)
{
    $c = new Chiamate();
    $categorie = $c->seleziona(
        "ap_categorie_strumenti",
        "parent_child",
        $parent,
        "ORDER BY nome_categoria"
    );

    if (empty($categorie)) {
        return '';
    }

    $html = '<ul>';
    foreach ($categorie as $cat) {
        $figli = $c->seleziona(
            "ap_categorie_strumenti",
            "parent_child",
            $cat['idCategoriaAutoIncrement'],
            "ORDER BY nome_categoria"
        );

        $icona = empty($figli)
            ? "data-jstree='{\"type\":\"file\"}'"
            : "data-jstree='{\"type\":\"folder\"}'";

        $idCat = (int)$cat['idCategoriaAutoIncrement'];
        $nome  = $cat['nome_categoria']; // lo stampiamo diretto

        $html .= '<li ' . $icona . ' id="IdCategoria_' . $idCat . '" nomecategoria="' . $nome . '">';
        $html .= $nome;

        if ($editMode) {
            $html .= ' <span class="azioni">';
            $html .= ' <a href="javascript:void(0)" onclick="AggiungiCategoria(' . $idCat . ', \'' . $nome . '\')"><i class="fas fa-plus text-success"></i></a> ';
            $html .= ' <a href="javascript:void(0)" onclick="ModificaCategoriaStrumenti(' . $idCat . ', \'' . $nome . '\')"><i class="fas fa-pen text-info"></i></a> ';
            $html .= ' <a href"javascript:void(0)" onclick="return confirm(\'Eliminare?\')"><i class="fas fa-trash text-danger"></i></a>';
            $html .= '</span>';
        }

        // ricorsione figli
        $html .= BuildCategorieStrumenti($idCat, $valore, $editMode);
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}

function GetCategorieStrumentiHtml($parent = 0, $valore = '', $editMode = true)
{
    AddFileHeader();

    $html  = '<input type="hidden" name="categoria_id" id="categoria_id" value="' . $valore . '">';
    $html .= '<div id="TreeStrumenti" class="jstree jstree-1 jstree-default">';
    $html .= BuildCategorieStrumenti($parent, $valore, $editMode);
    $html .= '</div>';

    if ($editMode) {
        $html .= '<div class="col-md-12 mt-3 mb-2">';
        $html .= '<a href="javascript:void(0)" class="text-warning" onclick="AggiungiCategoria(0, \'\')">Aggiungi Categoria Padre</a>';
        $html .= '</div>';
    }

    return $html;
}

function GetCategorieStrumentiAjax($parent = 0, $valore = '', $editMode = true)
{
    $html = GetCategorieStrumentiHtml($parent, $valore, $editMode);

    $logs          = Debug::renderAjaxLogs("AJAX");
    $flashMessages = Flash::GetMex();

    return json_encode([
        'success' => true,
        'data'    => $html,
        'flash'   => $flashMessages,
        'logs'    => $logs,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function AddFileHeader()
{
    AssetHelper::addCss('/App/public/libs/treeview/style.css', 'treeview-css');
    AssetHelper::addJs('/App/public/libs/jstree/jstree.min.js', 'jstree-min-js');
    AssetHelper::addJs('/App/public/js/CustomScript/categorieStrumenti.js', 'main-strumenti-js');
}
