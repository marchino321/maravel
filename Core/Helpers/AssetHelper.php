<?php

namespace Core\Helpers;

use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
class AssetHelper
{
    private static array $css = [];
    private static array $js = [];
    private static array $inlineJs = [];
    private static array $inlineCss = [];

    // ──────────────── CSS esterni ────────────────
    public static function addCss(string $file, ?string $id = null): void
    {
        $id ??= md5($file);
        self::$css[$id] = $file;
    }

    public static function getCss(): array
    {
        $out = [];
        foreach (self::$css as $id => $file) {
            $out[] = ['url' => $file, 'id' => $id, 'deps' => []];
        }
        return $out;
    }

    // Js
    public static function getJs(): array
    {
        $out = [];
        foreach (self::$js as $id => $data) {
            $out[] = ['file' => $data['file'], 'id' => $id, 'deps' => $data['deps'] ?? []];
        }
        return $out;
    }
    // ──────────────── JS esterni ────────────────
    public static function addJs(string $file, ?string $id = null, array $deps = []): void
    {
        $id ??= md5($file);
        self::$js[$id] = ['file' => $file, 'deps' => $deps];
    }
    // ──────────────── JS inline ────────────────
    public static function addInlineJs(string $id, string $script, bool $wrapDocumentReady = true): void
    {
        if ($wrapDocumentReady) {
            $script = "document.addEventListener('DOMContentLoaded', function() {\n{$script}\n});";
        }
        self::$inlineJs[$id] = $script;
    }
    // ──────────────── CSS inline ────────────────
    public static function addInlineCss(string $id, string $css): void
    {
        self::$inlineCss[$id] = $css;
    }


    public static function getInlineJs(): array
    {
        return array_values(self::$inlineJs);
    }

    public static function getInlineCss(): array
    {
        return array_values(self::$inlineCss);
    }
}
