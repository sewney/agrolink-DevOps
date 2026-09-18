#!/usr/bin/env python3
"""Run basic HTTP smoke tests against a deployed AgroLink application."""

import sys
import time
import urllib.error
import urllib.parse
import urllib.request

BASE_URL = sys.argv[1] if len(sys.argv) > 1 else "http://localhost"
MAX_RETRIES = 12
RETRY_DELAY = 5
REQUEST_TIMEOUT = 10

CHECKS = (
    ("homepage", "/", "text/html", b"<title>AgroLink - Farm to Market</title>"),
    ("login page", "/login", "text/html", b'<form id="loginForm"'),
    (
        "registration page",
        "/register",
        "text/html",
        b'<form id="registerForm"',
    ),
    (
        "main stylesheet",
        "/assets/css/style1.css",
        "text/css",
        b"--primary-green:",
    ),
)


def build_url(path):
    return urllib.parse.urljoin(BASE_URL.rstrip("/") + "/", path.lstrip("/"))


def run_checks():
    for name, path, expected_type, expected_content in CHECKS:
        url = build_url(path)
        try:
            with urllib.request.urlopen(url, timeout=REQUEST_TIMEOUT) as response:
                body = response.read()
                content_type = response.headers.get_content_type()

                if not 200 <= response.status < 300:
                    print(f"FAIL: {name} returned HTTP {response.status}")
                    return False
                if content_type != expected_type:
                    print(
                        f"FAIL: {name} returned {content_type}, expected {expected_type}"
                    )
                    return False
                if expected_content not in body:
                    print(f"FAIL: {name} did not contain the expected content")
                    return False

                print(f"PASS: {name} ({response.status})")
        except (urllib.error.URLError, TimeoutError) as error:
            print(f"FAIL: {name} could not be reached: {error}")
            return False

    return True


def smoke_test():
    for attempt in range(1, MAX_RETRIES + 1):
        print(f"Smoke test attempt {attempt}/{MAX_RETRIES}...")
        if run_checks():
            return True

        if attempt < MAX_RETRIES:
            print(f"Retrying in {RETRY_DELAY} seconds...")
            time.sleep(RETRY_DELAY)

    return False


if __name__ == "__main__":
    if smoke_test():
        print("Deployment smoke test passed.")
        sys.exit(0)

    print("Deployment smoke test failed.")
    sys.exit(1)
