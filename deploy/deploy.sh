#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -lt 1 ]; then
  echo "Usage: $0 path/to/.env"
  exit 1
fi

ENVFILE=$1
if [ ! -f "$ENVFILE" ]; then
  echo "Env file not found: $ENVFILE"
  exit 1
fi

set -a
source "$ENVFILE"
set +a

echo "Building React app..."
pushd "$LOCAL_REACT_APP"
npm ci
npm run build
popd

TMPDIR=$(mktemp -d)
echo "Preparing files in $TMPDIR"

# copy static build
mkdir -p "$TMPDIR/www"
cp -r "$LOCAL_REACT_APP/dist/"* "$TMPDIR/www/"

# copy PHP backend files (assumes PHP files are at repo root)
cp -r "$LOCAL_PHP_BACKEND"/*.php "$TMPDIR/www/" || true

# copy images
cp -r "$LOCAL_PHP_BACKEND/images" "$TMPDIR/www/" || true

echo "Syncing files to $REMOTE_USER@$REMOTE_HOST:$REMOTE_WEB_ROOT"
RSYNC_OPTS="-avz --delete --omit-dir-times --no-perms"
if [ -n "${SSH_KEY:-}" ] && [ -f "$SSH_KEY" ]; then
  RSYNC_SSH="-e 'ssh -i $SSH_KEY -p $REMOTE_PORT'"
else
  RSYNC_SSH="-e 'ssh -p $REMOTE_PORT'"
fi

eval rsync $RSYNC_OPTS $RSYNC_SSH "$TMPDIR/www/" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_WEB_ROOT/"

echo "Setting ownership on server and reloading services"
ssh -p $REMOTE_PORT $REMOTE_USER@$REMOTE_HOST <<EOF
sudo chown -R www-data:www-data $REMOTE_WEB_ROOT
if systemctl is-active --quiet nginx; then sudo nginx -t && sudo systemctl reload nginx; fi
EOF

echo "Deploy complete. Remember to import DB and configure /path/to/db-config.php on the server."
echo "Temporary folder: $TMPDIR (you can remove it)"
