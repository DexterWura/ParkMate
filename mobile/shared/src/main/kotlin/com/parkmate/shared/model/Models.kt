package com.parkmate.shared.model

import kotlinx.serialization.Serializable

@Serializable
enum class OccupancyStatus {
    FREE,
    TAKEN,
}

@Serializable
enum class FeeKind {
    ON_STREET,
    PARKADE,
    LOT,
}

@Serializable
enum class FeeUnit {
    HOUR,
}

@Serializable
enum class CongestionLevel {
    UNKNOWN,
    LOW,
    MODERATE,
    HEAVY,
    SEVERE,
}

@Serializable
data class MoneyAmount(
    val amount: Double,
    val currency: String,
)

@Serializable
data class FeePreview(
    val hourlyRate: MoneyAmount?,
    val estimate: MoneyAmount?,
    val hours: Double?,
    val sourceNote: String?,
    val kind: FeeKind?,
)

@Serializable
data class SpotSummary(
    val id: String,
    val name: String?,
    val lat: Double,
    val lng: Double,
    val status: OccupancyStatus?,
    val statusUpdatedAt: String?,
    val fee: FeePreview,
    val parkingType: String?,
    val access: String?,
    val capacity: Int?,
)

@Serializable
data class SpotDetail(
    val id: String,
    val name: String?,
    val lat: Double,
    val lng: Double,
    val status: OccupancyStatus?,
    val statusUpdatedAt: String?,
    val fee: FeePreview,
    val parkingType: String?,
    val access: String?,
    val capacity: Int?,
    val osmId: String?,
    val city: String?,
    val lastReportAt: String?,
    val attribution: String = "© OpenStreetMap contributors",
)

@Serializable
data class SpotListResponse(
    val spots: List<SpotSummary>,
    val attribution: String = "© OpenStreetMap contributors",
)

@Serializable
data class CreateReportRequest(
    val status: OccupancyStatus,
    val deviceId: String,
)

@Serializable
data class CreateSpotRequest(
    val name: String?,
    val lat: Double,
    val lng: Double,
    val deviceId: String,
)

@Serializable
data class SearchResult(
    val id: String,
    val title: String,
    val subtitle: String?,
    val lat: Double,
    val lng: Double,
    val kind: String,
    val spotId: String? = null,
)

@Serializable
data class SearchResponse(
    val results: List<SearchResult>,
)

@Serializable
data class RouteRequest(
    val originLat: Double,
    val originLng: Double,
    val destinationSpotId: String,
)

@Serializable
data class RoutePoint(
    val lat: Double,
    val lng: Double,
)

@Serializable
data class CongestionSegment(
    val fromIndex: Int,
    val toIndex: Int,
    val level: CongestionLevel,
)

@Serializable
data class RouteResponse(
    val available: Boolean,
    val durationSeconds: Double? = null,
    val distanceMeters: Double? = null,
    val geometry: List<RoutePoint> = emptyList(),
    val congestion: List<CongestionSegment> = emptyList(),
    val message: String? = null,
)

@Serializable
data class HealthResponse(
    val status: String,
    val database: String,
)
