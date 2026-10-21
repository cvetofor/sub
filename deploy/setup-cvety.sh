#!/usr/bin/env bash
# Развёртывание нового сайта на соседнем поддомене рядом со старым. Идемпотентно: повторный запуск безопасен.
# Запускается на VPS из GitHub Actions (deploy-cvety.yml) или вручную:
#   OLD_PATH=/var/www/sub NEW_PATH=/var/www/cvety DOMAIN=xn--b1ag3bn2a.xn--b1ag1aakjpl.xn--p1ai SITE_HOST=цветы.цветофор.рф bash deploy/setup-cvety.sh
set -euo pipefail

OLD_PATH="${OLD_PATH:-}"
NEW_PATH="${NEW_PATH:-/var/www/cvety}"
echo "скрипт развёртывания запущен на $(hostname) от $(whoami)"
if [ -z "$OLD_PATH" ]; then
  # Ищем старый сайт: папка Laravel с нашим платёжным модулем и .env, не считая новой папки
  for a in $(find /var/www /home /srv /opt /root -maxdepth 4 -name artisan -type f 2>/dev/null || true); do
    d="$(dirname "$a")"
    if [ "$d" != "$NEW_PATH" ] && [ -f "$d/.env" ] && [ -f "$d/app/Http/Api/Yookassa.php" ]; then OLD_PATH="$d"; break; fi
  done
  [ -n "$OLD_PATH" ] || { echo "Не нашёл старый сайт автоматически. Папки с artisan:"; find /var/www /home /srv /opt /root -maxdepth 4 -name artisan 2>/dev/null || true; echo "Укажите OLD_PATH при запуске"; exit 1; }
  echo "старый сайт найден: $OLD_PATH"
fi
DOMAIN="${DOMAIN:?punycode-домен нового сайта}"
SITE_HOST="${SITE_HOST:-$DOMAIN}"
NEW_DB="${NEW_DB:-cvety_sub}"
EXTRA_ENV="${EXTRA_ENV:-}"          # дополнительные строки .env (YM_ID, TELEGRAM_*), через перевод строки
PHP="${PHP:-php}"
SUDO=""; if [ "$(id -u)" != "0" ]; then if sudo -n true 2>/dev/null; then SUDO="sudo -n"; fi; fi

log() { printf '\n== %s\n' "$*"; }
val() { { grep -E "^$1=" "$OLD_PATH/.env" 2>/dev/null || true; } | head -1 | cut -d= -f2- | tr -d '"'"'" ; }

log "Проверки"
[ -f "$OLD_PATH/.env" ] || { echo "Нет $OLD_PATH/.env: укажите OLD_PATH правильно"; exit 1; }
[ -d "$NEW_PATH" ] || { echo "Нет $NEW_PATH: код должен быть скопирован до запуска скрипта"; exit 1; }
command -v composer >/dev/null || { echo "Нет composer"; exit 1; }
OLD_DB="$(val DB_DATABASE)"; DB_USER="$(val DB_USERNAME)"; DB_PASS="$(val DB_PASSWORD)"; DB_HOST="$(val DB_HOST)"; DB_PORT="$(val DB_PORT)"
[ -n "$OLD_DB" ] || { echo "В старом .env нет DB_DATABASE"; exit 1; }
echo "старый сайт: $OLD_PATH, база $OLD_DB; новый: $NEW_PATH, база $NEW_DB, домен $DOMAIN"

log "Зависимости PHP"
cd "$NEW_PATH"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

if [ ! -f "$NEW_PATH/public/build/manifest.json" ]; then
  log "Фронтенд не собран, пробую собрать на сервере"
  if command -v npm >/dev/null; then npm ci --no-audit --no-fund && npm run build; else echo "Нет npm и нет public/build: положите артефакт public-build из CI"; exit 1; fi
fi

log ".env"
if [ ! -f .env ]; then
  cp .env.example .env
  # Доступы к базе и платёжным сервисам берём из старого сайта: они те же
  for k in DB_CONNECTION DB_HOST DB_PORT DB_USERNAME DB_PASSWORD YOOKASSA_SHOP_ID YOOKASSA_SECRET_KEY SMSC_LOGIN SMSC_PASSWORD; do
    v="$(val "$k")"; [ -n "$v" ] && sed -i "s|^$k=.*|$k=$v|" .env || true
  done
  sed -i "s|^APP_ENV=.*|APP_ENV=production|; s|^APP_DEBUG=.*|APP_DEBUG=false|; s|^APP_URL=.*|APP_URL=https://$DOMAIN|; s|^SITE_HOST=.*|SITE_HOST=$SITE_HOST|; s|^DB_DATABASE=.*|DB_DATABASE=$NEW_DB|; s|^APP_NAME=.*|APP_NAME=Цветофор|; s|^APP_LOCALE=.*|APP_LOCALE=ru|" .env
  OLD_URL="$(val APP_URL)"; [ -n "$OLD_URL" ] && sed -i "s|^YOOKASSA_LEGACY_CALLBACK=.*|YOOKASSA_LEGACY_CALLBACK=${OLD_URL%/}/api/payment/yookassa/callback|" .env
  [ -n "$EXTRA_ENV" ] && printf '\n%s\n' "$EXTRA_ENV" >> .env
  $PHP artisan key:generate --force
  echo ".env создан"
else
  echo ".env уже есть, не трогаю"
fi

log "База данных"
export PGPASSWORD="$DB_PASS"
PSQL="psql -h ${DB_HOST:-127.0.0.1} -p ${DB_PORT:-5432} -U $DB_USER"
if $PSQL -d postgres -tAc "select 1 from pg_database where datname='$NEW_DB'" | grep -q 1; then
  echo "база $NEW_DB уже есть, копию не делаю"
else
  echo "создаю $NEW_DB как копию $OLD_DB"
  $PSQL -d postgres -c "create database \"$NEW_DB\" owner \"$DB_USER\""
  pg_dump -h "${DB_HOST:-127.0.0.1}" -p "${DB_PORT:-5432}" -U "$DB_USER" "$OLD_DB" | $PSQL -d "$NEW_DB" -q
fi

log "Миграции"
$PHP artisan migrate --force

log "Сдвиг счётчиков, чтобы номера не пересекались со старым сайтом"
for t in subscriptions payments users plans deliveries; do
  cur=$($PSQL -d "$NEW_DB" -tAc "select coalesce(max(id),0) from $t" 2>/dev/null || echo 0)
  cur="${cur:-0}"
  if [ "${cur:-0}" -lt 100000 ]; then $PSQL -d "$NEW_DB" -qc "alter sequence ${t}_id_seq restart with 100000"; echo "$t: со 100000"; fi
done

log "Расписание доставок для уже оплаченных подписок"
$PHP artisan deliveries:backfill

log "Права и кэши"
$PHP artisan storage:link >/dev/null 2>&1 || true
chmod -R ug+rwX storage bootstrap/cache
$PHP artisan config:cache && $PHP artisan route:cache && $PHP artisan view:cache && $PHP artisan filament:optimize >/dev/null

log "nginx и сертификат"
if [ -n "$SUDO" ] || [ "$(id -u)" = "0" ]; then
  CONF=/etc/nginx/sites-available/cvety
  if [ ! -f "$CONF" ]; then
    FPM=$( { ls /run/php/php*-fpm.sock 2>/dev/null || true; } | head -1)
    sed "s|xn--b1ag3bn2a.xn--b1ag1aakjpl.xn--p1ai|$DOMAIN|; s|/var/www/cvety/public|$NEW_PATH/public|; s|unix:/run/php/php8.3-fpm.sock|unix:${FPM:-/run/php/php8.3-fpm.sock}|" docs/nginx-cvety.conf | $SUDO tee "$CONF" >/dev/null
    $SUDO ln -sf "$CONF" /etc/nginx/sites-enabled/cvety
    $SUDO nginx -t && $SUDO systemctl reload nginx
    echo "nginx настроен"
  else
    echo "nginx-конфигурация уже есть"
  fi
  if command -v certbot >/dev/null; then
    for i in $(seq 1 20); do
      if { getent hosts "$DOMAIN" || true; } | grep -q .; then echo "DNS отвечает: $(getent hosts "$DOMAIN" | awk '{print $1}' | head -1)"; break; fi
      [ "$i" = 20 ] && echo "DNS для $DOMAIN ещё не виден, сертификат может не выпуститься с первого раза"; sleep 15
    done
    if ! $SUDO certbot certificates 2>/dev/null | grep -q "$DOMAIN"; then
      $SUDO certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --register-unsafely-without-email --redirect || echo "certbot не смог выпустить сертификат: проверьте, что DNS уже указывает на этот сервер"
    else echo "сертификат уже есть"; fi
  else
    echo "certbot не установлен, ставлю"; $SUDO apt-get update -qq && $SUDO apt-get install -y -qq certbot python3-certbot-nginx >/dev/null && $SUDO certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --register-unsafely-without-email --redirect || echo "сертификат выпустить не удалось, повторите позже: certbot --nginx -d $DOMAIN"
  fi
else
  echo "Нет sudo: nginx и сертификат нужно настроить вручную по docs/nginx-cvety.conf"
fi

log "Cron"
LINE="* * * * * cd $NEW_PATH && $PHP artisan schedule:run >> /dev/null 2>&1"
TMP="$(mktemp)"
{ crontab -l 2>/dev/null || true; } | { grep -vF "$NEW_PATH && $PHP artisan schedule:run" || true; } > "$TMP"
echo "$LINE" >> "$TMP"
crontab "$TMP" && rm -f "$TMP"
echo "cron: $LINE"

log "Готово"
echo "Проверка: curl -sI https://$DOMAIN/ | head -1"
echo "Не забыть: адрес уведомлений в кабинете ЮKassa → https://$DOMAIN/api/payment/yookassa/callback"
