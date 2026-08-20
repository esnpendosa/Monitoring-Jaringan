// Rozitech NMS Service Worker for PWA & Push Notifications
const CACHE_NAME = 'rozitech-nms-v1';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(clients.claim());
});

// Push Notification Listener (Handling Server Web Push & Background Notifications)
self.addEventListener('push', (event) => {
  let data = {
    title: 'Rozitech NMS',
    body: 'Notifikasi baru dari sistem Rozitech Network Management System',
    icon: '/net.rozitech.co.id/public/assets/img/pwa/icon-192x192.png',
    badge: '/net.rozitech.co.id/public/assets/img/pwa/icon-192x192.png',
    data: { url: '/net.rozitech.co.id/public/' }
  };

  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body || data.message,
    icon: data.icon || '/net.rozitech.co.id/public/assets/img/pwa/icon-192x192.png',
    badge: data.badge || '/net.rozitech.co.id/public/assets/img/pwa/icon-192x192.png',
    vibrate: [200, 100, 200, 100, 200],
    data: data.data || { url: data.action_url || '/net.rozitech.co.id/public/' },
    actions: [
      { action: 'open', title: 'Buka Aplikasi' },
      { action: 'close', title: 'Tutup' }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Notification Click Listener (Navigating to Action URL on Android/Desktop click)
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const targetUrl = (event.notification.data && event.notification.data.url) 
    ? event.notification.data.url 
    : '/net.rozitech.co.id/public/';

  if (event.action === 'close') return;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url.includes(targetUrl) && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});
