# Deploying to shared hosting with Jenkins (WSL)

This repo ships a `Jenkinsfile` that builds the app on your Jenkins agent (WSL)
and deploys it to shared hosting over passwordless SSH (you've already run
`ssh-copy-id`). Follow the steps below **once**; after that every build is one
click (or fully automatic via a webhook).

> ⚠️ **PHP version:** the locked dependencies (Laravel 13 / Symfony 8) require
> **PHP ≥ 8.4.1**. Your shared host must offer a PHP 8.4.1+ binary, and you must
> point the `PHP_BIN` parameter at it. If the host maxes out at 8.2/8.3, the app
> won't run there regardless of CI — check this first.

---

## 1. Prepare the Jenkins agent (WSL) — one time

Install the build tools the pipeline needs, inside WSL:

```bash
sudo apt update
sudo apt install -y php-cli php-mbstring php-xml php-curl php-zip unzip rsync openssh-client
# Composer
php -r "copy('https://getcomposer.org/installer','composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
# Node (use your preferred method; nvm or nodesource)
node -v && npm -v
```

Install Jenkins plugins (Manage Jenkins → Plugins → Available):

- **Git** and **Pipeline** (usually already present)
- **SSH Agent** ← required for the deploy step

> **Which user runs Jenkins?** If Jenkins runs as a systemd service it runs as
> the `jenkins` user, which does **not** have the SSH key you `ssh-copy-id`'d
> under your own account. That's exactly why step 3 loads the key into Jenkins
> credentials instead of relying on the ambient `~/.ssh` key.

---

## 2. Prepare the server (shared hosting) — one time

SSH into the host and lay out the app **above** the web root, then point the web
root at its `public/` folder.

```bash
# 2a. App root (kept out of the public web root)
mkdir -p ~/laravel_app
cd ~/laravel_app

# 2b. Create the production .env (NEVER deployed/overwritten by CI — it's excluded)
cat > .env <<'ENV'
APP_NAME="Land Administration"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_pass

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stack
ENV

# 2c. Runtime dirs (excluded from rsync, so create them once)
mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Point the public web root at Laravel's `public/`. Pick **one**:

- **Best — change the domain's Document Root** (cPanel → Domains) to
  `/home/USER/laravel_app/public`.
- **Or symlink** the existing `public_html`:
  ```bash
  rm -rf ~/public_html && ln -s ~/laravel_app/public ~/public_html
  ```

> The very first deploy (step 6) ships `vendor/` and the built assets, then runs
> migrations and caching. Before that first run you can generate the app key
> remotely once the code is present, or add a one-off `APP_KEY` now:
> `php8.4 artisan key:generate` (run from `~/laravel_app` after the first deploy,
> or temporarily here if Composer is available on the host).

---

## 3. Add the SSH key to Jenkins — one time

Manage Jenkins → **Credentials** → (System) → Global → **Add Credentials**:

| Field        | Value                                                            |
|--------------|-----------------------------------------------------------------|
| Kind         | **SSH Username with private key**                               |
| ID           | `shared-hosting-ssh`  ← must match the `SSH_CRED_ID` parameter   |
| Username     | your cPanel/SSH username                                         |
| Private Key  | **Enter directly** → paste the key you used for `ssh-copy-id` (e.g. `cat ~/.ssh/id_ed25519`) |

(If the private key has a passphrase, set it in the same dialog.)

---

## 4. (If the GitHub repo is private) add a GitHub credential

Add Credentials → **Username with password** (or a Personal Access Token as the
password). You'll select it in the job's SCM config in the next step. Skip if the
repo is public.

---

## 5. Create the Pipeline job

1. Jenkins dashboard → **New Item** → name it `land-administration-deploy` →
   **Pipeline** → OK.
2. Scroll to **Pipeline** section:
   - **Definition:** `Pipeline script from SCM`
   - **SCM:** `Git`
   - **Repository URL:** `https://github.com/iksanh/land-administration-system.git`
   - **Credentials:** the GitHub one from step 4 (leave as *none* if public)
   - **Branch Specifier:** `*/main`
   - **Script Path:** `Jenkinsfile`
3. **Save.**

---

## 6. First deploy — Build with Parameters

Open the job → **Build with Parameters**, fill in:

| Parameter          | Example                              |
|--------------------|--------------------------------------|
| `DEPLOY_HOST`      | `server123.webhost.com`              |
| `DEPLOY_USER`      | `youruser`                           |
| `DEPLOY_PATH`      | `/home/youruser/laravel_app`         |
| `PHP_BIN`          | `/usr/local/bin/ea-php84` (or `php8.4`) |
| `SSH_CRED_ID`      | `shared-hosting-ssh`                 |
| `RUN_MIGRATIONS`   | ✅ (uncheck for the very first run if you prefer to migrate manually) |
| `MAINTENANCE_MODE` | ✅                                    |

Click **Build**. Watch **Console Output**. The stages run:
**Checkout → Verify tooling → Composer → Assets → rsync → Release (migrate+cache)**.

Jenkins remembers these values, so later builds are just **Build** → Build.

---

## 7. Make it CI/CD (auto-deploy on push) — optional

**Option A — GitHub webhook (instant):** needs Jenkins reachable from the
internet. In the job → **Configure** → Build Triggers → check
*GitHub hook trigger for GITScm polling*. In GitHub → repo → Settings →
Webhooks → Add: `http://YOUR_JENKINS_URL/github-webhook/`, content type
`application/json`, event = *push*.

**Option B — Poll SCM (no inbound access needed):** Build Triggers → check
*Poll SCM* → schedule `H/5 * * * *` (every ~5 min). Jenkins checks `main` and
builds when it changes.

---

## How the pipeline protects your data

- **`.env` is never shipped** — the rsync `--exclude='/.env'` leaves the server's
  production `.env` untouched.
- **`storage/` and `public/storage` are excluded** — uploaded files, logs,
  sessions and the symlink survive every deploy.
- **`--delete`** keeps the rest of the tree exactly matching the build (no stale
  files), but only outside those excluded paths.
- **Maintenance window:** the site goes `artisan down` during migrate+cache and
  `artisan up` after; a failed build auto-runs `artisan up` so you're never left
  stuck in maintenance mode.

## Troubleshooting

| Symptom | Fix |
|---|---|
| `Host key verification failed` | First connect manually once: `ssh USER@HOST` to trust the host, or it's auto-trusted via `accept-new`. |
| `composer: command not found` (agent) | Re-do step 1 in the same WSL distro Jenkins runs in. |
| `php artisan ... PHP Parse error` / function errors on server | Server PHP < 8.4.1. Point `PHP_BIN` at a 8.4.1+ binary or upgrade the host PHP. |
| 500 after deploy, blank page | Check `storage/logs/laravel.log`; ensure `storage`/`bootstrap/cache` are `775` and `.env` `APP_KEY` is set. |
| Assets 404 | Confirm the web root points at `…/laravel_app/public` and `public/build` was shipped. |
