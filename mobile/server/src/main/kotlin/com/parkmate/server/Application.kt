package com.parkmate.server

import com.parkmate.server.api.parkMateRoutes
import com.parkmate.server.config.AppConfig
import com.parkmate.server.db.DatabaseFactory
import com.parkmate.server.db.SpotRepository
import com.parkmate.server.service.FeeEstimator
import com.parkmate.server.service.MapboxRouteTraffic
import com.parkmate.server.service.OccupancyBoard
import com.parkmate.server.service.ParkingCatalog
import com.parkmate.server.service.PlaceSearch
import com.parkmate.server.service.UnavailableRouteTraffic
import io.ktor.client.HttpClient
import io.ktor.client.engine.cio.CIO
import io.ktor.client.plugins.contentnegotiation.ContentNegotiation as ClientContentNegotiation
import io.ktor.client.plugins.logging.LogLevel
import io.ktor.client.plugins.logging.Logging
import io.ktor.http.HttpHeaders
import io.ktor.http.HttpMethod
import io.ktor.http.HttpStatusCode
import io.ktor.serialization.kotlinx.json.json
import io.ktor.server.application.Application
import io.ktor.server.application.install
import io.ktor.server.engine.embeddedServer
import io.ktor.server.netty.Netty
import io.ktor.server.plugins.calllogging.CallLogging
import io.ktor.server.plugins.contentnegotiation.ContentNegotiation
import io.ktor.server.plugins.cors.routing.CORS
import io.ktor.server.plugins.statuspages.StatusPages
import io.ktor.server.response.respond
import io.ktor.server.routing.routing
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.serialization.json.Json
import org.slf4j.LoggerFactory

fun main() {
    val config = AppConfig.fromEnv()
    embeddedServer(Netty, port = config.port, host = "0.0.0.0") {
        module(config)
    }.start(wait = true)
}

fun Application.module(config: AppConfig = AppConfig.fromEnv()) {
    val log = LoggerFactory.getLogger("ParkMate")
    val db = DatabaseFactory(config)
    db.connect()
    val spots = SpotRepository(db)
    val fees = FeeEstimator(spots)
    val occupancy = OccupancyBoard(spots)

    val http = HttpClient(CIO) {
        install(ClientContentNegotiation) {
            json(Json { ignoreUnknownKeys = true })
        }
        install(Logging) { level = LogLevel.INFO }
    }

    val catalog = ParkingCatalog(spots, http)
    val search = PlaceSearch(spots, http)
    val traffic = if (config.mapboxToken != null) {
        MapboxRouteTraffic(config.mapboxToken, spots, http)
    } else {
        UnavailableRouteTraffic()
    }

    CoroutineScope(Dispatchers.Default).launch {
        try {
            catalog.ensureZimbabwePrefetch()
        } catch (ex: Exception) {
            log.warn("Zimbabwe prefetch failed: {}", ex.message)
        }
    }

    install(ContentNegotiation) {
        json(Json {
            prettyPrint = false
            ignoreUnknownKeys = true
            encodeDefaults = true
        })
    }
    install(CallLogging)
    install(CORS) {
        anyHost()
        allowHeader(HttpHeaders.ContentType)
        allowHeader("X-Device-Id")
        allowMethod(HttpMethod.Get)
        allowMethod(HttpMethod.Post)
        allowMethod(HttpMethod.Options)
    }
    install(StatusPages) {
        exception<Throwable> { call, cause ->
            log.error("Unhandled error", cause)
            call.respond(HttpStatusCode.InternalServerError, mapOf("error" to (cause.message ?: "error")))
        }
    }

    routing {
        parkMateRoutes(
            spots = spots,
            catalog = catalog,
            fees = fees,
            occupancy = occupancy,
            search = search,
            traffic = traffic,
            databaseLabel = db.dialect.name.lowercase(),
        )
    }
}
