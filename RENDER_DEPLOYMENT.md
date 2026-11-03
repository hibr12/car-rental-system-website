# Deploy Apex Car Rentals on Render and Vercel

This repository deploys the Laravel API and PostgreSQL database on Render and
the React frontend on Vercel. Browser requests use relative `/api` and
`/sanctum` paths; Vercel proxies them to Render so authentication cookies remain
first-party in the browser.

## Deploy the backend

1. Push the repository's `main` branch to GitHub.
2. Sign in to [Render](https://dashboard.render.com/) and choose **New > Blueprint**.
3. Connect `hibr12/car-rental-system-website` and select the `main` branch.
4. Render detects `render.yaml`. Enter `INITIAL_ADMIN_EMAIL` and an
   `INITIAL_ADMIN_PASSWORD` containing at least 12 characters. Also enter the
   production Vercel URL for `FRONTEND_URL` and its hostname without `https://`
   for `SANCTUM_STATEFUL_DOMAINS`. Render stores these as private environment
   values.
5. Review the `car-rental-system-website` web service and `car-rental-db` database,
   then apply the Blueprint.
6. Wait for the web service health check at `/up` to pass. Open the URL Render
   assigns to `car-rental-system-website`.

The first start runs all database migrations. Render generates a persistent key
seed automatically, so sessions remain valid after restarts.

After the first successful login, you can remove `INITIAL_ADMIN_EMAIL` and
`INITIAL_ADMIN_PASSWORD` from the Render environment. The administrator remains
in PostgreSQL, and later restarts do not reset its password.

## Deploy the frontend

1. In Vercel, import `hibr12/car-rental-system-website`.
2. Set **Root Directory** to `frontend`.
3. Use Node.js 22, build command `npm run build`, and output directory `dist`.
4. `frontend/vercel.json` proxies both API paths to
   `https://car-rental-system-website.onrender.com`; deploy the `main` branch.
5. Remove any stale `VITE_API_URL` or `VITE_API_BASE_URL` project variables.

After Vercel assigns the production URL, make sure Render's `FRONTEND_URL` and
`SANCTUM_STATEFUL_DOMAINS` values match it exactly as described above. Redeploy
the Render service after changing those values.

## Optional production integrations

The site starts without these values, but add them in the web service's
**Environment** page before enabling the corresponding features.

### Durable document uploads

Render's local filesystem is temporary. Add all three Cloudinary values so
driver-license uploads survive restarts:

- `CLOUDINARY_CLOUD_NAME`
- `CLOUDINARY_KEY`
- `CLOUDINARY_SECRET`

### Chapa payments

The Blueprint starts in test mode. Add a Chapa test key first:

- `CHAPA_MODE=test`
- `CHAPA_SECRET_KEY=CHASECK_TEST-...`

For live payments, change `CHAPA_MODE` to `live`, use a live key, and set:

- `CHAPA_CALLBACK_URL=https://YOUR_RENDER_HOST/api/payments/callback`
- `CHAPA_RETURN_URL=https://YOUR_RENDER_HOST/payments/status`
- `CHAPA_WEBHOOK_URL=https://YOUR_RENDER_HOST/api/payments/chapa/webhook`
- `CHAPA_WEBHOOK_SECRET`

### Password-reset email

The default mailer writes messages to the Render log. Configure an SMTP provider
and set `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
`MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME` to send real email.

## Plans

The Blueprint requests Render's free plans. If your Render account does not offer
a free PostgreSQL plan, choose the smallest currently available database plan in
the Blueprint review screen. Free services can sleep and are intended for demos;
use paid plans for reliable production traffic and database retention.
