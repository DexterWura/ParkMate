package com.openlot.android.ui.map

import android.graphics.Color as AndroidColor
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.viewinterop.AndroidView
import com.openlot.android.ui.RouteUi
import com.openlot.android.ui.Viewport
import com.openlot.android.ui.theme.CongestionHeavy
import com.openlot.android.ui.theme.CongestionLow
import com.openlot.android.ui.theme.CongestionModerate
import com.openlot.android.ui.theme.CongestionSevere
import com.openlot.android.ui.theme.StatusUnknown
import com.openlot.shared.model.CongestionLevel
import com.openlot.shared.model.OccupancyStatus
import com.openlot.shared.model.SpotSummary
import org.maplibre.android.camera.CameraUpdateFactory
import org.maplibre.android.geometry.LatLng
import org.maplibre.android.maps.MapView
import org.maplibre.android.maps.MapLibreMap
import org.maplibre.android.maps.Style
import org.maplibre.android.style.expressions.Expression
import org.maplibre.android.style.layers.CircleLayer
import org.maplibre.android.style.layers.LineLayer
import org.maplibre.android.style.layers.PropertyFactory
import org.maplibre.android.style.sources.GeoJsonSource
import org.maplibre.geojson.Feature
import org.maplibre.geojson.FeatureCollection
import org.maplibre.geojson.LineString
import org.maplibre.geojson.Point

private const val DEFAULT_STYLE_URI = "https://tiles.openfreemap.org/styles/liberty"
private const val SPOTS_SOURCE = "openlot-spots"
private const val SPOTS_LAYER = "openlot-spots-layer"
private const val ROUTE_SOURCE = "openlot-route"
private const val ROUTE_LAYER = "openlot-route-layer"
private const val USER_SOURCE = "openlot-user"
private const val USER_LAYER = "openlot-user-layer"

@Composable
fun OpenLotMap(
    spots: List<SpotSummary>,
    route: RouteUi?,
    cameraTarget: Pair<Double, Double>?,
    userLat: Double?,
    userLng: Double?,
    mapStyleUrl: String = DEFAULT_STYLE_URI,
    onViewportChanged: (Viewport) -> Unit,
    onSpotClick: (String) -> Unit,
    onCameraTargetConsumed: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val context = LocalContext.current
    val mapView = remember {
        MapView(context).apply { onCreate(null) }
    }
    var mapRef by remember { mutableStateOf<MapLibreMap?>(null) }
    var styleReady by remember { mutableStateOf(false) }
    var appliedStyleUrl by remember { mutableStateOf<String?>(null) }

    DisposableEffect(mapView) {
        mapView.onStart()
        mapView.onResume()
        onDispose {
            mapView.onPause()
            mapView.onStop()
            mapView.onDestroy()
        }
    }

    AndroidView(
        factory = {
            mapView.getMapAsync { map ->
                mapRef = map
                map.uiSettings.isCompassEnabled = false
                map.uiSettings.isAttributionEnabled = true
                map.uiSettings.isLogoEnabled = false
                map.addOnCameraIdleListener {
                    val bounds = map.projection.visibleRegion.latLngBounds
                    onViewportChanged(
                        Viewport(
                            swLat = bounds.latitudeSouth,
                            swLng = bounds.longitudeWest,
                            neLat = bounds.latitudeNorth,
                            neLng = bounds.longitudeEast,
                        ),
                    )
                }
                map.addOnMapClickListener { point ->
                    val screen = map.projection.toScreenLocation(point)
                    val features = map.queryRenderedFeatures(screen, SPOTS_LAYER)
                    val id = features.firstOrNull()?.getStringProperty("id")
                    if (id != null) {
                        onSpotClick(id)
                        true
                    } else {
                        false
                    }
                }
            }
            mapView
        },
        modifier = modifier,
    )

    LaunchedEffect(mapStyleUrl, mapRef) {
        val map = mapRef ?: return@LaunchedEffect
        val styleUri = mapStyleUrl.ifBlank { DEFAULT_STYLE_URI }
        if (appliedStyleUrl == styleUri && styleReady) return@LaunchedEffect
        styleReady = false
        map.setStyle(Style.Builder().fromUri(styleUri)) { style ->
            ensureMapLayers(style)
            appliedStyleUrl = styleUri
            styleReady = true
        }
    }

    LaunchedEffect(spots, styleReady) {
        if (!styleReady) return@LaunchedEffect
        val map = mapRef ?: return@LaunchedEffect
        val style = map.style ?: return@LaunchedEffect
        val source = style.getSourceAs<GeoJsonSource>(SPOTS_SOURCE) ?: return@LaunchedEffect
        val features = spots.map { spot ->
            Feature.fromGeometry(Point.fromLngLat(spot.lng, spot.lat)).also {
                it.addStringProperty("id", spot.id)
                it.addStringProperty("color", colorHex(spot.status))
            }
        }
        source.setGeoJson(FeatureCollection.fromFeatures(features))
    }

    LaunchedEffect(userLat, userLng, styleReady) {
        if (!styleReady) return@LaunchedEffect
        val map = mapRef ?: return@LaunchedEffect
        val style = map.style ?: return@LaunchedEffect
        val source = style.getSourceAs<GeoJsonSource>(USER_SOURCE) ?: return@LaunchedEffect
        if (userLat == null || userLng == null) {
            source.setGeoJson(FeatureCollection.fromFeatures(emptyArray()))
            return@LaunchedEffect
        }
        source.setGeoJson(
            FeatureCollection.fromFeatures(
                arrayOf(Feature.fromGeometry(Point.fromLngLat(userLng, userLat))),
            ),
        )
    }

    LaunchedEffect(route, styleReady) {
        if (!styleReady) return@LaunchedEffect
        val map = mapRef ?: return@LaunchedEffect
        val style = map.style ?: return@LaunchedEffect
        val source = style.getSourceAs<GeoJsonSource>(ROUTE_SOURCE) ?: return@LaunchedEffect
        val layer = style.getLayer(ROUTE_LAYER) as? LineLayer
        if (route == null || !route.available || route.geometry.size < 2) {
            source.setGeoJson(FeatureCollection.fromFeatures(emptyArray()))
            return@LaunchedEffect
        }
        val points = route.geometry.map { Point.fromLngLat(it.lng, it.lat) }
        source.setGeoJson(Feature.fromGeometry(LineString.fromLngLats(points)))
        val dominant = route.congestion
            .groupingBy { it.second }
            .eachCount()
            .maxByOrNull { it.value }
            ?.key
        val color = when (dominant) {
            CongestionLevel.LOW -> CongestionLow
            CongestionLevel.MODERATE -> CongestionModerate
            CongestionLevel.HEAVY -> CongestionHeavy
            CongestionLevel.SEVERE -> CongestionSevere
            else -> StatusUnknown
        }
        layer?.setProperties(
            PropertyFactory.lineColor(
                AndroidColor.argb(
                    (color.alpha * 255).toInt(),
                    (color.red * 255).toInt(),
                    (color.green * 255).toInt(),
                    (color.blue * 255).toInt(),
                ),
            ),
        )
    }

    LaunchedEffect(cameraTarget) {
        val target = cameraTarget ?: return@LaunchedEffect
        mapRef?.animateCamera(CameraUpdateFactory.newLatLngZoom(LatLng(target.first, target.second), 15.0))
        onCameraTargetConsumed()
    }
}

private fun ensureMapLayers(style: Style) {
    if (style.getSource(SPOTS_SOURCE) == null) {
        style.addSource(GeoJsonSource(SPOTS_SOURCE, FeatureCollection.fromFeatures(emptyArray())))
        style.addLayer(
            CircleLayer(SPOTS_LAYER, SPOTS_SOURCE).withProperties(
                PropertyFactory.circleRadius(8f),
                PropertyFactory.circleStrokeWidth(2f),
                PropertyFactory.circleStrokeColor(AndroidColor.WHITE),
                PropertyFactory.circleColor(
                    Expression.toColor(Expression.get("color")),
                ),
            ),
        )
    }
    if (style.getSource(ROUTE_SOURCE) == null) {
        style.addSource(GeoJsonSource(ROUTE_SOURCE))
        style.addLayer(
            LineLayer(ROUTE_LAYER, ROUTE_SOURCE).withProperties(
                PropertyFactory.lineWidth(5f),
                PropertyFactory.lineColor(AndroidColor.parseColor("#111111")),
                PropertyFactory.lineOpacity(0.85f),
            ),
        )
    }
    if (style.getSource(USER_SOURCE) == null) {
        style.addSource(GeoJsonSource(USER_SOURCE, FeatureCollection.fromFeatures(emptyArray())))
        style.addLayer(
            CircleLayer(USER_LAYER, USER_SOURCE).withProperties(
                PropertyFactory.circleRadius(7f),
                PropertyFactory.circleColor(AndroidColor.parseColor("#2563EB")),
                PropertyFactory.circleStrokeWidth(3f),
                PropertyFactory.circleStrokeColor(AndroidColor.WHITE),
            ),
        )
    }
}

private fun colorHex(status: OccupancyStatus?): String = when (status) {
    OccupancyStatus.FREE -> "#1F9D55"
    OccupancyStatus.TAKEN -> "#D64545"
    null -> "#9A9A9A"
}
