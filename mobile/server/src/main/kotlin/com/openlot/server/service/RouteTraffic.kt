package com.openlot.server.service

import com.openlot.server.db.SpotRepository
import com.openlot.shared.model.CongestionLevel
import com.openlot.shared.model.CongestionSegment
import com.openlot.shared.model.RoutePoint
import com.openlot.shared.model.RouteResponse
import io.ktor.client.HttpClient
import io.ktor.client.request.get
import io.ktor.client.statement.bodyAsText
import io.ktor.http.isSuccess
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.doubleOrNull
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import kotlinx.serialization.json.contentOrNull
import org.slf4j.LoggerFactory

interface RouteTraffic {
    suspend fun route(originLat: Double, originLng: Double, destinationSpotId: String): RouteResponse
}

class UnavailableRouteTraffic : RouteTraffic {
    override suspend fun route(originLat: Double, originLng: Double, destinationSpotId: String): RouteResponse =
        RouteResponse(
            available = false,
            message = "Traffic routing is not configured. Set MAPBOX_TOKEN to enable congestion routes.",
        )
}

class MapboxRouteTraffic(
    private val token: String,
    private val spots: SpotRepository,
    private val http: HttpClient,
) : RouteTraffic {
    private val log = LoggerFactory.getLogger(MapboxRouteTraffic::class.java)
    private val json = Json { ignoreUnknownKeys = true }

    override suspend fun route(originLat: Double, originLng: Double, destinationSpotId: String): RouteResponse {
        val spot = spots.findById(destinationSpotId)
            ?: return RouteResponse(available = false, message = "Destination spot not found")

        val url =
            "https://api.mapbox.com/directions/v5/mapbox/driving-traffic/" +
                "$originLng,$originLat;${spot.lng},${spot.lat}" +
                "?geometries=geojson&overview=full&annotations=congestion&access_token=$token"

        return try {
            val response = http.get(url)
            if (!response.status.isSuccess()) {
                log.warn("Mapbox directions failed: {}", response.status)
                return RouteResponse(available = false, message = "Traffic provider returned ${response.status}")
            }
            val body = response.bodyAsText()
            val root = json.parseToJsonElement(body).jsonObject
            val route = root["routes"]?.jsonArray?.firstOrNull()?.jsonObject
                ?: return RouteResponse(available = false, message = "No route found")

            val duration = route["duration"]?.jsonPrimitive?.doubleOrNull
            val distance = route["distance"]?.jsonPrimitive?.doubleOrNull
            val coords = route["geometry"]?.jsonObject?.get("coordinates")?.jsonArray.orEmpty()
            val geometry = coords.mapNotNull { pair ->
                val arr = pair.jsonArray
                val lng = arr.getOrNull(0)?.jsonPrimitive?.doubleOrNull ?: return@mapNotNull null
                val lat = arr.getOrNull(1)?.jsonPrimitive?.doubleOrNull ?: return@mapNotNull null
                RoutePoint(lat = lat, lng = lng)
            }

            val congestionValues = route["legs"]?.jsonArray
                ?.firstOrNull()
                ?.jsonObject
                ?.get("annotation")
                ?.jsonObject
                ?.get("congestion")
                ?.jsonArray
                .orEmpty()

            val segments = mutableListOf<CongestionSegment>()
            var index = 0
            while (index < congestionValues.size) {
                val level = mapCongestion(congestionValues[index].jsonPrimitive.contentOrNull)
                var end = index
                while (end + 1 < congestionValues.size &&
                    mapCongestion(congestionValues[end + 1].jsonPrimitive.contentOrNull) == level
                ) {
                    end++
                }
                segments += CongestionSegment(fromIndex = index, toIndex = end + 1, level = level)
                index = end + 1
            }

            RouteResponse(
                available = true,
                durationSeconds = duration,
                distanceMeters = distance,
                geometry = geometry,
                congestion = segments,
            )
        } catch (ex: Exception) {
            log.warn("Mapbox route error: {}", ex.message)
            RouteResponse(available = false, message = "Traffic routing failed")
        }
    }

    private fun mapCongestion(raw: String?): CongestionLevel = when (raw?.lowercase()) {
        "low" -> CongestionLevel.LOW
        "moderate" -> CongestionLevel.MODERATE
        "heavy" -> CongestionLevel.HEAVY
        "severe" -> CongestionLevel.SEVERE
        else -> CongestionLevel.UNKNOWN
    }
}
