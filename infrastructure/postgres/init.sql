-- ─────────────────────────────────────────────────────────────
--  Equencia — PostgreSQL Init
--  Isolation multi-tenant : Row Level Security (RLS)
--  Exécuté une seule fois à la création du container
-- ─────────────────────────────────────────────────────────────

-- Extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ── Fonction helper : tenant courant ─────────────────────────
-- Chaque service SET app.current_tenant_id = 'uuid' avant ses requêtes
CREATE OR REPLACE FUNCTION current_tenant_id() RETURNS uuid AS $$
BEGIN
    RETURN current_setting('app.current_tenant_id', true)::uuid;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'app.current_tenant_id non défini — TenantMiddleware manquant';
END;
$$ LANGUAGE plpgsql STABLE;

-- ── Fonction helper : activer RLS sur une table ───────────────
-- Usage : SELECT enable_tenant_rls('employees');
CREATE OR REPLACE FUNCTION enable_tenant_rls(table_name text) RETURNS void AS $$
BEGIN
    EXECUTE format('ALTER TABLE %I ENABLE ROW LEVEL SECURITY', table_name);
    EXECUTE format('ALTER TABLE %I FORCE ROW LEVEL SECURITY', table_name);
    EXECUTE format(
        'CREATE POLICY tenant_isolation ON %I USING (tenant_id = current_tenant_id())',
        table_name
    );
    EXECUTE format(
        'CREATE POLICY tenant_insert ON %I FOR INSERT WITH CHECK (tenant_id = current_tenant_id())',
        table_name
    );
END;
$$ LANGUAGE plpgsql;

-- ── Rôle applicatif (services PHP) ───────────────────────────
-- Les services se connectent avec ce rôle (pas superuser)
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'equencia_app') THEN
        CREATE ROLE equencia_app LOGIN PASSWORD 'equencia_app_secret';
    END IF;
END $$;

GRANT CONNECT ON DATABASE equencia TO equencia_app;
GRANT USAGE ON SCHEMA public TO equencia_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO equencia_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO equencia_app;

-- ── Table tenants (partagée entre tous les services) ──────────
-- Cette table N'a PAS de RLS — elle est le pivot de l'isolation
CREATE TABLE IF NOT EXISTS tenants (
    id              UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(100) NOT NULL,
    slug            VARCHAR(50)  NOT NULL UNIQUE,
    plan            VARCHAR(20)  NOT NULL DEFAULT 'trial' CHECK (plan IN ('trial','starter','business','pro','enterprise')),
    status          VARCHAR(20)  NOT NULL DEFAULT 'trial' CHECK (status IN ('trial','active','suspended','cancelled')),
    employee_limit  INTEGER     NOT NULL DEFAULT 10,
    trial_ends_at   TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_tenants_slug ON tenants(slug);
CREATE INDEX IF NOT EXISTS idx_tenants_status ON tenants(status);

-- ── Table users (auth service) ────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID        NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    email           VARCHAR(180) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    role            VARCHAR(20)  NOT NULL DEFAULT 'reader' CHECK (role IN ('super_admin','admin','manager','hr','agent','reader')),
    phone_whatsapp  VARCHAR(20),
    is_active       BOOLEAN     NOT NULL DEFAULT TRUE,
    last_login_at   TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_users_tenant    ON users(tenant_id);
CREATE INDEX IF NOT EXISTS idx_users_email     ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_tenant_role ON users(tenant_id, role);

SELECT enable_tenant_rls('users');

-- ── Table refresh_tokens ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id   UUID        NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    user_id     UUID        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash  CHAR(64)    NOT NULL UNIQUE,
    expires_at  TIMESTAMPTZ NOT NULL,
    revoked_at  TIMESTAMPTZ,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_refresh_tokens_hash ON refresh_tokens(token_hash);
CREATE INDEX IF NOT EXISTS idx_refresh_tokens_user ON refresh_tokens(user_id);

SELECT enable_tenant_rls('refresh_tokens');

-- ── Trigger : updated_at automatique ─────────────────────────
CREATE OR REPLACE FUNCTION update_updated_at() RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE TRIGGER tenants_updated_at
    BEFORE UPDATE ON tenants
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE OR REPLACE TRIGGER users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- ── Tenant de test (développement uniquement) ─────────────────
INSERT INTO tenants (id, name, slug, plan, status, employee_limit)
VALUES (
    '00000000-0000-0000-0000-000000000001',
    'Equencia Dev',
    'equencia-dev',
    'enterprise',
    'active',
    9999
) ON CONFLICT (slug) DO NOTHING;
