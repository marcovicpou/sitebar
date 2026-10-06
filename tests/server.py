"""Run PHP with disposable CMS storage so browser tests never edit user data."""
from pathlib import Path
import argparse, os, shutil, signal, subprocess, tempfile

parser = argparse.ArgumentParser()
parser.add_argument("--port", type=int, default=8081)
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
storage = tempfile.TemporaryDirectory(prefix="sitebar-browser-")
for name in ("settings.json", "menu.json", "events.json", "gallery.json"):
    shutil.copyfile(root / "storage" / name, Path(storage.name) / name)
runtime = os.environ.get("PHP_BIN") or shutil.which("php") or "/workspace/tooling/bin/php"
env = dict(os.environ, SITE_STORAGE_DIR=storage.name)
uploads_before = set((root / "assets/uploads").glob("upload_*"))
process = subprocess.Popen([runtime, "-S", "127.0.0.1:" + str(args.port), "router.php"], cwd=root, env=env)

def stop(*_):
    if process.poll() is None:
        process.terminate()
    process.wait(timeout=10)
    for image in set((root / "assets/uploads").glob("upload_*")) - uploads_before:
        image.unlink(missing_ok=True)
    storage.cleanup()
    raise SystemExit()

signal.signal(signal.SIGTERM, stop)
signal.signal(signal.SIGINT, stop)
try:
    process.wait()
finally:
    stop()
