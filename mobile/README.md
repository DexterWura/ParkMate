# OpenLot mobile

Kotlin Android client for the OpenLot PHP API in `../web`.

Package: `com.openlot.android`

## Run

1. Start the web API: `cd ../web && php artisan serve`
2. Build/install: `gradlew.bat :android:installDebug`

- **Debug** API base URL (emulator): `http://10.0.2.2:8000/api/v1` (cleartext allowed in debug)
- **Release** API base URL: `https://openlot.companyop.com/api/v1`

Override in `android/build.gradle.kts` (`API_BASE_URL`) for a physical device on the LAN.

## Release signing

Debug builds use the Android debug keystore. For Play Store / production APKs, add a release signing config in `android/build.gradle.kts`:

```kotlin
android {
    signingConfigs {
        create("release") {
            storeFile = file(System.getenv("OPENLOT_KEYSTORE") ?: "release.keystore")
            storePassword = System.getenv("OPENLOT_KEYSTORE_PASSWORD")
            keyAlias = System.getenv("OPENLOT_KEY_ALIAS")
            keyPassword = System.getenv("OPENLOT_KEY_PASSWORD")
        }
    }
    buildTypes {
        release {
            signingConfig = signingConfigs.getByName("release")
            // minifyEnabled stays false for MVP (MapLibre needs careful keep rules if enabled)
        }
    }
}
```

## Modules

- `:android` — Compose UI + MapLibre OSM map
- `:shared` — shared Kotlin models
- `:server` — optional legacy Ktor stub (product API is PHP in `../web`)
