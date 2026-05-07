#!/bin/bash
echo "🚀 Iniciando deploy de Hoose..."

SFTP_USER="u105933725"
SFTP_HOST="access885200975.webspace-data.io"
SSH_KEY="$HOME/.ssh/hoose_ionos"

# 1. Build Angular
echo "📦 Compilando Angular..."
cd ~/Desktop/hoose-1/development
export NODE_OPTIONS=--openssl-legacy-provider
npm run build -- --configuration=production

if [ $? -ne 0 ]; then
  echo "❌ Error en la compilación"
  exit 1
fi

# 2. Copiar .htaccess al dist
cat > ~/Desktop/hoose-1/development/dist/.htaccess << 'HTACCESS'
RewriteEngine On
RewriteBase /
RewriteRule ^index\.html$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
HTACCESS

# 3. Subir frontend
echo "⬆️  Subiendo frontend..."
lftp -e "set sftp:auto-confirm yes; mirror -R --delete ~/Desktop/hoose-1/development/dist /httpdocs; bye" \
  -u $SFTP_USER sftp://$SFTP_HOST --sftp-connect-program "ssh -i $SSH_KEY -p 22"

# 4. Subir API
echo "⬆️  Subiendo API..."
lftp -e "set sftp:auto-confirm yes; mirror -R --delete /Applications/XAMPP/xamppfiles/htdocs/api /httpdocs/api; bye" \
  -u $SFTP_USER sftp://$SFTP_HOST --sftp-connect-program "ssh -i $SSH_KEY -p 22"

# 5. Git commit y push
echo "💾 Guardando en GitHub..."
cd ~/Desktop/hoose-1
git add .
git commit -m "Deploy: $(date '+%Y-%m-%d %H:%M')"
git push

echo ""
echo "✅ Deploy completado!"
echo "🌐 http://residenciales.hoose.mx"
