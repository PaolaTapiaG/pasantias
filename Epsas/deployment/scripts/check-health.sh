#!/usr/bin/env bash
set -euo pipefail

: "${EPSAS_HEALTH_URL:?EPSAS_HEALTH_URL es obligatorio}"
: "${MONITORING_HEALTH_TOKEN:?MONITORING_HEALTH_TOKEN es obligatorio}"

curl --fail --silent --show-error \
    --max-time 10 \
    --header "X-Health-Token: $MONITORING_HEALTH_TOKEN" \
    "$EPSAS_HEALTH_URL"
