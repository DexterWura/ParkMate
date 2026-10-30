package com.parkmate.android.data

import com.parkmate.android.BuildConfig
import com.parkmate.shared.model.CreateReportRequest
import com.parkmate.shared.model.CreateSpotRequest
import com.parkmate.shared.model.OccupancyStatus
import com.parkmate.shared.model.RouteRequest
import com.parkmate.shared.model.RouteResponse
import com.parkmate.shared.model.SearchResponse
import com.parkmate.shared.model.SpotDetail
import com.parkmate.shared.model.SpotListResponse
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import java.util.concurrent.TimeUnit

class ParkMateApi(
    private val baseUrl: String = BuildConfig.API_BASE_URL.trimEnd('/'),
    private val client: OkHttpClient = OkHttpClient.Builder()
        .connectTimeout(20, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .build(),
) {
    private val json = Json { ignoreUnknownKeys = true; encodeDefaults = true }
    private val media = "application/json".toMediaType()

    suspend fun spots(swLat: Double, swLng: Double, neLat: Double, neLng: Double, hours: Double = 1.0): SpotListResponse =
        get("/spots?swLat=$swLat&swLng=$swLng&neLat=$neLat&neLng=$neLng&hours=$hours")

    suspend fun spot(id: String, hours: Double = 1.0): SpotDetail =
        get("/spots/$id?hours=$hours")

    suspend fun report(id: String, status: OccupancyStatus, deviceId: String): SpotDetail =
        post("/spots/$id/reports", CreateReportRequest(status, deviceId))

    suspend fun createSpot(name: String?, lat: Double, lng: Double, deviceId: String): SpotDetail =
        post("/spots", CreateSpotRequest(name, lat, lng, deviceId))

    suspend fun search(query: String): SearchResponse =
        get("/search?q=${java.net.URLEncoder.encode(query, Charsets.UTF_8.name())}")

    suspend fun route(originLat: Double, originLng: Double, destinationSpotId: String): RouteResponse =
        post("/routes", RouteRequest(originLat, originLng, destinationSpotId))

    private suspend inline fun <reified T> get(path: String): T = withContext(Dispatchers.IO) {
        val request = Request.Builder().url(baseUrl + path).get().build()
        client.newCall(request).execute().use { response ->
            val body = response.body?.string().orEmpty()
            if (!response.isSuccessful) error("HTTP ${response.code}: $body")
            json.decodeFromString(body)
        }
    }

    private suspend inline fun <reified Req, reified Res> post(path: String, payload: Req): Res =
        withContext(Dispatchers.IO) {
            val request = Request.Builder()
                .url(baseUrl + path)
                .post(json.encodeToString(payload).toRequestBody(media))
                .build()
            client.newCall(request).execute().use { response ->
                val body = response.body?.string().orEmpty()
                if (!response.isSuccessful) error("HTTP ${response.code}: $body")
                json.decodeFromString(body)
            }
        }
}
