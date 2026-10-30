package com.parkmate.server.db

import com.parkmate.shared.model.OccupancyStatus
import java.sql.Connection
import java.sql.ResultSet
import java.time.Instant
import java.util.UUID

data class SpotRow(
    val id: String,
    val name: String?,
    val lat: Double,
    val lng: Double,
    val parkingType: String?,
    val accessType: String?,
    val capacity: Int?,
    val osmId: String?,
    val osmFee: String?,
    val osmCharge: String?,
    val city: String?,
    val source: String,
    val status: OccupancyStatus?,
    val statusUpdatedAt: Instant?,
)

data class FeeRuleRow(
    val id: String,
    val city: String,
    val kind: String,
    val amount: Double,
    val currency: String,
    val unit: String,
    val sourceNote: String,
    val spotId: String?,
)

class SpotRepository(private val db: DatabaseFactory) {

    fun findInViewport(swLat: Double, swLng: Double, neLat: Double, neLng: Double): List<SpotRow> {
        return db.withConnection { conn ->
            val sql = """
                SELECT s.*, r.status AS report_status, r.created_at AS report_created_at
                FROM spots s
                LEFT JOIN (
                    SELECT o.spot_id, o.status, o.created_at
                    FROM occupancy_reports o
                    INNER JOIN (
                        SELECT spot_id, MAX(created_at) AS max_created
                        FROM occupancy_reports
                        WHERE expires_at > ?
                        GROUP BY spot_id
                    ) latest ON latest.spot_id = o.spot_id AND latest.max_created = o.created_at
                    WHERE o.expires_at > ?
                ) r ON r.spot_id = s.id
                WHERE s.lat BETWEEN ? AND ? AND s.lng BETWEEN ? AND ?
            """.trimIndent()
            val now = Instant.now().toString()
            conn.prepareStatement(sql).use { ps ->
                ps.setString(1, now)
                ps.setString(2, now)
                ps.setDouble(3, minOf(swLat, neLat))
                ps.setDouble(4, maxOf(swLat, neLat))
                ps.setDouble(5, minOf(swLng, neLng))
                ps.setDouble(6, maxOf(swLng, neLng))
                ps.executeQuery().use { rs ->
                    val rows = mutableListOf<SpotRow>()
                    while (rs.next()) rows += mapSpot(rs)
                    rows
                }
            }
        }
    }

    fun findById(id: String): SpotRow? {
        return db.withConnection { conn ->
            val sql = """
                SELECT s.*, r.status AS report_status, r.created_at AS report_created_at
                FROM spots s
                LEFT JOIN (
                    SELECT o.spot_id, o.status, o.created_at
                    FROM occupancy_reports o
                    WHERE o.spot_id = ? AND o.expires_at > ?
                    ORDER BY o.created_at DESC
                    LIMIT 1
                ) r ON r.spot_id = s.id
                WHERE s.id = ?
            """.trimIndent()
            val now = Instant.now().toString()
            conn.prepareStatement(sql).use { ps ->
                ps.setString(1, id)
                ps.setString(2, now)
                ps.setString(3, id)
                ps.executeQuery().use { rs -> if (rs.next()) mapSpot(rs) else null }
            }
        }
    }

    fun searchByName(query: String, limit: Int = 10): List<SpotRow> {
        return db.withConnection { conn ->
            val sql = """
                SELECT s.*, r.status AS report_status, r.created_at AS report_created_at
                FROM spots s
                LEFT JOIN (
                    SELECT o.spot_id, o.status, o.created_at
                    FROM occupancy_reports o
                    INNER JOIN (
                        SELECT spot_id, MAX(created_at) AS max_created
                        FROM occupancy_reports
                        WHERE expires_at > ?
                        GROUP BY spot_id
                    ) latest ON latest.spot_id = o.spot_id AND latest.max_created = o.created_at
                    WHERE o.expires_at > ?
                ) r ON r.spot_id = s.id
                WHERE lower(s.name) LIKE ?
                LIMIT ?
            """.trimIndent()
            val now = Instant.now().toString()
            conn.prepareStatement(sql).use { ps ->
                ps.setString(1, now)
                ps.setString(2, now)
                ps.setString(3, "%${query.lowercase()}%")
                ps.setInt(4, limit)
                ps.executeQuery().use { rs ->
                    val rows = mutableListOf<SpotRow>()
                    while (rs.next()) rows += mapSpot(rs)
                    rows
                }
            }
        }
    }

    fun upsertOsmSpot(
        osmId: String,
        name: String?,
        lat: Double,
        lng: Double,
        parkingType: String?,
        accessType: String?,
        capacity: Int?,
        osmFee: String?,
        osmCharge: String?,
        city: String?,
    ): String {
        return db.withConnection { conn ->
            val existing = conn.prepareStatement("SELECT id FROM spots WHERE osm_id = ?").use { ps ->
                ps.setString(1, osmId)
                ps.executeQuery().use { rs -> if (rs.next()) rs.getString("id") else null }
            }
            val now = Instant.now().toString()
            if (existing != null) {
                conn.prepareStatement(
                    """
                    UPDATE spots SET name = COALESCE(?, name), lat = ?, lng = ?, parking_type = ?,
                    access_type = ?, capacity = ?, osm_fee = ?, osm_charge = ?, city = COALESCE(?, city),
                    updated_at = ? WHERE id = ?
                    """.trimIndent()
                ).use { ps ->
                    ps.setString(1, name)
                    ps.setDouble(2, lat)
                    ps.setDouble(3, lng)
                    ps.setString(4, parkingType)
                    ps.setString(5, accessType)
                    if (capacity == null) ps.setObject(6, null) else ps.setInt(6, capacity)
                    ps.setString(7, osmFee)
                    ps.setString(8, osmCharge)
                    ps.setString(9, city)
                    ps.setString(10, now)
                    ps.setString(11, existing)
                    ps.executeUpdate()
                }
                existing
            } else {
                val id = UUID.randomUUID().toString()
                conn.prepareStatement(
                    """
                    INSERT INTO spots (id, name, lat, lng, parking_type, access_type, capacity, osm_id,
                    osm_fee, osm_charge, city, source, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'OSM', ?, ?)
                    """.trimIndent()
                ).use { ps ->
                    ps.setString(1, id)
                    ps.setString(2, name)
                    ps.setDouble(3, lat)
                    ps.setDouble(4, lng)
                    ps.setString(5, parkingType)
                    ps.setString(6, accessType)
                    if (capacity == null) ps.setObject(7, null) else ps.setInt(7, capacity)
                    ps.setString(8, osmId)
                    ps.setString(9, osmFee)
                    ps.setString(10, osmCharge)
                    ps.setString(11, city)
                    ps.setString(12, now)
                    ps.setString(13, now)
                    ps.executeUpdate()
                }
                id
            }
        }
    }

    fun createUserSpot(name: String?, lat: Double, lng: Double): SpotRow {
        val id = UUID.randomUUID().toString()
        val now = Instant.now().toString()
        db.withConnection { conn ->
            conn.prepareStatement(
                """
                INSERT INTO spots (id, name, lat, lng, parking_type, access_type, capacity, osm_id,
                osm_fee, osm_charge, city, source, created_at, updated_at)
                VALUES (?, ?, ?, ?, NULL, 'yes', NULL, NULL, NULL, NULL, NULL, 'USER', ?, ?)
                """.trimIndent()
            ).use { ps ->
                ps.setString(1, id)
                ps.setString(2, name)
                ps.setDouble(3, lat)
                ps.setDouble(4, lng)
                ps.setString(5, now)
                ps.setString(6, now)
                ps.executeUpdate()
            }
        }
        return findById(id)!!
    }

    fun addReport(spotId: String, status: OccupancyStatus, deviceId: String, expiresAt: Instant): String {
        val id = UUID.randomUUID().toString()
        val now = Instant.now()
        db.withConnection { conn ->
            conn.prepareStatement(
                """
                INSERT INTO occupancy_reports (id, spot_id, status, device_id, created_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?)
                """.trimIndent()
            ).use { ps ->
                ps.setString(1, id)
                ps.setString(2, spotId)
                ps.setString(3, status.name)
                ps.setString(4, deviceId)
                ps.setString(5, now.toString())
                ps.setString(6, expiresAt.toString())
                ps.executeUpdate()
            }
        }
        return id
    }

    fun countRecentReports(deviceId: String, since: Instant): Int {
        return db.withConnection { conn ->
            conn.prepareStatement(
                "SELECT COUNT(*) AS c FROM occupancy_reports WHERE device_id = ? AND created_at > ?"
            ).use { ps ->
                ps.setString(1, deviceId)
                ps.setString(2, since.toString())
                ps.executeQuery().use { rs ->
                    rs.next()
                    rs.getInt("c")
                }
            }
        }
    }

    fun feeRulesFor(spotId: String?, city: String?): List<FeeRuleRow> {
        return db.withConnection { conn ->
            val rows = mutableListOf<FeeRuleRow>()
            if (spotId != null) {
                conn.prepareStatement("SELECT * FROM fee_rules WHERE spot_id = ?").use { ps ->
                    ps.setString(1, spotId)
                    ps.executeQuery().use { rs -> while (rs.next()) rows += mapFee(rs) }
                }
            }
            if (rows.isEmpty() && city != null) {
                conn.prepareStatement("SELECT * FROM fee_rules WHERE city = ? AND spot_id IS NULL").use { ps ->
                    ps.setString(1, city)
                    ps.executeQuery().use { rs -> while (rs.next()) rows += mapFee(rs) }
                }
            }
            rows
        }
    }

    fun getIngestFetchedAt(cacheKey: String): Instant? {
        return db.withConnection { conn ->
            conn.prepareStatement("SELECT fetched_at FROM ingest_cache WHERE cache_key = ?").use { ps ->
                ps.setString(1, cacheKey)
                ps.executeQuery().use { rs ->
                    if (rs.next()) Instant.parse(rs.getString("fetched_at")) else null
                }
            }
        }
    }

    fun putIngestFetchedAt(cacheKey: String, at: Instant) {
        db.withConnection { conn ->
            upsertCache(conn, "ingest_cache", cacheKey, at.toString(), null)
        }
    }

    fun getSearchCache(queryKey: String): String? {
        return db.withConnection { conn ->
            conn.prepareStatement("SELECT payload, fetched_at FROM search_cache WHERE query_key = ?").use { ps ->
                ps.setString(1, queryKey)
                ps.executeQuery().use { rs ->
                    if (!rs.next()) return@use null
                    val fetched = Instant.parse(rs.getString("fetched_at"))
                    if (fetched.isBefore(Instant.now().minusSeconds(86400))) null else rs.getString("payload")
                }
            }
        }
    }

    fun putSearchCache(queryKey: String, payload: String) {
        db.withConnection { conn ->
            upsertCache(conn, "search_cache", queryKey, Instant.now().toString(), payload)
        }
    }

    private fun upsertCache(conn: Connection, table: String, key: String, fetchedAt: String, payload: String?) {
        if (table == "ingest_cache") {
            conn.prepareStatement(
                "DELETE FROM ingest_cache WHERE cache_key = ?"
            ).use { ps ->
                ps.setString(1, key)
                ps.executeUpdate()
            }
            conn.prepareStatement(
                "INSERT INTO ingest_cache (cache_key, fetched_at) VALUES (?, ?)"
            ).use { ps ->
                ps.setString(1, key)
                ps.setString(2, fetchedAt)
                ps.executeUpdate()
            }
        } else {
            conn.prepareStatement("DELETE FROM search_cache WHERE query_key = ?").use { ps ->
                ps.setString(1, key)
                ps.executeUpdate()
            }
            conn.prepareStatement(
                "INSERT INTO search_cache (query_key, payload, fetched_at) VALUES (?, ?, ?)"
            ).use { ps ->
                ps.setString(1, key)
                ps.setString(2, payload)
                ps.setString(3, fetchedAt)
                ps.executeUpdate()
            }
        }
    }

    private fun mapSpot(rs: ResultSet): SpotRow {
        val statusRaw = rs.getString("report_status")
        val status = statusRaw?.let { OccupancyStatus.valueOf(it) }
        val updated = rs.getString("report_created_at")?.let { Instant.parse(it) }
        val capacity = rs.getObject("capacity") as? Number
        return SpotRow(
            id = rs.getString("id"),
            name = rs.getString("name"),
            lat = rs.getDouble("lat"),
            lng = rs.getDouble("lng"),
            parkingType = rs.getString("parking_type"),
            accessType = rs.getString("access_type"),
            capacity = capacity?.toInt(),
            osmId = rs.getString("osm_id"),
            osmFee = rs.getString("osm_fee"),
            osmCharge = rs.getString("osm_charge"),
            city = rs.getString("city"),
            source = rs.getString("source"),
            status = status,
            statusUpdatedAt = updated,
        )
    }

    private fun mapFee(rs: ResultSet): FeeRuleRow = FeeRuleRow(
        id = rs.getString("id"),
        city = rs.getString("city"),
        kind = rs.getString("kind"),
        amount = rs.getDouble("amount"),
        currency = rs.getString("currency"),
        unit = rs.getString("unit"),
        sourceNote = rs.getString("source_note"),
        spotId = rs.getString("spot_id"),
    )
}
