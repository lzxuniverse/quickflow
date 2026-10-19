<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Analytics & Statistics | QuickFlow MCP Telemetry</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #090d16;
            --bg-card: rgba(17, 25, 40, 0.75);
            --bg-card-hover: rgba(23, 33, 53, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
            --border-cyan: rgba(34, 211, 238, 0.3);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-cyan: #22d3ee;
            --accent-emerald: #10b981;
            --accent-indigo: #6366f1;
            --accent-amber: #f59e0b;
            --accent-red: #ef4444;
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            font-family: var(--font-body);
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 10%, rgba(34, 211, 238, 0.06) 0%, transparent 40%),
                radial-gradient(circle at 50% 90%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
            background-attachment: fixed;
            padding: 2.5rem 1.5rem 5rem;
        }

        .container {
            max-width: 1240px;
            margin: 0 auto;
        }

        /* Top Nav & Breadcrumbs */
        .top-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.4rem 0.9rem;
            background: rgba(34, 211, 238, 0.08);
            border: 1px solid var(--border-cyan);
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--accent-cyan);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.1rem;
            border-radius: 0.6rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
            border: none;
            color: #fff;
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.25);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
            transform: translateY(-1px);
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2.5rem;
        }

        .page-title {
            font-family: var(--font-display);
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-desc {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 750px;
            line-height: 1.6;
        }

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            backdrop-filter: blur(12px);
            transition: transform 0.2s ease, border-color 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--card-accent, var(--accent-indigo)), transparent);
            opacity: 0.6;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.16);
        }

        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .metric-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.04);
            font-size: 1rem;
        }

        .metric-val {
            font-family: var(--font-display);
            font-size: 2.2rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .metric-sub {
            font-size: 0.825rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tag-active {
            color: var(--accent-emerald);
            background: rgba(16, 185, 129, 0.12);
            padding: 0.15rem 0.45rem;
            border-radius: 0.3rem;
            font-weight: 600;
        }

        .tag-inactive {
            color: var(--accent-red);
            background: rgba(239, 68, 68, 0.12);
            padding: 0.15rem 0.45rem;
            border-radius: 0.3rem;
            font-weight: 600;
        }

        /* 2-Column Split: Countries + MCP Payload */
        .split-grid {
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            gap: 1.5rem;
        }

        @media (max-width: 960px) {
            .split-grid {
                grid-template-columns: 1fr;
            }
        }

        .panel-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.75rem;
            backdrop-filter: blur(12px);
        }

        .panel-title {
            font-family: var(--font-display);
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Countries list */
        .country-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .country-item:last-child {
            border-bottom: none;
        }

        .country-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.925rem;
        }

        .country-flag {
            font-size: 1.1rem;
        }

        .country-bar-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .country-bar {
            width: 100px;
            height: 6px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            overflow: hidden;
        }

        .country-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            border-radius: 999px;
        }

        .country-count {
            font-family: var(--font-mono);
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--accent-cyan);
            min-width: 24px;
            text-align: right;
        }

        /* Code block for MCP Tool match */
        .json-viewer {
            background: #060911;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.75rem;
            padding: 1.25rem;
            font-family: var(--font-mono);
            font-size: 0.85rem;
            color: #93c5fd;
            overflow-x: auto;
            line-height: 1.6;
        }

        .json-key { color: #38bdf8; }
        .json-string { color: #a5f3fc; }
        .json-num { color: #f472b6; }
        .json-bool { color: #fbbf24; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Top Nav -->
        <div class="top-nav">
            <div class="brand-badge">
                <span>🛡️ QuickFlow MCP Tool Telemetry</span>
                <span>•</span>
                <span>Ground Truth Cross-Reference</span>
            </div>
            <div class="nav-links">
                <a href="{{ route('properties.index') }}" class="btn btn-outline">
                    ← Back to Properties Catalog
                </a>
                <a href="/mcp" target="_blank" class="btn btn-primary">
                    ⚡ MCP Gateway Endpoint
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.5rem;">
                <h1 class="page-title" style="margin-bottom: 0;">Database Analytics Ground Truth</h1>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="{{ route('properties.stats') }}" class="btn {{ !request('tenant') && !request('tenant_id') ? 'btn-primary' : 'btn-outline' }}" style="font-size: 0.85rem; padding: 0.45rem 0.9rem;">
                        📊 Global (196)
                    </a>
                    <a href="{{ route('properties.stats', ['tenant' => 'alpha']) }}" class="btn {{ request('tenant') === 'alpha' ? 'btn-primary' : 'btn-outline' }}" style="font-size: 0.85rem; padding: 0.45rem 0.9rem;">
                        🇫🇷 Alpha (47)
                    </a>
                    <a href="{{ route('properties.stats', ['tenant' => 'beta']) }}" class="btn {{ request('tenant') === 'beta' ? 'btn-primary' : 'btn-outline' }}" style="font-size: 0.85rem; padding: 0.45rem 0.9rem;">
                        🇩🇪 Beta (39)
                    </a>
                </div>
            </div>
            <p class="page-desc">
                Live statistics calculated directly by <code>GetPropertyStatsTool</code>.
                @if(request('tenant') === 'alpha')
                    <strong style="color: #22d3ee;">[Scope: Tenant Alpha — Europe / France Portfolio]</strong>
                @elseif(request('tenant') === 'beta')
                    <strong style="color: #10b981;">[Scope: Tenant Beta — Americas / Nordics Portfolio]</strong>
                @else
                    <strong style="color: #cbd5e1;">[Scope: Global Unpartitioned — Raw Multi-Tenant Catalog]</strong>
                @endif
            </p>
        </div>

        <!-- Metric Cards -->
        <div class="metrics-grid">
            <!-- Metric 1: Total Properties -->
            <div class="metric-card" style="--card-accent: #22d3ee;">
                <div class="metric-header">
                    <span class="metric-label">Total Properties</span>
                    <span class="metric-icon">🏢</span>
                </div>
                <div class="metric-val">{{ number_format($stats['total_properties'] ?? 0) }}</div>
                <div class="metric-sub">
                    <span class="tag-active">{{ $stats['active_properties'] ?? 0 }} Active</span>
                    <span class="tag-inactive">{{ $stats['inactive_properties'] ?? 0 }} Inactive</span>
                </div>
            </div>

            <!-- Metric 2: Room Capacity -->
            <div class="metric-card" style="--card-accent: #6366f1;">
                <div class="metric-header">
                    <span class="metric-label">Total Rooms</span>
                    <span class="metric-icon">🛏️</span>
                </div>
                <div class="metric-val">{{ number_format($stats['total_rooms'] ?? 0) }}</div>
                <div class="metric-sub">
                    <span>Across <strong>{{ number_format($stats['total_room_types'] ?? 0) }}</strong> room categories</span>
                </div>
            </div>

            <!-- Metric 3: Average Rating -->
            <div class="metric-card" style="--card-accent: #f59e0b;">
                <div class="metric-header">
                    <span class="metric-label">Average Guest Rating</span>
                    <span class="metric-icon">⭐</span>
                </div>
                <div class="metric-val">{{ $stats['average_rating'] ?? 'N/A' }} <span style="font-size: 1.1rem; color: #94a3b8; font-weight: 400;">/ 5.0</span></div>
                <div class="metric-sub">
                    <span>Aggregated from verified guest reviews</span>
                </div>
            </div>

            <!-- Metric 4: Ingress Policy -->
            <div class="metric-card" style="--card-accent: #10b981;">
                <div class="metric-header">
                    <span class="metric-label">Ingress Security Mode</span>
                    <span class="metric-icon">🛡️</span>
                </div>
                <div class="metric-val" style="font-size: 1.6rem; color: #34d399;">READ-ONLY</div>
                <div class="metric-sub">
                    <span>Least privilege • 0.02ms mutation refusal</span>
                </div>
            </div>
        </div>

        <!-- Split Grid: Countries & Raw Tool JSON -->
        <div class="split-grid">
            <!-- Left: Top Countries -->
            <div class="panel-card">
                <div class="panel-title">
                    <span>Top Countries Distribution</span>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: normal;">Top 5 Regions</span>
                </div>
                <div class="countries-list">
                    @php
                        $maxCount = !empty($stats['top_countries']) ? max($stats['top_countries']) : 1;
                    @endphp
                    @forelse($stats['top_countries'] ?? [] as $country => $count)
                        @php
                            $pct = round(($count / $maxCount) * 100);
                        @endphp
                        <div class="country-item">
                            <div class="country-info">
                                <span class="country-flag">🌐</span>
                                <span>{{ $country }}</span>
                            </div>
                            <div class="country-bar-wrap">
                                <div class="country-bar">
                                    <div class="country-bar-fill" style="width: {{ $pct }}%;"></div>
                                </div>
                                <span class="country-count">{{ $count }}</span>
                            </div>
                        </div>
                    @empty
                        <div style="color: var(--text-secondary); font-size: 0.9rem;">No country distribution data available.</div>
                    @endforelse
                </div>
            </div>

            <!-- Right: Raw Tool Output (For ChatGPT comparison) -->
            <div class="panel-card">
                <div class="panel-title">
                    <span>Tool JSON Output (<code>properties_stats</code>)</span>
                    <span style="font-size: 0.8rem; color: var(--accent-cyan); font-family: var(--font-mono);">1:1 MCP Ground Truth</span>
                </div>
                <pre class="json-viewer">{{ json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</body>
</html>
