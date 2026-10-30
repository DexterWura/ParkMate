package com.openlot.android.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextField
import androidx.compose.material3.TextFieldDefaults
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import com.openlot.android.ui.theme.CardShape
import com.openlot.android.ui.theme.CardWhite
import com.openlot.android.ui.theme.Ink
import com.openlot.android.ui.theme.InkMuted
import com.openlot.android.ui.theme.OpenLotTheme
import com.openlot.android.ui.theme.PillShape
import com.openlot.android.ui.theme.StatusFree
import com.openlot.android.ui.theme.StatusTaken
import com.openlot.android.ui.theme.StatusUnknown
import com.openlot.android.ui.theme.SurfaceLight
import com.openlot.shared.model.FeePreview
import com.openlot.shared.model.MoneyAmount
import com.openlot.shared.model.OccupancyStatus

@Composable
fun SearchPill(
    query: String,
    onQueryChange: (String) -> Unit,
    modifier: Modifier = Modifier,
) {
    TextField(
        value = query,
        onValueChange = onQueryChange,
        modifier = modifier
            .fillMaxWidth()
            .shadow(8.dp, PillShape)
            .clip(PillShape)
            .background(CardWhite),
        placeholder = { Text("Search parking or place", color = InkMuted) },
        singleLine = true,
        colors = TextFieldDefaults.colors(
            focusedContainerColor = CardWhite,
            unfocusedContainerColor = CardWhite,
            disabledContainerColor = CardWhite,
            focusedIndicatorColor = Color.Transparent,
            unfocusedIndicatorColor = Color.Transparent,
            disabledIndicatorColor = Color.Transparent,
            cursorColor = Ink,
        ),
    )
}

@Composable
fun StatusMarkerDot(status: OccupancyStatus?, modifier: Modifier = Modifier) {
    val fill = when (status) {
        OccupancyStatus.FREE -> StatusFree
        OccupancyStatus.TAKEN -> StatusTaken
        null -> StatusUnknown
    }
    Box(
        modifier = modifier
            .size(18.dp)
            .border(2.dp, Color.White, CircleShape)
            .background(fill, CircleShape),
    )
}

@Composable
fun BlackPillButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    Button(
        onClick = onClick,
        enabled = enabled,
        modifier = modifier
            .fillMaxWidth()
            .height(52.dp),
        shape = PillShape,
        colors = ButtonDefaults.buttonColors(
            containerColor = Ink,
            contentColor = Color.White,
            disabledContainerColor = Ink.copy(alpha = 0.4f),
        ),
    ) {
        Text(text)
    }
}

@Composable
fun FeeHeadline(fee: FeePreview) {
    val rate = fee.hourlyRate
    val estimate = fee.estimate
    Column {
        Text(
            text = when {
                estimate != null -> formatMoney(estimate)
                rate != null -> formatMoney(rate) + "/hr"
                else -> "—"
            },
            color = Ink,
            style = androidx.compose.material3.MaterialTheme.typography.displayLarge,
        )
        Text(
            text = when {
                estimate != null && rate != null ->
                    "${formatMoney(rate)}/hr · ${fee.hours ?: 1.0}h"
                rate != null -> fee.sourceNote ?: "Hourly rate"
                else -> "Fee unknown"
            },
            color = InkMuted,
            style = androidx.compose.material3.MaterialTheme.typography.bodyMedium,
        )
    }
}

@Composable
fun HoursStepper(hours: Double, onChange: (Double) -> Unit) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp),
        modifier = Modifier
            .clip(CardShape)
            .background(SurfaceLight)
            .padding(horizontal = 12.dp, vertical = 8.dp),
    ) {
        OutlinedButton(onClick = { onChange(hours - 0.5) }, shape = PillShape) { Text("−") }
        Text("${hours}h", color = Ink)
        OutlinedButton(onClick = { onChange(hours + 0.5) }, shape = PillShape) { Text("+") }
    }
}

@Composable
fun SpotListRow(
    title: String,
    subtitle: String,
    status: OccupancyStatus?,
    onClick: () -> Unit,
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clip(CardShape)
            .clickable(onClick = onClick)
            .padding(12.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        StatusMarkerDot(status)
        Spacer(Modifier.width(12.dp))
        Column(Modifier = Modifier.weight(1f)) {
            Text(title, color = Ink, style = androidx.compose.material3.MaterialTheme.typography.titleLarge)
            Text(subtitle, color = InkMuted, style = androidx.compose.material3.MaterialTheme.typography.bodyMedium)
        }
    }
}

fun formatMoney(money: MoneyAmount): String {
    val amount = if (money.amount % 1.0 == 0.0) {
        money.amount.toInt().toString()
    } else {
        "%.2f".format(money.amount)
    }
    return "${money.currency} $amount"
}

@Preview(showBackground = true)
@Composable
private fun ComponentsPreview() {
    OpenLotTheme {
        Column(
            Modifier
                .background(SurfaceLight)
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            SearchPill(query = "", onQueryChange = {})
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                StatusMarkerDot(OccupancyStatus.FREE)
                StatusMarkerDot(OccupancyStatus.TAKEN)
                StatusMarkerDot(null)
            }
            FeeHeadline(
                FeePreview(
                    hourlyRate = MoneyAmount(1.0, "USD"),
                    estimate = MoneyAmount(2.0, "USD"),
                    hours = 2.0,
                    sourceNote = "City Parking published rate",
                    kind = null,
                ),
            )
            HoursStepper(hours = 1.0, onChange = {})
            BlackPillButton(text = "Go to this bay", onClick = {})
        }
    }
}
