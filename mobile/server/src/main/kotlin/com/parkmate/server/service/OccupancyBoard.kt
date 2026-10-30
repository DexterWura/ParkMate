package com.parkmate.server.service

import com.parkmate.server.db.SpotRepository
import com.parkmate.shared.model.OccupancyStatus
import java.time.Instant
import java.time.temporal.ChronoUnit

class OccupancyBoard(private val spots: SpotRepository) {
    companion object {
        const val REPORT_TTL_MINUTES = 25L
        const val MAX_REPORTS_PER_HOUR = 30
    }

    class RateLimitedException : RuntimeException("Too many reports from this device")
    class SpotNotFoundException : RuntimeException("Spot not found")

    fun report(spotId: String, status: OccupancyStatus, deviceId: String) {
        if (spots.findById(spotId) == null) throw SpotNotFoundException()
        val since = Instant.now().minus(1, ChronoUnit.HOURS)
        if (spots.countRecentReports(deviceId, since) >= MAX_REPORTS_PER_HOUR) {
            throw RateLimitedException()
        }
        val expires = Instant.now().plus(REPORT_TTL_MINUTES, ChronoUnit.MINUTES)
        spots.addReport(spotId, status, deviceId, expires)
    }
}
