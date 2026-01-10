<?php

use Core\Helpers\AssetHelper;
use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
function GetDropZoneFile($name = "upload", $messaggio = "Trascina qui i file o clicca per selezionarli")
{
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
    $html = '<div class="upload-container">
  <div class="drop-zone" id="dropZone">
    <p>' . $messaggio . '</p>
    <input type="file" id="fileInputCustom" name="' . $name . '" multiple hidden>
  </div>
  <div class="preview-container" id="previewContainer"></div>
</div>';

    AssetHelper::addInlineJs("dropzone-custom-js", <<<JS
    const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInputCustom');
const previewContainer = document.getElementById('previewContainer');

let filesArray = [];

// Click per aprire file picker
dropZone.addEventListener('click', () => fileInput.click());

// Drag & Drop
dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.classList.add('dragover');
});
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  handleFiles(e.dataTransfer.files);
});

// File picker
fileInput.addEventListener('change', () => handleFiles(fileInput.files));

// Gestione files
function handleFiles(files) {
  for (let file of files) {
    filesArray.push(file);
    const reader = new FileReader();
    const previewItem = document.createElement('div');
    previewItem.classList.add('preview-item');

    const removeBtn = document.createElement('button');
    removeBtn.classList.add('remove-btn');
    removeBtn.innerHTML = '&times;';
    removeBtn.onclick = () => {
      filesArray = filesArray.filter(f => f !== file);
      previewItem.remove();
    };

    previewItem.appendChild(removeBtn);

    if (file.type.startsWith('image/')) {
      reader.onload = (e) => {
        const img = document.createElement('img');
        img.src = e.target.result;
        previewItem.appendChild(img);
      };
      reader.readAsDataURL(file);
    } else {
      // File non immagine, mostra icona generica
      const icon = document.createElement('div');
      icon.classList.add('file-icon');
      icon.textContent = file.name.split('.').pop().toUpperCase();
      previewItem.appendChild(icon);
    }

    previewContainer.appendChild(previewItem);
  }
}

JS);
    return $html;
}
