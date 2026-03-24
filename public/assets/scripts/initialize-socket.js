document.addEventListener('DOMContentLoaded', function() {
    // Initialize Socket.IO connection
    const socket = io('{{ config("app.url") }}', {
        path: '/socket.io',
        transports: ['websocket', 'polling'],
    });

    // Connection events
    socket.on('connect', function() {
        console.log('✅ Connected to server with ID:', socket.id);
    });

    socket.on('disconnect', function(reason) {
        console.log('❌ Disconnected from server:', reason);
    });

    socket.on('connect_error', function(error) {
        console.error('🔴 Connection Error:', error);
    });

    socket.on('reconnect', function(attemptNumber) {
        console.log('🔄 Reconnected after', attemptNumber, 'attempts');
    });

    socket.on('reconnect_attempt', function(attemptNumber) {
        console.log('🔄 Reconnection attempt:', attemptNumber);
    });

    // Global event listeners (add your custom events here)
    socket.on('notification', function(data) {
        console.log('New notification:', data);
        // You can show a toast notification here
        showNotification(data);
    });

    socket.on('user_online', function(data) {
        console.log('User online:', data);
    });

    socket.on('user_offline', function(data) {
        console.log('User offline:', data);
    });

    // Make socket globally available
    window.socket = socket;

    // Notification function
    function showNotification(data) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(data.title, {
                body: data.message,
                icon: '/favicon.ico'
            });
        }
    }

    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
});