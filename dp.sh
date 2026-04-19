#!/bin/bash
set -euo pipefail

# ── Configuración ─────────────────────────────────────────────────────────────
DEPLOY_DIR="/home2/elcerrit/elcerritovalle.org/rprtdrs"
REPO_URL="git@github.com:tiparamedicceo/rprtdrs.git"
BRANCH="main"
TMP_DIR="/tmp/rprtdrs_deploy_$$"

echo "🚀 Iniciando despliegue..."
echo "📍 Destino : $DEPLOY_DIR"
echo "🔗 Repo    : $REPO_URL  (rama: $BRANCH)"

# ── 1. Preservar conex.php ────────────────────────────────────────────────────
CONEX_SRC="$DEPLOY_DIR/conex.php"
CONEX_BAK="/tmp/conex_rprtdrs_$(whoami).php"
if [ -f "$CONEX_SRC" ]; then
    cp "$CONEX_SRC" "$CONEX_BAK"
    echo "💾 conex.php guardado en $CONEX_BAK"
else
    echo "⚠️  conex.php no encontrado en $DEPLOY_DIR — recuerda subirlo por FTP después."
fi

# ── 2. Clonar repo en directorio temporal ────────────────────────────────────
echo "📥 Clonando rama $BRANCH..."
rm -rf "$TMP_DIR"
if ! git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$TMP_DIR" 2>&1; then
    echo "❌ Error al clonar. Verifica la clave SSH y la URL del repositorio."
    exit 1
fi

COMMIT=$(git -C "$TMP_DIR" log --oneline -1)
echo "✅ Commit: $COMMIT"

# ── 3. Crear destino si no existe ─────────────────────────────────────────────
mkdir -p "$DEPLOY_DIR"

# ── 4. Rsync www/ → destino ──────────────────────────────────────────────────
echo "📂 Sincronizando archivos..."
rsync -a --delete --ignore-errors \
    --exclude='.git' \
    --exclude='conex.php' \
    --exclude='conex.local.php' \
    --exclude='uploads' \
    --exclude='error_log' \
    --exclude='debug_log.txt' \
    "$TMP_DIR/www/" "$DEPLOY_DIR/"

echo "✅ Archivos sincronizados."

# ── 5b. Eliminar www/ del destino (no debe ser accesible como URL) ────────────
rm -rf "$DEPLOY_DIR/www" && echo "🗑  www/ eliminado del destino." || true

# ── 5. Restaurar conex.php ────────────────────────────────────────────────────
if [ -f "$CONEX_BAK" ]; then
    cp "$CONEX_BAK" "$CONEX_SRC"
    echo "✅ conex.php restaurado."
fi

# ── 6. Asegurar carpeta uploads ───────────────────────────────────────────────
mkdir -p "$DEPLOY_DIR/uploads"

# ── 7. Permisos ───────────────────────────────────────────────────────────────
echo "🔐 Ajustando permisos..."
find "$DEPLOY_DIR" -maxdepth 4 -not -path '*/.git/*' -type d -exec chmod 755 {} \; 2>/dev/null || true
find "$DEPLOY_DIR" -maxdepth 4 -not -path '*/.git/*' -type f -exec chmod 644 {} \; 2>/dev/null || true
chmod -R u+w "$DEPLOY_DIR/uploads/" 2>/dev/null || true
[ -f "$DEPLOY_DIR/error_log" ] && chmod 666 "$DEPLOY_DIR/error_log" || true

# ── 8. Auto-actualizar dp.sh ──────────────────────────────────────────────────
SELF="$(readlink -f "$0")"
if [ -f "$TMP_DIR/dp.sh" ]; then
    cp "$TMP_DIR/dp.sh" "$SELF"
    chmod +x "$SELF"
    echo "🔄 dp.sh actualizado."
fi

# ── 9. Limpiar temp ───────────────────────────────────────────────────────────
rm -rf "$TMP_DIR"

echo ""
echo "✅ Despliegue completado."
echo "📍 https://elcerritovalle.org/rprtdrs"
