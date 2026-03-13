#!/usr/bin/env bash
set -euo pipefail

# SonarQube scan helper using .env variables
# Requires: SONAR_HOST_URL and SONAR_LOGIN in .env

PROJECT_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT_DIR"

if [[ ! -f .env ]]; then
  echo "[sonar] .env not found. Please create it and set SONAR_HOST_URL and SONAR_LOGIN."
  exit 1
fi

# Read values from .env (strip quotes)
SONAR_HOST_URL=$(grep -E '^SONAR_HOST_URL=' .env | tail -n1 | cut -d '=' -f2- | tr -d '"' || true)
SONAR_LOGIN=$(grep -E '^SONAR_LOGIN=' .env | tail -n1 | cut -d '=' -f2- | tr -d '"' || true)

if [[ -z "${SONAR_HOST_URL:-}" || -z "${SONAR_LOGIN:-}" ]]; then
  echo "[sonar] Missing SONAR_HOST_URL or SONAR_LOGIN in .env"
  echo "Example:"
  echo "  SONAR_HOST_URL=http://localhost:9000"
  echo "  SONAR_LOGIN=your-generated-token"
  exit 1
fi

# Ensure coverage.xml exists; generate if missing
if [[ ! -f coverage.xml ]]; then
  echo "[sonar] coverage.xml not found. Generating coverage via Pest..."
  ./vendor/bin/sail test --coverage --min=90 --coverage-clover=coverage.xml || {
    echo "[sonar] Failed to generate coverage.xml"; exit 1;
  }
fi

# Run sonar-scanner using project properties and .env overrides
if ! command -v sonar-scanner >/dev/null 2>&1; then
  echo "[sonar] sonar-scanner CLI not found. Install it or use Docker:"
  echo "  docker run --rm -v \"$(pwd):/usr/src\" -e SONAR_HOST_URL=$SONAR_HOST_URL -e SONAR_LOGIN=$SONAR_LOGIN sonarsource/sonar-scanner-cli"
  exit 127
fi

echo "[sonar] Running sonar-scanner against $SONAR_HOST_URL ..."
sonar-scanner \
  -Dsonar.host.url="$SONAR_HOST_URL" \
  -Dsonar.login="$SONAR_LOGIN"
