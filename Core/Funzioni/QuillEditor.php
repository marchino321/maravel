<?php

use Core\Helpers\AssetHelper;
use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
function GetQuill(string $nome, string $valore = ''): string
{
    // ────────── CSS di Quill ──────────
    AssetHelper::addCss('/App/public/libs/quill/quill.core.css', 'quill-core');
    AssetHelper::addCss('/App/public/libs/quill/quill.bubble.css', 'quill-bubble');
    AssetHelper::addCss('/App/public/libs/quill/quill.snow.css', 'quill-snow');

    // ────────── JS di Quill ──────────
    AssetHelper::addJs('/App/public/libs/quill/quill.min.js', 'quill-min-js');

    // ────────── JS inline di inizializzazione ──────────
    AssetHelper::addInlineJs(
        'quill-' . $nome . '-init',
        <<<JS
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Quill === 'undefined') return;
    var quill = new Quill("#{$nome}-editor", {
        theme: "snow",
        modules: {
            toolbar: [
                [{ font: [] }, { size: [] }],
                ["bold", "italic", "underline", "strike"],
                [{ color: [] }, { background: [] }],
                [{ script: "super" }, { script: "sub" }],
                [{ header: [!1, 1, 2, 3, 4, 5, 6] }, "blockquote", "code-block"],
                [{ list: "ordered" }, { list: "bullet" }, { indent: "-1" }, { indent: "+1" }],
                ["direction", { align: [] }],
                ["link", "image", "video"],
                ["clean"],
            ],
        },
    });
    quill.on('text-change', function() {
        document.getElementById("{$nome}").value = quill.root.innerHTML;
    });
});
JS,
        false
    ); // false perché il DOM è già pronto al momento del rendering

    // ────────── HTML dell'editor ──────────
    return <<<HTML
<div id="{$nome}-editor" style="height: 300px; border:1px solid #ccc;">{$valore}</div>
<textarea name="{$nome}" id="{$nome}" style="display:none;">{$valore}</textarea>
HTML;
}
