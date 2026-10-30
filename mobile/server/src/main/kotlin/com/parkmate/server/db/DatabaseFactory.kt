package com.parkmate.server.db

import com.parkmate.server.config.AppConfig
import com.zaxxer.hikari.HikariConfig
import com.zaxxer.hikari.HikariDataSource
import org.flywaydb.core.Flyway
import org.slf4j.LoggerFactory
import java.io.File
import java.sql.Connection
import javax.sql.DataSource

class DatabaseFactory(private val config: AppConfig) {
    private val log = LoggerFactory.getLogger(DatabaseFactory::class.java)
    lateinit var dataSource: DataSource
        private set
    var dialect: Dialect = Dialect.SQLITE
        private set

    enum class Dialect { SQLITE, POSTGRES }

    fun connect(): DataSource {
        val hikari = HikariConfig()
        if (config.databaseUrl != null) {
            dialect = Dialect.POSTGRES
            hikari.jdbcUrl = config.databaseUrl
            hikari.username = config.databaseUser
            hikari.password = config.databasePassword
            hikari.driverClassName = "org.postgresql.Driver"
            log.info("Using PostgreSQL at {}", config.databaseUrl)
        } else {
            dialect = Dialect.SQLITE
            val dir = File(config.dataDir)
            if (!dir.exists()) dir.mkdirs()
            val dbFile = File(dir, "parkmate.db").absolutePath
            hikari.jdbcUrl = "jdbc:sqlite:$dbFile"
            hikari.driverClassName = "org.sqlite.JDBC"
            hikari.maximumPoolSize = 1
            log.info("Using SQLite at {}", dbFile)
        }
        hikari.maximumPoolSize = if (dialect == Dialect.SQLITE) 1 else 10
        dataSource = HikariDataSource(hikari)

        dataSource.connection.use { conn ->
            if (dialect == Dialect.SQLITE) {
                conn.createStatement().use { st ->
                    st.execute("PRAGMA foreign_keys = ON")
                }
            }
        }

        Flyway.configure()
            .dataSource(dataSource)
            .locations("classpath:db/migration")
            .baselineOnMigrate(true)
            .load()
            .migrate()

        if (dialect == Dialect.POSTGRES) {
            enablePostgisIfAvailable()
        }

        return dataSource
    }

    private fun enablePostgisIfAvailable() {
        try {
            dataSource.connection.use { conn ->
                conn.createStatement().use { st ->
                    st.execute("CREATE EXTENSION IF NOT EXISTS postgis")
                    st.execute(
                        """
                        DO $$
                        BEGIN
                          IF NOT EXISTS (
                            SELECT 1 FROM information_schema.columns
                            WHERE table_name = 'spots' AND column_name = 'location'
                          ) THEN
                            ALTER TABLE spots ADD COLUMN location geography(Point, 4326);
                            UPDATE spots SET location = ST_SetSRID(ST_MakePoint(lng, lat), 4326)::geography;
                            CREATE INDEX IF NOT EXISTS spots_location_gix ON spots USING GIST (location);
                          END IF;
                        END $$;
                        """.trimIndent()
                    )
                }
            }
            log.info("PostGIS geography column ready on spots")
        } catch (ex: Exception) {
            log.warn("PostGIS not available; continuing with lat/lng indexes: {}", ex.message)
        }
    }

    fun <T> withConnection(block: (Connection) -> T): T {
        dataSource.connection.use { conn ->
            if (dialect == Dialect.SQLITE) {
                conn.createStatement().use { it.execute("PRAGMA foreign_keys = ON") }
            }
            return block(conn)
        }
    }
}
