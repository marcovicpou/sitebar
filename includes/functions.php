<?php
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function app_base(): string {
    $script = str_replace('\\','/',$_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base = rtrim(dirname($script), '/.');
    return preg_replace('~/(admin|api)$~','',$base);
}
function app_json(array $data): string { return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
