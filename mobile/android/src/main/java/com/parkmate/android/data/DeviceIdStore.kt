package com.parkmate.android.data

import android.content.Context
import java.util.UUID

class DeviceIdStore(context: Context) {
    private val prefs = context.getSharedPreferences("parkmate", Context.MODE_PRIVATE)

    fun get(): String {
        val existing = prefs.getString(KEY, null)
        if (existing != null) return existing
        val created = UUID.randomUUID().toString()
        prefs.edit().putString(KEY, created).apply()
        return created
    }

    companion object {
        private const val KEY = "device_id"
    }
}
