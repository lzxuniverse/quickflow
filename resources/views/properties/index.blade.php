<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties | QuickFlow Premium Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variable definitions & reset */
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

        /* Container Layout */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        /* Header section */
        header {
            margin-bottom: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .header-title-area h1 {
            font-family: var(--font-display);
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .header-title-area p {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 400;
        }

        /* Search Section */
        .search-container {
            width: 100%;
            max-width: 450px;
        }

        .search-form {
            display: flex;
            gap: 0.75rem;
            width: 100%;
        }

        .search-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .search-input {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border-radius: 12px;
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 0.95rem;
            transition: all 0.25s ease;
            backdrop-filter: blur(10px);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
        }

        .search-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
            width: 16px;
            height: 16px;
        }

        .btn {
            background: var(--accent-gradient);
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }

        .btn-clear {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            box-shadow: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-clear:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        /* Grid list of properties */
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 2rem;
            margin-bottom: 3.5rem;
        }

        /* Card styles */
        .property-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            backdrop-filter: blur(16px);
        }

        .property-card:hover {
            transform: translateY(-6px);
            border-color: var(--border-hover);
            box-shadow: 
                0 20px 25px -5px rgba(0, 0, 0, 0.2), 
                0 0 20px var(--primary-glow);
        }

        /* Dynamic banner generated at top of card */
        .property-banner {
            height: 100px;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 1.25rem;
        }

        .property-badge-group {
            display: flex;
            gap: 0.5rem;
            position: absolute;
            top: 1rem;
            right: 1rem;
        }

        .badge {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.35rem 0.65rem;
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

        /* Content area of card */
        .property-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .property-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.4;
            margin-bottom: 0.75rem;
        }

        .property-title a {
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .property-title a:hover {
            color: var(--accent);
        }

        .property-location {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        .property-location svg {
            flex-shrink: 0;
            margin-top: 2px;
            color: var(--accent);
            width: 14px;
            height: 14px;
        }

        .property-details {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 1.25rem;
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .detail-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .detail-row svg {
            color: rgba(255, 255, 255, 0.4);
            width: 14px;
            height: 14px;
        }

        .detail-row a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .detail-row a:hover {
            color: var(--accent);
        }

        /* Coordinate metadata */
        .coords-badge {
            font-family: monospace;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            display: inline-block;
        }

        /* No Results State */
        .no-results {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
        }

        .no-results svg {
            color: rgba(255, 255, 255, 0.15);
            margin-bottom: 1.5rem;
            width: 64px;
            height: 64px;
        }

        .no-results h3 {
            font-family: var(--font-display);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .no-results p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        /* Beautiful Custom Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 3rem;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            list-style: none;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 14px;
        }

        .pagination li a, .pagination li span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            color: var(--text-secondary);
            transition: all 0.2s ease;
        }

        .pagination li a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }

        .pagination li.active span {
            background: var(--accent-gradient);
            color: white;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);
        }

        .pagination li.disabled span {
            opacity: 0.3;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="header-title-area">
            <h1>Properties Directory</h1>
            <p>Showing {{ $properties->firstItem() ?? 0 }} - {{ $properties->lastItem() ?? 0 }} of {{ $properties->total() }} premium properties</p>
        </div>

        <div class="search-container">
            <form action="{{ route('properties.index') }}" method="GET" class="search-form">
                <div class="search-wrapper">
                    <!-- Search Icon SVG -->
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" class="search-input" placeholder="Search by name, city or country..." value="{{ request('search') }}">
                </div>
                <button type="submit" class="btn">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ route('properties.index') }}" class="btn btn-clear">Clear</a>
                @endif
            </form>
        </div>
    </header>

    @if($properties->isEmpty())
        <div class="no-results">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v3m-6 0h6" />
            </svg>
            <h3>No Properties Found</h3>
            <p>We couldn't find any properties matching "{{ request('search') }}".</p>
            <a href="{{ route('properties.index') }}" class="btn">View All Properties</a>
        </div>
    @else
        <div class="properties-grid">
            @foreach($properties as $property)
                @php
                    // Deterministic hues based on uuid to create a gorgeous distinct banner for each card
                    $hash = crc32($property->uuid);
                    $hue1 = $hash % 360;
                    $hue2 = ($hue1 + 45) % 360;
                @endphp
                <article class="property-card">
                    <div class="property-banner" style="background: linear-gradient(135deg, hsl({{ $hue1 }}, 60%, 45%) 0%, hsl({{ $hue2 }}, 65%, 25%) 100%);">
                        <div class="property-badge-group">
                            <span class="badge badge-currency">{{ $property->currency }}</span>
                            <span class="badge badge-status-{{ $property->status === 'active' ? 'active' : 'inactive' }}">
                                {{ $property->status }}
                            </span>
                        </div>
                    </div>
                    <div class="property-content">
                        <h2 class="property-title"><a href="{{ route('properties.show', $property->uuid) }}">{{ $property->name }}</a></h2>
                        
                        <div class="property-location">
                            <!-- Pin Icon SVG -->
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>
                                {{ $property->address_street }}, {{ $property->address_city }}, 
                                @if($property->address_state && $property->address_state !== $property->address_city)
                                    {{ $property->address_state }},
                                @endif
                                {{ $property->address_country }}
                            </span>
                        </div>

                        <div class="property-details">
                            @if($property->contact_phone)
                                <div class="detail-row">
                                    <!-- Phone Icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <a href="tel:{{ $property->contact_phone }}">{{ $property->contact_phone }}</a>
                                </div>
                            @endif

                            @if($property->contact_email)
                                <div class="detail-row">
                                    <!-- Mail Icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <a href="mailto:{{ $property->contact_email }}">{{ $property->contact_email }}</a>
                                </div>
                            @endif

                            @if($property->timezone)
                                <div class="detail-row">
                                    <!-- Clock Icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Timezone: {{ $property->timezone }}</span>
                                </div>
                            @endif

                            @if($property->lat && $property->lng)
                                <div class="detail-row" style="margin-top: 4px;">
                                    <span class="coords-badge">LOC: {{ $property->lat }}, {{ $property->lng }}</span>
                                </div>
                            @endif
                        </div>
                        <div style="margin-top: 1rem; border-top: 1px dashed rgba(255,255,255,0.06); padding-top: 0.75rem; display: flex; justify-content: flex-end;">
                            <a href="{{ route('properties.edit', $property->uuid) }}" style="color: var(--accent); font-weight: 600; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Property
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if($properties->hasPages())
            <nav class="pagination-container">
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if ($properties->onFirstPage())
                        <li class="disabled" aria-disabled="true"><span>&laquo;</span></li>
                    @else
                        <li><a href="{{ $properties->previousPageUrl() }}" rel="prev">&laquo;</a></li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($properties->getUrlRange(max(1, $properties->currentPage() - 2), min($properties->lastPage(), $properties->currentPage() + 2)) as $page => $url)
                        @if ($page == $properties->currentPage())
                            <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($properties->hasMorePages())
                        <li><a href="{{ $properties->nextPageUrl() }}" rel="next">&raquo;</a></li>
                    @else
                        <li class="disabled" aria-disabled="true"><span>&raquo;</span></li>
                    @endif
                </ul>
            </nav>
        @endif
    @endif
</div>

</body>
</html>
