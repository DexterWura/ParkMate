# ParkMate

Find parking on OpenStreetMap: free vs taken, estimated fees, and traffic on the way.

| Folder | What |
| --- | --- |
| [`web/`](web/) | PHP admin + mobile API (Shipit / Laravel) |
| [`mobile/`](mobile/) | Kotlin Android app (Compose + MapLibre) |

The Android app is a **client of the web API** only. It never talks to Mapbox, Overpass, or Nominatim directly.

## Web (admin + API)

Based on [DexterWura/shipit](https://github.com/DexterWura/shipit).

```bash
cd web
composer install
php artisan serve
```

Open `/` → installer (first run) or `/admin`.

After install / seed:

- Admin login: `admin@parkmate.local` / `password` (from `ParkMateSeeder`, or the owner you create in the installer)
- Mobile API: `http://localhost:8000/api/v1/...`
- Admin can manage users, parking spots, occupancy reports, and API keys (Mapbox, TomTom, Overpass, Nominatim)

### Useful API routes

- `GET /api/v1/health`
- `GET /api/v1/spots?swLat&swLng&neLat&neLng`
- `GET /api/v1/spots/{id}`
- `POST /api/v1/spots/{id}/reports` `{ "status": "FREE"|"TAKEN", "deviceId": "..." }`
- `POST /api/v1/spots`
- `GET /api/v1/search?q=`
- `POST /api/v1/routes`

## Mobile (Android)

```bash
cd mobile
# API base URL defaults to http://10.0.2.2:8000/api/v1 (emulator → host)
./gradlew.bat :android:installDebug
```

## Zimbabwe notes

TomTom parking availability and TomTom live traffic do **not** cover Zimbabwe. The PHP backend uses:

- OpenStreetMap / Overpass for parking places
- Driver reports for red/green occupancy
- Maintained City Parking fee rules (USD 1/hour seed)
- Optional Mapbox `driving-traffic` for route congestion (set token in admin)

## Attribution

Map data © OpenStreetMap contributors (ODbL).
