<?php

declare(strict_types=1);

namespace Core\Components;

use Core\View\ThemeManager;

class DropZone
{
  private string $name;
  private string $message;
  private string $id;
  private bool $multiple;
  private array  $extensions;
  public function __construct(array $options = [])
  {
    $this->name    = $options['name'] ?? 'upload';
    $this->message = $options['message'] ?? 'Trascina qui i file o clicca per selezionarli';
    $this->multiple   = $options['multiple'] ?? false;
    $this->extensions = array_map(
      'strtolower',
      $options['extensions'] ?? []   // 👈 vuoto = tutto consentito
    );
    // ID univoco per evitare collisioni DOM
    $this->id = 'dz_' . substr(md5($this->name . microtime()), 0, 10);

    $this->registerAssets();
  }

  /**
   * Registra CSS / JS nel ThemeManager
   */
  private function registerAssets(): void
  {
    // ✅ CSS inline (una sola volta)
    ThemeManager::addInlineCss('component-dropzone', <<<CSS
.upload-container {
  width: 100%;
  margin: 20px auto;
}

.drop-zone {
  border: 2px dashed #ccc;
  border-radius: 10px;
  padding: 30px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.drop-zone.dragover {
  background-color: #f0f0f026;
  box-shadow: 3px 5px 10px rgba(0,0,0,.8);
}

.preview-container {
  display: flex;
  flex-wrap: wrap;
  margin-top: 15px;
  gap: 10px;
}

.preview-item {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 6px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 5px 10px 20px rgba(0,0,0,.5);
}

.preview-item img,
.preview-item .file-icon {
  max-width: 90%;
  max-height: 90%;
}

.preview-item .remove-btn {
  position: absolute;
  top: 2px;
  right: 2px;
  background: rgba(0,0,0,.6);
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  cursor: pointer;
}
CSS);

    // ✅ JS inline isolato per istanza
    ThemeManager::addInlineJs(
      'component-dropzone-' . $this->id,
      $this->buildJs()
    );
  }

  /**
   * JavaScript per questa istanza
   */
  private function buildJs(): string
  {
    return <<<JS
document.addEventListener('DOMContentLoaded', () => {

  const root = document.getElementById('{$this->id}');
  if (!root) return;

  const dropZone = root.querySelector('.drop-zone');
  const fileInput = root.querySelector('input[type="file"]');
  const previewContainer = root.querySelector('.preview-container');

  const dataTransfer = new DataTransfer();

  // ✅ estensioni DINAMICHE dal DOM
  const allowedExt = root.dataset.extensions
    ? root.dataset.extensions.split(',').map(e => e.trim().toLowerCase())
    : [];

  const isMultiple = fileInput.hasAttribute('multiple');

  dropZone.addEventListener('click', () => fileInput.click());

  dropZone.addEventListener('dragover', e => {
    e.preventDefault();
    dropZone.classList.add('dragover');
  });

  dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
  });

  dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
  });

  fileInput.addEventListener('change', () => handleFiles(fileInput.files));

  function handleFiles(files) {
    for (let file of files) {

      const ext = file.name.split('.').pop().toLowerCase();

      if (allowedExt.length && !allowedExt.includes(ext)) {
        alert('Sono ammessi solo file: ' + allowedExt.join(', '));
        continue;
      }

      if (!isMultiple) {
        dataTransfer.items.clear();
        previewContainer.innerHTML = '';
      }

      dataTransfer.items.add(file);
      fileInput.files = dataTransfer.files;

      const previewItem = document.createElement('div');
      previewItem.className = 'preview-item';

      const removeBtn = document.createElement('button');
      removeBtn.className = 'remove-btn';
      removeBtn.innerHTML = '&times;';
      removeBtn.onclick = () => {
        const newDT = new DataTransfer();
        [...dataTransfer.files].forEach(f => {
          if (f !== file) newDT.items.add(f);
        });
        dataTransfer.items.clear();
        [...newDT.files].forEach(f => dataTransfer.items.add(f));
        fileInput.files = dataTransfer.files;
        previewItem.remove();
      };

      previewItem.appendChild(removeBtn);

      const icon = document.createElement('div');
      icon.className = 'file-icon';
      icon.textContent = ext.toUpperCase();
      previewItem.appendChild(icon);

      previewContainer.appendChild(previewItem);
    }
  }
});
JS;
  }

  /**
   * Render HTML del componente
   */
  public function render(): string
  {
    $accept = $this->extensions
      ? implode(',', array_map(fn($e) => '.' . $e, $this->extensions))
      : '';
    $multiple = $this->multiple ? 'multiple' : '';
    $array = $this->multiple ? '[]' : '';
    return <<<HTML
<div class="upload-container" id="{$this->id}">
  <div class="drop-zone">
    <p>{$this->message}</p>
    <input type="file" name="{$this->name}{$array}" accept="{$accept}" {$multiple} hidden>
  </div>
  <div class="preview-container"></div>
</div>
HTML;
  }
}
