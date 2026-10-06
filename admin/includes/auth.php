<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('bea_admin');
    session_start(['cookie_httponly'=>true,'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','cookie_samesite'=>'Lax','use_strict_mode'=>true,'cookie_path'=>'/']);
}
function admin_data(): array { return cms_read_json('admin.json'); }
function admin_exists(): bool { $a=admin_data(); return !empty($a['username']) && !empty($a['password_hash']); }
function admin_csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function admin_logged(): bool {
    if (!empty($_SESSION['admin_logged']) && time()-($_SESSION['last_activity']??0) < 1800) {
        $a=admin_data();
        if (!empty($a) && hash_equals(hash('sha256',$a['username'].$a['password_hash']),$_SESSION['auth_version']??'')) {
            $_SESSION['last_activity']=time(); return true;
        }
    }
    unset($_SESSION['admin_logged'],$_SESSION['admin_user']); return false;
}
function admin_check_csrf(string $token): bool { return $token !== '' && hash_equals(admin_csrf(),$token); }
function admin_login(string $username,string $password): bool {
    $key=hash('sha256',$_SERVER['REMOTE_ADDR']??'local'); $blocked=false;
    cms_mutate('login_attempts.json',function($rows) use($key,&$blocked) {
        $rows=array_filter($rows,fn($r)=>($r['until']??0)>time());
        $record=$rows[$key]??['count'=>0,'until'=>time()+900];
        $blocked=$record['count'] >= 8;
        if (!$blocked) $record['count']++;
        $rows[$key]=$record; return $rows;
    });
    if ($blocked) throw new OverflowException('Muitas tentativas. Aguarde 15 minutos e tente novamente.');
    $a=admin_data();
    $passwordOk=password_verify($password,$a['password_hash']??'$2y$10$7EqJtq98hPqEX7fNZaFWoO5pj.OFwXkbOoDOxdKrsiHHQSUnid3aO');
    if (!$passwordOk || !hash_equals($a['username']??'',trim($username))) return false;
    cms_mutate('login_attempts.json',function($rows) use($key) {unset($rows[$key]);return $rows;});
    session_regenerate_id(true);
    $_SESSION['admin_logged']=true;$_SESSION['admin_user']=$a['username'];$_SESSION['last_activity']=time();
    $_SESSION['auth_version']=hash('sha256',$a['username'].$a['password_hash']);
    $_SESSION['csrf']=bin2hex(random_bytes(32));return true;
}
function admin_logout(): void {
    $_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
}
