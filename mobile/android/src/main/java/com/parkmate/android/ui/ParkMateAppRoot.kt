package com.parkmate.android.ui

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
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Snackbar
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TextField
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.parkmate.android.ui.components.BlackPillButton
import com.parkmate.android.ui.components.FeeHeadline
import com.parkmate.android.ui.components.HoursStepper
import com.parkmate.android.ui.components.SearchPill
import com.parkmate.android.ui.components.SpotListRow
import com.parkmate.android.ui.components.formatMoney
import com.parkmate.android.ui.map.ParkMateMap
import com.parkmate.android.ui.theme.CardShape
import com.parkmate.android.ui.theme.CardWhite
import com.parkmate.android.ui.theme.Ink
import com.parkmate.android.ui.theme.InkMuted
import com.parkmate.android.ui.theme.SheetShape
import com.parkmate.android.ui.theme.SurfaceLight
import com.parkmate.shared.model.OccupancyStatus

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ParkMateAppRoot(viewModel: MapViewModel = viewModel()) {
    val state by viewModel.state.collectAsState()
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = false)
    var addName by remember { mutableStateOf("") }

    Box(Modifier.fillMaxSize()) {
        ParkMateMap(
            spots = state.spots,
            route = state.route,
            cameraTarget = state.cameraTarget,
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
                        Text(
                            text = result.title,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { viewModel.onSearchResult(result) }
                                .padding(14.dp),
                            color = Ink,
                        )
                    }
                }
            }
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
                val label = when {
                    route.available && route.durationSeconds != null ->
                        "Traffic · ${((route.durationSeconds) / 60).toInt()} min"
                    !route.available -> route.message ?: "Traffic unavailable"
                    else -> "Route ready"
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

            val nearest = state.selected ?: state.spots.firstOrNull()?.let { null }
            PeekCard(
                title = state.selected?.name ?: state.spots.firstOrNull()?.name ?: "Nearby parking",
                subtitle = when {
                    state.selected?.fee?.hourlyRate != null ->
                        formatMoney(state.selected!!.fee.hourlyRate!!) + "/hr"
                    state.spots.firstOrNull()?.fee?.hourlyRate != null ->
                        formatMoney(state.spots.first().fee.hourlyRate!!) + "/hr"
                    else -> "${state.spots.size} spots in view"
                },
                onOpen = {
                    val id = state.selected?.id ?: state.spots.firstOrNull()?.id
                    if (id != null) viewModel.selectSpot(id)
                },
                onAdd = { viewModel.setShowAddSpot(true) },
            )
            // silence unused
            nearest.let { }
        }

        if (state.selected != null) {
            ModalBottomSheet(
                onDismissRequest = { },
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
                        Text("Drops a bay at your current map center (Harare default if unknown).")
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
                            val lat = state.cameraTarget?.first ?: state.userLat ?: -17.8292
                            val lng = state.cameraTarget?.second ?: state.userLng ?: 31.0522
                            viewModel.addMissingBay(addName.ifBlank { null }, lat, lng)
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
                    TextButton(onClick = viewModel::dismissError) { Text("OK") }
                },
            ) { Text(message) }
        }
    }
}

@Composable
private fun PeekCard(
    title: String,
    subtitle: String,
    onOpen: () -> Unit,
    onAdd: () -> Unit,
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
            TextButton(onClick = onAdd) { Text("Add bay") }
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
                append(when (selected.status) {
                    OccupancyStatus.FREE -> "Free now"
                    OccupancyStatus.TAKEN -> "Taken"
                    null -> "Unknown"
                })
                selected.statusUpdatedAt?.let { append(" · reported $it") }
            },
            color = InkMuted,
        )
        FeeHeadline(selected.fee)
        HoursStepper(hours = state.hours, onChange = onHours)
        BlackPillButton(text = "Go to this bay", onClick = onGo)
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
