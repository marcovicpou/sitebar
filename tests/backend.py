"""Functional API checks against an isolated PHP server and disposable data."""
from pathlib import Path
import concurrent.futures, hashlib, http.cookiejar, json, os, secrets, shutil, socket
import subprocess, tempfile, time, urllib.error, urllib.request

ROOT = Path(__file__).resolve().parents[1]
passed = 0
def check(condition, name):
    global passed
    if not condition:
        raise AssertionError(name)
    passed += 1
    print("PASS:", name)

class Client:
    def __init__(self, base):
        self.base = base
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.token = ""
    def request(self, action, payload=None, method=None, token=True):
        headers = {"Content-Type": "application/json"}
        if token: headers["X-CSRF-Token"] = self.token
        req = urllib.request.Request(self.base + "/api/index.php?action=" + action, data=json.dumps(payload).encode() if payload is not None else None, headers=headers, method=method or ("POST" if payload is not None else "GET"))
        try:
            response = self.opener.open(req, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        status, body = response.code, json.load(response)
        if "csrf" in body: self.token = body["csrf"]
        return status, body

with tempfile.TemporaryDirectory(prefix="sitebar-api-") as work:
    storage = Path(work) / "storage"; storage.mkdir()
    fingerprints = {}
    for name in ("settings.json", "events.json", "menu.json", "gallery.json"):
        original = ROOT / "storage" / name
        fingerprints[name] = hashlib.sha256(original.read_bytes()).hexdigest()
        shutil.copyfile(original, storage / name)
    with socket.socket() as sock:
        sock.bind(("127.0.0.1", 0)); port = sock.getsockname()[1]
    runtime = os.environ.get("PHP_BIN") or shutil.which("php") or "/workspace/tooling/bin/php"
    with (Path(work) / "server.log").open("w") as log:
        server = subprocess.Popen([runtime, "-S", "127.0.0.1:" + str(port), "router.php"], cwd=ROOT, env=dict(os.environ, SITE_STORAGE_DIR=str(storage)), stdout=log, stderr=log)
        base = "http://127.0.0.1:" + str(port)
        client = Client(base)
        try:
            for attempt in range(60):
                try: client.request("session"); break
                except urllib.error.URLError: time.sleep(.1)
            else: raise RuntimeError("PHP did not start")
            status, public = client.request("public")
            check(status == 200 and len(public["gallery"]) == len(json.loads((storage/"gallery.json").read_text())), "public API preserves imported content")
            for path in ["/storage/settings.json", "/includes/config.php", "/src/main.tsx", "/.git/config", "/package.json"]:
                try: client.opener.open(base + path)
                except urllib.error.HTTPError as e: check(e.code in (403,404), "private path blocked: " + path)
                else: check(False, "private path was exposed")
            check(client.request("admin")[0] == 401, "unauthenticated admin access rejected")
            check(client.request("delete", method="GET")[0] == 405, "GET cannot delete records")
            check(client.request("setup", {"username":"test","password":"anything"}, token=False)[0] == 419, "setup requires CSRF")
            check(client.request("setup", {"username":"test","password":"tiny","confirm":"tiny"})[0] == 422, "weak setup password rejected")
            password = secrets.token_urlsafe(18)
            status, _ = client.request("setup", {"username":"api-test","password":password,"confirm":password})
            check(status == 201, "first administrator created without default credentials")
            check(json.loads((storage/"admin.json").read_text())["password_hash"] != password, "password stored as a hash")
            check(client.request("setup", {"username":"other","password":password,"confirm":password})[0] == 409, "second setup rejected")
            check(client.request("admin")[0] == 200, "authenticated administration works")
            check(client.request("save", {"type":"menu","titulo":"Bad price","preco":"-1","ativo":True})[0] == 422, "invalid price rejected")
            check(client.request("save", {"type":"events","titulo":"Bad date","data":"2026-02-30","hora":"19:00","ativo":True})[0] == 422, "impossible calendar date rejected")
            check(client.request("save", {"type":"gallery","titulo":"Missing image","ativo":True})[0] == 422, "gallery requires an actual image")
            status, result = client.request("save", {"type":"menu","titulo":"Item do teste <script>","categoria":"Teste","descricao":"Criado pela API","preco":"12.50","ativo":True})
            ident = result["row"]["id"]
            check(status == 201 and result["row"]["preco"] == "12,50", "menu create normalizes price")
            check(any(r["id"] == ident for r in client.request("public")[1]["menu"]), "published item reaches public API")
            row = result["row"]; row.update(type="menu",ativo=False,titulo="Rascunho do teste")
            check(client.request("save",row)[0] == 200, "existing item updates")
            check(not any(r["id"] == ident for r in client.request("public")[1]["menu"]), "unpublished content stays private")
            check(client.request("save", {"type":"menu","id":"missing","titulo":"Missing"})[0] == 404, "update cannot silently create a missing ID")
            check(client.request("delete", {"type":"menu","id":ident})[0] == 200, "POST deletes an existing record")
            check(client.request("delete", {"type":"menu","id":ident})[0] == 404, "missing record deletion is reported")
            check(client.request("settings", {"instagram":"javascript:alert(1)"})[0] == 422, "unsafe social URL rejected")
            check(client.request("settings", {"nome":""})[0] == 422, "empty site name rejected")
            check(client.request("settings", {"hero_subtitle":"Texto atualizado em teste"})[0] == 200, "site settings update")
            check(client.request("public")[1]["settings"]["hero_subtitle"] == "Texto atualizado em teste", "saved settings reach public site")
            second = Client(base); second.request("session")
            check(second.request("login", {"username":"api-test","password":password})[0] == 200, "a second session can authenticate")
            new_password = secrets.token_urlsafe(18)
            check(client.request("password", {"username":"api-test","current":password,"password":new_password,"confirm":new_password})[0] == 200, "password change works")
            check(second.request("admin")[0] == 401, "credential change invalidates other sessions")
            check(client.request("logout",{})[0] == 200, "logout works through POST")
            check(client.request("admin")[0] == 401, "logged-out session loses access")
            check(client.request("login", {"username":"api-test","password":password})[0] == 401, "previous password is rejected")
            check(client.request("login", {"username":"api-test","password":new_password})[0] == 200, "new password authenticates")
            wrong = Client(base); wrong.request("session")
            for _ in range(8): status, _ = wrong.request("login", {"username":"api-test","password":"incorrect"})
            check(wrong.request("login", {"username":"api-test","password":"incorrect"})[0] == 429, "repeated login attempts are rate limited")
            # Separate PHP processes exercise actual concurrent file transactions.
            code = 'require "includes/cms.php"; cms_upsert("menu",["titulo"=>"Concurrent","descricao"=>"","categoria"=>"Teste","preco"=>"1,00","ativo"=>true]);'
            before = len(json.loads((storage/"menu.json").read_text()))
            def create(_):
                subprocess.run([runtime,"-r",code],cwd=ROOT,env=dict(os.environ,SITE_STORAGE_DIR=str(storage)),check=True,capture_output=True)
            with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
                list(pool.map(create,range(12)))
            check(len(json.loads((storage/"menu.json").read_text())) == before + 12, "concurrent writes do not lose records")
            for name,digest in fingerprints.items():
                check(hashlib.sha256((ROOT/"storage"/name).read_bytes()).hexdigest() == digest, "original data unchanged: " + name)
        finally:
            server.terminate(); server.wait(timeout=10)
print(str(passed) + " functional backend checks passed.")
