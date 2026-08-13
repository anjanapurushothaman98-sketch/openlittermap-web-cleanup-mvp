@extends('app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

    <style>
        :root {
            --olive-bg: #e8e6c2;
            --olive-dark: #2d3a1f;
            --olive-mid: #6b7a4a;
            --cream: #faf9f0;
        }

        #toolSidebar {
            position: absolute;
            top: 80px;
            left: 10px;
            z-index: 1000;
            background: var(--cream);
            border-radius: 12px;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 130px;
        }

        .tool-btn {
            background: var(--olive-bg);
            color: var(--olive-dark);
            border: none;
            border-radius: 8px;
            padding: 10px 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s;
        }

        .tool-btn:hover {
            background: var(--olive-mid);
            color: var(--cream);
        }

        .tool-btn.active {
            background: var(--olive-dark);
            color: var(--cream);
        }

        .tool-icon {
            font-size: 16px;
        }

        #userBar {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: var(--cream);
            border-radius: 12px;
            padding: 10px 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            font-size: 14px;
            color: var(--olive-dark);
        }

        #userBar a {
            color: var(--olive-dark);
            text-decoration: none;
            font-weight: 600;
        }

        #userBar a:hover {
            text-decoration: underline;
        }
    </style>

    <div id="map" style="height: 100vh; width: 100%;"></div>

    <div id="toolSidebar">
        <button class="tool-btn" id="tool-marker">
            <span class="tool-icon">&#128204;</span> Trash spot
        </button>
        <button class="tool-btn" id="tool-polygon">
            <span class="tool-icon">&#9698;</span> Draw area
        </button>
        <button class="tool-btn" id="tool-bin">
            <span class="tool-icon">&#128465;</span> Add bin
        </button>
        <button class="tool-btn" id="tool-cleanup">
            <span class="tool-icon">&#128197;</span> Plan cleanup
        </button>
    </div>

    <div id="userBar">
        <span id="userBarContent">Loading...</span>
    </div>

    <script>
        var map = L.map('map').setView([53.55, 9.99], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        var statusColors = { red: '#e03131', yellow: '#f2c037', green: '#2f9e44' };

        // ---------- USER BAR ----------

        fetch('/api/profile', { headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(data => {
                var userBarContent = document.getElementById('userBarContent');

                if (data.error) {
                    userBarContent.innerHTML = `<a href="/auth">Login / Register</a>`;
                } else {
                    var displayName = data.name || data.username;
                    userBarContent.innerHTML = `
                        <a href="/profile">${displayName}</a>
                        &nbsp;|&nbsp;
                        <a href="#" id="mapLogoutLink">Logout</a>
                    `;

                    document.getElementById('mapLogoutLink').addEventListener('click', function (e) {
                        e.preventDefault();
                        fetch('/api/auth/logout', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(() => window.location.reload());
                    });
                }
            })
            .catch(() => {
                document.getElementById('userBarContent').innerHTML = `<a href="/auth">Login / Register</a>`;
            });

        // ---------- TOOL STATE ----------
        // Only one tool can be "armed" at a time: 'marker', 'polygon', 'bin', 'cleanup', or null

        var activeTool = null;
        var polygonDrawHandler = new L.Draw.Polygon(map, {
            shapeOptions: { color: '#6b7a4a' }
        });

        function setActiveTool(tool) {
            // Always cancel any in-progress polygon drawing when switching tools
            if (activeTool === 'polygon' && tool !== 'polygon') {
                polygonDrawHandler.disable();
            }

            activeTool = tool;

            document.getElementById('tool-marker').classList.toggle('active', tool === 'marker');
            document.getElementById('tool-polygon').classList.toggle('active', tool === 'polygon');
            document.getElementById('tool-bin').classList.toggle('active', tool === 'bin');
            document.getElementById('tool-cleanup').classList.toggle('active', tool === 'cleanup');

            if (tool === 'polygon') {
                polygonDrawHandler.enable();
            }
        }

        document.getElementById('tool-marker').addEventListener('click', function () {
            setActiveTool(activeTool === 'marker' ? null : 'marker');
        });

        document.getElementById('tool-polygon').addEventListener('click', function () {
            setActiveTool(activeTool === 'polygon' ? null : 'polygon');
        });

        document.getElementById('tool-bin').addEventListener('click', function () {
            setActiveTool(activeTool === 'bin' ? null : 'bin');
        });

        document.getElementById('tool-cleanup').addEventListener('click', function () {
            setActiveTool(activeTool === 'cleanup' ? null : 'cleanup');
        });

        // ---------- MARKERS ----------

function buildMarkerPopup(marker) {
            var photoHtml = marker.photo
                ? `<br><img src="/storage/${marker.photo}" style="max-width:180px; margin-top:6px;">`
                : '';

            return `
                <strong>Marker #${marker.id}</strong><br>
                Status: ${marker.status}<br>
                Description: ${marker.description ?? 'n/a'}<br>
                Creator: ${marker.creator ?? 'n/a'}<br>
                Litter type: ${marker.litter_type ?? 'n/a'}<br>
                Weight: ${marker.weight_kg ? marker.weight_kg + ' kg' : 'n/a'}<br>
                Lat: ${marker.lat}<br>
                Lng: ${marker.lng}<br>
                Created: ${marker.created_at ?? 'n/a'}
                ${photoHtml}
            `;
        }

  function buildMarkerFormPopup(lat, lng) {
            var container = document.createElement('div');
            container.innerHTML = `
                <div style="min-width:200px">
                    <label>Description:</label><br>
                    <input type="text" id="markerDesc" style="width:100%; margin-bottom:6px;"><br>
                    <label>Status:</label><br>
                    <select id="markerStatus" style="width:100%; margin-bottom:6px;">
                        <option value="red">Red</option>
                        <option value="yellow">Yellow</option>
                        <option value="green">Green</option>
                    </select><br>
                    <label>Litter type:</label><br>
                    <select id="markerLitterType" style="width:100%; margin-bottom:6px;">
                        <option value="plastic">Plastic</option>
                        <option value="glass">Glass</option>
                        <option value="metal">Metal</option>
                        <option value="cigarette_butts">Cigarette butts</option>
                        <option value="paper">Paper</option>
                        <option value="food_waste">Food waste</option>
                        <option value="other">Other</option>
                    </select><br>
                    <label>Weight (kg):</label><br>
                    <input type="number" step="0.1" min="0" id="markerWeight" style="width:100%; margin-bottom:6px;"><br>
                    <label>Photo:</label><br>
                    <input type="file" id="markerPhoto" accept="image/*" style="width:100%; margin-bottom:6px;"><br>
                    <button id="saveMarkerBtn">Save</button>
                </div>
            `;

            var newMarker = L.marker([lat, lng]).addTo(map);
            newMarker.bindPopup(container).openPopup();

container.querySelector('#saveMarkerBtn').addEventListener('click', function () {
                var description = container.querySelector('#markerDesc').value;
                var status = container.querySelector('#markerStatus').value;
                var litterType = container.querySelector('#markerLitterType').value;
                var weight = container.querySelector('#markerWeight').value;
                var photoFile = container.querySelector('#markerPhoto').files[0];

                var formData = new FormData();
                formData.append('lat', lat);
                formData.append('lng', lng);
                formData.append('description', description);
                formData.append('status', status);
                formData.append('litter_type', litterType);
                formData.append('weight_kg', weight);
                if (photoFile) {
                    formData.append('photo', photoFile);
                }

                fetch('/api/markers', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        newMarker.bindPopup(buildMarkerPopup(data)).openPopup();
                    }
                })
                .catch(error => console.error("MARKER SAVE ERROR:", error));
            });
        }

        fetch('/api/markers')
            .then(response => response.json())
            .then(markers => {
                markers.forEach(function(m) {
                    L.marker([m.lat, m.lng])
                        .addTo(map)
                        .bindPopup(buildMarkerPopup(m));
                });
            })
            .catch(error => console.error("LOAD MARKERS ERROR:", error));

        // ---------- AREAS (POLYGONS) ----------

        var drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        function buildAreaPopup(area, layer) {
            var coords = JSON.parse(area.coordinates);
            var coordsText = coords[0].map(function(p) {
                return `(${p.lat.toFixed(5)}, ${p.lng.toFixed(5)})`;
            }).join(', ');

            var container = document.createElement('div');
            container.innerHTML = `
                <div style="min-width:220px">
                    <strong>Area #${area.id}</strong><br>
                    <label>Status:</label><br>
                    <select id="editAreaStatus" style="width:100%; margin-bottom:6px;">
                        <option value="red" ${area.status === 'red' ? 'selected' : ''}>Red</option>
                        <option value="yellow" ${area.status === 'yellow' ? 'selected' : ''}>Yellow</option>
                        <option value="green" ${area.status === 'green' ? 'selected' : ''}>Green</option>
                    </select><br>
                    <label>Description:</label><br>
                    <input type="text" id="editAreaDesc" value="${area.description ?? ''}" style="width:100%; margin-bottom:6px;"><br>
                    Creator: ${area.creator ?? 'n/a'}<br>
                    Created: ${area.created_at ?? 'n/a'}<br>
                    Coordinates: ${coordsText}<br><br>
                    <button id="updateAreaBtn">Update</button>
                    <button id="deleteAreaBtn" style="color:red;">Delete</button>
                </div>
            `;

            container.querySelector('#updateAreaBtn').addEventListener('click', function () {
                var status = container.querySelector('#editAreaStatus').value;
                var description = container.querySelector('#editAreaDesc').value;

                fetch(`/api/areas/${area.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ status: status, description: description })
                })
                .then(response => response.json())
                .then(updated => {
                    layer.setStyle({ color: statusColors[updated.status] || '#3388ff' });
                    layer.setPopupContent(buildAreaPopup(updated, layer));
                })
                .catch(error => console.error("AREA UPDATE ERROR:", error));
            });

            container.querySelector('#deleteAreaBtn').addEventListener('click', function () {
                if (!confirm('Delete this area?')) return;

                fetch(`/api/areas/${area.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(() => {
                    drawnItems.removeLayer(layer);
                })
                .catch(error => console.error("AREA DELETE ERROR:", error));
            });

            return container;
        }

        function buildAreaFormPopup(layer) {
            var container = document.createElement('div');
            container.innerHTML = `
                <div style="min-width:200px">
                    <label>Description:</label><br>
                    <input type="text" id="areaDesc" style="width:100%; margin-bottom:6px;"><br>
                    <label>Status:</label><br>
                    <select id="areaStatus" style="width:100%; margin-bottom:6px;">
                        <option value="red">Red</option>
                        <option value="yellow">Yellow</option>
                        <option value="green">Green</option>
                    </select><br>
                    <button id="saveAreaBtn">Save</button>
                </div>
            `;

            layer.bindPopup(container).openPopup();

            container.querySelector('#saveAreaBtn').addEventListener('click', function () {
                var description = container.querySelector('#areaDesc').value;
                var status = container.querySelector('#areaStatus').value;
                var coordinates = layer.getLatLngs();

                fetch('/api/areas', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        coordinates: coordinates,
                        description: description,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        layer.setStyle({ color: statusColors[data.status] || '#3388ff' });
                        layer.bindPopup(buildAreaPopup(data, layer)).openPopup();
                    }
                })
                .catch(error => console.error("AREA SAVE ERROR:", error));
            });
        }

        map.on(L.Draw.Event.CREATED, function (e) {
            var layer = e.layer;
            drawnItems.addLayer(layer);
            buildAreaFormPopup(layer);
            setActiveTool(null);
        });

        fetch('/api/areas')
            .then(response => response.json())
            .then(areas => {
                areas.forEach(function(a) {
                    var latlngs = JSON.parse(a.coordinates);
                    var polygon = L.polygon(latlngs, {
                        color: statusColors[a.status] || '#3388ff'
                    }).addTo(drawnItems);
                    polygon.bindPopup(buildAreaPopup(a, polygon));
                });
            })
            .catch(error => console.error("LOAD AREAS ERROR:", error));

        // ---------- BINS ----------

        var binIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        function buildBinPopup(bin, marker) {
            var container = document.createElement('div');
            container.innerHTML = `
                <div style="min-width:200px">
                    <strong>Bin #${bin.id}</strong><br>
                    <label>Status:</label><br>
                    <select id="editBinStatus" style="width:100%; margin-bottom:6px;">
                        <option value="available" ${bin.status === 'available' ? 'selected' : ''}>Available</option>
                        <option value="full" ${bin.status === 'full' ? 'selected' : ''}>Full</option>
                        <option value="damaged" ${bin.status === 'damaged' ? 'selected' : ''}>Damaged</option>
                    </select><br>
                    <label>Description:</label><br>
                    <input type="text" id="editBinDesc" value="${bin.description ?? ''}" style="width:100%; margin-bottom:6px;"><br>
                    Creator: ${bin.creator ?? 'n/a'}<br>
                    Created: ${bin.created_at ?? 'n/a'}<br><br>
                    <button id="updateBinBtn">Update</button>
                    <button id="deleteBinBtn" style="color:red;">Delete</button>
                </div>
            `;

            container.querySelector('#updateBinBtn').addEventListener('click', function () {
                var status = container.querySelector('#editBinStatus').value;
                var description = container.querySelector('#editBinDesc').value;

                fetch(`/api/bins/${bin.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ status: status, description: description })
                })
                .then(response => response.json())
                .then(updated => {
                    marker.setPopupContent(buildBinPopup(updated, marker));
                })
                .catch(error => console.error("BIN UPDATE ERROR:", error));
            });

            container.querySelector('#deleteBinBtn').addEventListener('click', function () {
                if (!confirm('Delete this bin?')) return;

                fetch(`/api/bins/${bin.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(() => {
                    map.removeLayer(marker);
                })
                .catch(error => console.error("BIN DELETE ERROR:", error));
            });

            return container;
        }

        function buildBinFormPopup(lat, lng) {
            var container = document.createElement('div');
            container.innerHTML = `
                <div style="min-width:200px">
                    <label>Description:</label><br>
                    <input type="text" id="binDesc" style="width:100%; margin-bottom:6px;"><br>
                    <label>Status:</label><br>
                    <select id="binStatus" style="width:100%; margin-bottom:6px;">
                        <option value="available">Available</option>
                        <option value="full">Full</option>
                        <option value="damaged">Damaged</option>
                    </select><br>
                    <button id="saveBinBtn">Save</button>
                </div>
            `;

            var newBin = L.marker([lat, lng], { icon: binIcon }).addTo(map);
            newBin.bindPopup(container).openPopup();

            container.querySelector('#saveBinBtn').addEventListener('click', function () {
                var description = container.querySelector('#binDesc').value;
                var status = container.querySelector('#binStatus').value;

                fetch('/api/bins', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ lat: lat, lng: lng, description: description, status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        newBin.bindPopup(buildBinPopup(data, newBin)).openPopup();
                    }
                })
                .catch(error => console.error("BIN SAVE ERROR:", error));
            });
        }

        fetch('/api/bins')
            .then(response => response.json())
            .then(bins => {
                bins.forEach(function(b) {
                    var m = L.marker([b.lat, b.lng], { icon: binIcon }).addTo(map);
                    m.bindPopup(buildBinPopup(b, m));
                });
            })
            .catch(error => console.error("LOAD BINS ERROR:", error));

        // ---------- CLEANUPS ----------

        var cleanupIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        function buildCleanupPopup(cleanup) {
            return `
                <strong>${cleanup.name}</strong><br>
                Date: ${cleanup.date}<br>
                Description: ${cleanup.description}<br>
                Participants: ${cleanup.users ? cleanup.users.length : 1}<br>
                <button class="joinCleanupBtn" data-link="${cleanup.invite_link}">Join this cleanup</button>
            `;
        }

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('joinCleanupBtn')) {
                var link = e.target.getAttribute('data-link');

                fetch(`/api/cleanups/${link}/join`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('You joined this cleanup!');
                    } else {
                        alert(data.msg || 'Could not join cleanup');
                    }
                })
                .catch(error => console.error("JOIN CLEANUP ERROR:", error));
            }
        });

        function buildCleanupFormPopup(lat, lng) {
            var container = document.createElement('div');
            container.innerHTML = `
                <div style="min-width:220px">
                    <label>Event name:</label><br>
                    <input type="text" id="cleanupName" style="width:100%; margin-bottom:6px;"><br>
                    <label>Date:</label><br>
                    <input type="date" id="cleanupDate" style="width:100%; margin-bottom:6px;"><br>
                    <label>Time:</label><br>
                    <input type="time" id="cleanupTime" style="width:100%; margin-bottom:6px;"><br>
                    <label>Description:</label><br>
                    <input type="text" id="cleanupDesc" style="width:100%; margin-bottom:6px;"><br>
                    <button id="saveCleanupBtn">Create event</button>
                </div>
            `;

            var newCleanup = L.marker([lat, lng], { icon: cleanupIcon }).addTo(map);
            newCleanup.bindPopup(container).openPopup();

            container.querySelector('#saveCleanupBtn').addEventListener('click', function () {
                var name = container.querySelector('#cleanupName').value;
                var date = container.querySelector('#cleanupDate').value;
                var time = container.querySelector('#cleanupTime').value;
                var description = container.querySelector('#cleanupDesc').value;
                var inviteLink = 'cleanup-' + Date.now();

                fetch('/api/cleanups/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        name: name,
                        date: date,
                        time: time,
                        lat: lat,
                        lon: lng,
                        description: description,
                        invite_link: inviteLink
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        newCleanup.bindPopup(buildCleanupPopup(data.cleanup)).openPopup();
                    } else {
                        alert('Could not create cleanup: ' + JSON.stringify(data));
                    }
                })
                .catch(error => console.error("CLEANUP SAVE ERROR:", error));
            });
        }

        fetch('/api/cleanups/get-cleanups')
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.geojson || !data.geojson.features) return;

                data.geojson.features.forEach(function(feature) {
                    var coords = feature.geometry.coordinates;
                    var m = L.marker([coords[1], coords[0]], { icon: cleanupIcon }).addTo(map);
                    m.bindPopup(buildCleanupPopup(feature.properties));
                });
            })
            .catch(error => console.error("LOAD CLEANUPS ERROR:", error));

        // ---------- SINGLE CLICK ROUTER ----------
        // Routes a map click to whichever tool is currently armed

        map.on('click', function(e) {
            if (activeTool === 'marker') {
                buildMarkerFormPopup(e.latlng.lat, e.latlng.lng);
                setActiveTool(null);
            } else if (activeTool === 'bin') {
                buildBinFormPopup(e.latlng.lat, e.latlng.lng);
                setActiveTool(null);
            } else if (activeTool === 'cleanup') {
                buildCleanupFormPopup(e.latlng.lat, e.latlng.lng);
                setActiveTool(null);
            }
            // polygon clicks are handled internally by polygonDrawHandler
        });
    </script>
@endsection