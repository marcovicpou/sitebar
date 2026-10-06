<?php
declare(strict_types=1);
require_once __DIR__ . '/defaults.php';
date_default_timezone_set('America/Sao_Paulo');
function cms_root(): string { return dirname(__DIR__); }
function cms_storage_dir(): string { return getenv('SITE_STORAGE_DIR') ?: cms_root() . '/storage'; }
function cms_upload_dir(): string { return cms_root() . '/assets/uploads'; }
function cms_path(string $file): string {
    if (!in_array($file, ['settings.json','events.json','menu.json','gallery.json','admin.json','login_attempts.json'], true)) throw new InvalidArgumentException('Arquivo de dados inválido.');
    return cms_storage_dir() . '/' . $file;
}
function cms_read_json(string $file, array $fallback = []): array {
    $path = cms_path($file);
    if (!is_file($path)) return $fallback;
    $raw = file_get_contents($path);
    if ($raw === false) throw new RuntimeException('Não foi possível ler os dados.');
    $result = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($result)) throw new RuntimeException('Arquivo de dados inválido.');
    return $result;
}
// Hold the lock across the complete read/modify/write operation.
function cms_mutate(string $file, callable $callback): array {
    $path = cms_path($file); $dir = cms_storage_dir();
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) throw new RuntimeException('Não foi possível criar o armazenamento.');
    $lock = fopen($path . '.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Não foi possível bloquear o armazenamento.');
    $temp = null;
    try {
        $data = $callback(cms_read_json($file));
        if (!is_array($data)) throw new RuntimeException('Dados inválidos.');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $temp = tempnam($dir, '.write-');
        if (!$temp || file_put_contents($temp, $json . "\n") === false || !rename($temp, $path)) throw new RuntimeException('Não foi possível salvar os dados.');
        chmod($path, 0640);
        return $data;
    } finally {
        if ($temp && is_file($temp)) unlink($temp);
        flock($lock, LOCK_UN); fclose($lock);
    }
}
function cms_write_json(string $file, array $data): bool { cms_mutate($file, fn() => $data); return true; }
function cms_bootstrap(): void {
    $defaults = ['settings.json'=>cms_default_settings(),'events.json'=>cms_default_events(),'menu.json'=>cms_default_menu(),'gallery.json'=>cms_default_gallery()];
    foreach ($defaults as $file=>$data) if (!is_file(cms_path($file))) cms_mutate($file, fn($existing)=>$existing ?: $data);
}
function cms_settings(): array {
    return array_replace(cms_default_settings(), ['hero_subtitle'=>'Boa conversa, bebida gelada e um lugar para chamar de nosso.'], cms_read_json('settings.json'));
}
function cms_save_settings(array $input): bool {
    $keys = array_keys(cms_settings());
    cms_mutate('settings.json', function($current) use ($input,$keys) {
        foreach ($keys as $key) if (array_key_exists($key,$input)) $current[$key] = trim((string)$input[$key]);
        return $current;
    });
    return true;
}
function cms_collection_file(string $type): string {
    return match($type) { 'events'=>'events.json','menu'=>'menu.json','gallery'=>'gallery.json',default=>throw new InvalidArgumentException('Coleção inválida.') };
}
function cms_collection(string $type): array { return cms_read_json(cms_collection_file($type)); }
function cms_find(string $type, string $id): ?array {
    foreach (cms_collection($type) as $row) if ($row['id'] === $id) return $row;
    return null;
}
function cms_upsert(string $type, array $row): array {
    $saved = [];
    cms_mutate(cms_collection_file($type), function($rows) use ($type,$row,&$saved) {
        $id = $row['id'] ?? '';
        if ($id !== '') {
            foreach ($rows as &$existing) if ($existing['id'] === $id) {
                $existing = array_replace($existing,$row,['updated_at'=>date('c')]);
                $saved = $existing; return $rows;
            }
            throw new OutOfBoundsException('Este registro não foi encontrado.');
        }
        $saved = array_replace($row,['id'=>$type . '_' . bin2hex(random_bytes(8)),'created_at'=>date('c'),'updated_at'=>date('c')]);
        $rows[] = $saved; return $rows;
    });
    return $saved;
}
function cms_delete(string $type, string $id): bool {
    cms_mutate(cms_collection_file($type), function($rows) use ($id) {
        $filtered = array_values(array_filter($rows,fn($row)=>$row['id'] !== $id));
        if (count($filtered) === count($rows)) throw new OutOfBoundsException('Este registro não foi encontrado.');
        return $filtered;
    }); return true;
}
function cms_active(string $type): array {
    $rows = array_values(array_filter(cms_collection($type),fn($row)=>!empty($row['ativo'])));
    if ($type === 'events') usort($rows,fn($a,$b)=>strcmp(($a['data']??'').($a['hora']??''),($b['data']??'').($b['hora']??'')));
    return $rows;
}
function cms_upload_image(array $file): string {
    if (($file['error']??UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Não foi possível enviar a imagem.');
    $temp = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($temp) || filesize($temp) > 5*1024*1024) throw new InvalidArgumentException('Envie uma imagem de até 5 MB.');
    $info = @getimagesize($temp);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
    $types = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!$info || !isset($types[$mime]) || $info['mime'] !== $mime || $info[0]*$info[1] > 40000000) throw new InvalidArgumentException('Use uma imagem JPG, PNG ou WEBP válida.');
    if (!is_dir(cms_upload_dir())) mkdir(cms_upload_dir(),0755,true);
    $name = 'upload_' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
    if (!move_uploaded_file($temp,cms_upload_dir().'/'.$name)) throw new RuntimeException('Não foi possível salvar a imagem.');
    return 'assets/uploads/'.$name;
}
cms_bootstrap();
