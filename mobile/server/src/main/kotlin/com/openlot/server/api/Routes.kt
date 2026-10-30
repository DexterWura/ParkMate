package com.openlot.server.api

import com.openlot.server.db.SpotRepository
import com.openlot.server.db.SpotRow
import com.openlot.server.service.FeeEstimator
import com.openlot.server.service.OccupancyBoard
import com.openlot.server.service.ParkingCatalog
import com.openlot.server.service.PlaceSearch
import com.openlot.server.service.RouteTraffic
import com.openlot.shared.model.CreateReportRequest
import com.openlot.shared.model.CreateSpotRequest
import com.openlot.shared.model.HealthResponse
import com.openlot.shared.model.RouteRequest
import com.openlot.shared.model.SpotDetail
import com.openlot.shared.model.SpotListResponse
import com.openlot.shared.model.SpotSummary
import io.ktor.http.HttpStatusCode
import io.ktor.server.application.call
import io.ktor.server.request.receive
import io.ktor.server.response.respond
import io.ktor.server.routing.Route
import io.ktor.server.routing.get
import io.ktor.server.routing.post
import io.ktor.server.routing.route

fun Route.openLotRoutes(
    spots: SpotRepository,
    catalog: ParkingCatalog,
    fees: FeeEstimator,
    occupancy: OccupancyBoard,
    search: PlaceSearch,
    traffic: RouteTraffic,
    databaseLabel: String,
) {
    get("/health") {
        call.respond(HealthResponse(status = "ok", database = databaseLabel))
    }

    route("/v1") {
        get("/spots") {
            val swLat = call.request.queryParameters["swLat"]?.toDoubleOrNull()
            val swLng = call.request.queryParameters["swLng"]?.toDoubleOrNull()
            val neLat = call.request.queryParameters["neLat"]?.toDoubleOrNull()
            val neLng = call.request.queryParameters["neLng"]?.toDoubleOrNull()
            if (swLat == null || swLng == null || neLat == null || neLng == null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "swLat, swLng, neLat, neLng are required"))
                return@get
            }
            val hours = call.request.queryParameters["hours"]?.toDoubleOrNull() ?: 1.0
            val rows = catalog.spotsInViewport(swLat, swLng, neLat, neLng)
            call.respond(SpotListResponse(spots = rows.map { it.toSummary(fees, hours) }))
        }

        get("/spots/{id}") {
            val id = call.parameters["id"] ?: return@get call.respond(HttpStatusCode.BadRequest)
            val hours = call.request.queryParameters["hours"]?.toDoubleOrNull() ?: 1.0
            val row = spots.findById(id)
            if (row == null) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Spot not found"))
                return@get
            }
            call.respond(row.toDetail(fees, hours))
        }

        post("/spots/{id}/reports") {
            val id = call.parameters["id"] ?: return@post call.respond(HttpStatusCode.BadRequest)
            val body = call.receive<CreateReportRequest>()
            try {
                occupancy.report(id, body.status, body.deviceId)
                val row = spots.findById(id)!!
                call.respond(row.toDetail(fees, 1.0))
            } catch (_: OccupancyBoard.SpotNotFoundException) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Spot not found"))
            } catch (_: OccupancyBoard.RateLimitedException) {
                call.respond(HttpStatusCode.TooManyRequests, mapOf("error" to "Rate limited"))
            }
        }

        post("/spots") {
            val body = call.receive<CreateSpotRequest>()
            val row = spots.createUserSpot(body.name, body.lat, body.lng)
            call.respond(HttpStatusCode.Created, row.toDetail(fees, 1.0))
        }

        get("/search") {
            val q = call.request.queryParameters["q"].orEmpty()
            call.respond(search.search(q))
        }

        post("/routes") {
            val body = call.receive<RouteRequest>()
            call.respond(traffic.route(body.originLat, body.originLng, body.destinationSpotId))
        }
    }
}

private fun SpotRow.toSummary(fees: FeeEstimator, hours: Double): SpotSummary = SpotSummary(
    id = id,
    name = name,
    lat = lat,
    lng = lng,
    status = status,
    statusUpdatedAt = statusUpdatedAt?.toString(),
    fee = fees.preview(this, hours),
    parkingType = parkingType,
    access = accessType,
    capacity = capacity,
)

private fun SpotRow.toDetail(fees: FeeEstimator, hours: Double): SpotDetail = SpotDetail(
    id = id,
    name = name,
    lat = lat,
    lng = lng,
    status = status,
    statusUpdatedAt = statusUpdatedAt?.toString(),
    fee = fees.preview(this, hours),
    parkingType = parkingType,
    access = accessType,
    capacity = capacity,
    osmId = osmId,
    city = city,
    lastReportAt = statusUpdatedAt?.toString(),
)
