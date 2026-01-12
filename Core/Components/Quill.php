<?php

declare(strict_types=1);

namespace Core\Components;

use Core\View\ThemeManager;

final class Quill
{
  private string $name;
  private string $value;
  private int $height;

  public function __construct(
    string $name,
    string $value = '',
    int $height = 300
  ) {
    $this->name   = $name;
    $this->value  = $value;
    $this->height = $height;

    $this->registerAssets();
  }

  /**
   * Registra CSS / JS una sola volta
   */
  private function registerAssets(): void
  {
    // CSS
    ThemeManager::addCss('/App/public/libs/quill/quill.core.css', 'quill-core');
    ThemeManager::addCss('/App/public/libs/quill/quill.bubble.css', 'quill-bubble');
    ThemeManager::addCss('/App/public/libs/quill/quill.snow.css', 'quill-snow');

    // JS
    ThemeManager::addJsFooter('/App/public/libs/quill/quill.min.js', 'quill-js');

    // Inline JS (id univoco → niente conflitti)
    ThemeManager::addInlineJs(
      'quill-init-' . $this->name,
      $this->renderJs()
    );
  }

  private function renderJs(): string
  {
    $name = $this->name;

    return <<<JS
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Quill === 'undefined') return;

    const editor = document.getElementById('{$name}-editor');
    const input  = document.getElementById('{$name}');

    if (!editor || !input) return;

    const quill = new Quill(editor, {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ font: [] }, { size: [] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ script: 'super' }, { script: 'sub' }],
                [{ header: [1, 2, 3, 4, 5, 6, false] }, 'blockquote', 'code-block'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ align: [] }],
                ['link', 'image', 'video'],
                ['clean']
            ]
        }
    });

    quill.on('text-change', function () {
        input.value = quill.root.innerHTML;
    });
});
JS;
  }

  /**
   * HTML del componente
   */
  public function render(): string
  {
    $name   = $this->name;
    $value  = htmlspecialchars($this->value, ENT_QUOTES, 'UTF-8');
    $height = $this->height;

    return <<<HTML
<div id="{$name}-editor" style="height: {$height}px; border:1px solid #ccc;">
    {$value}
</div>
<textarea name="{$name}" id="{$name}" hidden>{$value}</textarea>
HTML;
  }

  /**
   * Shortcut statico
   */
  public static function make(
    string $name,
    string $value = '',
    int $height = 300
  ): string {
    return (new self($name, $value, $height))->render();
  }
}
