package com.openlot.android.ui

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.openlot.android.data.ApiException
import com.openlot.android.data.DeviceIdStore
import com.openlot.android.data.OpenLotApi
import com.openlot.shared.model.AppConfigResponse
import com.openlot.shared.model.CongestionLevel
import com.openlot.shared.model.FeatureFlags
import com.openlot.shared.model.OccupancyStatus
import com.openlot.shared.model.ProviderStatus
import com.openlot.shared.model.RoutePoint
import com.openlot.shared.model.RouteResponse
import com.openlot.shared.model.SearchResult
import com.openlot.shared.model.SpotDetail
import com.openlot.shared.model.SpotSummary
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class Viewport(val swLat: Double, val swLng: Double, val neLat: Double, val neLng: Double)

data class RouteUi(
    val geometry: List<RoutePoint>,
    val congestion: List<Pair<IntRange, CongestionLevel>>,
    val durationSeconds: Double?,
    val available: Boolean,
    val message: String?,
    val provider: String? = null,
)

data class MapUiState(
    val spots: List<SpotSummary> = emptyList(),
    val selected: SpotDetail? = null,
    val hours: Double = 1.0,
    val searchQuery: String = "",
    val searchResults: List<SearchResult> = emptyList(),
    val loading: Boolean = false,
    val error: String? = null,
    val userLat: Double? = null,
    val userLng: Double? = null,
    val mapCenterLat: Double = -17.8292,
    val mapCenterLng: Double = 31.0522,
    val mapStyleUrl: String = "https://tiles.openfreemap.org/styles/liberty",
    val defaultMapZoom: Double = 14.0,
    val nearbyRadiusMeters: Int = 800,
    val hasGpsFix: Boolean = false,
    val locationPermissionNeeded: Boolean = false,
    val statusFilter: OccupancyStatus? = null,
    val features: FeatureFlags = FeatureFlags(),
    val providers: ProviderStatus = ProviderStatus(),
    val appName: String = "OpenLot",
    val route: RouteUi? = null,
    val showAddSpot: Boolean = false,
    val cameraTarget: Pair<Double, Double>? = null,
    val lastViewport: Viewport? = null,
)

class MapViewModel(app: Application) : AndroidViewModel(app) {
    private val api = OpenLotApi()
    private val deviceId = DeviceIdStore(app).get()
    private val _state = MutableStateFlow(MapUiState())
    val state: StateFlow<MapUiState> = _state.asStateFlow()
    private var loadJob: Job? = null
    private var searchJob: Job? = null

    init {
        viewModelScope.launch {
            try {
                api.health()
                applyConfig(api.config())
            } catch (_: Exception) {
                // Keep defaults when offline / API unavailable.
            }
        }
    }

    private fun applyConfig(config: AppConfigResponse) {
        _state.update {
            it.copy(
                appName = config.appName,
                mapCenterLat = config.defaultMapLat,
                mapCenterLng = config.defaultMapLng,
                defaultMapZoom = config.defaultMapZoom,
                mapStyleUrl = config.mapStyleUrl ?: it.mapStyleUrl,
                hours = config.defaultParkingHours.coerceIn(0.5, 12.0),
                nearbyRadiusMeters = config.nearbyRadiusMeters,
                features = config.features,
                providers = config.providers,
                cameraTarget = config.defaultMapLat to config.defaultMapLng,
            )
        }
    }

    fun onViewportChanged(viewport: Viewport) {
        _state.update {
            it.copy(
                lastViewport = viewport,
                mapCenterLat = (viewport.swLat + viewport.neLat) / 2.0,
                mapCenterLng = (viewport.swLng + viewport.neLng) / 2.0,
            )
        }
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            delay(350)
            refreshSpots(viewport)
        }
    }

    fun refreshNow() {
        val viewport = _state.value.lastViewport ?: return
        loadJob?.cancel()
        loadJob = viewModelScope.launch { refreshSpots(viewport) }
    }

    fun setStatusFilter(status: OccupancyStatus?) {
        _state.update { it.copy(statusFilter = status) }
        refreshNow()
    }

    fun findNearby() {
        val lat = _state.value.userLat ?: _state.value.mapCenterLat
        val lng = _state.value.userLng ?: _state.value.mapCenterLng
        viewModelScope.launch {
            _state.update { it.copy(loading = true) }
            try {
                val response = api.nearby(
                    lat = lat,
                    lng = lng,
                    radiusMeters = _state.value.nearbyRadiusMeters,
                    hours = _state.value.hours,
                    status = _state.value.statusFilter,
                )
                val nearest = response.spots.firstOrNull()
                _state.update {
                    it.copy(
                        spots = response.spots,
                        loading = false,
                        error = null,
                        cameraTarget = if (nearest != null) nearest.lat to nearest.lng else lat to lng,
                    )
                }
                nearest?.let { selectSpot(it.id) }
            } catch (ex: Exception) {
                _state.update { it.copy(loading = false, error = friendlyError(ex)) }
            }
        }
    }

    fun selectSpot(id: String) {
        viewModelScope.launch {
            try {
                val detail = api.spot(id, _state.value.hours)
                _state.update { it.copy(selected = detail, error = null) }
            } catch (ex: Exception) {
                _state.update { it.copy(error = friendlyError(ex)) }
            }
        }
    }

    fun clearSelection() {
        _state.update { it.copy(selected = null) }
    }

    fun setHours(hours: Double) {
        val clamped = hours.coerceIn(0.5, 12.0)
        _state.update { it.copy(hours = clamped) }
        val selected = _state.value.selected ?: return
        viewModelScope.launch {
            try {
                val detail = api.spot(selected.id, clamped)
                _state.update { it.copy(selected = detail) }
            } catch (ex: Exception) {
                _state.update { it.copy(error = friendlyError(ex)) }
            }
        }
    }

    fun onSearchQuery(query: String) {
        _state.update { it.copy(searchQuery = query) }
        searchJob?.cancel()
        if (query.isBlank()) {
            _state.update { it.copy(searchResults = emptyList()) }
            return
        }
        searchJob = viewModelScope.launch {
            delay(300)
            try {
                val response = api.search(query)
                _state.update { it.copy(searchResults = response.results, error = null) }
            } catch (ex: Exception) {
                _state.update { it.copy(error = friendlyError(ex)) }
            }
        }
    }

    fun onSearchResult(result: SearchResult) {
        _state.update {
            it.copy(
                searchQuery = result.title,
                searchResults = emptyList(),
                cameraTarget = result.lat to result.lng,
            )
        }
        result.spotId?.let { selectSpot(it) }
    }

    fun report(status: OccupancyStatus) {
        if (!_state.value.features.occupancyReports) {
            _state.update { it.copy(error = "Occupancy reports are disabled") }
            return
        }
        val selected = _state.value.selected ?: return
        val hours = _state.value.hours
        _state.update { state ->
            val optimisticSpots = state.spots.map { spot ->
                if (spot.id == selected.id) spot.copy(status = status) else spot
            }
            state.copy(
                spots = optimisticSpots,
                selected = selected.copy(status = status),
            )
        }
        viewModelScope.launch {
            try {
                val detail = api.report(selected.id, status, deviceId, hours)
                _state.update { state ->
                    state.copy(
                        selected = detail,
                        spots = state.spots.map { if (it.id == detail.id) detail.toSummary() else it },
                        error = null,
                    )
                }
            } catch (ex: Exception) {
                _state.update { it.copy(error = friendlyError(ex)) }
                refreshSelected()
            }
        }
    }

    fun goToBay() {
        if (!_state.value.features.routing) {
            _state.update { it.copy(error = "Routing is disabled") }
            return
        }
        if (!_state.value.providers.routingAvailable) {
            _state.update { it.copy(error = "No routing provider configured on the server") }
            return
        }
        val selected = _state.value.selected ?: return
        val originLat = _state.value.userLat ?: _state.value.mapCenterLat
        val originLng = _state.value.userLng ?: _state.value.mapCenterLng
        viewModelScope.launch {
            try {
                val route = api.route(originLat, originLng, selected.id)
                _state.update { it.copy(route = route.toUi(), error = null) }
            } catch (ex: Exception) {
                _state.update { it.copy(error = friendlyError(ex)) }
            }
        }
    }

    fun clearRoute() {
        _state.update { it.copy(route = null) }
    }

    fun setUserLocation(lat: Double, lng: Double) {
        val firstFix = !_state.value.hasGpsFix
        _state.update {
            it.copy(
                userLat = lat,
                userLng = lng,
                hasGpsFix = true,
                locationPermissionNeeded = false,
                cameraTarget = if (firstFix) lat to lng else it.cameraTarget,
            )
        }
    }

    fun setLocationPermissionNeeded(needed: Boolean) {
        _state.update { it.copy(locationPermissionNeeded = needed) }
    }

    fun recenterOnUser() {
        val lat = _state.value.userLat ?: return
        val lng = _state.value.userLng ?: return
        _state.update { it.copy(cameraTarget = lat to lng) }
    }

    fun setShowAddSpot(show: Boolean) {
        if (show && !_state.value.features.userSpots) {
            _state.update { it.copy(error = "Adding spots is disabled") }
            return
        }
        _state.update { it.copy(showAddSpot = show) }
    }

    fun addMissingBay(name: String?) {
        if (!_state.value.features.userSpots) {
            _state.update { it.copy(error = "Adding spots is disabled") }
            return
        }
        val lat = _state.value.mapCenterLat
        val lng = _state.value.mapCenterLng
        val hours = _state.value.hours
        viewModelScope.launch {
            try {
                val detail = api.createSpot(name, lat, lng, deviceId, hours)
                _state.update { state ->
                    val already = state.spots.any { it.id == detail.id }
                    state.copy(
                        selected = detail,
                        showAddSpot = false,
                        spots = if (already) {
                            state.spots.map { if (it.id == detail.id) detail.toSummary() else it }
                        } else {
                            state.spots + detail.toSummary()
                        },
                        cameraTarget = detail.lat to detail.lng,
                        error = null,
                    )
                }
            } catch (ex: Exception) {
                _state.update { it.copy(error = friendlyError(ex)) }
            }
        }
    }

    fun clearCameraTarget() {
        _state.update { it.copy(cameraTarget = null) }
    }

    fun dismissError() {
        _state.update { it.copy(error = null) }
    }

    private suspend fun refreshSpots(viewport: Viewport) {
        _state.update { it.copy(loading = true) }
        try {
            val response = api.spots(
                swLat = viewport.swLat,
                swLng = viewport.swLng,
                neLat = viewport.neLat,
                neLng = viewport.neLng,
                hours = _state.value.hours,
                status = _state.value.statusFilter,
            )
            _state.update { it.copy(spots = response.spots, loading = false, error = null) }
        } catch (ex: Exception) {
            _state.update { it.copy(loading = false, error = friendlyError(ex)) }
        }
    }

    private suspend fun refreshSelected() {
        val id = _state.value.selected?.id ?: return
        try {
            val detail = api.spot(id, _state.value.hours)
            _state.update { it.copy(selected = detail) }
        } catch (_: Exception) {
        }
    }

    private fun friendlyError(ex: Exception): String = when (ex) {
        is ApiException -> ex.message
        else -> ex.message ?: "Something went wrong"
    }

    private fun SpotDetail.toSummary(): SpotSummary = SpotSummary(
        id = id,
        name = name,
        lat = lat,
        lng = lng,
        status = status,
        statusUpdatedAt = statusUpdatedAt,
        fee = fee,
        parkingType = parkingType,
        access = access,
        capacity = capacity,
    )

    private fun RouteResponse.toUi(): RouteUi = RouteUi(
        geometry = geometry,
        congestion = congestion.map { it.fromIndex..it.toIndex to it.level },
        durationSeconds = durationSeconds,
        available = available,
        message = message,
        provider = provider,
    )
}
