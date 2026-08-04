-- QuickFlow OTA Demo Platform
-- Database Schema for PostgreSQL 17
-- Generates 8 schemas for 50-100 demo projects with logical multi-tenancy

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- AI pgvector extension (can fail if not installed on system, so handled gracefully)
DO $$
BEGIN
    CREATE EXTENSION IF NOT EXISTS vector;
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'pgvector extension not available. ai.embeddings.embedding will fall back to numerical array if needed.';
END;
$$;

-- Create Schemas
CREATE SCHEMA IF NOT EXISTS core;
CREATE SCHEMA IF NOT EXISTS booking;
CREATE SCHEMA IF NOT EXISTS channel;
CREATE SCHEMA IF NOT EXISTS pms;
CREATE SCHEMA IF NOT EXISTS reviews;
CREATE SCHEMA IF NOT EXISTS analytics;
CREATE SCHEMA IF NOT EXISTS ai;
CREATE SCHEMA IF NOT EXISTS integration;

-- =========================================================================
-- UTILITY FUNCTIONS
-- =========================================================================

-- PL/pgSQL function to generate UUIDv7 (Time-Ordered UUIDs) for PostgreSQL 17
CREATE OR REPLACE FUNCTION public.generate_uuid_v7()
RETURNS uuid AS $$
DECLARE
    timestamp_ms bigint;
    timestamp_hex text;
    random_hex text;
BEGIN
    -- Get current time in milliseconds since epoch
    timestamp_ms := floor(extract(epoch from clock_timestamp()) * 1000)::bigint;
    
    -- Format timestamp to 12 hex characters (48 bits)
    timestamp_hex := lpad(to_hex(timestamp_ms), 12, '0');
    
    -- Generate random hex bytes for the remaining part (excluding version & variant bits)
    random_hex := encode(gen_random_bytes(10), 'hex');
    
    -- Construct UUIDv7 format (version 7 at pos 13, variant 8 at pos 17)
    RETURN (
        substring(timestamp_hex from 1 for 8) || '-' ||
        substring(timestamp_hex from 9 for 4) || '-' ||
        '7' || substring(random_hex from 1 for 3) || '-' ||
        '8' || substring(random_hex from 4 for 3) || '-' ||
        substring(random_hex from 7 for 12)
    )::uuid;
END;
$$ LANGUAGE plpgsql;

-- =========================================================================
-- SCHEMA: core (Property Management)
-- =========================================================================

CREATE TABLE core.projects (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    name VARCHAR(150) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    updated_by VARCHAR(100)
);

CREATE TABLE core.properties (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    name VARCHAR(200) NOT NULL,
    type VARCHAR(50) NOT NULL, -- hotel, resort, apartment, villa, hostel
    address JSONB NOT NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    deleted_at TIMESTAMP WITH TIME ZONE
);

CREATE TABLE core.room_types (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    property_id UUID NOT NULL REFERENCES core.properties(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL, -- Deluxe Room, Presidential Suite, Single Room
    base_capacity INTEGER NOT NULL DEFAULT 2,
    base_price NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    deleted_at TIMESTAMP WITH TIME ZONE
);

CREATE TABLE core.rooms (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    room_type_id UUID NOT NULL REFERENCES core.room_types(id) ON DELETE CASCADE,
    room_number VARCHAR(50) NOT NULL,
    floor VARCHAR(20),
    status VARCHAR(50) NOT NULL DEFAULT 'available', -- available, occupied, maintenance, dirty
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    deleted_at TIMESTAMP WITH TIME ZONE
);

CREATE TABLE core.amenities (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    name VARCHAR(100) NOT NULL, -- WiFi, Swimming Pool, Air Conditioning, Breakfast
    category VARCHAR(50) NOT NULL -- room, property
);

CREATE TABLE core.property_amenities (
    property_id UUID REFERENCES core.properties(id) ON DELETE CASCADE,
    amenity_id UUID REFERENCES core.amenities(id) ON DELETE CASCADE,
    PRIMARY KEY (property_id, amenity_id)
);

CREATE TABLE core.policies (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    property_id UUID NOT NULL REFERENCES core.properties(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL, -- cancellation, check_in_out, pet, smoking, extra_bed
    description TEXT NOT NULL,
    rules JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================================
-- SCHEMA: booking (Booking Engine)
-- =========================================================================

CREATE TABLE booking.guests (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50),
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE
);

CREATE TABLE booking.reservations (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    guest_id UUID NOT NULL REFERENCES booking.guests(id) ON DELETE RESTRICT,
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- pending, confirmed, checked_in, checked_out, cancelled
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    total_amount NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    source VARCHAR(50) NOT NULL DEFAULT 'booking_engine', -- booking_engine, channel_manager, walk_in
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    deleted_at TIMESTAMP WITH TIME ZONE
);

CREATE TABLE booking.reservation_rooms (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    reservation_id UUID NOT NULL REFERENCES booking.reservations(id) ON DELETE CASCADE,
    room_id UUID NOT NULL REFERENCES core.rooms(id) ON DELETE RESTRICT,
    price_per_night NUMERIC(12, 2) NOT NULL,
    guest_names JSONB -- array of guest names staying in this specific room
);

CREATE TABLE booking.payments (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    reservation_id UUID NOT NULL REFERENCES booking.reservations(id) ON DELETE CASCADE,
    amount NUMERIC(12, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- pending, completed, failed, refunded
    payment_method VARCHAR(50) NOT NULL, -- credit_card, bank_transfer, stripe, paypal
    transaction_reference VARCHAR(200),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE booking.invoices (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    reservation_id UUID NOT NULL REFERENCES booking.reservations(id) ON DELETE CASCADE,
    invoice_number VARCHAR(100) UNIQUE NOT NULL,
    issue_date DATE NOT NULL DEFAULT CURRENT_DATE,
    due_date DATE NOT NULL,
    subtotal NUMERIC(12, 2) NOT NULL,
    tax NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    total NUMERIC(12, 2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'unpaid', -- unpaid, paid, void
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================================
-- SCHEMA: channel (OTA Channel Manager)
-- =========================================================================

CREATE TABLE channel.ota_channels (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    name VARCHAR(100) NOT NULL, -- Booking.com, Airbnb, Expedia, Agoda
    api_base_url VARCHAR(255),
    status VARCHAR(50) NOT NULL DEFAULT 'active'
);

CREATE TABLE channel.channel_mappings (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    ota_channel_id UUID NOT NULL REFERENCES channel.ota_channels(id) ON DELETE RESTRICT,
    property_id UUID NOT NULL REFERENCES core.properties(id) ON DELETE CASCADE,
    room_type_id UUID NOT NULL REFERENCES core.room_types(id) ON DELETE CASCADE,
    ota_room_type_id VARCHAR(150) NOT NULL,
    ota_rate_plan_id VARCHAR(150),
    sync_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE channel.availability_sync (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    channel_mapping_id UUID NOT NULL REFERENCES channel.channel_mappings(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    available_rooms INTEGER NOT NULL DEFAULT 0,
    last_synced_at TIMESTAMP WITH TIME ZONE,
    sync_status VARCHAR(50) NOT NULL DEFAULT 'pending' -- pending, synced, failed
);

CREATE TABLE channel.rate_sync (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    channel_mapping_id UUID NOT NULL REFERENCES channel.channel_mappings(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    price NUMERIC(12, 2) NOT NULL,
    last_synced_at TIMESTAMP WITH TIME ZONE,
    sync_status VARCHAR(50) NOT NULL DEFAULT 'pending' -- pending, synced, failed
);

-- Note: channel.sync_logs is PARTITIONED BY RANGE (created_at)
CREATE TABLE channel.sync_logs (
    id UUID NOT NULL,
    project_id UUID NOT NULL,
    channel_mapping_id UUID NOT NULL,
    sync_type VARCHAR(50) NOT NULL, -- availability, rate, reservation_import
    request_payload JSONB,
    response_payload JSONB,
    status VARCHAR(50) NOT NULL, -- success, failure
    error_message TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (created_at);

-- =========================================================================
-- SCHEMA: pms (PMS Integrations)
-- =========================================================================

CREATE TABLE pms.pms_providers (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    name VARCHAR(100) NOT NULL, -- Opera, Cloudbeds, Mews, eZee
    version VARCHAR(50)
);

CREATE TABLE pms.pms_connections (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    pms_provider_id UUID NOT NULL REFERENCES pms.pms_providers(id) ON DELETE RESTRICT,
    property_id UUID NOT NULL REFERENCES core.properties(id) ON DELETE CASCADE,
    credentials JSONB NOT NULL, -- encrypted API keys, secrets, client_ids
    status VARCHAR(50) NOT NULL DEFAULT 'connected', -- connected, disconnected, auth_failed
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pms.pms_sync_jobs (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    pms_connection_id UUID NOT NULL REFERENCES pms.pms_connections(id) ON DELETE CASCADE,
    sync_type VARCHAR(50) NOT NULL, -- pull_bookings, push_rates, sync_inventory, sync_guests
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- pending, running, completed, failed
    started_at TIMESTAMP WITH TIME ZONE,
    completed_at TIMESTAMP WITH TIME ZONE,
    logs TEXT
);

-- =========================================================================
-- SCHEMA: reviews (Reputation Management)
-- =========================================================================

CREATE TABLE reviews.review_sources (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    name VARCHAR(100) NOT NULL, -- Google, TripAdvisor, Booking.com, Airbnb
    source_type VARCHAR(50) NOT NULL DEFAULT 'scraped' -- api, scraped, manual
);

CREATE TABLE reviews.reviews (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    property_id UUID NOT NULL REFERENCES core.properties(id) ON DELETE CASCADE,
    review_source_id UUID NOT NULL REFERENCES reviews.review_sources(id) ON DELETE RESTRICT,
    guest_name VARCHAR(150) NOT NULL,
    rating NUMERIC(3,1) NOT NULL,
    title VARCHAR(255),
    content TEXT NOT NULL,
    sentiment VARCHAR(20), -- positive, neutral, negative
    source_review_id VARCHAR(100), -- original ID on TripAdvisor/Google
    reviewed_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reviews.review_replies (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    review_id UUID NOT NULL REFERENCES reviews.reviews(id) ON DELETE CASCADE,
    author_name VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    is_ai_generated BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================================
-- SCHEMA: analytics (Hospitality Analytics - Partitioned)
-- =========================================================================

CREATE TABLE analytics.daily_metrics (
    id UUID NOT NULL,
    project_id UUID NOT NULL,
    property_id UUID NOT NULL,
    date DATE NOT NULL,
    occupancy_rate NUMERIC(5,2) NOT NULL DEFAULT 0.00,
    adr NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    revpar NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    total_rooms INTEGER NOT NULL,
    occupied_rooms INTEGER NOT NULL,
    PRIMARY KEY (id, date)
) PARTITION BY RANGE (date);

CREATE TABLE analytics.revenue_metrics (
    id UUID NOT NULL,
    project_id UUID NOT NULL,
    property_id UUID NOT NULL,
    date DATE NOT NULL,
    booking_source VARCHAR(50) NOT NULL, -- direct, booking_com, airbnb
    revenue NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    commission NUMERIC(12,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id, date)
) PARTITION BY RANGE (date);

CREATE TABLE analytics.occupancy_metrics (
    id UUID NOT NULL,
    project_id UUID NOT NULL,
    property_id UUID NOT NULL,
    room_type_id UUID NOT NULL,
    date DATE NOT NULL,
    allotment INTEGER NOT NULL, -- total rooms available for sale
    sold INTEGER NOT NULL, -- total rooms sold
    PRIMARY KEY (id, date)
) PARTITION BY RANGE (date);

CREATE TABLE analytics.traffic_sources (
    id UUID NOT NULL,
    project_id UUID NOT NULL,
    date DATE NOT NULL,
    source VARCHAR(100) NOT NULL, -- Google organic, Direct, Facebook ads, Google Ads
    sessions INTEGER NOT NULL DEFAULT 0,
    conversions INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (id, date)
) PARTITION BY RANGE (date);

-- =========================================================================
-- SCHEMA: ai (AI Layer)
-- =========================================================================

CREATE TABLE ai.knowledge_documents (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ai.embeddings (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    document_id UUID NOT NULL REFERENCES ai.knowledge_documents(id) ON DELETE CASCADE,
    chunk_content TEXT NOT NULL,
    -- Store 1536 dimension OpenAI embedding vector
    -- Falls back to real[] if pgvector extension isn't loaded
    embedding vector(1536) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ai.mcp_tools (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    name VARCHAR(100) UNIQUE NOT NULL, -- get_room_availability, sync_rates_to_ota
    description TEXT NOT NULL,
    parameters_schema JSONB NOT NULL -- JSON schema for parameters validation
);

CREATE TABLE ai.tool_executions (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    mcp_tool_id UUID NOT NULL REFERENCES ai.mcp_tools(id) ON DELETE RESTRICT,
    agent_id VARCHAR(100) NOT NULL,
    arguments JSONB NOT NULL,
    response JSONB,
    execution_time_ms INTEGER,
    status VARCHAR(50) NOT NULL, -- success, failure
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ai.ai_conversations (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    guest_id UUID REFERENCES booking.guests(id) ON DELETE SET NULL,
    channel VARCHAR(50) NOT NULL DEFAULT 'web_chat', -- web_chat, whatsapp, telegram
    conversation_history JSONB NOT NULL, -- list of role/content messages
    summary TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'active', -- active, resolved, escalated
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================================
-- SCHEMA: integration (Integration Layer)
-- =========================================================================

CREATE TABLE integration.ical_feeds (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    room_id UUID NOT NULL REFERENCES core.rooms(id) ON DELETE CASCADE,
    direction VARCHAR(20) NOT NULL, -- import, export
    feed_url VARCHAR(500) NOT NULL,
    last_synced_at TIMESTAMP WITH TIME ZONE,
    sync_interval_minutes INTEGER DEFAULT 15,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE integration.ical_events (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    ical_feed_id UUID NOT NULL REFERENCES integration.ical_feeds(id) ON DELETE CASCADE,
    uid VARCHAR(255) NOT NULL, -- UID from iCal RFC 5545
    summary VARCHAR(255),
    start_time TIMESTAMP WITH TIME ZONE NOT NULL,
    end_time TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE integration.webhooks (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    target_url VARCHAR(500) NOT NULL,
    events JSONB NOT NULL, -- list of subscribed events, e.g. ["booking.created", "booking.cancelled"]
    secret_key VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE integration.api_keys (
    id UUID PRIMARY KEY DEFAULT public.generate_uuid_v7(),
    project_id UUID NOT NULL REFERENCES core.projects(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    key_hash VARCHAR(255) UNIQUE NOT NULL,
    scopes JSONB NOT NULL, -- list of permissions, e.g., ["read:bookings", "write:bookings"]
    expires_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP WITH TIME ZONE
);

-- Note: integration.event_logs is PARTITIONED BY RANGE (created_at)
CREATE TABLE integration.event_logs (
    id UUID NOT NULL,
    project_id UUID NOT NULL,
    event_type VARCHAR(100) NOT NULL, -- webhook.triggered, api_key.validated, etc.
    payload JSONB NOT NULL,
    delivery_status VARCHAR(50) NOT NULL, -- delivered, failed, pending
    http_status INTEGER,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (created_at);

-- =========================================================================
-- DEFAULT PARTITIONS INITIALIZATION (Year 2026)
-- =========================================================================

-- Channel sync logs partitions
CREATE TABLE channel.sync_logs_y2026m08 PARTITION OF channel.sync_logs FOR VALUES FROM ('2026-08-01 00:00:00+00') TO ('2026-09-01 00:00:00+00');
CREATE TABLE channel.sync_logs_y2026m09 PARTITION OF channel.sync_logs FOR VALUES FROM ('2026-09-01 00:00:00+00') TO ('2026-10-01 00:00:00+00');
CREATE TABLE channel.sync_logs_y2026m10 PARTITION OF channel.sync_logs FOR VALUES FROM ('2026-10-01 00:00:00+00') TO ('2026-11-01 00:00:00+00');

-- Integration event logs partitions
CREATE TABLE integration.event_logs_y2026m08 PARTITION OF integration.event_logs FOR VALUES FROM ('2026-08-01 00:00:00+00') TO ('2026-09-01 00:00:00+00');
CREATE TABLE integration.event_logs_y2026m09 PARTITION OF integration.event_logs FOR VALUES FROM ('2026-09-01 00:00:00+00') TO ('2026-10-01 00:00:00+00');
CREATE TABLE integration.event_logs_y2026m10 PARTITION OF integration.event_logs FOR VALUES FROM ('2026-10-01 00:00:00+00') TO ('2026-11-01 00:00:00+00');

-- Analytics partitions
CREATE TABLE analytics.daily_metrics_y2026 PARTITION OF analytics.daily_metrics FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');
CREATE TABLE analytics.revenue_metrics_y2026 PARTITION OF analytics.revenue_metrics FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');
CREATE TABLE analytics.occupancy_metrics_y2026 PARTITION OF analytics.occupancy_metrics FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');
CREATE TABLE analytics.traffic_sources_y2026 PARTITION OF analytics.traffic_sources FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');

-- =========================================================================
-- INDEX RECOMMENDATIONS
-- =========================================================================

-- B-tree indexes for multi-tenant (project_id) performance
CREATE INDEX idx_properties_project ON core.properties(project_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_room_types_property ON core.room_types(property_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_rooms_room_type ON core.rooms(room_type_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_guests_project ON booking.guests(project_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_reservations_project ON booking.reservations(project_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_reservations_checkin_checkout ON booking.reservations(check_in, check_out);
CREATE INDEX idx_payments_reservation ON booking.payments(reservation_id);
CREATE INDEX idx_invoices_reservation ON booking.invoices(reservation_id);
CREATE INDEX idx_channel_mappings_project ON channel.channel_mappings(project_id);
CREATE INDEX idx_pms_connections_project ON pms.pms_connections(project_id);
CREATE INDEX idx_reviews_project ON reviews.reviews(project_id);
CREATE INDEX idx_ical_feeds_project ON integration.ical_feeds(project_id);
CREATE INDEX idx_webhooks_project ON integration.webhooks(project_id);
CREATE INDEX idx_api_keys_project ON integration.api_keys(project_id);

-- JSONB indexes for payload/filter searching
CREATE INDEX idx_properties_address_gin ON core.properties USING gin (address);
CREATE INDEX idx_api_keys_scopes_gin ON integration.api_keys USING gin (scopes);
CREATE INDEX idx_webhooks_events_gin ON integration.webhooks USING gin (events);

-- HNSW Vector index on AI embeddings for fast cosine similarity search (pgvector)
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_am WHERE amname = 'hnsw') THEN
        CREATE INDEX idx_embeddings_hnsw ON ai.embeddings USING hnsw (embedding vector_cosine_ops);
    ELSE
        -- Fallback to ivfflat if hnsw is not supported but vector exists
        IF EXISTS (SELECT 1 FROM pg_am WHERE amname = 'ivfflat') THEN
            CREATE INDEX idx_embeddings_ivfflat ON ai.embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100);
        END IF;
    END IF;
END;
$$;

-- =========================================================================
-- ROW LEVEL SECURITY (RLS) FOR MULTI-TENANCY ISOLATION
-- =========================================================================

-- Example configurations for enabling Row Level Security
ALTER TABLE core.properties ENABLE ROW LEVEL SECURITY;
ALTER TABLE booking.reservations ENABLE ROW LEVEL SECURITY;
ALTER TABLE booking.guests ENABLE ROW LEVEL SECURITY;
ALTER TABLE reviews.reviews ENABLE ROW LEVEL SECURITY;
ALTER TABLE ai.knowledge_documents ENABLE ROW LEVEL SECURITY;
ALTER TABLE ai.ai_conversations ENABLE ROW LEVEL SECURITY;

-- Creating standard RLS Policies based on current session variable 'app.current_project_id'
-- Before querying, the application should run: SET LOCAL app.current_project_id = 'your-uuid-here';

CREATE POLICY tenant_isolation_properties ON core.properties
    FOR ALL USING (project_id = NULLIF(current_setting('app.current_project_id', true), '')::UUID);

CREATE POLICY tenant_isolation_reservations ON booking.reservations
    FOR ALL USING (project_id = NULLIF(current_setting('app.current_project_id', true), '')::UUID);

CREATE POLICY tenant_isolation_guests ON booking.guests
    FOR ALL USING (project_id = NULLIF(current_setting('app.current_project_id', true), '')::UUID);

CREATE POLICY tenant_isolation_reviews ON reviews.reviews
    FOR ALL USING (project_id = NULLIF(current_setting('app.current_project_id', true), '')::UUID);

CREATE POLICY tenant_isolation_knowledge_documents ON ai.knowledge_documents
    FOR ALL USING (project_id = NULLIF(current_setting('app.current_project_id', true), '')::UUID);

CREATE POLICY tenant_isolation_ai_conversations ON ai.ai_conversations
    FOR ALL USING (project_id = NULLIF(current_setting('app.current_project_id', true), '')::UUID);
