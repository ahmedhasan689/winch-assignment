<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { ordersApi } from '../api/orders.js';

// Tab state
const activeTab = ref('pending'); // 'pending' | 'assigned'

const tabs = [
    { key: 'pending', label: 'Pending' },
    { key: 'assigned', label: 'Assigned' },
];

const orders = ref([]);
const isLoading = ref(false);
const message = ref(null);
const assigningId = ref(null);

const counts = ref({ pending: 0, assigned: 0 });

async function loadOrders() {
    isLoading.value = true;
    try {
        const response = await ordersApi.list({
            status: activeTab.value,
            perPage: 50,
        });
        orders.value = response.data;
    } catch (e) {
        showMessage('error', 'Failed to load orders');
    } finally {
        isLoading.value = false;
    }
}

async function refreshCounts() {
    try {
        const [pending, assigned] = await Promise.all([
            ordersApi.list({ status: 'pending', perPage: 1 }),
            ordersApi.list({ status: 'assigned', perPage: 1 }),
        ]);
        counts.value = {
            pending: pending.meta.total,
            assigned: assigned.meta.total,
        };
    } catch (e) {
        // Silent fail for counters
    }
}

async function assignOrder(orderId) {
    assigningId.value = orderId;
    try {
        const response = await ordersApi.assign(orderId);
        const driver = response.data.driver;
        showMessage('success', `Order #${orderId} assigned to ${driver.name}`);
        await Promise.all([loadOrders(), refreshCounts()]);
    } catch (e) {
        const code = e.response?.data?.error_code;
        const msg = e.response?.data?.message ?? 'Assignment failed';
        showMessage('error', `${code ?? 'ERROR'}: ${msg}`);
    } finally {
        assigningId.value = null;
    }
}

async function refreshAll() {
    await Promise.all([loadOrders(), refreshCounts()]);
}

function showMessage(type, text) {
    message.value = { type, text };
    setTimeout(() => (message.value = null), 4000);
}

function formatCoord(value) {
    return Number(value).toFixed(4);
}

function formatTime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString();
}

watch(activeTab, loadOrders);

onMounted(refreshAll);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">WINCH Dispatcher</h1>
                <button
                    @click="refreshAll"
                    :disabled="isLoading"
                    class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 text-sm font-medium"
                >
                    {{ isLoading ? 'Loading...' : 'Refresh' }}
                </button>
            </div>
        </header>

        <!-- Flash message -->
        <div
            v-if="message"
            :class="[
                'max-w-7xl mx-auto px-6 mt-4 py-3 rounded-lg text-sm font-medium',
                message.type === 'success'
                    ? 'bg-green-100 text-green-900 border border-green-200'
                    : 'bg-red-100 text-red-900 border border-red-200',
            ]"
        >
            {{ message.text }}
        </div>

        <main class="max-w-7xl mx-auto px-6 py-8">
            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex space-x-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'py-3 px-1 border-b-2 font-medium text-sm transition-colors',
                            activeTab === tab.key
                                ? 'border-gray-900 text-gray-900'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        ]"
                    >
                        {{ tab.label }}
                        <span
                            :class="[
                                'ml-2 px-2 py-0.5 rounded-full text-xs font-medium',
                                activeTab === tab.key
                                    ? 'bg-gray-900 text-white'
                                    : 'bg-gray-100 text-gray-600',
                            ]"
                        >
                            {{ counts[tab.key] }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Empty / Loading states -->
            <div
                v-if="isLoading && orders.length === 0"
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center text-gray-500"
            >
                Loading orders...
            </div>

            <div
                v-else-if="orders.length === 0"
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center text-gray-500"
            >
                <p>No {{ activeTab }} orders.</p>
            </div>

            <!-- PENDING table -->
            <div
                v-else-if="activeTab === 'pending'"
                class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto"
            >
                <table class="w-full">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pickup</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dropoff</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">#{{ order.id }}</td>
                        <td class="px-6 py-4 text-sm">
                            <div class="text-gray-900 font-medium">{{ order.customer.name }}</div>
                            <div class="text-gray-500 text-xs">{{ order.customer.phone }}</div>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-600 font-mono">
                            {{ formatCoord(order.pickup.lat) }}, {{ formatCoord(order.pickup.lng) }}
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-600 font-mono">
                            {{ formatCoord(order.dropoff.lat) }}, {{ formatCoord(order.dropoff.lng) }}
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            {{ formatTime(order.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button
                                @click="assignOrder(order.id)"
                                :disabled="assigningId === order.id"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ assigningId === order.id ? 'Assigning...' : 'Assign' }}
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- ASSIGNED table -->
            <div
                v-else
                class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto"
            >
                <table class="w-full">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pickup</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dropoff</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Driver</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Driver Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Driver Location</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Assigned At</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">#{{ order.id }}</td>
                        <td class="px-6 py-4 text-sm">
                            <div class="text-gray-900 font-medium">{{ order.customer.name }}</div>
                            <div class="text-gray-500 text-xs">{{ order.customer.phone }}</div>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-600 font-mono">
                            {{ formatCoord(order.pickup.lat) }}, {{ formatCoord(order.pickup.lng) }}
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-600 font-mono">
                            {{ formatCoord(order.dropoff.lat) }}, {{ formatCoord(order.dropoff.lng) }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <template v-if="order.driver">
                                <div class="text-gray-900 font-medium">{{ order.driver.name }}</div>
                                <div class="text-gray-500 text-xs">{{ order.driver.phone }}</div>
                            </template>
                            <span v-else class="text-gray-400 italic text-xs">N/A</span>
                        </td>
                        <td class="px-6 py-4">
                                <span
                                    v-if="order.driver"
                                    :class="[
                                        'inline-flex px-2 py-0.5 text-xs font-medium rounded-full',
                                        order.driver.status === 'busy'
                                            ? 'bg-orange-100 text-orange-800'
                                            : order.driver.status === 'available'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-gray-100 text-gray-700',
                                    ]"
                                >
                                    {{ order.driver.status }}
                                </span>
                            <span v-else class="text-gray-400 text-xs">—</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-600 font-mono">
                            <template v-if="order.driver">
                                {{ formatCoord(order.driver.current_location.lat) }},
                                {{ formatCoord(order.driver.current_location.lng) }}
                            </template>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            {{ formatTime(order.assigned_at) }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</template>
