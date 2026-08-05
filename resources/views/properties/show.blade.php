<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $property->name }} | Property Details | QuickFlow Premium</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #090d16;
            --bg-card: rgba(17, 25, 40, 0.75);
            --bg-input: rgba(15, 23, 42, 0.6);
            --border-color: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(99, 102, 241, 0.4);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent: #6366f1;
            --accent-glow: rgba(99, 102, 241, 0.2);
            --accent-gradient: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
            --badge-active-bg: rgba(16, 185, 129, 0.12);
            --badge-active-color: #34d399;
            --badge-inactive-bg: rgba(239, 68, 68, 0.12);
            --badge-inactive-color: #f87171;
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
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
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.05) 0%, transparent 40%);
            background-attachment: fixed;
            padding-bottom: 5rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        /* Navigation Header */
        header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            text-decoration: none;
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.25s ease;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            transform: translateX(-3px);
        }

        /* Detail Banner Layout */
        .property-banner-large {
            height: 240px;
            border-radius: 24px;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .banner-glow {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0) 40%, rgba(9, 13, 22, 0.9) 100%);
            z-index: 1;
        }

        .banner-content {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            width: 100%;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .banner-title-area h1 {
            font-family: var(--font-display);
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .banner-badge-group {
            display: flex;
            gap: 0.75rem;
        }

        .badge {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            backdrop-filter: blur(8px);
        }

        .badge-status-active {
            background-color: var(--badge-active-bg);
            color: var(--badge-active-color);
            border: 1px solid rgba(52, 211, 153, 0.2);
        }

        .badge-status-inactive {
            background-color: var(--badge-inactive-bg);
            color: var(--badge-inactive-color);
            border: 1px solid rgba(248, 113, 113, 0.2);
        }

        .badge-currency {
            background: rgba(15, 23, 42, 0.6);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Overview Stats Row */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            backdrop-filter: blur(10px);
            transition: border-color 0.25s;
        }

        .stat-card:hover {
            border-color: rgba(99, 102, 241, 0.25);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .stat-meta {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Grid Layout for Info & Settings */
        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 900px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(10px);
        }

        .card-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title svg {
            color: var(--accent);
            width: 18px;
            height: 18px;
        }

        /* Info Fields */
        .info-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 600px) {
            .info-fields {
                grid-template-columns: 1fr;
            }
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .field-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .field-value {
            font-size: 0.95rem;
            color: var(--text-primary);
            line-height: 1.5;
        }

        .field-value a {
            color: var(--accent);
            text-decoration: none;
        }

        .field-value a:hover {
            text-decoration: underline;
        }

        /* Settings card list */
        .settings-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.05);
        }

        .setting-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .setting-name {
            color: var(--text-secondary);
        }

        .setting-val {
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Custom Table */
        .section-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            margin-top: 1rem;
            color: var(--text-primary);
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            margin-bottom: 3rem;
            backdrop-filter: blur(10px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 0.95rem;
            color: var(--text-primary);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .room-code {
            font-family: monospace;
            background: rgba(255, 255, 255, 0.04);
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            color: var(--text-secondary);
        }

        /* Reviews Cards Layout */
        .reviews-section {
            margin-bottom: 3rem;
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }

        .review-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .review-source {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: capitalize;
        }

        .stars {
            color: #fbbf24;
            font-size: 1rem;
            display: inline-flex;
            gap: 0.1rem;
        }

        .review-body {
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--text-primary);
            font-style: italic;
        }

        .review-response {
            background: rgba(99, 102, 241, 0.05);
            border-left: 3px solid var(--accent);
            padding: 0.75rem 1rem;
            border-radius: 0 8px 8px 0;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .response-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            text-decoration: none;
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        /* Modal Backdrop */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(9, 13, 22, 0.85);
            backdrop-filter: blur(12px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-backdrop.show {
            opacity: 1;
        }

        /* Modal Card */
        .modal-card {
            background: rgba(17, 25, 40, 0.95);
            border: 1px solid rgba(239, 68, 68, 0.2);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255,255,255,0.05);
            border-radius: 24px;
            width: 90%;
            max-width: 480px;
            padding: 2.5rem;
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.25rem;
        }

        .modal-backdrop.show .modal-card {
            transform: scale(1);
        }

        .modal-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 1rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.15);
        }

        .modal-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
        }

        .modal-text {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            width: 100%;
            margin-top: 0.5rem;
        }

        .btn-modal-cancel {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
            border: 1px solid var(--border-color);
            padding: 0.8rem;
            border-radius: 12px;
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-modal-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
        }

        .btn-modal-confirm {
            flex: 1;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-modal-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(239, 68, 68, 0.45);
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <a href="{{ route('properties.index') }}" class="btn-back">
            <!-- Left Arrow SVG -->
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Properties
        </a>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="{{ route('properties.edit', $property->uuid) }}" class="btn-back">
                <!-- Edit Icon SVG -->
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Property
            </a>
            <form id="delete-property-form" action="{{ route('properties.destroy', $property->uuid) }}" method="POST" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
            <button type="button" class="btn-delete" onclick="openDeleteModal()">
                <!-- Trash Icon SVG -->
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete Property
            </button>
        </div>
    </header>

    @if(session('success'))
        <div class="alert-success-toast" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(52, 211, 153, 0.2); padding: 1rem; border-radius: 12px; margin-bottom: 2rem; color: #34d399; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
            <!-- Check Circle SVG -->
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @php
        // Deterministic hues based on uuid to create a matching banner
        $hash = crc32($property->uuid);
        $hue1 = $hash % 360;
        $hue2 = ($hue1 + 45) % 360;
    @endphp

    <div class="property-banner-large" style="background: linear-gradient(135deg, hsl({{ $hue1 }}, 60%, 45%) 0%, hsl({{ $hue2 }}, 65%, 25%) 100%);">
        <div class="banner-glow"></div>
        <div class="banner-content">
            <div class="banner-title-area">
                <h1>{{ $property->name }}</h1>
                <div class="banner-badge-group">
                    <span class="badge badge-currency">{{ $property->currency }}</span>
                    <span class="badge badge-status-{{ $property->status === 'active' ? 'active' : 'inactive' }}">
                        {{ $property->status }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Stats Row -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-label">Average Rating</span>
            <span class="stat-value">
                @if($avgRating)
                    {{ number_format($avgRating, 1) }} <span style="font-size: 1.2rem; color: #fbbf24;">★</span>
                @else
                    N/A
                @endif
            </span>
            <span class="stat-meta">Out of 5 stars</span>
        </div>

        <div class="stat-card">
            <span class="stat-label">Room Types</span>
            <span class="stat-value">{{ $property->roomTypes->count() }}</span>
            <span class="stat-meta">Configured room classes</span>
        </div>

        <div class="stat-card">
            <span class="stat-label">Total Reviews</span>
            <span class="stat-value">{{ $property->reviews->count() }}</span>
            <span class="stat-meta">Guest feedback count</span>
        </div>
    </div>

    <!-- Grid Details -->
    <div class="details-grid">
        <!-- Info Card -->
        <div class="card">
            <h2 class="card-title">
                <!-- Location icon SVG -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                General Information
            </h2>
            <div class="info-fields">
                <div class="field-group">
                    <span class="field-label">Address</span>
                    <span class="field-value">
                        {{ $property->address_street }}<br>
                        {{ $property->address_city }}, 
                        @if($property->address_state && $property->address_state !== $property->address_city)
                            {{ $property->address_state }},
                        @endif
                        {{ $property->address_country }}
                    </span>
                </div>

                <div class="field-group">
                    <span class="field-label">Postal Code</span>
                    <span class="field-value">{{ $property->address_postal_code ?: 'N/A' }}</span>
                </div>

                <div class="field-group">
                    <span class="field-label">GPS Coordinates</span>
                    <span class="field-value" style="font-family: monospace;">
                        @if($property->lat && $property->lng)
                            Lat: {{ $property->lat }}<br>Lng: {{ $property->lng }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>

                <div class="field-group">
                    <span class="field-label">Timezone</span>
                    <span class="field-value">{{ $property->timezone }}</span>
                </div>

                <div class="field-group">
                    <span class="field-label">Contact Email</span>
                    <span class="field-value">
                        @if($property->contact_email)
                            <a href="mailto:{{ $property->contact_email }}">{{ $property->contact_email }}</a>
                        @else
                            N/A
                        @endif
                    </span>
                </div>

                <div class="field-group">
                    <span class="field-label">Contact Phone</span>
                    <span class="field-value">
                        @if($property->contact_phone)
                            <a href="tel:{{ $property->contact_phone }}">{{ $property->contact_phone }}</a>
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Settings Card -->
        <div class="card">
            <h2 class="card-title">
                <!-- Cog Icon SVG -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Property Settings
            </h2>
            <div class="settings-list">
                @if($property->propertySetting)
                    <div class="setting-item">
                        <span class="setting-name">Check-In Time</span>
                        <span class="setting-val">{{ $property->propertySetting->check_in_time }}</span>
                    </div>
                    <div class="setting-item">
                        <span class="setting-name">Check-Out Time</span>
                        <span class="setting-val">{{ $property->propertySetting->check_out_time }}</span>
                    </div>
                    <div class="setting-item">
                        <span class="setting-name">Tax Inclusive</span>
                        <span class="setting-val">{{ $property->propertySetting->tax_inclusive ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="setting-item">
                        <span class="setting-name">Overbooking Limit</span>
                        <span class="setting-val">{{ number_format($property->propertySetting->overbooking_limit_percent, 2) }}%</span>
                    </div>
                @else
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">No settings configured.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Room Types Section -->
    <h2 class="section-title">Configured Room Types</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Room Name</th>
                    <th>Code</th>
                    <th>Max Occupancy</th>
                    <th>Base Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($property->roomTypes as $roomType)
                    <tr>
                        <td style="font-weight: 600;">{{ $roomType->name }}</td>
                        <td><span class="room-code">{{ $roomType->code }}</span></td>
                        <td>
                            {{ $roomType->max_adults }} Adults
                            @if($roomType->max_children)
                                , {{ $roomType->max_children }} Children
                            @endif
                        </td>
                        <td style="font-weight: 600;">{{ $property->currency }} {{ number_format($roomType->base_rate, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                            No room types configured.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Reviews Section -->
    @if($property->reviews->isNotEmpty())
        <div class="reviews-section">
            <h2 class="section-title">Recent Guest Reviews</h2>
            <div class="reviews-grid">
                @foreach($property->reviews as $review)
                    <div class="review-card">
                        <div class="review-header">
                            <span class="review-source">{{ $review->source ?: 'Guest Review' }}</span>
                            <div class="stars">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= round($review->rating_overall))
                                        ★
                                    @else
                                        <span style="color: rgba(255,255,255,0.15)">★</span>
                                    @endif
                                @endfor
                            </div>
                        </div>
                        <p class="review-body">"{{ $review->content }}"</p>
                        @if($review->response)
                            <div class="review-response">
                                <div class="response-label">Response from Management</div>
                                <p>"{{ $review->response }}"</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<!-- Custom Delete Confirmation Modal -->
<div id="deleteModal" class="modal-backdrop">
    <div class="modal-card">
        <div class="modal-icon">
            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="modal-title">Delete Property</h3>
        <p class="modal-text">Are you sure you want to delete <strong>{{ $property->name }}</strong>? This action will permanently remove all associated room types and reviews. It cannot be undone.</p>
        <div class="modal-actions">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn-modal-confirm" onclick="submitDeleteForm()">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
    function openDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.style.display = 'flex';
        // Force reflow
        modal.offsetHeight;
        modal.classList.add('show');
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    function submitDeleteForm() {
        document.getElementById('delete-property-form').submit();
    }

    // Close on click outside modal card
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
</script>

</body>
</html>
