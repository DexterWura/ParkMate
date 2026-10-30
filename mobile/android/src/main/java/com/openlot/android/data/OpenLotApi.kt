package com.openlot.android.data

import com.openlot.android.BuildConfig
import com.openlot.shared.model.AppConfigResponse
import com.openlot.shared.model.CreateReportRequest
import com.openlot.shared.model.CreateSpotRequest
import com.openlot.shared.model.HealthResponse
import com.openlot.shared.model.OccupancyStatus
import com.openlot.shared.model.RouteRequest
import com.openlot.shared.model.RouteResponse
import com.openlot.shared.model.SearchResponse
import com.openlot.shared.model.SpotDetail
import com.openlot.shared.model.SpotListResponse
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.jsonPrimitive
import okhttp3.HttpUrl.Companion.toHttpUrl
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import java.util.concurrent.TimeUnit

class OpenLotApi(
    private val baseUrl: String = BuildConfig.API_BASE_URL.trimEnd('/'),
    private val client: OkHttpClient = OkHttpClient.Builder()
        .connectTimeout(20, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .addInterceptor { chain ->
            chain.proceed(
                chain.request().newBuilder()
                    .header("Accept", "application/json")
                    .header("User-Agent", "OpenLot-Android/1.0")
                    .build(),
            )
        }
        .build(),
) {
    private val json = Json { ignoreUnknownKeys = true; encodeDefaults = true }
    private val media = "application/json".toMediaType()

    suspend fun health(): HealthResponse = get("/health")

    suspend fun config(): AppConfigResponse = get("/config")

    suspend fun spots(
        swLat: Double,
        swLng: Double,
        neLat: Double,
        neLng: Double,
        hours: Double = 1.0,
        status: OccupancyStatus? = null,
        parkingType: String? = null,
    ): SpotListResponse {
        val url = (baseUrl + "/spots").toHttpUrl().newBuilder()
            .addQueryParameter("swLat", swLat.toString())
            .addQueryParameter("swLng", swLng.toString())
            .addQueryParameter("neLat", neLat.toString())
            .addQueryParameter("neLng", neLng.toString())
            .addQueryParameter("hours", hours.toString())
            .apply {
                status?.let { addQueryParameter("status", it.name) }
                parkingType?.takeIf { it.isNotBlank() }?.let { addQueryParameter("parkingType", it) }
            }
            .build()
        return getAbsolute(url.toString())
    }

    suspend fun nearby(
        lat: Double,
        lng: Double,
        radiusMeters: Int = 800,
        hours: Double = 1.0,
        status: OccupancyStatus? = null,
        limit: Int = 40,
    ): SpotListResponse {
        val url = (baseUrl + "/spots/nearby").toHttpUrl().newBuilder()
            .addQueryParameter("lat", lat.toString())
            .addQueryParameter("lng", lng.toString())
            .addQueryParameter("radiusMeters", radiusMeters.toString())
            .addQueryParameter("hours", hours.toString())
            .addQueryParameter("limit", limit.toString())
            .apply { status?.let { addQueryParameter("status", it.name) } }
            .build()
        return getAbsolute(url.toString())
    }

    suspend fun spot(id: String, hours: Double = 1.0): SpotDetail =
        get("/spots/$id?hours=$hours")

    suspend fun report(id: String, status: OccupancyStatus, deviceId: String, hours: Double = 1.0): SpotDetail =
        post("/spots/$id/reports", CreateReportRequest(status, deviceId, hours))

    suspend fun createSpot(name: String?, lat: Double, lng: Double, deviceId: String, hours: Double = 1.0): SpotDetail =
        post("/spots", CreateSpotRequest(name, lat, lng, deviceId, hours))

    suspend fun search(query: String): SearchResponse =
        get("/search?q=${java.net.URLEncoder.encode(query, Charsets.UTF_8.name())}")

    suspend fun route(originLat: Double, originLng: Double, destinationSpotId: String): RouteResponse =
        post("/routes", RouteRequest(originLat, originLng, destinationSpotId))

    private suspend inline fun <reified T> get(path: String): T = getAbsolute(baseUrl + path)

    private suspend inline fun <reified T> getAbsolute(url: String): T = withContext(Dispatchers.IO) {
        val request = Request.Builder().url(url).get().build()
        client.newCall(request).execute().use { response ->
            val body = response.body?.string().orEmpty()
            if (!response.isSuccessful) throw ApiException(response.code, parseError(body))
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
                if (!response.isSuccessful) throw ApiException(response.code, parseError(body))
                json.decodeFromString(body)
            }
        }

    private fun parseError(body: String): String {
        if (body.isBlank()) return "Request failed"
        return runCatching {
            val obj = json.decodeFromString<JsonObject>(body)
            obj["error"]?.jsonPrimitive?.content
                ?: obj["message"]?.jsonPrimitive?.content
                ?: body
        }.getOrElse { body.take(180) }
    }
}

class ApiException(val code: Int, override val message: String) : Exception(message)
