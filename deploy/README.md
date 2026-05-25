Single-server deploy (Nginx + PHP-FPM + MySQL)

This folder contains scripts and example configs to deploy the project to a single Ubuntu VPS.

Overview
- Build the React app locally (or on the server).
- Copy `dist/`, PHP files, and `images/` to the server web root (e.g. `/var/www/bfc`).
- Configure MySQL, import `bfc.sql`, and update `db-config.php` with DB credentials.
- Configure Nginx to serve the SPA and PHP endpoints.
- Obtain an SSL cert with Certbot.

Files
- `deploy.sh` — helper script to build and sync files to the server.
- `nginx-bfc.conf` — example Nginx server block.
- `.env.example` — environment values used by the script and notes about DB credentials.

Usage
1. Edit `.env.example` and save as `.env` with your server values.
2. Make `deploy.sh` executable: `chmod +x deploy.sh`.
3. Run locally from repo root to build and push:

```bash
./deploy/deploy.sh ./deploy/.env
```

This script expects `ssh` access and `rsync` available locally and on the server.

If you prefer to do everything on the server, follow the manual steps in this README (install nginx/php/mysql, copy files, import DB, enable site, obtain SSL).
