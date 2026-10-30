package com.parkmate.android.ui

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.parkmate.android.data.DeviceIdStore
import com.parkmate.android.data.ParkMateApi
import com.parkmate.shared.model.CongestionLevel
import com.parkmate.shared.model.OccupancyStatus
import com.parkmate.shared.model.RoutePoint
import com.parkmate.shared.model.RouteResponse
import com.parkmate.shared.model.SearchResult
import com.parkmate.shared.model.SpotDetail
import com.parkmate.shared.model.SpotSummary
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
)

data class MapUiState(
    val spots: List<SpotSummary> = emptyList(),
    val selected: SpotDetail? = null,
    val hours: Double = 1.0,
    val searchQuery: String = "",
    val searchResults: List<SearchResult> = emptyList(),
    val sheetExpanded: Boolean = false,
    val loading: Boolean = false,
    val error: String? = null,
    val userLat: Double? = -17.8292,
    val userLng: Double? = 31.0522,
    val route: RouteUi? = null,
    val showAddSpot: Boolean = false,
    val cameraTarget: Pair<Double, Double>? = Pair(-17.8292, 31.0522),
)

class MapViewModel(app: Application) : AndroidViewModel(app) {
    private val api = ParkMateApi()
    private val deviceId = DeviceIdStore(app).get()
    private val _state = MutableStateFlow(MapUiState())
    val state: StateFlow<MapUiState> = _state.asStateFlow()
    private var loadJob: Job? = null
    private var searchJob: Job? = null

    fun onViewportChanged(viewport: Viewport) {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            delay(350)
            refreshSpots(viewport)
        }
    }

    fun selectSpot(id: String) {
        viewModelScope.launch {
            try {
                val detail = api.spot(id, _state.value.hours)
                _state.update { it.copy(selected = detail, sheetExpanded = true, error = null) }
            } catch (ex: Exception) {
                _state.update { it.copy(error = ex.message) }
            }
        }
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
                _state.update { it.copy(error = ex.message) }
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
                _state.update { it.copy(error = ex.message) }
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
        val selected = _state.value.selected ?: return
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
                val detail = api.report(selected.id, status, deviceId)
                _state.update { state ->
                    state.copy(
                        selected = detail,
                        spots = state.spots.map { if (it.id == detail.id) detail.toSummary() else it },
                        error = null,
                    )
                }
            } catch (ex: Exception) {
                _state.update { it.copy(error = ex.message) }
                refreshSelected()
            }
        }
    }

    fun goToBay() {
        val selected = _state.value.selected ?: return
        val originLat = _state.value.userLat ?: selected.lat
        val originLng = _state.value.userLng ?: selected.lng
        viewModelScope.launch {
            try {
                val route = api.route(originLat, originLng, selected.id)
                _state.update { it.copy(route = route.toUi(), error = null) }
            } catch (ex: Exception) {
                _state.update { it.copy(error = ex.message) }
            }
        }
    }

    fun clearRoute() {
        _state.update { it.copy(route = null) }
    }

    fun setUserLocation(lat: Double, lng: Double) {
        _state.update { it.copy(userLat = lat, userLng = lng) }
    }

    fun setShowAddSpot(show: Boolean) {
        _state.update { it.copy(showAddSpot = show) }
    }

    fun addMissingBay(name: String?, lat: Double, lng: Double) {
        viewModelScope.launch {
            try {
                val detail = api.createSpot(name, lat, lng, deviceId)
                _state.update { state ->
                    state.copy(
                        selected = detail,
                        showAddSpot = false,
                        spots = state.spots + detail.toSummary(),
                        cameraTarget = detail.lat to detail.lng,
                        sheetExpanded = true,
                        error = null,
                    )
                }
            } catch (ex: Exception) {
                _state.update { it.copy(error = ex.message) }
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
            val response = api.spots(viewport.swLat, viewport.swLng, viewport.neLat, viewport.neLng, _state.value.hours)
            _state.update { it.copy(spots = response.spots, loading = false, error = null) }
        } catch (ex: Exception) {
            _state.update { it.copy(loading = false, error = ex.message) }
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
    )
}
