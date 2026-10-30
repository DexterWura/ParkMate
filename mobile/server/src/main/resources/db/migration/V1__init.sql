CREATE TABLE spots (
    id TEXT PRIMARY KEY,
    name TEXT,
    lat DOUBLE PRECISION NOT NULL,
    lng DOUBLE PRECISION NOT NULL,
    parking_type TEXT,
    access_type TEXT,
    capacity INTEGER,
    osm_id TEXT,
    osm_fee TEXT,
    osm_charge TEXT,
    city TEXT,
    source TEXT NOT NULL DEFAULT 'OSM',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE INDEX spots_lat_lng_idx ON spots (lat, lng);
CREATE UNIQUE INDEX spots_osm_id_uidx ON spots (osm_id) WHERE osm_id IS NOT NULL;

CREATE TABLE occupancy_reports (
    id TEXT PRIMARY KEY,
    spot_id TEXT NOT NULL,
    status TEXT NOT NULL,
    device_id TEXT NOT NULL,
    created_at TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE
);

CREATE INDEX occupancy_reports_spot_expires_idx ON occupancy_reports (spot_id, expires_at);
CREATE INDEX occupancy_reports_device_created_idx ON occupancy_reports (device_id, created_at);

CREATE TABLE fee_rules (
    id TEXT PRIMARY KEY,
    city TEXT NOT NULL,
    kind TEXT NOT NULL,
    amount DOUBLE PRECISION NOT NULL,
    currency TEXT NOT NULL,
    unit TEXT NOT NULL,
    source_note TEXT NOT NULL,
    effective_from TEXT NOT NULL,
    spot_id TEXT,
    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE SET NULL
);

CREATE INDEX fee_rules_city_kind_idx ON fee_rules (city, kind);

CREATE TABLE ingest_cache (
    cache_key TEXT PRIMARY KEY,
    fetched_at TEXT NOT NULL
);

CREATE TABLE search_cache (
    query_key TEXT PRIMARY KEY,
    payload TEXT NOT NULL,
    fetched_at TEXT NOT NULL
);

CREATE TABLE route_cache (
    cache_key TEXT PRIMARY KEY,
    payload TEXT NOT NULL,
    fetched_at TEXT NOT NULL
);
