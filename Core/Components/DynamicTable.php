<?php

namespace Core\Components;

use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

class DynamicTable
{
    private array $rows;
    private array $columns;
    private array $preloadedData = [];
    private array $options = [];

    /**
     * @param array $rows Dati delle righe
     * @param array $columns Definizione colonne (colName => callback|string)
     * @param array $preloadedData Dati precalcolati
     * @param array $options Opzioni extra, es: ['tfootClasses' => ['Colonna1' => 'text-end']]
     */
    public function __construct(array $rows, array $columns, array $preloadedData = [], array $options = [])
    {
        $this->rows = $rows;
        $this->columns = $columns;
        $this->options = $options;

        // Mappatura automatica dei dati pre-caricati
        foreach ($preloadedData as $key => $data) {
            $this->preloadedData[$key] = $this->autoMap($key, $data);
        }
    }

    public function setPreloadedData(string $key, array $data): void
    {
        $this->preloadedData[$key] = $this->autoMap($key, $data);
    }

    private function autoMap(string $key, array $data): array
    {
        $map = [];
        if ($key === 'comments') {
            foreach ($data as $row) {
                $postId = $row['comment_post_ID'];
                $map[$postId] = ($map[$postId] ?? 0) + 1;
            }
        } elseif ($key === 'users') {
            foreach ($data as $row) {
                $map[$row['ID']] = $row['display_name'];
            }
        } else {
            $map = $data;
        }
        return $map;
    }

    public function render(): string
    {
        $tfootClasses = $this->options['tfootClasses'] ?? [];

        $html = '<table class="table table-bordered" id="dynamicTableCustom">';

        // ---- THEAD ----
        $html .= '<thead><tr>';
        foreach ($this->columns as $col => $callback) {
            $html .= "<th>{$col}</th>";
        }
        $html .= '</tr></thead>';

        // ---- TFOOT (uguale a THEAD ma con classi custom) ----
        $html .= '<tfoot><tr>';
        foreach ($this->columns as $col => $callback) {
            $class = $tfootClasses[$col] ?? '';
            $html .= "<th class=\"{$class}\">{$col}</th>";
        }
        $html .= '</tr></tfoot>';

        // ---- TBODY ----
        $html .= '<tbody>';
        foreach ($this->rows as $row) {
            // Controllo righe da nascondere se opzione hideZeroRows è attiva
            if (!empty($this->options['hideZeroRows'])) {
                $skip = false;
                foreach ($this->options['hideZeroRows'] as $col) {
                    $val = is_callable($this->columns[$col])
                        ? $this->columns[$col]($row, $this->preloadedData)
                        : ($row[$col] ?? 0);
                    if ($val === "0"  || $val === 0) $skip = true;
                }
                if ($skip) continue;
            }

            $html .= '<tr>';
            foreach ($this->columns as $col => $callback) {
                if (is_callable($callback)) {
                    $html .= '<td>' . $callback($row, $this->preloadedData) . '</td>';
                } else {
                    $html .= '<td>' . ($row[$col] ?? '') . '</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        $html .= '</table>';

        return $html;
    }
}
