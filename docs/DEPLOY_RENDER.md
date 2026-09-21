# Deploying Portfoedit on Render

The production deployment is one Docker web service. The Docker build compiles
the Vue SPA and copies it into Laravel's `public` directory, keeping the browser
and API on the same origin for Sanctum cookie authentication.

## 1. External services

Create these before the first working deploy:

1. A Neon PostgreSQL project. Copy its pooled connection string.
2. A Cloudflare R2 bucket with public access (or a custom public domain).
3. R2 API credentials with Object Read & Write access to that bucket.

## 2. Render web service

In Render choose **Web Services**, connect the GitHub repository, then use:

- Language: `Docker`
- Branch: `main`
- Dockerfile path: `./Dockerfile`
- Instance type: `Free`
- Health check path: `/up`

No separate static site or Render Postgres service is needed.

## 3. Environment variables

Set the following in the Render service. Replace `your-service` with the exact
Render hostname assigned to the service.

```dotenv
APP_NAME=Portfoedit
APP_ENV=production
APP_DEBUG=false
APP_KEY=<php artisan key:generate --show>
APP_URL=https://your-service.onrender.com

FRONTEND_URL=https://your-service.onrender.com
SANCTUM_STATEFUL_DOMAINS=your-service.onrender.com
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

DB_CONNECTION=pgsql
DB_URL=<Neon pooled connection string>
DB_SSLMODE=require

CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=warning

FILESYSTEM_DISK=s3
PORTFOLIO_UPLOAD_DISK=s3
PORTFOLIO_EXPORT_DISK=s3
PORTFOLIO_EXPORT_QUEUE=false

AWS_ACCESS_KEY_ID=<R2 access key>
AWS_SECRET_ACCESS_KEY=<R2 secret key>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=<R2 bucket name>
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_URL=https://<public bucket domain>
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Generate `APP_KEY` locally from the backend directory:

```bash
php artisan key:generate --show
```

Do not commit that key or any database/R2 credentials.

## 4. Deploy

Start the deploy after all environment variables are saved. At container start,
the entrypoint caches Laravel configuration, creates the storage link, and runs
`php artisan migrate --force`. Future pushes to `main` trigger a new deployment.

The free Render filesystem is ephemeral. Keeping both
`PORTFOLIO_UPLOAD_DISK=s3` and `PORTFOLIO_EXPORT_DISK=s3` is required for images
and exported ZIP files to survive restarts and idle spin-downs.

## 5. Troubleshooting

### Every request returns 500 with "attempt to write a readonly database"

The database environment variables are missing, so Laravel fell back to its
default `sqlite` connection. Migrations then run as `root` at container start
and create `database/database.sqlite`, but Apache serves as `www-data` and
cannot write to it, so the first request that touches the session fails.

Set `DB_CONNECTION=pgsql`, `DB_URL` and `DB_SSLMODE=require` in the service
environment and redeploy. `DB_URL` on its own is not enough: it only supplies
credentials to whichever connection is already selected, so without
`DB_CONNECTION` the default still wins.

Since this misconfiguration is easy to make and hard to read off a stack trace,
the entrypoint now refuses to boot when `APP_ENV=production` and
`DB_CONNECTION` is anything other than `pgsql`. A deploy that stops with

```
DB_CONNECTION must be 'pgsql' in production (current: 'unset').
```

is reporting this same problem before it can reach a browser.

### Register and login return 500, but `GET /api/user` returns 401

The host the browser used is not listed in `sanctum.stateful`, so Sanctum does
not treat the request as coming from the SPA and never starts a session. Read
endpoints still answer normally — only the routes that rotate the session id
fail, which is why the two symptoms look unrelated.

Set `SANCTUM_STATEFUL_DOMAINS` to the exact hostname, with no scheme, no port
and no trailing slash:

```dotenv
APP_URL=https://portfoeditor.onrender.com
FRONTEND_URL=https://portfoeditor.onrender.com
SANCTUM_STATEFUL_DOMAINS=portfoeditor.onrender.com
SESSION_SECURE_COOKIE=true
```

Leave `SESSION_DOMAIN` unset on an `onrender.com` hostname: pinning the cookie
to a domain the browser considers public suffix-adjacent stops it being stored.

The controller now logs the mismatch before failing, so the deploy log names the
origin it received and the domains it was willing to accept.
