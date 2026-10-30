package com.openlot.android.data

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Bundle
import android.os.Looper
import androidx.core.content.ContextCompat

class UserLocationTracker(
    private val context: Context,
    private val onLocation: (Double, Double) -> Unit,
) {
    private val manager = context.getSystemService(Context.LOCATION_SERVICE) as LocationManager
    private var listening = false

    private val listener = object : LocationListener {
        override fun onLocationChanged(location: Location) {
            onLocation(location.latitude, location.longitude)
        }

        @Deprecated("Deprecated in Java")
        override fun onStatusChanged(provider: String?, status: Int, extras: Bundle?) = Unit

        override fun onProviderEnabled(provider: String) = Unit
        override fun onProviderDisabled(provider: String) = Unit
    }

    fun hasPermission(): Boolean {
        val fine = ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) ==
            PackageManager.PERMISSION_GRANTED
        val coarse = ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_COARSE_LOCATION) ==
            PackageManager.PERMISSION_GRANTED
        return fine || coarse
    }

    @SuppressLint("MissingPermission")
    fun start() {
        if (!hasPermission() || listening) return
        listening = true
        val providers = listOf(
            LocationManager.GPS_PROVIDER,
            LocationManager.NETWORK_PROVIDER,
        ).filter { manager.isProviderEnabled(it) }

        providers.forEach { provider ->
            manager.getLastKnownLocation(provider)?.let { last ->
                onLocation(last.latitude, last.longitude)
            }
            manager.requestLocationUpdates(provider, 4_000L, 12f, listener, Looper.getMainLooper())
        }
    }

    fun stop() {
        if (!listening) return
        listening = false
        runCatching { manager.removeUpdates(listener) }
    }
}
