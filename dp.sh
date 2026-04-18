#!/bin/bash
set -euo pipefail

# Script de Despliegue — sincroniza www/ al directorio raíz del servidor web
DEPLOY_DIR="/home2/elcerrit/elcerritovalle.org/rprtdrs"
REPO_URL="git@github.com:tiparamedicceo/rprtdrs.git"
BRANCH="master"

echo "🚀 Iniciando actualización en el servidor..."
echo "📍 Directorio: $DEPLOY_DIR"

cd "$DEPLOY_DIR" 2>/dev/null || { echo "❌ Directorio no encontrado: $DEPLOY_DIR"; exit 1; }

# ── Clonar si no es un repositorio git ──────────────────────────────────────
if [ ! -d ".git" ]; then
    echo "📦 Clonando repositorio..."
    BACKUP_NAME="../rprtdrs_backup_$(date +%s)"
    [ "$(ls -A .)" ] && mv "$DEPLOY_DIR" "$BACKUP_NAME" && mkdir -p "$DEPLOY_DIR" && cd "$DEPLOY_DIR"
    git clone -b "$BRANCH" "$REPO_URL" .
    echo "✅ Clonado en rama $BRANCH."
fi

# ── Sincronizar con origin (descarta cambios locales del servidor) ───────────
echo "📥 Actualizando desde GitHub (rama: $BRANCH)..."
git fetch origin
git checkout "$BRANCH" 2>/dev/null || git checkout -b "$BRANCH" "origin/$BRANCH"
git reset --hard "origin/$BRANCH"
echo "✅ Código actualizado al último commit."

# ── Sincronizar www/ a la raíz ───────────────────────────────────────────────
echo "📂 Sincronizando www/ con la raíz..."
rsync -av --delete --ignore-errors \
    --exclude='.git' \
    --exclude='www' \
    --exclude='android' \
    --exclude='rprtdrs' \
    --exclude='dp.sh' \
    --exclude='CLAUDE.md' \
    --exclude='error_log' \
    --exclude='debug_log.txt' \
    --exclude='uploads' \
    --exclude='conex.local.php' \
    --exclude='conex.php' \
    www/ .

# ── Proteger conex.php (no sobreescribir si ya existe en el servidor) ─────────
if [ ! -f "conex.php" ]; then
    echo "⚠️  conex.php no existe — copiando plantilla desde www/. Edita las credenciales vía FTP."
    cp www/conex.php conex.php 2>/dev/null || true
fi

# ── Permisos ─────────────────────────────────────────────────────────────────
echo "🔐 Ajustando permisos..."
find . -maxdepth 4 -not -path './.git/*' -type d -exec chmod 755 {} \;
find . -maxdepth 4 -not -path './.git/*' -type f -exec chmod 644 {} \; 2>/dev/null || true
[ -d "uploads" ] && chmod -R 755 uploads/ && chmod -R u+w uploads/
[ -f "error_log" ] && chmod 666 error_log

echo "✅ Despliegue completado."
echo "📍 https://elcerritovalle.org/rprtdrs"
