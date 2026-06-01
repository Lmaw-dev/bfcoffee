Deployment guide — Vercel (frontend) + Render (PHP backend) + Supabase (PostgreSQL)
=========================================================

This guide collects exact commands and steps to deploy the project live using:
- Frontend: Vercel (static site from `react-app/dist`)
- Backend: Render (Docker service running PHP + Apache), with the existing Supabase PostgreSQL project as the database

Prerequisites (local)
- A GitHub account and the repo pushed to GitHub.
- `git`, `npm` installed locally.
- `vercel` CLI (optional) and a Vercel account.
- Render account.

1) Push repository to GitHub

From your project root:

```bash
git init # if not already
git add .
git commit -m "Initial deploy-ready commit"
# create a repo on GitHub and push (replace URL)
git remote add origin git@github.com:youruser/yourrepo.git
git branch -M main
git push -u origin main
```

2) Replace the existing Supabase project in place, then connect Render to it

A. Ensure the root `Dockerfile` and root `render.yaml` are in your repo (already added).

B. Open the existing Supabase project and go to the SQL editor.
   - Run [`supabase/schema.sql`](supabase/schema.sql) to drop and recreate the app tables in place.
   - This replaces the live project's current cafe tables and reseeds them with the repo data.

C. On Render:
   1. New → Blueprint → Connect to your GitHub repo.
   2. Render will detect the root-level `render.yaml` and create `bfc-backend` service (docker).
   3. Set Environment variables in the Service Settings:
      - `DB_DRIVER` = `pgsql`
      - `DATABASE_URL` = the existing Supabase database connection string (or `SUPABASE_DB_URL`)
   4. Deploy. The service will start and expose a public URL like `https://bfc-backend.onrender.com`.

D. Verify the database import by checking the seeded tables in Supabase.

   - The seeded products, staff, orders, registration, users, and settings tables are defined in [`supabase/schema.sql`](supabase/schema.sql).

E. Verify API endpoints:

```bash
curl "https://bfc-backend.onrender.com/store-api.php?action=get_products"
```

3) Frontend — Vercel (must point to the repo root that contains `react-app`)

A. On GitHub, ensure `react-app` is included in the repo.
B. On Vercel dashboard: New Project → Import Git Repository → choose your repo.
C. Configure:
   - Root Directory: leave blank unless your GitHub repo actually contains a top-level `react-app` folder.
   - If the root directory setting shows `react-app does not exist`, remove the Root Directory value and use the repo root instead.
   - If deploying from the repo root, set Build Command to `cd react-app && npm ci && npm run build`.
   - Output Directory: `react-app/dist` if you build from the repo root, otherwise `dist` if Root Directory is `react-app`.
D. Environment Variables (Vercel project settings):
   - `VITE_PHP_API_BASE` = `https://bfc-backend.onrender.com` (replace with your Render URL)
E. Deploy. Vercel will build and host the frontend; it will provide a public URL with HTTPS.

4) Verify end-to-end

- Open the Vercel URL, go to the menu page, and verify products load and images resolve from the Render backend.
- Admin login: use `jireh` / `faith` (these are the credentials in the DB). The Admin page is inside the SPA and uses the backend login endpoints.

5) Optional: Custom domain

- Add your domain in Vercel (frontend) and in Render (backend) if you want custom hostnames. Update `VITE_PHP_API_BASE` to the full backend URL.

Notes & troubleshooting
- If images are missing, ensure `images/` exists in the repo root and is copied into the backend container (the Dockerfile copies repo root into `/var/www/html`). The image URLs resolved by the frontend will be absolute using `VITE_PHP_API_BASE`.
- If you prefer the backend to serve assets from a CDN, consider uploading `images/` to an object storage (DigitalOcean Spaces, S3) and update DB `image` columns to point to the absolute URLs.

If you want, I can generate the exact Vercel & Render dashboard steps with screenshots, or prepare a short shell script that runs `mysql` import once you provide the managed DB host and credentials.
