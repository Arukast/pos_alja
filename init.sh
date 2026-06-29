#!/bin/bash

set -euo pipefail

CONTAINER_WORKDIR="/var/www/html/pos_alja"
CONTAINER_NAME="local-php83"
DOCKER_EXEC_BASE="docker exec -it -w ${CONTAINER_WORKDIR} ${CONTAINER_NAME}"

INSTALL_CMD="${DOCKER_EXEC_BASE} composer install && ${DOCKER_EXEC_BASE} npm install"
VERIFY_CMD="${DOCKER_EXEC_BASE} env DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test"
START_CMD="${DOCKER_EXEC_BASE} sh -lc 'php artisan reverb:start --host=0.0.0.0 --port=8080 & composer run dev'"

trap 'echo "[init] ERROR: script failed at line ${LINENO} while running: ${BASH_COMMAND}" >&2' ERR

echo "[init] Current working directory:"
pwd

echo "[init] Installing dependencies..."
bash -lc "${INSTALL_CMD}"

echo "[init] Running baseline verification..."
if ! bash -lc "${VERIFY_CMD}"; then
    echo "[init] ERROR: Baseline verification failed. Stopping initialization to avoid stacking changes on a broken codebase." >&2
    exit 1
fi

if [[ "${RUN_START_COMMAND:-0}" == "1" ]]; then
    echo "[init] RUN_START_COMMAND=1 detected. Launching development services..."
    bash -lc "${START_CMD}"
else
    echo "[init] RUN_START_COMMAND is not 1. Run this command manually to start services:"
    echo "${START_CMD}"
fi