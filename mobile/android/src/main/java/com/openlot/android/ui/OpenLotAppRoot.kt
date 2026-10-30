package com.openlot.android.ui

import android.Manifest
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Snackbar
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TextField
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.openlot.android.data.UserLocationTracker
import com.openlot.android.ui.components.BlackPillButton
import com.openlot.android.ui.components.FeeHeadline
import com.openlot.android.ui.components.HoursStepper
import com.openlot.android.ui.components.SearchPill
import com.openlot.android.ui.components.SpotListRow
import com.openlot.android.ui.components.formatMoney
import com.openlot.android.ui.map.OpenLotMap
import com.openlot.android.ui.theme.CardShape
import com.openlot.android.ui.theme.CardWhite
import com.openlot.android.ui.theme.Ink
import com.openlot.android.ui.theme.InkMuted
import com.openlot.android.ui.theme.SheetShape
import com.openlot.android.ui.theme.SurfaceLight
import com.openlot.shared.model.OccupancyStatus

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OpenLotAppRoot(viewModel: MapViewModel = viewModel()) {
    val state by viewModel.state.collectAsState()
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = false)
    var addName by remember { mutableStateOf("") }
    val context = LocalContext.current

    val locationTracker = remember {
        UserLocationTracker(context) { lat, lng -> viewModel.setUserLocation(lat, lng) }
    }

    val permissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions(),
    ) { grants ->
        val granted = grants.values.any { it }
        if (granted) {
            locationTracker.start()
        } else {
            viewModel.setLocationPermissionNeeded(true)
        }
    }

    LaunchedEffect(Unit) {
        if (locationTracker.hasPermission()) {
            locationTracker.start()
        } else {
            permissionLauncher.launch(
                arrayOf(
                    Manifest.permission.ACCESS_FINE_LOCATION,
                    Manifest.permission.ACCESS_COARSE_LOCATION,
                ),
            )
        }
    }

    DisposableEffect(locationTracker) {
        onDispose { locationTracker.stop() }
    }

    Box(Modifier.fillMaxSize()) {
        OpenLotMap(
            spots = state.spots,
            route = state.route,
            cameraTarget = state.cameraTarget,
            userLat = state.userLat,
            userLng = state.userLng,
            mapStyleUrl = state.mapStyleUrl,
            onViewportChanged = viewModel::onViewportChanged,
            onSpotClick = viewModel::selectSpot,
            onCameraTargetConsumed = viewModel::clearCameraTarget,
            modifier = Modifier.fillMaxSize(),
        )

        Column(
            modifier = Modifier
                .fillMaxWidth()
                .statusBarsPadding()
                .padding(horizontal = 16.dp, vertical = 12.dp),
        ) {
            SearchPill(
                query = state.searchQuery,
                onQueryChange = viewModel::onSearchQuery,
            )
            Row(
                modifier = Modifier.padding(top = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                FilterChip(
                    label = "All",
                    selected = state.statusFilter == null,
                    onClick = { viewModel.setStatusFilter(null) },
                )
                FilterChip(
                    label = "Free",
                    selected = state.statusFilter == OccupancyStatus.FREE,
                    onClick = { viewModel.setStatusFilter(OccupancyStatus.FREE) },
                )
                FilterChip(
                    label = "Taken",
                    selected = state.statusFilter == OccupancyStatus.TAKEN,
                    onClick = { viewModel.setStatusFilter(OccupancyStatus.TAKEN) },
                )
            }
            if (state.searchResults.isNotEmpty()) {
                Column(
                    modifier = Modifier
                        .padding(top = 8.dp)
                        .shadow(8.dp, CardShape)
                        .clip(CardShape)
                        .background(CardWhite)
                        .fillMaxWidth(),
                ) {
                    state.searchResults.take(6).forEach { result ->
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { viewModel.onSearchResult(result) }
                                .padding(14.dp),
                        ) {
                            Text(text = result.title, color = Ink)
                            result.subtitle?.let {
                                Text(text = it, color = InkMuted, maxLines = 1)
                            }
                        }
                    }
                }
            } else if (state.searchQuery.isNotBlank()) {
                Text(
                    text = "No places found",
                    color = InkMuted,
                    modifier = Modifier
                        .padding(top = 8.dp)
                        .shadow(4.dp, CardShape)
                        .clip(CardShape)
                        .background(CardWhite)
                        .padding(12.dp),
                )
            }
        }

        Column(
            modifier = Modifier
                .align(Alignment.CenterEnd)
                .padding(end = 16.dp)
                .navigationBarsPadding(),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            FloatingActionButton(
                onClick = {
                    if (locationTracker.hasPermission()) {
                        locationTracker.start()
                        viewModel.recenterOnUser()
                    } else {
                        permissionLauncher.launch(
                            arrayOf(
                                Manifest.permission.ACCESS_FINE_LOCATION,
                                Manifest.permission.ACCESS_COARSE_LOCATION,
                            ),
                        )
                    }
                },
                containerColor = CardWhite,
                contentColor = Ink,
                modifier = Modifier.size(48.dp),
            ) {
                Text("◎", color = Ink)
            }
            FloatingActionButton(
                onClick = viewModel::findNearby,
                containerColor = CardWhite,
                contentColor = Ink,
                modifier = Modifier.size(48.dp),
            ) {
                Text("⊕", color = Ink)
            }
            FloatingActionButton(
                onClick = viewModel::refreshNow,
                containerColor = CardWhite,
                contentColor = Ink,
                modifier = Modifier.size(48.dp),
            ) {
                Text("↻", color = Ink)
            }
        }

        if (state.loading) {
            CircularProgressIndicator(
                modifier = Modifier
                    .align(Alignment.TopCenter)
                    .statusBarsPadding()
                    .padding(top = 72.dp)
                    .size(28.dp),
                color = Ink,
                strokeWidth = 3.dp,
            )
        }

        Text(
            text = "© OpenStreetMap",
            color = InkMuted,
            modifier = Modifier
                .align(Alignment.BottomStart)
                .navigationBarsPadding()
                .padding(start = 12.dp, bottom = 96.dp),
        )

        Column(
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .fillMaxWidth()
                .navigationBarsPadding()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            state.route?.let { route ->
                val provider = route.provider?.replaceFirstChar { it.uppercase() }
                val label = when {
                    route.available && route.durationSeconds != null ->
                        listOfNotNull(provider, "${((route.durationSeconds) / 60).toInt()} min")
                            .joinToString(" · ")
                    !route.available -> route.message ?: "Traffic unavailable"
                    else -> listOfNotNull(provider, "Route ready").joinToString(" · ")
                }
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .shadow(6.dp, CardShape)
                        .clip(CardShape)
                        .background(CardWhite)
                        .padding(12.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Text(label, color = Ink)
                    TextButton(onClick = viewModel::clearRoute) { Text("Clear") }
                }
            }

            PeekCard(
                title = state.selected?.name
                    ?: state.spots.firstOrNull()?.name
                    ?: if (state.spots.isEmpty() && !state.loading) "No bays in view" else "Nearby parking",
                subtitle = when {
                    state.selected?.fee?.hourlyRate != null ->
                        formatMoney(state.selected!!.fee.hourlyRate!!) + "/hr"
                    state.spots.firstOrNull()?.fee?.hourlyRate != null ->
                        formatMoney(state.spots.first().fee.hourlyRate!!) + "/hr"
                    state.locationPermissionNeeded -> "Enable location for better routes"
                    else -> "${state.spots.size} spots in view"
                },
                onOpen = {
                    val id = state.selected?.id ?: state.spots.firstOrNull()?.id
                    if (id != null) viewModel.selectSpot(id)
                },
                onAdd = if (state.features.userSpots) {
                    { viewModel.setShowAddSpot(true) }
                } else {
                    null
                },
            )
        }

        if (state.selected != null) {
            ModalBottomSheet(
                onDismissRequest = viewModel::clearSelection,
                sheetState = sheetState,
                shape = SheetShape,
                containerColor = CardWhite,
            ) {
                SpotSheet(
                    state = state,
                    onHours = viewModel::setHours,
                    onReport = viewModel::report,
                    onGo = viewModel::goToBay,
                    onSelect = viewModel::selectSpot,
                )
            }
        }

        if (state.showAddSpot) {
            AlertDialog(
                onDismissRequest = { viewModel.setShowAddSpot(false) },
                title = { Text("Add a missing bay") },
                text = {
                    Column {
                        Text(
                            "Drops a bay at the current map center " +
                                "(${"%.5f".format(state.mapCenterLat)}, ${"%.5f".format(state.mapCenterLng)}).",
                        )
                        Spacer(Modifier.height(8.dp))
                        TextField(
                            value = addName,
                            onValueChange = { addName = it },
                            placeholder = { Text("Optional name") },
                        )
                    }
                },
                confirmButton = {
                    TextButton(
                        onClick = {
                            viewModel.addMissingBay(addName.ifBlank { null })
                            addName = ""
                        },
                    ) { Text("Add") }
                },
                dismissButton = {
                    TextButton(onClick = { viewModel.setShowAddSpot(false) }) { Text("Cancel") }
                },
            )
        }

        state.error?.let { message ->
            Snackbar(
                modifier = Modifier
                    .align(Alignment.TopCenter)
                    .statusBarsPadding()
                    .padding(top = 72.dp, start = 16.dp, end = 16.dp),
                action = {
                    Row {
                        TextButton(onClick = viewModel::refreshNow) { Text("Retry") }
                        TextButton(onClick = viewModel::dismissError) { Text("OK") }
                    }
                },
            ) { Text(message) }
        }
    }
}

@Composable
private fun FilterChip(
    label: String,
    selected: Boolean,
    onClick: () -> Unit,
) {
    Text(
        text = label,
        color = if (selected) CardWhite else Ink,
        modifier = Modifier
            .shadow(4.dp, CardShape)
            .clip(CardShape)
            .background(if (selected) Ink else CardWhite)
            .clickable(onClick = onClick)
            .padding(horizontal = 12.dp, vertical = 8.dp),
    )
}

@Composable
private fun PeekCard(
    title: String,
    subtitle: String,
    onOpen: () -> Unit,
    onAdd: (() -> Unit)?,
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(10.dp, CardShape)
            .clip(CardShape)
            .background(CardWhite)
            .clickable(onClick = onOpen)
            .padding(16.dp),
    ) {
        Box(
            Modifier
                .align(Alignment.CenterHorizontally)
                .padding(bottom = 8.dp)
                .height(4.dp)
                .fillMaxWidth(0.12f)
                .clip(CardShape)
                .background(SurfaceLight),
        )
        Text(title, color = Ink, style = androidx.compose.material3.MaterialTheme.typography.headlineMedium)
        Text(subtitle, color = InkMuted)
        Spacer(Modifier.height(8.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            TextButton(onClick = onOpen) { Text("Details") }
            if (onAdd != null) {
                TextButton(onClick = onAdd) { Text("Add bay") }
            }
        }
    }
}

@Composable
private fun SpotSheet(
    state: MapUiState,
    onHours: (Double) -> Unit,
    onReport: (OccupancyStatus) -> Unit,
    onGo: () -> Unit,
    onSelect: (String) -> Unit,
) {
    val selected = state.selected ?: return
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .navigationBarsPadding()
            .padding(horizontal = 20.dp, vertical = 8.dp)
            .heightIn(max = 520.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Text(
            selected.name ?: "Parking spot",
            style = androidx.compose.material3.MaterialTheme.typography.headlineMedium,
            color = Ink,
        )
        Text(
            text = buildString {
                append(
                    when (selected.status) {
                        OccupancyStatus.FREE -> "Free now"
                        OccupancyStatus.TAKEN -> "Taken"
                        null -> "Unknown"
                    },
                )
                selected.statusUpdatedAt?.let { append(" · reported $it") }
            },
            color = InkMuted,
        )
        FeeHeadline(selected.fee)
        HoursStepper(hours = state.hours, onChange = onHours)
        if (state.features.routing) {
            BlackPillButton(text = "Go to this bay", onClick = onGo)
        }
        if (state.features.occupancyReports) {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
                TextButton(
                    onClick = { onReport(OccupancyStatus.TAKEN) },
                    modifier = Modifier.weight(1f),
                ) { Text("I'm parked") }
                TextButton(
                    onClick = { onReport(OccupancyStatus.FREE) },
                    modifier = Modifier.weight(1f),
                ) { Text("I left, it's free") }
            }
        }
        selected.fee.sourceNote?.let {
            Text(it, color = InkMuted, style = androidx.compose.material3.MaterialTheme.typography.bodyMedium)
        }
        Text("Nearby", color = Ink, style = androidx.compose.material3.MaterialTheme.typography.titleLarge)
        LazyColumn(verticalArrangement = Arrangement.spacedBy(4.dp)) {
            items(state.spots.take(12), key = { it.id }) { spot ->
                SpotListRow(
                    title = spot.name ?: "Parking",
                    subtitle = spot.fee.hourlyRate?.let { formatMoney(it) + "/hr" } ?: "Fee unknown",
                    status = spot.status,
                    onClick = { onSelect(spot.id) },
                )
            }
        }
        Spacer(Modifier.height(12.dp))
    }
}
