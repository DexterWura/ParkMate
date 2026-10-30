# OpenLot

Find parking on OpenStreetMap: free vs taken, estimated fees, and traffic on the way.

Production host: [openlot.companyop.com](https://openlot.companyop.com)

| Folder | What |
| --- | --- |
| [`web/`](web/) | PHP admin + mobile API (Laravel / Shipit) |
| [`mobile/`](mobile/) | Kotlin Android app (Compose + MapLibre) |

The Android app is a **client of the web API** only. It never talks to Mapbox, Overpass, or Nominatim directly.

## Deploy to openlot.companyop.com

1. Point the domain document root at `web/public` (not the repo root).
2. On the server:

```bash
cd web
composer install --no-dev --optimize-autoloader
cp .env.example .env   # if needed
# Visit https://openlot.companyop.com/ and run the installer
```

3. Installer writes `.env` with `APP_URL`, `APP_DEBUG=false`, `FORCE_HTTPS=true` (when URL is https), and `QUEUE_CONNECTION=sync`.
4. After install, sign in at `/admin` and set API keys under **API settings** (Mapbox recommended for traffic routes).
5. Optional: `php artisan config:cache` and `php artisan route:cache`.

OSM ingest and viewport refresh run **after the HTTP response** without a queue worker. If you later switch `QUEUE_CONNECTION` to `database`/`redis`, run a worker.

## Local web

```bash
cd web
composer install
php artisan serve
```

Open `/` → installer (first run) or `/admin`.

- Seed admin (only if no admin exists): `admin@openlot.companyop.com` / `password`
- Mobile API: `http://localhost:8000/api/v1/...`

### Useful API routes

- `GET /api/v1/health`
- `GET /api/v1/config`
- `GET /api/v1/providers`
- `GET /api/v1/spots?swLat&swLng&neLat&neLng&status=`
- `GET /api/v1/spots/nearby?lat&lng&radiusMeters=`
- `GET /api/v1/spots/{id}`
- `POST /api/v1/spots/{id}/reports`
- `POST /api/v1/spots`
- `GET /api/v1/search?q=`
- `POST /api/v1/routes`

## Mobile (Android)

```bash
cd mobile
# Debug → http://10.0.2.2:8000/api/v1
# Release → https://openlot.companyop.com/api/v1
./gradlew.bat :android:installDebug
```

Package: `com.openlot.android`

## Zimbabwe notes

TomTom parking availability is weak in Zimbabwe. OpenLot uses:

- OpenStreetMap / Overpass for parking places
- Driver reports for occupancy
- City Parking fee rules (USD 1/hour seed)
- Mapbox / TomTom / OpenRouteService for routes (configure in admin)

## Attribution

Map data © OpenStreetMap contributors (ODbL).
