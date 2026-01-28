# TalentQX Frontend Deployment Guide

## SPA Routing Configuration

TalentQX is a Single Page Application (SPA) using client-side routing. For proper routing to work in production, your web server must be configured to serve `index.html` for all routes that don't match static files.

## Nginx Configuration

```nginx
server {
    listen 80;
    server_name talentqx.example.com;
    root /var/www/talentqx/frontend/dist;
    index index.html;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript;
    gzip_min_length 1000;

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # API proxy (adjust backend URL as needed)
    location /api/ {
        proxy_pass http://localhost:8000/api/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # SPA fallback - serve index.html for all non-file routes
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

### Nginx with HTTPS (Recommended for Production)

```nginx
server {
    listen 80;
    server_name talentqx.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name talentqx.example.com;
    root /var/www/talentqx/frontend/dist;
    index index.html;

    # SSL configuration
    ssl_certificate /etc/letsencrypt/live/talentqx.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/talentqx.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript;
    gzip_min_length 1000;

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # API proxy
    location /api/ {
        proxy_pass http://localhost:8000/api/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # SPA fallback
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}
```

## Apache Configuration

### Using .htaccess (mod_rewrite required)

Create or update `.htaccess` in the `dist` folder:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # If the request is for an existing file, serve it directly
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # Otherwise, serve index.html (SPA fallback)
    RewriteRule ^ index.html [L]
</IfModule>

# Gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css application/json application/javascript text/xml application/xml text/javascript
</IfModule>

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

### Apache Virtual Host Configuration

```apache
<VirtualHost *:80>
    ServerName talentqx.example.com
    DocumentRoot /var/www/talentqx/frontend/dist

    <Directory /var/www/talentqx/frontend/dist>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # SPA fallback without .htaccess
        FallbackResource /index.html
    </Directory>

    # API proxy
    ProxyPreserveHost On
    ProxyPass /api/ http://localhost:8000/api/
    ProxyPassReverse /api/ http://localhost:8000/api/

    # Gzip compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/css application/json application/javascript text/xml application/xml text/javascript
    </IfModule>

    # Security headers
    <IfModule mod_headers.c>
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>
</VirtualHost>
```

## XAMPP (Local Development)

For XAMPP on Windows, add this to your Apache `httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName talentqx.local
    DocumentRoot "C:/xampp/htdocs/ikkaynak/talentqx/frontend/dist"

    <Directory "C:/xampp/htdocs/ikkaynak/talentqx/frontend/dist">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        FallbackResource /index.html
    </Directory>

    # API proxy to Laravel backend
    ProxyPreserveHost On
    ProxyPass /api/ http://localhost:8000/api/
    ProxyPassReverse /api/ http://localhost:8000/api/
</VirtualHost>
```

Then add to your `hosts` file (`C:\Windows\System32\drivers\etc\hosts`):
```
127.0.0.1 talentqx.local
```

## Build Commands

```bash
# Development build
npm run dev

# Production build
npm run build

# Preview production build locally
npm run preview
```

## Environment Variables

Create `.env.production` for production builds:

```env
VITE_API_BASE_URL=https://api.talentqx.example.com/api/v1
```

## Route Structure

The application uses the following route structure:

| Route | Description |
|-------|-------------|
| `/` | Landing page (public) |
| `/login` | Login page (public) |
| `/app` | Dashboard (protected) |
| `/app/jobs` | Job listings |
| `/app/jobs/:id` | Job detail |
| `/app/candidates` | Candidates list |
| `/app/candidates/:id` | Candidate detail |
| `/app/interviews/:id` | Interview detail |
| `/app/employees` | Employees list |
| `/app/employees/:id` | Employee detail |
| `/app/assessments` | Assessment results |

## Troubleshooting

### Routes return 404 on refresh
- Ensure SPA fallback is configured (serve `index.html` for non-file routes)
- Check that `mod_rewrite` is enabled (Apache)
- Verify `try_files` directive is present (Nginx)

### API calls fail
- Check CORS configuration on backend
- Verify API proxy is correctly configured
- Ensure `VITE_API_BASE_URL` is set correctly

### Assets not loading
- Verify build output is in the correct directory
- Check that static file paths are correct
- Ensure proper permissions on the dist folder
