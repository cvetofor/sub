#!/usr/bin/env bash
# Подготовка через API Beget перед заходом на сервер (выполняется в GitHub Actions):
#  1) JWT облачного API → новый SSH-ключ на аккаунте → ключ прикрепляется к VPS с нужным IP;
#  2) API хостинга → поддомен и A-запись на IP сервера.
# Переменные: BEGET_LOGIN, BEGET_PASSWORD (пароль аккаунта для JWT), BEGET_API_PASSWORD (пароль API для DNS),
#             VPS_IP, DOMAIN_IDN (кириллицей, например цветы.цветофор.рф), KEY_PATH (куда положить приватный ключ)
set -euo pipefail

: "${BEGET_LOGIN:?}"; : "${VPS_IP:?}"; : "${DOMAIN_IDN:?}"; KEY_PATH="${KEY_PATH:-$HOME/.ssh/id_deploy}"
BEGET_PASSWORD="${BEGET_PASSWORD:-${BEGET_API_PASSWORD:-}}"; BEGET_API_PASSWORD="${BEGET_API_PASSWORD:-$BEGET_PASSWORD}"
[ -n "$BEGET_PASSWORD" ] || { echo "Нужен BEGET_PASSWORD или BEGET_API_PASSWORD"; exit 1; }
KEY_NAME="github-actions-cvety"
log() { printf '\n== %s\n' "$*"; }

log "JWT облачного API"
AUTH=$(curl -sS -X POST https://api.beget.com/v1/auth -H 'Content-Type: application/json' \
  -d "$(jq -cn --arg l "$BEGET_LOGIN" --arg p "$BEGET_PASSWORD" '{login:$l,password:$p,saveMe:false}')")
TOKEN=$(echo "$AUTH" | jq -r '.token // empty')
if [ -z "$TOKEN" ]; then
  echo "Не удалось получить токен: $(echo "$AUTH" | jq -r '.error // .message // .' 2>/dev/null)"
  echo "Если ошибка CODE_REQUIRED_*: на аккаунте включена двухфакторная аутентификация, API так не войдёт. Используйте SSH_PASSWORD."
  exit 1
fi
api() { curl -sS -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' "$@"; }

log "Сервер с IP $VPS_IP"
VPS_ID=$(api "https://api.beget.com/v1/vps/server/list?limit=200" | jq -r --arg ip "$VPS_IP" '.vps[] | select(.ip_address==$ip) | .id' | head -1)
[ -n "$VPS_ID" ] || { echo "Сервер с IP $VPS_IP не найден в аккаунте"; api "https://api.beget.com/v1/vps/server/list?limit=200" | jq -r '.vps[] | "\(.display_name) \(.ip_address)"'; exit 1; }
echo "id сервера: $VPS_ID"

log "SSH-ключ $KEY_NAME"
mkdir -p "$(dirname "$KEY_PATH")" && chmod 700 "$(dirname "$KEY_PATH")"
rm -f "$KEY_PATH" "$KEY_PATH.pub"
ssh-keygen -q -t ed25519 -N "" -C "$KEY_NAME" -f "$KEY_PATH"
# Старый ключ с тем же именем убираем: приватная часть от прошлого запуска не сохранялась
for old in $(api "https://api.beget.com/v1/vps/sshKey" | jq -r --arg n "$KEY_NAME" '.keys[]? | select(.name==$n) | .id'); do
  api -X DELETE "https://api.beget.com/v1/vps/sshKey/$old" >/dev/null && echo "удалён прежний ключ $old"
done
ADD=$(api -X POST "https://api.beget.com/v1/vps/sshKey" -d "$(jq -cn --arg n "$KEY_NAME" --arg k "$(cat "$KEY_PATH.pub")" '{name:$n,public_key:$k}')")
KEY_ID=$(echo "$ADD" | jq -r '.key.id // empty')
[ -n "$KEY_ID" ] || { echo "Не удалось добавить ключ: $ADD"; exit 1; }
ATTACH=$(api -X POST "https://api.beget.com/v1/vps/$VPS_ID/sshKey/$KEY_ID")
if echo "$ATTACH" | jq -e '.error' >/dev/null 2>&1; then echo "Не удалось прикрепить ключ к серверу: $(echo "$ATTACH" | jq -c '.error')"; exit 1; fi
echo "ключ $KEY_ID прикреплён к серверу"

log "Проверка входа по SSH (ключ применяется агентом Beget, ждём до 3 минут)"
ssh-keyscan -H "$VPS_IP" >> "$HOME/.ssh/known_hosts" 2>/dev/null || true
for i in $(seq 1 18); do
  if ssh -o BatchMode=yes -o ConnectTimeout=10 -i "$KEY_PATH" "root@$VPS_IP" 'echo ok' 2>/dev/null | grep -q ok; then echo "SSH по ключу работает"; break; fi
  [ "$i" = 18 ] && { echo "SSH по ключу так и не заработал"; exit 1; }
  sleep 10
done

log "DNS: $DOMAIN_IDN → $VPS_IP"
legacy() { # метод, json
  curl -sS -G "https://api.beget.com/api/$1" --data-urlencode "login=$BEGET_LOGIN" --data-urlencode "passwd=$BEGET_API_PASSWORD" \
    --data-urlencode "input_format=json" --data-urlencode "output_format=json" --data-urlencode "input_data=$2"
}
PARENT="${DOMAIN_IDN#*.}"; SUB="${DOMAIN_IDN%%.*}"
DOMAINS=$(legacy domain/getList '{}')
if [ "$(echo "$DOMAINS" | jq -r '.status')" != "success" ]; then
  echo "API хостинга недоступно: $(echo "$DOMAINS" | jq -c '.error_text // .')"; echo "Включите доступ к API и пароль API: https://cp.beget.com/settings/security/api. DNS-запись пока добавьте вручную: A, $SUB → $VPS_IP"
else
  PARENT_ID=$(echo "$DOMAINS" | jq -r --arg f "$PARENT" '.answer.result[] | select(.fqdn==$f) | .id' | head -1)
  [ -n "$PARENT_ID" ] || { echo "Домен $PARENT не найден в аккаунте"; echo "$DOMAINS" | jq -r '.answer.result[].fqdn'; exit 1; }
  if ! legacy domain/getSubdomainList '{}' | jq -e --arg f "$DOMAIN_IDN" '.answer.result[] | select(.fqdn==$f)' >/dev/null; then
    R=$(legacy domain/addSubdomainVirtual "$(jq -cn --arg s "$SUB" --argjson d "$PARENT_ID" '{subdomain:$s,domain_id:$d}')")
    echo "поддомен создан: $(echo "$R" | jq -c '.answer.result // .answer.errors // .error_text')"
  else echo "поддомен уже есть"; fi
  CUR=$(legacy dns/getData "$(jq -cn --arg f "$DOMAIN_IDN" '{fqdn:$f}')")
  if echo "$CUR" | jq -e --arg ip "$VPS_IP" '.answer.result.records.A[]? | select(.value==$ip)' >/dev/null; then
    echo "A-запись уже указывает на $VPS_IP"
  else
    R=$(legacy dns/changeRecords "$(jq -cn --arg f "$DOMAIN_IDN" --arg ip "$VPS_IP" '{fqdn:$f,records:{A:[{priority:10,value:$ip}]}}')")
    echo "A-запись: $(echo "$R" | jq -c '.answer.result // .answer.errors // .error_text')"
  fi
fi

log "Готово: ключ в $KEY_PATH"
