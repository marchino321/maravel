<?php

namespace Core\Helpers;

use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

class UploadHelper
{
    private string $uploadDir;
    private array $allowedExtensions;
    private int $maxSize; // in bytes
    private bool $overwrite;

    public function __construct(
        string $uploadDir = "/uploads",
        array $allowedExtensions = ["jpg", "jpeg", "png", "gif", "webp"],
        int $maxSize = 5242880, // 5MB
        bool $overwrite = false
    ) {
        $this->uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'] . $uploadDir, "/");
        $this->allowedExtensions = $allowedExtensions;
        $this->maxSize = $maxSize;
        $this->overwrite = $overwrite;

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
    }

    /**
     * Carica uno o più file
     */
    public function upload(string $inputName): array
    {
        // Normalizza chiave tipo "fotoProfilo[]"
        $key = rtrim($inputName, '[]');

        if (!isset($_FILES[$key])) {
            return [["success" => false, "error" => "Nessun file caricato per '$key'", "debug" => array_keys($_FILES)]];
        }

        $files = $_FILES[$key];
        $results = [];

        if (is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                $results[] = $this->processFile(
                    $name,
                    $files['tmp_name'][$i],
                    (int)$files['size'][$i],
                    (int)$files['error'][$i]
                );
            }
        } else {
            $results[] = $this->processFile(
                $files['name'],
                $files['tmp_name'],
                (int)$files['size'],
                (int)$files['error']
            );
        }

        return $results;
    }

    private function processFile(string $name, string $tmpName, int $size, int $error): array
    {
        if ($error !== UPLOAD_ERR_OK) {
            return ["success" => false, "error" => $this->codeToMessage($error)];
        }

        if (!is_uploaded_file($tmpName)) {
            return ["success" => false, "error" => "File temporaneo non valido"];
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, $this->allowedExtensions)) {
            return ["success" => false, "error" => "Estensione non permessa ($ext)"];
        }

        if ($size > $this->maxSize) {
            return ["success" => false, "error" => "File troppo grande"];
        }

        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $finalName = $safeName . "." . $ext;

        if (!$this->overwrite && file_exists($this->uploadDir . "/" . $finalName)) {
            $finalName = $safeName . "_" . time() . "." . $ext;
        }

        $finalPath = $this->uploadDir . "/" . $finalName;

        if (!move_uploaded_file($tmpName, $finalPath)) {
            return ["success" => false, "error" => "Errore nel salvataggio del file"];
        }

        return [
            "success" => true,
            "path" => str_replace($_SERVER['DOCUMENT_ROOT'], '', $finalPath),
            "name" => $finalName,
            "size" => $size
        ];
    }
    private function codeToMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => "File oltre il limite (upload_max_filesize)",
            UPLOAD_ERR_FORM_SIZE => "File oltre il limite del form (MAX_FILE_SIZE)",
            UPLOAD_ERR_PARTIAL => "Upload parziale",
            UPLOAD_ERR_NO_FILE => "Nessun file inviato",
            UPLOAD_ERR_NO_TMP_DIR => "Manca la cartella temporanea",
            UPLOAD_ERR_CANT_WRITE => "Impossibile scrivere su disco",
            UPLOAD_ERR_EXTENSION => "Upload bloccato da estensione PHP",
            default => "Errore upload (codice $code)",
        };
    }
}
