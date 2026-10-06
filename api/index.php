<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(array $body, int $status = 200): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); exit;
}
function text_field(array $data, string $key, int $max = 3000): string {
    $value = $data[$key] ?? '';
    if (!is_scalar($value)) throw new InvalidArgumentException('Campo inválido: ' . $key);
    $value = trim((string)$value);
    if (strlen($value) > $max) throw new InvalidArgumentException('O campo ' . $key . ' é muito longo.');
    return $value;
}
function password_field(array $data, string $key): string {
    $value = $data[$key] ?? '';
    if (!is_string($value) || strlen($value)>72) throw new InvalidArgumentException('A senha deve ter no máximo 72 bytes.');
    return $value;
}
function valid_asset(string $path): bool {
    return $path === '' || (preg_match('~^assets/(?:uploads|img)/[a-zA-Z0-9_.-]+\.(?:png|jpe?g|webp)$~',$path) && is_file(cms_root().'/'.$path));
}
function validate_row(string $type, array $data, ?array $existing): array {
    $row = ['id'=>text_field($data,'id',80),'titulo'=>text_field($data,'titulo',180),'descricao'=>text_field($data,'descricao'), 'ativo'=>filter_var($data['ativo']??false,FILTER_VALIDATE_BOOLEAN)];
    if ($row['titulo'] === '') throw new InvalidArgumentException('Informe o título.');
    if ($type === 'menu') {
        $row['categoria'] = text_field($data,'categoria',80);
        $price = str_replace(',','.',text_field($data,'preco',20));
        if ($price !== '' && (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/',$price) || (float)$price < 0)) throw new InvalidArgumentException('Informe um preço válido, como 49,90.');
        $row['preco'] = $price === '' ? '' : number_format((float)$price,2,',','');
    }
    if ($type === 'events') {
        $row['data'] = text_field($data,'data',10);
        $row['hora'] = text_field($data,'hora',5);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d',$row['data']);
        if (!$date || $date->format('Y-m-d') !== $row['data']) throw new InvalidArgumentException('Informe uma data válida.');
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$row['hora'])) throw new InvalidArgumentException('Informe um horário válido.');
        $row['destaque'] = filter_var($data['destaque']??false,FILTER_VALIDATE_BOOLEAN);
    }
    $row['imagem'] = $existing['imagem'] ?? '';
    if (isset($_FILES['imagem']) && ($_FILES['imagem']['error']??UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $row['imagem'] = cms_upload_image($_FILES['imagem']);
    if ($type === 'gallery' && !$row['imagem']) throw new InvalidArgumentException('Envie uma foto para a galeria.');
    return $row;
}

try {
    $action = $_GET['action'] ?? 'public';
    $method = $_SERVER['REQUEST_METHOD'];
    if ($action === 'public' && $method === 'GET') {
        respond(['ok'=>true,'settings'=>cms_settings(),'menu'=>cms_active('menu'),'events'=>cms_active('events'),'gallery'=>cms_active('gallery')]);
    }
    require_once __DIR__ . '/../admin/includes/auth.php';
    if ($action === 'session' && $method === 'GET') respond(['ok'=>true,'logged'=>admin_logged(),'setup'=>!admin_exists(),'username'=>$_SESSION['admin_user']??'','csrf'=>admin_csrf()]);
    if ($action === 'admin' && $method === 'GET') {
        if (!admin_logged()) respond(['ok'=>false,'error'=>'Sua sessão terminou. Entre novamente.'],401);
        respond(['ok'=>true,'settings'=>cms_settings(),'menu'=>cms_collection('menu'),'events'=>cms_collection('events'),'gallery'=>cms_collection('gallery'),'username'=>$_SESSION['admin_user']]);
    }
    $mutations = ['setup','login','logout','save','delete','settings','password'];
    if (!in_array($action,$mutations,true)) respond(['ok'=>false,'error'=>'Operação não encontrada.'],404);
    if ($method !== 'POST') { header('Allow: POST'); respond(['ok'=>false,'error'=>'Esta operação exige POST.'],405); }
    if (!admin_check_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??'')) respond(['ok'=>false,'error'=>'Sua sessão expirou. Atualize a página e tente novamente.'],419);
    $input = isset($_POST['payload']) ? json_decode($_POST['payload'],true,512,JSON_THROW_ON_ERROR) : json_decode(file_get_contents('php://input') ?: '{}',true,512,JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new InvalidArgumentException('Dados inválidos.');
    if ($action === 'setup') {
        if (admin_exists()) respond(['ok'=>false,'error'=>'O administrador já foi criado.'],409);
        $username = text_field($input,'username',80);
        $password = password_field($input,'password');
        if (!preg_match('/^[\p{L}\p{N}_.@-]{3,80}$/u',$username)) throw new InvalidArgumentException('Use um usuário com 3 a 80 caracteres, sem espaços.');
        if (strlen($password)<8) throw new InvalidArgumentException('A senha deve ter pelo menos 8 caracteres.');
        if ($password !== ($input['confirm']??'')) throw new InvalidArgumentException('As senhas não conferem.');
        cms_mutate('admin.json',function($current) use($username,$password) {
            if ($current) throw new DomainException('O administrador já foi criado.');
            return ['username'=>$username,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'created_at'=>date('c'),'updated_at'=>date('c')];
        });
        admin_login($username,$password);
        respond(['ok'=>true,'csrf'=>admin_csrf(),'username'=>$username],201);
    }
    if ($action === 'login') {
        if (!admin_exists()) respond(['ok'=>false,'error'=>'Crie o administrador no primeiro acesso.'],409);
        if (!admin_login(text_field($input,'username',80),password_field($input,'password'))) respond(['ok'=>false,'error'=>'Usuário ou senha incorretos.'],401);
        respond(['ok'=>true,'csrf'=>admin_csrf(),'username'=>$_SESSION['admin_user']]);
    }
    if (!admin_logged()) respond(['ok'=>false,'error'=>'Entre no painel para continuar.'],401);
    if ($action === 'logout') { admin_logout(); respond(['ok'=>true,'csrf'=>admin_csrf()]); }
    if ($action === 'save' || $action === 'delete') {
        $type = text_field($input,'type',20); cms_collection_file($type);
        $id = text_field($input,'id',80);
        $existing = $id !== '' ? cms_find($type,$id) : null;
        if ($id !== '' && !$existing) throw new OutOfBoundsException('O registro não foi encontrado.');
        if ($action === 'delete') { if ($id === '') throw new InvalidArgumentException('Registro inválido.'); cms_delete($type,$id); respond(['ok'=>true]); }
        $row = validate_row($type,$input,$existing);
        respond(['ok'=>true,'row'=>cms_upsert($type,$row)],$existing ? 200 : 201);
    }
    if ($action === 'settings') {
        $settings = cms_settings(); $payload = [];
        foreach ($settings as $key=>$value) if (array_key_exists($key,$input)) $payload[$key] = text_field($input,$key);
        if (isset($payload['nome']) && $payload['nome'] === '') throw new InvalidArgumentException('Informe o nome do bar.');
        foreach (['url','instagram','facebook'] as $key) if (!empty($payload[$key]) && (!filter_var($payload[$key],FILTER_VALIDATE_URL) || !preg_match('~^https?://~i',$payload[$key]))) throw new InvalidArgumentException('Informe um endereço HTTPS ou HTTP válido para '.$key.'.');
        if (!empty($payload['email']) && !filter_var($payload['email'],FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Informe um e-mail válido.');
        foreach (['logo_image','hero_image'] as $key) {
            unset($payload[$key]);
            if (isset($_FILES[$key]) && ($_FILES[$key]['error']??UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $payload[$key]=cms_upload_image($_FILES[$key]);
        }
        cms_save_settings($payload); respond(['ok'=>true,'settings'=>cms_settings()]);
    }
    if ($action === 'password') {
        $current = admin_data();
        if (!password_verify(password_field($input,'current'),$current['password_hash'])) throw new InvalidArgumentException('A senha atual não está correta.');
        $username = text_field($input,'username',80); $password = password_field($input,'password');
        if (!preg_match('/^[\p{L}\p{N}_.@-]{3,80}$/u',$username)) throw new InvalidArgumentException('Informe um usuário válido, com pelo menos 3 caracteres.');
        if ($password !== '' && strlen($password)<8) throw new InvalidArgumentException('A nova senha precisa ter pelo menos 8 caracteres.');
        if ($password !== ($input['confirm']??'')) throw new InvalidArgumentException('As novas senhas não conferem.');
        cms_mutate('admin.json',function($a) use($username,$password) { $a['username']=$username; if ($password !== '') $a['password_hash']=password_hash($password,PASSWORD_DEFAULT);$a['updated_at']=date('c');return $a; });
        $_SESSION['admin_user']=$username;
        $updated=admin_data();$_SESSION['auth_version']=hash('sha256',$updated['username'].$updated['password_hash']);
        session_regenerate_id(true);
        respond(['ok'=>true,'username'=>$username]);
    }
} catch (OverflowException $error) { respond(['ok'=>false,'error'=>$error->getMessage()],429);
} catch (OutOfBoundsException $error) { respond(['ok'=>false,'error'=>$error->getMessage()],404);
} catch (DomainException $error) { respond(['ok'=>false,'error'=>$error->getMessage()],409);
} catch (InvalidArgumentException | JsonException $error) { respond(['ok'=>false,'error'=>$error instanceof JsonException ? 'JSON inválido.' : $error->getMessage()],422);
} catch (Throwable $error) { error_log((string)$error); respond(['ok'=>false,'error'=>'Não foi possível concluir a operação. Tente novamente.'],500); }
