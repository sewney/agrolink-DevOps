<div class="content-section transporter-requests-page">
    <div class="content-header">
        <h1 class="content-title">Available Deliveries</h1>
        <button class="btn btn-outline btn-sm" onclick="TransporterRequests.refreshDeliveries()">Refresh</button>
    </div>

    <div class="content-card delivery-filter-card">
        <div class="delivery-filter-row">
            <div class="transporter-filter-shell">
                <div class="transporter-filter-grid">
                    <div class="transporter-filter-group">
                        <label for="locationFilter">Pickup</label>
                        <select id="locationFilter" class="form-control transporter-filter-control">
                            <option value="">All Locations</option>
                            <option value="colombo">Colombo</option>
                            <option value="kandy">Kandy</option>
                            <option value="galle">Galle</option>
                            <option value="matale">Matale</option>
                            <option value="anuradhapura">Anuradhapura</option>
                        </select>
                    </div>
                    <div class="transporter-filter-group">
                        <label for="distanceFilter">Max Distance</label>
                        <select id="distanceFilter" class="form-control transporter-filter-control">
                            <option value="">Any Distance</option>
                            <option value="10">Within 10km</option>
                            <option value="25">Within 25km</option>
                            <option value="50">Within 50km</option>
                            <option value="100">Within 100km</option>
                        </select>
                    </div>
                    <div class="transporter-filter-group">
                        <label for="weightFilter">Max Weight</label>
                        <select id="weightFilter" class="form-control transporter-filter-control">
                            <option value="">Any Weight</option>
                            <option value="10">Up to 10kg</option>
                            <option value="25">Up to 25kg</option>
                            <option value="50">Up to 50kg</option>
                            <option value="100">Up to 100kg</option>
                        </select>
                    </div>
                    <div class="transporter-filter-group">
                        <label for="paymentFilter">Min Payment</label>
                        <select id="paymentFilter" class="form-control transporter-filter-control">
                            <option value="">Any Payment</option>
                            <option value="500">Rs. 500+</option>
                            <option value="1000">Rs. 1000+</option>
                            <option value="1500">Rs. 1500+</option>
                            <option value="2000">Rs. 2000+</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card transporter-requests-card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">Delivery Requests</h3>
        </div>
        <div class="card-content">
            <div id="availableDeliveriesList" class="transporter-request-list"></div>
        </div>
    </div>
</div>
