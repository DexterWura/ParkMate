package com.openlot.server.config

data class AppConfig(
    val port: Int,
    val databaseUrl: String?,
    val databaseUser: String,
    val databasePassword: String,
    val mapboxToken: String?,
    val dataDir: String,
) {
    companion object {
        fun fromEnv(): AppConfig = AppConfig(
            port = System.getenv("PORT")?.toIntOrNull() ?: 8080,
            databaseUrl = System.getenv("DATABASE_URL")?.takeIf { it.isNotBlank() },
            databaseUser = System.getenv("DATABASE_USER") ?: "openlot",
            databasePassword = System.getenv("DATABASE_PASSWORD") ?: "openlot",
            mapboxToken = System.getenv("MAPBOX_TOKEN")?.takeIf { it.isNotBlank() },
            dataDir = System.getenv("DATA_DIR") ?: "data",
        )
    }
}
