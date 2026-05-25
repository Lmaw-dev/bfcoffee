Render deployment notes

Overview
- This directory contains a simple `Dockerfile` and `render.yaml` to deploy the PHP backend to Render (as a Docker web service).

Database
- Render provides managed PostgreSQL; for MySQL you can use an external managed MySQL provider (DigitalOcean Managed Databases) or a managed MySQL add-on and then set `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` in the Render service environment variables.

Steps
1. Push your repo to GitHub (if not already).
2. On Render, create a new service and connect the repo. Use the repo root and the `render.yaml` manifest (Render will detect it).
3. In the Render dashboard, set these environment variables for the `bfc-backend` service:
   - `DB_HOST` — host of your MySQL instance
   - `DB_USER` — `jireh`
   - `DB_PASS` — `faith`
   - `DB_NAME` — `web_system`
   - Optionally `VIRTUAL_HOST` or configure a Render custom domain
4. Deploy. After the container is up, ensure the API endpoints work (e.g. `/store-api.php?action=get_products`).
5. For images, ensure the `images/` folder is present in the repo root so the container serves them at `https://<your-backend>/images/...`.

Frontend
- Deploy the `react-app` to Vercel and set the `VITE_PHP_API_BASE` env var to your Render public URL (e.g. `https://bfc-backend.onrender.com`).
