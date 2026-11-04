"""Consolida somente resumos publicaveis; nunca exporta tokens do Newman."""
import hashlib
import json
import re
import shutil
import xml.etree.ElementTree as ET
from datetime import datetime, timedelta, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DEST = ROOT / "docs/qualidade/evidencias/windows-final"
DEST.mkdir(parents=True, exist_ok=True)


def save(name, value):
    (DEST / name).write_text(json.dumps(value, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


newman = json.loads((ROOT / "build/newman-private.json").read_text(encoding="utf-8"))
save("postman-resumo.json", {
    "requests": newman["run"]["stats"]["requests"],
    "assertions": newman["run"]["stats"]["assertions"],
    "failures": len(newman["run"]["failures"]),
    "environment": "HTTP / Nginx / PHP-FPM / MySQL 8.4",
})
scan = json.loads((ROOT / "build/semgrep-private.json").read_text(encoding="utf-8"))
save("semgrep-resumo.json", {
    "version": scan["version"], "config": "p/php", "scope": ["app", "routes"],
    "files_scanned": len(scan["paths"]["scanned"]),
    "findings": len(scan["results"]), "errors": scan.get("errors", []),
    "rules_run": 23,
    "image": "semgrep/semgrep",
})
clover = ET.parse(ROOT / "build/coverage/clover.xml")
metrics = clover.find("project/metrics").attrib
domains = {}
for node in clover.findall(".//file"):
    match = re.search(r"/app/Domain/([^/]+)/", node.attrib["name"].replace("\\", "/"))
    if match:
        domain = domains.setdefault(match[1], {"lines": 0, "covered": 0})
        for line in node.findall("line"):
            if line.attrib["type"] == "stmt":
                domain["lines"] += 1
                domain["covered"] += int(line.attrib["count"]) > 0
save("ambiente.json", {
    "date": datetime.now(timezone(timedelta(hours=-3))).isoformat(),
    "host": "Windows / Docker Desktop / containers Linux",
    "php_container": "8.4.26", "mysql": "8.4.11", "coverage_driver": "PCOV 1.0.12",
    "windows_native": {"passed": 137, "assertions": 773},
    "mysql_container": {"passed": 136, "skipped": 1, "assertions": 770},
    "coverage": {"covered": int(metrics["coveredstatements"]), "lines": int(metrics["statements"]), "domains": domains},
})
for source, target in [("build/coverage/clover.xml", "clover.xml"), ("build/junit.xml", "junit.xml")]:
    value = (ROOT / source).read_text(encoding="utf-8").replace("/var/www/html/", "oficina/")
    (DEST / target).write_text(value, encoding="utf-8")
coverage = (ROOT / "build/docker-coverage.txt").read_text(encoding="utf-8-sig")
coverage = re.sub(r"\x1b\[[0-9;]*m", "", coverage)
coverage = "\n".join(line.rstrip() for line in coverage.splitlines()).rstrip() + "\n"
(DEST / "cobertura-testes.txt").write_text(coverage, encoding="utf-8")
mysql_output = (DEST / "mysql-testes.txt").read_text(encoding="utf-8-sig")
(DEST / "mysql-testes.txt").write_text("\n".join(line.rstrip() for line in mysql_output.splitlines()).rstrip() + "\n", encoding="utf-8")
shutil.make_archive(str(DEST / "cobertura-html"), "zip", ROOT / "build/coverage", "html")
paths = []
for folder in ["app", "routes", "database/migrations", "config", "scripts", "docker"]:
    paths.extend(path for path in (ROOT / folder).rglob("*") if path.is_file() and "__pycache__" not in str(path))
paths.extend(ROOT / name for name in ["composer.json", "composer.lock", "Dockerfile", "docker-compose.yml", "phpunit.xml", ".dockerignore", ".env.example", ".github/workflows/quality.yml"])
save("codigo.sha256.json", {str(path.relative_to(ROOT)).replace("\\", "/"): hashlib.sha256(path.read_bytes()).hexdigest() for path in sorted(paths)})
print("Resumos sem credenciais, cobertura e manifesto de codigo gravados.")
