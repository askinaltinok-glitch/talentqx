# TalentQX API - Claude Code Notes

## Multi-tenant Architecture

The User model uses `company_id` (NOT `tenant_id`) to identify the tenant.

**IMPORTANT**: In all OrgHealth controllers (`app/Http/Controllers/V1/OrgHealth/`), the tenant is resolved via:
```php
$tenantId = $request->user()->company_id;
```
Do NOT change this to `$request->user()->tenant_id` — the User model does not have a `tenant_id` column.

## Database Connections

- `BrandDatabaseMiddleware` switches the default DB connection based on `X-Brand-Key` header
- Default brand `talentqx` -> `mysql_talentqx` connection -> `talentqx_hr` database
- Brand `octopus` -> `mysql` connection -> `talentqx` database
- OrgHealth tables live in `talentqx_hr` (run migrations with `--database=mysql_talentqx`)

## PHP Version

Use `/www/server/php/82/bin/php` for artisan commands (default php is 7.2).
