(function () {
'use strict';

let vehicleTypesData = [];

function notify(message, type) {
    const text = String(message || '').trim() || 'Notification';

    if (typeof window.showNotification === 'function') {
        window.showNotification(text, type || 'info');
        return;
    }

    const notification = document.getElementById('notification');
    if (notification) {
        notification.textContent = text;
        notification.className = `notification ${type || 'info'} show`;
        setTimeout(() => notification.classList.remove('show'), 2200);
        return;
    }

    alert(text);
}

function getTransporterApiBase() {
    const origin = String(window.location.origin || '').replace(/\/+$/, '');
    const path = String(window.location.pathname || '');
    const publicMatch = path.match(/^(.*\/public)(?:\/|$)/i);
    if (publicMatch && publicMatch[1]) {
        return origin + publicMatch[1];
    }

    const appRoot = String(window.APP_ROOT || '').replace(/\/+$/, '');
    if (appRoot) {
        return appRoot;
    }

    return origin;
}

function transporterApi(path) {
    const cleanPath = String(path || '').replace(/^\/+/, '');
    return `${getTransporterApiBase()}/transporterdashboard/${cleanPath}`;
}

function parseJsonResponse(response) {
    return response.text().then((raw) => {
        try {
            return JSON.parse(raw);
        } catch (error) {
            throw new Error('Invalid JSON response');
        }
    });
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';

    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return String(text).replace(/[&<>"']/g, (m) => map[m]);
}

function closeModalSafe(modalId) {
    if (!modalId) return;

    if (typeof window.closeModal === 'function') {
        window.closeModal(modalId);
        return;
    }

    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

function vehicleNameToSlug(name) {
    return String(name || '').toLowerCase().replace(/\s+/g, '');
}

function getVehicleTypeName(slug) {
    if (!slug) return '';

    const normalized = String(slug).toLowerCase();
    const matched = vehicleTypesData.find((vt) => vehicleNameToSlug(vt.vehicle_name) === normalized);
    if (matched) {
        return matched.vehicle_name;
    }

    return normalized.charAt(0).toUpperCase() + normalized.slice(1).replace(/([a-z])([A-Z])/g, '$1 $2');
}

function getVehicleIcon(type) {
    const slug = String(type || '').toLowerCase();
    if (slug.includes('bike') || slug.includes('motor')) return 'Bike';
    if (slug.includes('three') || slug.includes('wheel')) return 'Three Wheeler';
    if (slug.includes('car')) return 'Car';
    if (slug.includes('van')) return 'Van';
    if (slug.includes('lorry') || slug.includes('truck')) return 'Truck';
    return 'Vehicle';
}

function generateVehicleTypeOptions(selectedType) {
    const selectedSlug = String(selectedType || '').toLowerCase();
    let options = '<option value="">Select Type</option>';

    vehicleTypesData.forEach((vt) => {
        const slug = vehicleNameToSlug(vt.vehicle_name);
        const selected = slug === selectedSlug ? 'selected' : '';
        options += `<option value="${slug}" data-min="${vt.min_weight_kg}" data-max="${vt.max_weight_kg}" data-type-id="${vt.id}" ${selected}>${vt.vehicle_name} (${vt.min_weight_kg}-${vt.max_weight_kg}kg)</option>`;
    });

    return options;
}

function loadVehicleTypes() {
    fetch(transporterApi('getVehicleTypes'), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((data) => {
            const types = data.types || data.vehicleTypes || [];
            if (!data.success || !Array.isArray(types)) return;

            vehicleTypesData = types;

            const select = document.getElementById('vehicleType');
            if (select && select.options.length <= 1) {
                select.innerHTML = '<option value="">Select Type...</option>' + types.map((t) => {
                    const slug = String(t.vehicle_name || '').toLowerCase().replace(/\s+/g, '');
                    return `<option value="${slug}" data-min="${t.min_weight_kg}" data-max="${t.max_weight_kg}" data-type-id="${t.id}">${escapeHtml(t.vehicle_name)} (${t.min_weight_kg}-${t.max_weight_kg}kg)</option>`;
                }).join('');
            }
        })
        .catch((error) => {
            console.error('Error loading vehicle types:', error);
        });
}

function updateCurrentStatus(vehicles) {
    const activeVehicleSpan = document.getElementById('activeVehicle');
    if (!activeVehicleSpan) return;

    if (!vehicles || vehicles.length === 0) {
        activeVehicleSpan.textContent = 'No vehicles added';
        activeVehicleSpan.style.color = '#666';
        return;
    }

    const activeVehicle = vehicles.find((v) => v.status === 'active');
    if (activeVehicle) {
        const vehicleName = activeVehicle.model || getVehicleTypeName(activeVehicle.type);
        activeVehicleSpan.textContent = `${vehicleName} (${activeVehicle.registration})`;
        activeVehicleSpan.style.color = '#65b57c';
        activeVehicleSpan.style.fontWeight = '700';
        return;
    }

    const firstVehicle = vehicles[0];
    const firstName = firstVehicle.model || getVehicleTypeName(firstVehicle.type);
    activeVehicleSpan.textContent = `${firstName} (${firstVehicle.registration}) - ${firstVehicle.status}`;
    activeVehicleSpan.style.color = '#666';
}

function displayVehicles(vehicles) {
    const container = document.getElementById('myVehiclesContainer');
    const tbody = document.getElementById('vehiclesTableBody');
    if (!container || !tbody) return;

    if (!vehicles || vehicles.length === 0) {
        container.innerHTML = `
            <div class="content-card">
                <div style="padding: 60px 20px; text-align: center; color: #666;">
                    <div style="font-size: 1.4rem; margin-bottom: 20px;">No vehicles yet</div>
                    <p>Click "Add Vehicle" to add your first vehicle.</p>
                </div>
            </div>
        `;

        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 60px 20px; color: #666;">
                    No vehicles added yet. Click "Add Vehicle" to get started.
                </td>
            </tr>
        `;
        return;
    }

    container.innerHTML = vehicles.map((vehicle) => {
        const statusText = String(vehicle.status || '').charAt(0).toUpperCase() + String(vehicle.status || '').slice(1);
        const vehicleStatusClass = vehicle.status === 'active'
            ? 'badge-success'
            : (vehicle.status === 'maintenance' ? 'badge-warning' : 'badge-neutral');
        const vehicleTypeName = getVehicleTypeName(vehicle.type);

        return `
            <div class="content-card" style="margin-bottom: 24px;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">${escapeHtml(vehicle.model || vehicleTypeName)}</h3>
                </div>
                <div class="card-content">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div>
                            <div style="margin-bottom: 20px; line-height: 2.2;">
                                <strong>Vehicle Type:</strong> ${vehicleTypeName}<br>
                                <strong>Registration:</strong> ${escapeHtml(vehicle.registration)}<br>
                                <strong>Capacity:</strong> ${escapeHtml(vehicle.capacity)}kg<br>
                                <strong>Fuel Type:</strong> ${escapeHtml(vehicle.fuel_type || 'N/A')}<br>
                                <strong>Status:</strong> <span class="badge ${vehicleStatusClass}">${statusText}</span>
                            </div>
                            <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 20px;">
                                ${vehicle.status !== 'active' ? `<button class="btn btn-primary" onclick="setActiveVehicle(${vehicle.id})">Set as Active</button>` : ''}
                                <button class="btn btn-outline" onclick="editVehicleModal(${vehicle.id})">Edit</button>
                                <button class="btn btn-outline" onclick="deleteVehicleConfirm(${vehicle.id})">Delete</button>
                            </div>
                        </div>
                        <div>
                            <div style="background: #f8f9fa; border-radius: 12px; padding: 24px; text-align: center;">
                                <div style="font-size: 1.2rem; margin-bottom: 20px;">${getVehicleIcon(vehicle.type)}</div>
                                <div style="font-weight: 600; margin-bottom: 12px; color: #2c3e50;">${escapeHtml(vehicle.model || vehicleTypeName)}</div>
                                <div style="color: #666; margin-bottom: 20px;">
                                    ${vehicle.status === 'active' ? 'Available for delivery' : vehicle.status === 'maintenance' ? 'Under maintenance' : 'Not available'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    tbody.innerHTML = vehicles.map((vehicle) => {
        const statusText = String(vehicle.status || '').charAt(0).toUpperCase() + String(vehicle.status || '').slice(1);
        const vehicleStatusClass = vehicle.status === 'active'
            ? 'badge-success'
            : (vehicle.status === 'maintenance' ? 'badge-warning' : 'badge-neutral');

        return `
            <tr>
                <td>${escapeHtml(vehicle.model || 'N/A')}</td>
                <td>${escapeHtml(vehicle.registration)}</td>
                <td>${getVehicleTypeName(vehicle.type)}</td>
                <td>${escapeHtml(vehicle.capacity)}kg</td>
                <td><span class="badge ${vehicleStatusClass}">${statusText}</span></td>
                <td>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        ${vehicle.status !== 'active' ? `<button class="btn btn-sm btn-primary" onclick="setActiveVehicle(${vehicle.id})">Set Active</button>` : ''}
                        <button class="btn btn-sm btn-outline" onclick="editVehicleModal(${vehicle.id})">Edit</button>
                        <button class="btn btn-sm btn-outline" onclick="deleteVehicleConfirm(${vehicle.id})">Delete</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function loadVehicles() {
    fetch(transporterApi('getVehicles'), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((data) => {
            if (data.success && Array.isArray(data.vehicles)) {
                displayVehicles(data.vehicles);
                updateCurrentStatus(data.vehicles);
                return;
            }
            displayVehicles([]);
            updateCurrentStatus([]);
        })
        .catch((error) => {
            console.error('Error loading vehicles:', error);
            displayVehicles([]);
            updateCurrentStatus([]);
            notify('Failed to load vehicles', 'error');
        });
}

function setupAddVehicleForm() {
    const form = document.getElementById('addVehicleForm');
    if (!form || form.dataset.bound === '1') return;

    form.dataset.bound = '1';
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        const typeSelect = document.getElementById('vehicleType');
        if (typeSelect) {
            const selectedOption = typeSelect.options[typeSelect.selectedIndex];
            const selectedTypeId = selectedOption ? selectedOption.dataset.typeId : '';
            if (selectedTypeId) {
                formData.set('vehicle_type_id', selectedTypeId);
            }
        }

        fetch(transporterApi('addVehicle'), {
            method: 'POST',
            credentials: 'include',
            body: formData,
        })
            .then(parseJsonResponse)
            .then((data) => {
                if (data.success) {
                    notify(data.message || 'Vehicle added successfully', 'success');
                    form.reset();
                    closeModalSafe('addVehicleModal');
                    loadVehicles();
                    return;
                }

                if (data.errors && typeof data.errors === 'object') {
                    const firstError = Object.values(data.errors)[0];
                    notify(firstError || 'Validation failed', 'error');
                } else {
                    notify(data.message || 'Failed to add vehicle', 'error');
                }
            })
            .catch((error) => {
                console.error('Error adding vehicle:', error);
                notify('Failed to add vehicle. Please try again.', 'error');
            });
    });
}

function editVehicleModal(vehicleId) {
    fetch(transporterApi('getVehicles'), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((data) => {
            if (!data.success || !Array.isArray(data.vehicles)) return;
            const vehicle = data.vehicles.find((v) => Number(v.id) === Number(vehicleId));
            if (vehicle) {
                showEditVehicleModal(vehicle);
            }
        })
        .catch((error) => {
            console.error('Error loading vehicle for edit:', error);
            notify('Failed to load vehicle data', 'error');
        });
}

function showEditVehicleModal(vehicle) {
    fetch(transporterApi('getVehicleTypes'), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((res) => {
            const types = res.types || res.vehicleTypes || [];
            if (!res.success || !types.length) {
                notify('Failed to load vehicle types', 'error');
                return;
            }

            vehicleTypesData = types;

            const modalHtml = `
                <div id="editVehicleModal" class="modal" style="display: flex; align-items: center; justify-content: center;" onclick="closeModalOnBackdrop(event, 'editVehicleModal')">
                    <div class="modal-content" onclick="event.stopPropagation()">
                        <div class="modal-header">
                            <h3>Edit Vehicle</h3>
                        </div>
                        <div class="modal-body">
                            <form id="editVehicleForm" onsubmit="submitEditVehicle(event, ${Number(vehicle.id)})">
                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label for="editVehicleType">Vehicle Type *</label>
                                        <select id="editVehicleType" name="type" class="form-control" required>
                                            ${generateVehicleTypeOptions(vehicle.type)}
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="editVehicleRegistration">Registration Number *</label>
                                        <input type="text" id="editVehicleRegistration" name="registration" class="form-control" value="${escapeHtml(vehicle.registration)}" required pattern="^[A-Z]{2,3} \\d{4}$" title="Registration must be 2 or 3 capital letters, a space, and 4 numbers (e.g. AB 1234)">
                                    </div>
                                </div>

                                <div id="editWeightRangeDisplay" style="display: none; padding: 10px; background: #f0f9ff; border-left: 3px solid #3b82f6; margin: 10px 0;">
                                    <strong>Weight Range:</strong> <span id="editWeightRangeText"></span>
                                </div>

                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label for="editVehicleFuelType">Fuel Type</label>
                                        <select id="editVehicleFuelType" name="fuel_type" class="form-control">
                                            <option value="petrol" ${vehicle.fuel_type === 'petrol' ? 'selected' : ''}>Petrol</option>
                                            <option value="diesel" ${vehicle.fuel_type === 'diesel' ? 'selected' : ''}>Diesel</option>
                                            <option value="electric" ${vehicle.fuel_type === 'electric' ? 'selected' : ''}>Electric</option>
                                            <option value="hybrid" ${vehicle.fuel_type === 'hybrid' ? 'selected' : ''}>Hybrid</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="editVehicleModel">Vehicle Model</label>
                                        <input type="text" id="editVehicleModel" name="model" class="form-control" value="${escapeHtml(vehicle.model || '')}" placeholder="e.g., Toyota Hiace">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="editVehicleStatus">Status</label>
                                    <select id="editVehicleStatus" name="status" class="form-control">
                                        <option value="active" ${vehicle.status === 'active' ? 'selected' : ''}>Active</option>
                                        <option value="inactive" ${vehicle.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                        <option value="maintenance" ${vehicle.status === 'maintenance' ? 'selected' : ''}>Maintenance</option>
                                    </select>
                                </div>

                                <div style="display: flex; gap: 20px; margin-top: var(--spacing-lg);">
                                    <button type="submit" class="btn btn-primary">Update Vehicle</button>
                                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;

            const existingModal = document.getElementById('editVehicleModal');
            if (existingModal) existingModal.remove();
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            const typeSelect = document.getElementById('editVehicleType');
            if (!typeSelect) return;

            const syncWeightRange = () => {
                const option = typeSelect.options[typeSelect.selectedIndex];
                const display = document.getElementById('editWeightRangeDisplay');
                const text = document.getElementById('editWeightRangeText');
                if (!display || !text) return;

                if (option && option.value) {
                    text.textContent = `${option.dataset.min}-${option.dataset.max}kg`;
                    display.style.display = 'block';
                } else {
                    display.style.display = 'none';
                }
            };

            syncWeightRange();
            typeSelect.addEventListener('change', syncWeightRange);
        })
        .catch((error) => {
            console.error('Error loading vehicle types:', error);
            notify('Failed to load vehicle types', 'error');
        });
}

function closeEditModal() {
    const modal = document.getElementById('editVehicleModal');
    if (modal) modal.remove();
}

function closeModalOnBackdrop(event, modalId) {
    if (event.target.id === modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.remove();
    }
}

function submitEditVehicle(event, vehicleId) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const typeSelect = document.getElementById('editVehicleType');
    if (typeSelect) {
        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        const selectedTypeId = selectedOption ? selectedOption.dataset.typeId : '';
        if (selectedTypeId) {
            formData.set('vehicle_type_id', selectedTypeId);
        }
    }

    fetch(transporterApi(`editVehicle/${vehicleId}`), {
        method: 'POST',
        credentials: 'include',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    })
        .then(parseJsonResponse)
        .then((data) => {
            if (data.success) {
                notify(data.message || 'Vehicle updated successfully', 'success');
                closeEditModal();
                loadVehicles();
                return;
            }

            if (data.errors && typeof data.errors === 'object') {
                const firstError = Object.values(data.errors)[0];
                notify(firstError || 'Validation failed', 'error');
            } else {
                notify(data.message || 'Failed to update vehicle', 'error');
            }
        })
        .catch((error) => {
            console.error('Error updating vehicle:', error);
            notify('Failed to update vehicle. Please try again.', 'error');
        });
}

function setActiveVehicle(vehicleId) {
    if (!confirm('Set this vehicle as active? This will deactivate all other vehicles.')) {
        return;
    }

    fetch(transporterApi(`setActiveVehicle/${vehicleId}`), {
        method: 'POST',
        credentials: 'include',
    })
        .then(parseJsonResponse)
        .then((data) => {
            if (data.success) {
                notify(data.message || 'Vehicle set as active', 'success');
                loadVehicles();
            } else {
                notify(data.message || 'Failed to set active vehicle', 'error');
            }
        })
        .catch((error) => {
            console.error('Error setting active vehicle:', error);
            notify('Failed to set active vehicle. Please try again.', 'error');
        });
}

function deleteVehicleConfirm(vehicleId) {
    if (!confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) {
        return;
    }

    fetch(transporterApi(`deleteVehicle/${vehicleId}`), {
        method: 'POST',
        credentials: 'include',
    })
        .then(parseJsonResponse)
        .then((data) => {
            if (data.success) {
                notify(data.message || 'Vehicle deleted', 'success');
                loadVehicles();
            } else {
                notify(data.message || 'Failed to delete vehicle', 'error');
            }
        })
        .catch((error) => {
            console.error('Error deleting vehicle:', error);
            notify('Failed to delete vehicle. Please try again.', 'error');
        });
}

window.editVehicleModal = editVehicleModal;
window.closeEditModal = closeEditModal;
window.closeModalOnBackdrop = closeModalOnBackdrop;
window.submitEditVehicle = submitEditVehicle;
window.setActiveVehicle = setActiveVehicle;
window.deleteVehicleConfirm = deleteVehicleConfirm;

document.addEventListener('DOMContentLoaded', function () {
    loadVehicleTypes();
    setupAddVehicleForm();
    loadVehicles();
});
})();
