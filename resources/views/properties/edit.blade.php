<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit {{ $property->name }} | QuickFlow Premium</title>
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
            max-width: 800px;
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

        .page-title {
            font-family: var(--font-display);
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        /* Form Card */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem;
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.3);
        }

        .card-section-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            margin-top: 1.5rem;
            color: var(--text-primary);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-section-title:first-of-type {
            margin-top: 0;
        }

        /* Form Inputs */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        select {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            padding: 0.8rem 1rem;
            border-radius: 12px;
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 0.95rem;
            transition: all 0.25s ease;
            backdrop-filter: blur(10px);
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
        }

        select option {
            background-color: var(--bg-main);
            color: var(--text-primary);
        }

        /* Error/Alert Styles */
        .error-message {
            color: var(--badge-inactive-color);
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            color: #f87171;
            font-size: 0.9rem;
        }

        .alert ul {
            list-style-type: none;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        /* Buttons footer */
        .form-actions {
            margin-top: 2.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 1.5rem;
        }

        .btn {
            padding: 0.8rem 1.75rem;
            border-radius: 12px;
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <a href="{{ route('properties.show', $property->uuid) }}" class="btn-back">
            <!-- Left Arrow SVG -->
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Details
        </a>
    </header>

    <h1 class="page-title">Edit Property</h1>

    @if ($errors->any())
        <div class="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form action="{{ route('properties.update', $property->uuid) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Section: General -->
            <h2 class="card-section-title">General Information</h2>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="name">Property Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $property->name) }}" required>
                    @error('name') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="active" {{ old('status', $property->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $property->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="currency">Currency</label>
                    <input type="text" name="currency" id="currency" value="{{ old('currency', $property->currency) }}" placeholder="e.g. USD" required maxlength="3">
                    @error('currency') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <input type="text" name="timezone" id="timezone" value="{{ old('timezone', $property->timezone) }}" placeholder="e.g. America/New_York" required>
                    @error('timezone') <span class="error-message">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Section: Location -->
            <h2 class="card-section-title">Address & Location</h2>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="address_street">Street Address</label>
                    <input type="text" name="address_street" id="address_street" value="{{ old('address_street', $property->address_street) }}" required>
                    @error('address_street') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="address_city">City</label>
                    <input type="text" name="address_city" id="address_city" value="{{ old('address_city', $property->address_city) }}" required>
                    @error('address_city') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="address_state">State / Province</label>
                    <input type="text" name="address_state" id="address_state" value="{{ old('address_state', $property->address_state) }}">
                    @error('address_state') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="address_postal_code">Postal Code</label>
                    <input type="text" name="address_postal_code" id="address_postal_code" value="{{ old('address_postal_code', $property->address_postal_code) }}">
                    @error('address_postal_code') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="address_country">Country</label>
                    <input type="text" name="address_country" id="address_country" value="{{ old('address_country', $property->address_country) }}" required>
                    @error('address_country') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="lat">Latitude</label>
                    <input type="number" name="lat" id="lat" value="{{ old('lat', $property->lat) }}" step="any" min="-90" max="90">
                    @error('lat') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="lng">Longitude</label>
                    <input type="number" name="lng" id="lng" value="{{ old('lng', $property->lng) }}" step="any" min="-180" max="180">
                    @error('lng') <span class="error-message">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Section: Contact -->
            <h2 class="card-section-title">Contact Information</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="contact_phone">Phone Number</label>
                    <input type="text" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $property->contact_phone) }}">
                    @error('contact_phone') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="contact_email">Email Address</label>
                    <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $property->contact_email) }}">
                    @error('contact_email') <span class="error-message">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="{{ route('properties.show', $property->uuid) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
