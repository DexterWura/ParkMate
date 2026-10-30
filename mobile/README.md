# ParkMate mobile

Kotlin Android client for the ParkMate PHP API in `../web`.

## Run

1. Start the web API: `cd ../web && php artisan serve`
2. Build/install: `gradlew.bat :android:installDebug`

Default API base URL (emulator): `http://10.0.2.2:8000/api/v1`

Change it in `android/build.gradle.kts` (`API_BASE_URL`) for a physical device or deployed host.

## Modules

- `:android` — Compose UI + MapLibre OSM map
- `:shared` — shared Kotlin models
- `:server` — optional legacy Ktor stub (the product API is PHP in `../web`)
