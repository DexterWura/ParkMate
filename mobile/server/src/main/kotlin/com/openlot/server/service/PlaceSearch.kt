package com.openlot.server.service

import com.openlot.server.db.SpotRepository
import com.openlot.shared.model.SearchResponse
import com.openlot.shared.model.SearchResult
import io.ktor.client.HttpClient
import io.ktor.client.request.get
import io.ktor.client.request.header
import io.ktor.client.request.parameter
import io.ktor.client.statement.bodyAsText
import kotlinx.coroutines.delay
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.doubleOrNull
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import kotlinx.serialization.json.contentOrNull
import org.slf4j.LoggerFactory

class PlaceSearch(
    private val spots: SpotRepository,
    private val http: HttpClient,
) {
    private val log = LoggerFactory.getLogger(PlaceSearch::class.java)
    private val json = Json { ignoreUnknownKeys = true }
    private val mutex = Mutex()
    private var lastCallAtMs = 0L

    suspend fun search(query: String): SearchResponse {
        val q = query.trim()
        if (q.isEmpty()) return SearchResponse(emptyList())

        val local = spots.searchByName(q).map {
            SearchResult(
                id = "spot:${it.id}",
                title = it.name ?: "Parking spot",
                subtitle = listOfNotNull(it.city, it.parkingType).joinToString(" · ").ifBlank { null },
                lat = it.lat,
                lng = it.lng,
                kind = "spot",
                spotId = it.id,
            )
        }

        val cacheKey = q.lowercase()
        val cached = spots.getSearchCache(cacheKey)
        val remote: List<SearchResult> = if (cached != null) {
            json.decodeFromString(cached)
        } else {
            fetchNominatim(q).also { results ->
                spots.putSearchCache(cacheKey, json.encodeToString(results))
            }
        }

        val merged = LinkedHashMap<String, SearchResult>()
        for (item in local + remote) {
            val key = "${item.lat},${item.lng},${item.title.lowercase()}"
            merged.putIfAbsent(key, item)
        }
        return SearchResponse(merged.values.toList())
    }

    private suspend fun fetchNominatim(query: String): List<SearchResult> = mutex.withLock {
        val wait = 1100 - (System.currentTimeMillis() - lastCallAtMs)
        if (wait > 0) delay(wait)
        lastCallAtMs = System.currentTimeMillis()
        return try {
            val body = http.get("https://nominatim.openstreetmap.org/search") {
                header("User-Agent", "OpenLot/1.0 (parking finder; contact=admin@openlot.companyop.com)")
                parameter("q", query)
                parameter("format", "json")
                parameter("limit", "8")
            }.bodyAsText()
            val arr = json.parseToJsonElement(body).jsonArray
            arr.mapNotNull { el ->
                val obj = el.jsonObject
                val lat = obj["lat"]?.jsonPrimitive?.contentOrNull?.toDoubleOrNull() ?: return@mapNotNull null
                val lon = obj["lon"]?.jsonPrimitive?.contentOrNull?.toDoubleOrNull() ?: return@mapNotNull null
                val display = obj["display_name"]?.jsonPrimitive?.contentOrNull ?: return@mapNotNull null
                val osmId = obj["osm_id"]?.jsonPrimitive?.contentOrNull ?: display
                SearchResult(
                    id = "place:$osmId",
                    title = display.substringBefore(","),
                    subtitle = display,
                    lat = lat,
                    lng = lon,
                    kind = "place",
                )
            }
        } catch (ex: Exception) {
            log.warn("Nominatim search failed: {}", ex.message)
            emptyList()
        }
    }
}
