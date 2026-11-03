# Deploy Apex Car Rentals on Render

This repository includes a Render Blueprint that deploys the React frontend,
Laravel API, and PostgreSQL database together. The frontend and API use one
domain, so Laravel Sanctum authentication works without cross-site cookies.

## Deploy

1. Push the repository's `main` branch to GitHub.
2. Sign in to [Render](https://dashboard.render.com/) and choose **New > Blueprint**.
3. Connect `hibr12/car-rental-system-website` and select the `main` branch.
4. Render detects `render.yaml`. Enter `INITIAL_ADMIN_EMAIL` and an
   `INITIAL_ADMIN_PASSWORD` containing at least 12 characters. Render stores
   these as private environment values.
5. Review the `apex-car-rentals` web service and `car-rental-db` database, then
   apply the Blueprint.
6. Wait for the web service health check at `/up` to pass. Open the URL Render
   assigns to `apex-car-rentals`.

The first start runs all database migrations. Render generates a persistent key
seed automatically, so sessions remain valid after restarts.

After the first successful login, you can remove `INITIAL_ADMIN_EMAIL` and
`INITIAL_ADMIN_PASSWORD` from the Render environment. The administrator remains
in PostgreSQL, and later restarts do not reset its password.

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
