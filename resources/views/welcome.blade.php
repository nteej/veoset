<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VEOset - Energy Asset Management</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Styles -->
    <style>
        .map-container {
            height: 70vh;
            min-height: 500px;
        }

        .site-marker {
            background-color: #059669;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.875rem;
        }

        .site-marker:hover {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }

        .asset-status-operational {
            @apply bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium;
        }

        .asset-status-maintenance {
            @apply bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium;
        }

        .asset-status-offline {
            @apply bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium;
        }

        .asset-type-turbine {
            @apply bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium;
        }

        .asset-type-solar_panel {
            @apply bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium;
        }

        .asset-type-transformer {
            @apply bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-medium;
        }

        .asset-type-generator {
            @apply bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="hero-gradient text-white">
        <div class="container mx-auto px-4 py-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-4">VEOset</h1>
                <p class="text-xl md:text-2xl mb-2">Energy Asset Management Excellence</p>
                <p class="text-lg md:text-xl opacity-90">Real-time monitoring • Predictive maintenance • Sustainable operations</p>
            </div>
        </div>
    </header>

    <!-- Stats Overview -->
    <section class="bg-white shadow-lg -mt-8 mx-4 md:mx-8 lg:mx-16 rounded-lg z-10 relative">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ $sites->count() }}</div>
                <div class="text-sm text-gray-600">Active Sites</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $sites->sum(fn($site) => $site->assets->count()) }}</div>
                <div class="text-sm text-gray-600">Total Assets</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-emerald-600">{{ $sites->sum(fn($site) => $site->assets->where('status', 'operational')->count()) }}</div>
                <div class="text-sm text-gray-600">Operational</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">98.5%</div>
                <div class="text-sm text-gray-600">Availability</div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Introduction -->
        <section class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Our Energy Infrastructure</h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Explore our nationwide network of renewable energy sites. Click on any marker to discover
                the advanced equipment and technology powering sustainable energy solutions.
            </p>
        </section>

        <!-- Interactive Map -->
        <section class="mb-8">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="p-4 bg-gray-50 border-b">
                    <h3 class="text-xl font-semibold text-gray-800">Energy Sites Location Map</h3>
                    <p class="text-sm text-gray-600">Click on site markers to view detailed asset information</p>
                </div>
                <div id="map" class="map-container"></div>
            </div>
        </section>

        <!-- Site Cards -->
        <section class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($sites as $site)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-800">{{ $site->name }}</h3>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                            Active
                        </span>
                    </div>

                    <p class="text-gray-600 mb-4">{{ $site->description }}</p>

                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $site->location }}
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-blue-600">{{ $site->assets->count() }}</div>
                            <div class="text-xs text-gray-600">Assets</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600">{{ $site->assets->where('status', 'operational')->count() }}</div>
                            <div class="text-xs text-gray-600">Online</div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </section>

        <!-- Features Section -->
        <section class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Why Choose VEOset?</h3>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold mb-2">Real-time Monitoring</h4>
                    <p class="text-gray-600">24/7 monitoring of all energy assets with instant alerts and notifications</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold mb-2">Predictive Maintenance</h4>
                    <p class="text-gray-600">AI-driven maintenance scheduling to prevent failures and optimize performance</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold mb-2">Global Reach</h4>
                    <p class="text-gray-600">Comprehensive energy asset management across multiple locations worldwide</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-4 text-center">
            <h3 class="text-xl font-bold mb-2">VEOset Energy Solutions</h3>
            <p class="text-gray-400 mb-4">Leading the future of sustainable energy management</p>
            <div class="flex justify-center space-x-6 text-sm">
                <a href="#" class="hover:text-green-400 transition-colors">About Us</a>
                <a href="#" class="hover:text-green-400 transition-colors">Services</a>
                <a href="#" class="hover:text-green-400 transition-colors">Contact</a>
                <a href="/admin" class="hover:text-green-400 transition-colors">Admin Portal</a>
            </div>
        </div>
    </footer>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    <!-- Map Initialization Script -->
    <script>
        // Initialize the map
        const map = L.map('map').setView([39.8283, -98.5795], 4); // Center on USA

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Sites data from Laravel
        const sites = @json($sites);

        // Asset type icons mapping
        const assetTypeIcons = {
            'turbine': '🌪️',
            'solar_panel': '☀️',
            'transformer': '⚡',
            'generator': '🔋',
            'battery': '🔋',
            'inverter': '🔌'
        };

        // Status color mapping
        const statusColors = {
            'operational': '#10b981',
            'maintenance': '#f59e0b',
            'offline': '#ef4444',
            'decommissioned': '#6b7280'
        };

        // Add markers for each site
        sites.forEach(site => {
            if (site.latitude && site.longitude) {
                // Create custom marker
                const markerHtml = `
                    <div class="site-marker" style="background-color: ${statusColors.operational}">
                        ${site.assets.length}
                    </div>
                `;

                const customIcon = L.divIcon({
                    html: markerHtml,
                    className: 'custom-marker',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16],
                    popupAnchor: [0, -16]
                });

                // Create marker
                const marker = L.marker([site.latitude, site.longitude], {
                    icon: customIcon
                }).addTo(map);

                // Create popup content
                let popupContent = `
                    <div class="p-4 min-w-[300px]">
                        <h3 class="text-lg font-bold text-gray-800 mb-2">${site.name}</h3>
                        <p class="text-sm text-gray-600 mb-3">${site.description}</p>
                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            ${site.location}
                        </div>
                `;

                if (site.assets && site.assets.length > 0) {
                    popupContent += `
                        <div class="border-t pt-3">
                            <h4 class="font-semibold text-gray-800 mb-2">Energy Assets (${site.assets.length})</h4>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                    `;

                    site.assets.forEach(asset => {
                        const assetIcon = assetTypeIcons[asset.asset_type] || '⚙️';
                        const statusClass = `asset-status-${asset.status}`;
                        const typeClass = `asset-type-${asset.asset_type}`;

                        popupContent += `
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg">${assetIcon}</span>
                                    <div>
                                        <div class="font-medium text-sm">${asset.name}</div>
                                        <div class="text-xs text-gray-500">${asset.manufacturer || ''} ${asset.model || ''}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="${statusClass}">${asset.status}</div>
                                </div>
                            </div>
                        `;
                    });

                    popupContent += `</div></div>`;
                } else {
                    popupContent += `
                        <div class="border-t pt-3 text-center text-gray-500">
                            <p>No active assets found</p>
                        </div>
                    `;
                }

                popupContent += `</div>`;

                // Bind popup to marker
                marker.bindPopup(popupContent, {
                    maxWidth: 350,
                    className: 'custom-popup'
                });

                // Add click event for additional details
                marker.on('click', function() {
                    // You can add additional functionality here, like loading more detailed asset data
                    console.log(`Clicked on site: ${site.name}`);
                });
            }
        });

        // Fit map to show all markers
        if (sites.length > 0) {
            const group = new L.featureGroup();
            sites.forEach(site => {
                if (site.latitude && site.longitude) {
                    group.addLayer(L.marker([site.latitude, site.longitude]));
                }
            });

            if (group.getLayers().length > 0) {
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }

        // Add map controls
        L.control.scale().addTo(map);

        // Store markers for real-time updates
        window.siteMarkers = {};

        // Store markers by site ID for easy updates
        sites.forEach(site => {
            if (site.latitude && site.longitude) {
                const marker = L.marker([site.latitude, site.longitude]).getLayers ?
                    map.getLayers().find(layer => layer instanceof L.Marker &&
                        layer.getLatLng().lat === parseFloat(site.latitude) &&
                        layer.getLatLng().lng === parseFloat(site.longitude)) : null;

                if (marker) {
                    window.siteMarkers[site.id] = marker;
                }
            }
        });
    </script>

    <!-- Pusher JavaScript for real-time updates -->
    @if(env('BROADCAST_CONNECTION') === 'pusher')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Initialize Pusher
        Pusher.logToConsole = true;

        const pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
            cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
            @if(env('PUSHER_SCHEME') === 'http')
            wsHost: '{{ env('PUSHER_HOST') }}',
            wsPort: {{ env('PUSHER_PORT', 6001) }},
            wssPort: {{ env('PUSHER_PORT', 6001) }},
            forceTLS: false,
            disableStats: true,
            @endif
        });

        // Subscribe to asset updates channel
        const assetChannel = pusher.subscribe('asset-updates');

        // Listen for asset status changes
        assetChannel.bind('asset.status.changed', function(data) {
            console.log('Asset status changed:', data);
            updateSiteMarker(data);
            showStatusChangeNotification(data);
        });

        // Subscribe to individual site channels for more targeted updates
        sites.forEach(site => {
            const siteChannel = pusher.subscribe(`site-updates.${site.id}`);
            siteChannel.bind('asset.status.changed', function(data) {
                console.log(`Site ${site.id} asset update:`, data);
                updateSiteMarker(data);
            });
        });
    @else
    <script>
        console.log('Real-time updates are disabled (broadcasting set to {{ env('BROADCAST_CONNECTION') }})');
        console.log('To enable real-time map updates, set BROADCAST_CONNECTION=pusher in .env file');
    @endif

        function updateSiteMarker(data) {
            const siteId = data.site_id;
            const marker = window.siteMarkers[siteId];

            if (!marker) return;

            // Update the site data in our local sites array
            const siteIndex = sites.findIndex(s => s.id === siteId);
            if (siteIndex !== -1) {
                const assetIndex = sites[siteIndex].assets.findIndex(a => a.id === data.asset_id);
                if (assetIndex !== -1) {
                    sites[siteIndex].assets[assetIndex].status = data.new_status;
                }
            }

            // Update marker color based on overall site status
            const site = sites.find(s => s.id === siteId);
            if (site) {
                const operationalCount = site.assets.filter(a => a.status === 'operational').length;
                const totalAssets = site.assets.length;

                let markerColor = '#10b981'; // Default operational green
                if (operationalCount === 0) {
                    markerColor = '#ef4444'; // All offline - red
                } else if (operationalCount < totalAssets) {
                    markerColor = '#f59e0b'; // Some issues - yellow
                }

                // Update marker appearance
                const markerElement = marker.getElement();
                if (markerElement) {
                    const markerDiv = markerElement.querySelector('.site-marker');
                    if (markerDiv) {
                        markerDiv.style.backgroundColor = markerColor;

                        // Add pulse animation for critical changes
                        if (data.new_status === 'emergency' || data.new_status === 'offline') {
                            markerDiv.style.animation = 'pulse 2s infinite';
                        } else {
                            markerDiv.style.animation = '';
                        }
                    }
                }

                // Update popup content
                updateMarkerPopup(marker, site);
            }
        }

        function updateMarkerPopup(marker, site) {
            let popupContent = `
                <div class="p-4 min-w-[300px]">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">${site.name}</h3>
                    <p class="text-sm text-gray-600 mb-3">${site.description}</p>
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                        ${site.location}
                    </div>
            `;

            if (site.assets && site.assets.length > 0) {
                popupContent += `
                    <div class="border-t pt-3">
                        <h4 class="font-semibold text-gray-800 mb-2">Energy Assets (${site.assets.length})</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                `;

                site.assets.forEach(asset => {
                    const assetIcon = assetTypeIcons[asset.asset_type] || '⚙️';
                    const statusClass = `asset-status-${asset.status}`;

                    popupContent += `
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                            <div class="flex items-center space-x-2">
                                <span class="text-lg">${assetIcon}</span>
                                <div>
                                    <div class="font-medium text-sm">${asset.name}</div>
                                    <div class="text-xs text-gray-500">${asset.manufacturer || ''} ${asset.model || ''}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="${statusClass}">${asset.status}</div>
                            </div>
                        </div>
                    `;
                });

                popupContent += `</div></div>`;
            }

            popupContent += `</div>`;

            marker.setPopupContent(popupContent);
        }

        function showStatusChangeNotification(data) {
            // Create a notification toast
            const notification = document.createElement('div');
            notification.className = `
                fixed top-4 right-4 z-50 p-4 mb-4 rounded-lg shadow-lg max-w-sm
                ${getNotificationClass(data.new_status)}
                transform transition-all duration-500 ease-in-out translate-x-full
            `;

            const icon = getStatusIcon(data.new_status);
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        ${icon}
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">
                            Asset Status Update
                        </p>
                        <p class="text-xs">
                            ${data.asset_name} at ${data.site_name} is now ${data.new_status}
                        </p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.parentElement.remove()"
                                class="text-gray-400 hover:text-gray-600">
                            <span class="sr-only">Close</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(notification);

            // Slide in animation
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            }, 5000);
        }

        function getNotificationClass(status) {
            switch(status) {
                case 'operational': return 'bg-green-100 border border-green-500 text-green-700';
                case 'maintenance': return 'bg-yellow-100 border border-yellow-500 text-yellow-700';
                case 'offline': return 'bg-red-100 border border-red-500 text-red-700';
                case 'emergency': return 'bg-red-200 border border-red-600 text-red-800';
                default: return 'bg-blue-100 border border-blue-500 text-blue-700';
            }
        }

        function getStatusIcon(status) {
            switch(status) {
                case 'operational':
                    return '<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
                case 'maintenance':
                    return '<svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                case 'offline':
                case 'emergency':
                    return '<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                default:
                    return '<svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
            }
        }

        // Add CSS for pulse animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.1); opacity: 0.8; }
            }
        `;
        document.head.appendChild(style);

        console.log('Real-time asset monitoring initialized');
    </script>
</body>
</html>