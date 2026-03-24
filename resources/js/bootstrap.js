import axios from 'axios';
import Echo from "laravel-echo";

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

// import './echo';

window.io = require("socket.io-client");

window.Echo = new Echo({
    broadcaster: "socket.io",
    host: window.location.hostname + ":3000",
    auth: {
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            "Authorization": `Bearer ${localStorage.getItem('token') ?? ''}` // optional if you use API tokens
        }
    }
});