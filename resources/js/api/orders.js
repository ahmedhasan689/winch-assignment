import axios from 'axios';

export const ordersApi = {
    list({ status = null, perPage = 15, page = 1 } = {}) {
        const params = { per_page: perPage, page };
        if (status) params.status = status;

        return axios.get('/orders', { params }).then(r => r.data);
    },

    assign(orderId) {
        return axios.post(`/orders/${orderId}/assign`).then(r => r.data);
    },
};
