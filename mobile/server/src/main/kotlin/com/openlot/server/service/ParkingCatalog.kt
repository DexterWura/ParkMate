package com.openlot.server.service

import com.openlot.server.db.SpotRepository
import com.openlot.server.db.SpotRow
import io.ktor.client.HttpClient
import io.ktor.client.request.forms.submitForm
import io.ktor.client.statement.bodyAsText
import io.ktor.http.Parameters
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.doubleOrNull
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import kotlinx.serialization.json.contentOrNull
import org.slf4j.LoggerFactory
import java.time.Instant
import java.time.temporal.ChronoUnit

class ParkingCatalog(
    private val spots: SpotRepository,
    private val http: HttpClient,
) {
    private val log = LoggerFactory.getLogger(ParkingCatalog::class.java)
    private val json = Json { ignoreUnknownKeys = true }
    private val mutex = Mutex()

    data class CitySeed(val name: String, val south: Double, val west: Double, val north: Double, val east: Double)

    private val zimCities = listOf(
        CitySeed("Harare", -17.95, 30.90, -17.70, 31.20),
        CitySeed("Bulawayo", -20.25, 28.45, -20.05, 28.75),
        CitySeed("Mutare", -19.00, 32.55, -18.90, 32.75),
        CitySeed("Gweru", -19.50, 29.70, -19.40, 29.90),
    )

    suspend fun ensureZimbabwePrefetch() {
        for (city in zimCities) {
            refreshBbox(city.south, city.west, city.north, city.east, city.name, force = false)
        }
    }

    suspend fun spotsInViewport(
        swLat: Double,
        swLng: Double,
        neLat: Double,
        neLng: Double,
    ): List<SpotRow> {
        refreshBbox(swLat, swLng, neLat, neLng, cityHint = null, force = false)
        return spots.findInViewport(swLat, swLng, neLat, neLng)
    }

    suspend fun refreshBbox(
        south: Double,
        west: Double,
        north: Double,
        east: Double,
        cityHint: String?,
        force: Boolean,
    ) {
        val key = "bbox:%.3f:%.3f:%.3f:%.3f".format(south, west, north, east)
        val fetched = spots.getIngestFetchedAt(key)
        if (!force && fetched != null && fetched.isAfter(Instant.now().minus(24, ChronoUnit.HOURS))) {
            return
        }
        mutex.withLock {
            val again = spots.getIngestFetchedAt(key)
            if (!force && again != null && again.isAfter(Instant.now().minus(24, ChronoUnit.HOURS))) {
                return
            }
            try {
                ingestOverpass(south, west, north, east, cityHint)
                spots.putIngestFetchedAt(key, Instant.now())
            } catch (ex: Exception) {
                log.warn("Overpass ingest failed for {}: {}", key, ex.message)
            }
        }
    }

    private suspend fun ingestOverpass(
        south: Double,
        west: Double,
        north: Double,
        east: Double,
        cityHint: String?,
    ) = withContext(Dispatchers.IO) {
        val query = """
            [out:json][timeout:60];
            (
              node["amenity"="parking"]($south,$west,$north,$east);
              way["amenity"="parking"]($south,$west,$north,$east);
              relation["amenity"="parking"]($south,$west,$north,$east);
            );
            out center tags;
        """.trimIndent()

        val body = http.submitForm(
            url = "https://overpass-api.de/api/interpreter",
            formParameters = Parameters.build {
                append("data", query)
            },
        ).bodyAsText()

        val root = json.parseToJsonElement(body).jsonObject
        val elements = root["elements"]?.jsonArray ?: JsonArray(emptyList())
        var count = 0
        for (el in elements) {
            val obj = el.jsonObject
            val type = obj["type"]?.jsonPrimitive?.contentOrNull ?: continue
            val id = obj["id"]?.jsonPrimitive?.contentOrNull ?: continue
            val tags = obj["tags"]?.jsonObject
            val lat = when (type) {
                "node" -> obj["lat"]?.jsonPrimitive?.doubleOrNull
                else -> obj["center"]?.jsonObject?.get("lat")?.jsonPrimitive?.doubleOrNull
            } ?: continue
            val lon = when (type) {
                "node" -> obj["lon"]?.jsonPrimitive?.doubleOrNull
                else -> obj["center"]?.jsonObject?.get("lon")?.jsonPrimitive?.doubleOrNull
            } ?: continue
            val name = tags?.get("name")?.jsonPrimitive?.contentOrNull
            val parkingType = tags?.get("parking")?.jsonPrimitive?.contentOrNull
            val access = tags?.get("access")?.jsonPrimitive?.contentOrNull
            val capacity = tags?.get("capacity")?.jsonPrimitive?.contentOrNull?.toIntOrNull()
            val fee = tags?.get("fee")?.jsonPrimitive?.contentOrNull
            val charge = tags?.get("charge")?.jsonPrimitive?.contentOrNull
            spots.upsertOsmSpot(
                osmId = "$type/$id",
                name = name,
                lat = lat,
                lng = lon,
                parkingType = parkingType,
                accessType = access,
                capacity = capacity,
                osmFee = fee,
                osmCharge = charge,
                city = cityHint,
            )
            count++
        }
        log.info("Ingested {} OSM parking features for bbox {},{},{},{}", count, south, west, north, east)
    }
}
