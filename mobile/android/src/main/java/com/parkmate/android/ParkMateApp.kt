package com.parkmate.android

import android.app.Application
import org.maplibre.android.MapLibre

class ParkMateApp : Application() {
    override fun onCreate() {
        super.onCreate()
        MapLibre.getInstance(this)
    }
}
