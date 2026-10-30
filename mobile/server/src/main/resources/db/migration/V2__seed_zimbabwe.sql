-- City Parking facilities (Harare) seeded before Overpass fills the rest.
INSERT INTO spots (id, name, lat, lng, parking_type, access_type, capacity, osm_id, osm_fee, osm_charge, city, source, created_at, updated_at)
VALUES
('seed-samora-machel', 'Samora Machel Parkade', -17.8292, 31.0522, 'multi-storey', 'yes', NULL, NULL, 'yes', NULL, 'Harare', 'CITY_PARKING', '2024-01-01T00:00:00Z', '2024-01-01T00:00:00Z'),
('seed-julius-nyerere', 'Julius Nyerere Parkade', -17.8310, 31.0475, 'multi-storey', 'yes', NULL, NULL, 'yes', NULL, 'Harare', 'CITY_PARKING', '2024-01-01T00:00:00Z', '2024-01-01T00:00:00Z'),
('seed-simon-muzenda', 'Simon Muzenda Parking Lot', -17.8275, 31.0488, 'surface', 'yes', NULL, NULL, 'yes', NULL, 'Harare', 'CITY_PARKING', '2024-01-01T00:00:00Z', '2024-01-01T00:00:00Z'),
('seed-rufaro', 'Rufaro Stadium Parking Lot', -17.8635, 31.0345, 'surface', 'yes', NULL, NULL, 'yes', NULL, 'Harare', 'CITY_PARKING', '2024-01-01T00:00:00Z', '2024-01-01T00:00:00Z'),
('seed-harare-cbd-street', 'Harare CBD On-Street Sample', -17.8277, 31.0530, 'lane', 'yes', NULL, NULL, 'yes', NULL, 'Harare', 'CITY_PARKING', '2024-01-01T00:00:00Z', '2024-01-01T00:00:00Z');

INSERT INTO fee_rules (id, city, kind, amount, currency, unit, source_note, effective_from, spot_id)
VALUES
('fee-harare-on-street', 'Harare', 'ON_STREET', 1.00, 'USD', 'HOUR',
 'City Parking published rate (US$1/hour). SI 41 of 2026 gazetted a US$0.50 cap that was not yet what drivers were being charged.',
 '2024-01-01', NULL),
('fee-bulawayo-on-street', 'Bulawayo', 'ON_STREET', 1.00, 'USD', 'HOUR',
 'Bulawayo published on-street rate (US$1/hour). SI 41 of 2026 gazetted a US$0.50 cap that was not yet consistently applied.',
 '2024-01-01', NULL),
('fee-samora', 'Harare', 'PARKADE', 1.00, 'USD', 'HOUR',
 'City Parking published rate for parkades/lots.',
 '2024-01-01', 'seed-samora-machel'),
('fee-julius', 'Harare', 'PARKADE', 1.00, 'USD', 'HOUR',
 'City Parking published rate for parkades/lots.',
 '2024-01-01', 'seed-julius-nyerere'),
('fee-simon', 'Harare', 'LOT', 1.00, 'USD', 'HOUR',
 'City Parking published rate for parkades/lots.',
 '2024-01-01', 'seed-simon-muzenda'),
('fee-rufaro', 'Harare', 'LOT', 1.00, 'USD', 'HOUR',
 'City Parking published rate for parkades/lots.',
 '2024-01-01', 'seed-rufaro'),
('fee-cbd-sample', 'Harare', 'ON_STREET', 1.00, 'USD', 'HOUR',
 'City Parking published rate (US$1/hour).',
 '2024-01-01', 'seed-harare-cbd-street');
