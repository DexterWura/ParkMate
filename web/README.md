# OpenLot web

Laravel admin console and `/api/v1` mobile API for OpenLot.

Based on [DexterWura/shipit](https://github.com/DexterWura/shipit).

## Quick start

```bash
composer install
php artisan serve
```

First visit runs the installer (creates `.env`, migrates, seeds sample Harare spots / fee rules / settings via `OpenLotSeeder`).

Admin: `/admin`  
API: `/api/v1`

## Production

Document root must be `public/`. See the root [README](../README.md) for deploying to `openlot.companyop.com`.
