package com.parkmate.android.ui.theme

import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

val Ink = Color(0xFF111111)
val InkMuted = Color(0xFF5C5C5C)
val SurfaceLight = Color(0xFFF7F7F5)
val CardWhite = Color(0xFFFFFFFF)
val StatusFree = Color(0xFF1F9D55)
val StatusTaken = Color(0xFFD64545)
val StatusUnknown = Color(0xFF9A9A9A)
val CongestionLow = Color(0xFF1F9D55)
val CongestionModerate = Color(0xFFE6A700)
val CongestionHeavy = Color(0xFFD64545)
val CongestionSevere = Color(0xFF8B1E1E)

private val ColorScheme = lightColorScheme(
    primary = Ink,
    onPrimary = Color.White,
    secondary = InkMuted,
    background = SurfaceLight,
    surface = CardWhite,
    onBackground = Ink,
    onSurface = Ink,
)

val SheetShape = RoundedCornerShape(topStart = 20.dp, topEnd = 20.dp)
val PillShape = RoundedCornerShape(999.dp)
val CardShape = RoundedCornerShape(20.dp)

@Composable
fun ParkMateTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = ColorScheme,
        typography = MaterialTheme.typography.copy(
            displayLarge = TextStyle(
                fontFamily = FontFamily.SansSerif,
                fontWeight = FontWeight.Bold,
                fontSize = 34.sp,
                color = Ink,
            ),
            headlineMedium = TextStyle(
                fontFamily = FontFamily.SansSerif,
                fontWeight = FontWeight.SemiBold,
                fontSize = 22.sp,
                color = Ink,
            ),
            titleLarge = TextStyle(
                fontFamily = FontFamily.SansSerif,
                fontWeight = FontWeight.SemiBold,
                fontSize = 18.sp,
                color = Ink,
            ),
            bodyLarge = TextStyle(
                fontFamily = FontFamily.SansSerif,
                fontWeight = FontWeight.Normal,
                fontSize = 16.sp,
                color = Ink,
            ),
            bodyMedium = TextStyle(
                fontFamily = FontFamily.SansSerif,
                fontWeight = FontWeight.Normal,
                fontSize = 14.sp,
                color = InkMuted,
            ),
            labelLarge = TextStyle(
                fontFamily = FontFamily.SansSerif,
                fontWeight = FontWeight.SemiBold,
                fontSize = 14.sp,
                color = Ink,
            ),
        ),
        content = content,
    )
}
