package com.openlot.android

import android.app.Application
import org.maplibre.android.MapLibre

class OpenLotApp : Application() {
    override fun onCreate() {
        super.onCreate()
        MapLibre.getInstance(this)
    }
}
