<?php

namespace Core\Helpers;

use App\Config;
use Core\Auth;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

class FormHelper
{
    /**
     * Genera un campo form centralizzato (Bootstrap 5 ready)
     *
     * @param string $name   Nome del campo
     * @param array  $config Configurazione campo:
     *   - type        : 'text'|'email'|'number'|'password'|'textarea'|'select'|'checkbox'|'radio'
     *   - label       : string
     *   - placeholder : string
     *   - required    : bool
     *   - class       : string
     *   - options     : array (per select, checkbox, radio)
     *   - attrs       : array attributi extra
     *   - use_select2 : bool (solo select, default true)
     *   - multiple    : bool (solo select/checkbox)
     * @param mixed $value Valore/i preimpostati
     * @return string HTML generato
     */
    public static function field(string $name, array $config = [], mixed $value = null): string
    {
        $type        = $config['type'] ?? 'text';
        $label       = $config['label'] ?? '';
        $placeholder = $config['placeholder'] ?? '';
        $required    = !empty($config['required']) ? 'required' : '';
        $class       = $config['class'] ?? 'form-control';
        $attrs       = $config['attrs'] ?? [];
        $options     = $config['options'] ?? [];
        $useSelect2  = $config['use_select2'] ?? true;
        $multiple    = $config['multiple'] ?? false;

        // attributi extra
        $extraAttrs = self::renderAttrs($attrs);

        // output
        $html = '';

        if ($label && !in_array($type, ['checkbox', 'radio'])) {
            $html .= "<label for=\"{$name}\" class=\"form-label\">" . htmlspecialchars($label) . "</label>";
        }

        switch ($type) {
            case 'textarea':
                $html .= "<textarea name=\"{$name}\" id=\"{$name}\" class=\"{$class}\" placeholder=\"{$placeholder}\" {$required}{$extraAttrs}>"
                    . htmlspecialchars((string)$value)
                    . "</textarea>";
                break;

            case 'select':
                $pluginThema = "";
                if ($useSelect2) {
                    $pluginThema = 'data-toggle="select2"';
                    $class .= ' select2';
                }
                $multipleAttr = $multiple ? ' multiple' : '';
                $nameAttr = $multiple ? "{$name}[]" : $name;

                $html .= "<select name=\"{$nameAttr}\" id=\"{$name}\" {$pluginThema} class=\"{$class}\" {$required}{$extraAttrs}{$multipleAttr}>";

                foreach ($options as $optVal => $optData) {
                    if (is_array($optData)) {
                        $optLabel = $optData['label'] ?? $optVal;
                        $optAttrs = $optData['attrs'] ?? [];
                    } else {
                        $optLabel = $optData;
                        $optAttrs = [];
                    }
                    $optAttrStr = self::renderAttrs($optAttrs);

                    $selected = '';
                    if ($multiple && is_array($value) && in_array((string)$optVal, array_map('strval', $value))) {
                        $selected = ' selected';
                    } elseif ((string)$value === (string)$optVal) {
                        $selected = ' selected';
                    }

                    $html .= "<option value=\"" . htmlspecialchars((string)$optVal) . "\"{$selected}{$optAttrStr}>"
                        . htmlspecialchars($optLabel) . "</option>";
                }

                $html .= "</select>";
                break;

            case 'checkbox':
            case 'radio':
                $inputType = $type;
                $isMultiple = ($inputType === 'checkbox' && $multiple);
                $nameAttr = $isMultiple ? "{$name}[]" : $name;

                foreach ($options as $optVal => $optData) {
                    $optLabel = is_array($optData) ? ($optData['label'] ?? $optVal) : $optData;
                    $optAttrs = is_array($optData) ? ($optData['attrs'] ?? []) : [];
                    $optAttrStr = self::renderAttrs($optAttrs);

                    $checked = '';
                    if ($isMultiple && is_array($value) && in_array((string)$optVal, array_map('strval', $value))) {
                        $checked = ' checked';
                    } elseif ((string)$value === (string)$optVal) {
                        $checked = ' checked';
                    }

                    $html .= "<div class=\"form-check\">";
                    $html .= "<input type=\"{$inputType}\" name=\"{$nameAttr}\" id=\"{$name}_{$optVal}\" "
                        . "value=\"" . htmlspecialchars((string)$optVal) . "\" class=\"form-check-input\" {$required}{$optAttrStr}{$checked}>";
                    $html .= "<label for=\"{$name}_{$optVal}\" class=\"form-check-label\">" . htmlspecialchars($optLabel) . "</label>";
                    $html .= "</div>";
                }
                break;

      default: // input text, email, password, number...
        $patternAttr = '';
        $titleAttr   = '';

        if ($type === 'email') {
          // ✅ regex semplice e compatibile HTML5
          $patternAttr = ' pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"';
          $titleAttr   = ' title="Inserisci un indirizzo email valido (es. nome@dominio.it)"';
        }

        $html .= "<input type=\"{$type}\" name=\"{$name}\" id=\"{$name}\" class=\"{$class}\" "
          . "placeholder=\"" . htmlspecialchars($placeholder) . "\" "
          . "value=\"" . htmlspecialchars((string)$value) . "\" {$required}{$extraAttrs}{$patternAttr}{$titleAttr}>";
        break;
        }

        return $html;
    }
    /**
     * Versione con permessi
     *
     * @param string $name
     * @param array $config
     * @param string $permesso es: "edit", "create", "delete"
     * @param mixed $value
     */
    // public static function fieldWithPermission(
    //     string $name,
    //     array $config,
    //     string $permesso,
    //     mixed $value = null
    // ): string {
    //     // Se l’utente NON ha permesso
    //     if (!Auth::can($permesso)) {
    //         // Se vuoi renderlo solo readonly
    //         $config['attrs']['readonly'] = 'readonly';
    //         $config['attrs']['disabled'] = 'disabled';

    //         // oppure, se vuoi proprio nasconderlo:
    //         // return '';
    //     }

    //     return self::field($name, $config, $value);
    // }

    /**
     * Renderizza un array di attributi HTML in stringa
     */
    private static function renderAttrs(array $attrs): string
    {
        $str = '';
        foreach ($attrs as $k => $v) {
            $str .= " {$k}=\"" . htmlspecialchars((string)$v) . "\"";
        }
        return $str;
    }
  public static function fileUpload(
    string $name = "upload",
    string $messaggio = "Trascina qui i file o clicca per selezionarli",
    string $id = "fileUpload"
  ): string {
    // CSS iniettato in <head>
    AssetHelper::addInlineCss("dropzone-custom-css", <<<CSS
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
      border: 1px solid #ddd;
      border-radius: 5px;
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
      background: rgba(0,0,0,0.5);
      color: white;
      border: none;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      cursor: pointer;
    }
    CSS);

    // HTML
    $html = <<<HTML
    <div class="upload-container" id="{$id}_container">
      <div class="drop-zone" id="{$id}_dropZone">
        <p>{$messaggio}</p>
        <input type="file" id="{$id}_input" name="{$name}[]" multiple hidden>
      </div>
      <div class="preview-container" id="{$id}_preview"></div>
    </div>
    HTML;

    // JS iniettato nel footer
    AssetHelper::addInlineJs("dropzone-custom-js", <<<JS
    (function(){
        const dropZone = document.getElementById("{$id}_dropZone");
        const fileInput = document.getElementById("{$id}_input");
        const previewContainer = document.getElementById("{$id}_preview");

        let filesArray = [];

        // Click per aprire file picker
        dropZone.addEventListener("click", () => fileInput.click());

        // Drag & Drop
        dropZone.addEventListener("dragover", (e) => {
          e.preventDefault();
          dropZone.classList.add("dragover");
        });
        dropZone.addEventListener("dragleave", () => dropZone.classList.remove("dragover"));
        dropZone.addEventListener("drop", (e) => {
          e.preventDefault();
          dropZone.classList.remove("dragover");
          handleFiles(e.dataTransfer.files);
        });

        // File picker
        fileInput.addEventListener("change", () => handleFiles(fileInput.files));

        // Gestione files
        function handleFiles(files) {
          for (let file of files) {
            filesArray.push(file);

            const reader = new FileReader();
            const previewItem = document.createElement("div");
            previewItem.classList.add("preview-item");

            const removeBtn = document.createElement("button");
            removeBtn.classList.add("remove-btn");
            removeBtn.innerHTML = "&times;";
            removeBtn.onclick = () => {
              filesArray = filesArray.filter(f => f !== file);
              previewItem.remove();
              syncInput();
            };

            previewItem.appendChild(removeBtn);

            if (file.type.startsWith("image/")) {
              reader.onload = (e) => {
                const img = document.createElement("img");
                img.src = e.target.result;
                previewItem.appendChild(img);
              };
              reader.readAsDataURL(file);
            } else {
              const icon = document.createElement("div");
              icon.classList.add("file-icon");
              icon.textContent = file.name.split(".").pop().toUpperCase();
              previewItem.appendChild(icon);
            }

            previewContainer.appendChild(previewItem);
          }

          syncInput(); // 🔑 aggiorna input con i file correnti
        }

        // Sincronizza filesArray con l'input[type=file]
        function syncInput() {
          const dt = new DataTransfer();
          filesArray.forEach(f => dt.items.add(f));
          fileInput.files = dt.files;
        }
    })();
    JS);

    return $html;
  }
}
