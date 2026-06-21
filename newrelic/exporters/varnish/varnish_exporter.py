import json
import os
import re
import subprocess
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer


LISTEN = os.getenv("VARNISH_EXPORTER_LISTEN", "0.0.0.0")
PORT = int(os.getenv("VARNISH_EXPORTER_PORT", "9131"))
VARNISH_NAME = os.getenv("VARNISH_NAME", "/var/lib/varnish/varnishd")


def metric_name(counter_name):
    name = re.sub(r"[^a-zA-Z0-9_]", "_", counter_name).strip("_").lower()
    return "varnish_" + name


def metric_help(description):
    return description.replace("\\", "\\\\").replace("\n", " ").replace('"', '\\"')


def collect_metrics():
    output = subprocess.check_output(
        ["varnishstat", "-1", "-j", "-n", VARNISH_NAME],
        stderr=subprocess.STDOUT,
        text=True,
        timeout=10,
    )
    payload = json.loads(output)
    counters = payload.get("counters", {})
    lines = []

    for counter_name, counter in sorted(counters.items()):
        if not isinstance(counter, dict) or "value" not in counter:
            continue

        try:
            value = float(counter["value"])
        except (TypeError, ValueError):
            continue

        name = metric_name(counter_name)
        description = metric_help(str(counter.get("description", counter_name)))
        lines.append(f"# HELP {name} {description}")
        lines.append(f"# TYPE {name} gauge")
        lines.append(f"{name} {value}")

    return "\n".join(lines) + "\n"


class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/healthz":
            self.send_response(200)
            self.end_headers()
            self.wfile.write(b"ok\n")
            return

        if self.path != "/metrics":
            self.send_response(404)
            self.end_headers()
            return

        try:
            body = collect_metrics().encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/plain; version=0.0.4; charset=utf-8")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)
        except Exception as exc:
            body = f"varnish_exporter_error {type(exc).__name__}: {exc}\n".encode("utf-8")
            self.send_response(500)
            self.send_header("Content-Type", "text/plain; charset=utf-8")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)

    def log_message(self, format, *args):
        return


if __name__ == "__main__":
    server = ThreadingHTTPServer((LISTEN, PORT), Handler)
    server.serve_forever()
