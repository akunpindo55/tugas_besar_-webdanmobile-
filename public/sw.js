self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(clients.claim());
});

self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data?.json() ?? {};
  } catch (e) {
    data = { title: 'Campus Connect', body: event.data?.text() ?? '' };
  }

  const title = data.title || 'Notifikasi Baru';
  const options = {
    body: data.body || '',
    icon: data.icon || '/favicon.ico',
    badge: '/favicon.ico',
    vibrate: [200, 100, 200],
    data: data.data || {},
    tag: data.tag || 'notification',
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const data = event.notification.data;
  let url = '/';

  if (data.type === 'message' && data.conversation_id) {
    url = `/chat/${data.conversation_id}`;
  } else if (data.type === 'forum_reply' && data.topic_id) {
    url = `/topics/${data.topic_id}`;
  } else if (data.type === 'forum_invite' && data.forum_id) {
    url = `/forums/${data.forum_id}`;
  } else if (data.type === 'group_invite' && data.conversation_id) {
    url = `/chat/${data.conversation_id}`;
  }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url.includes(url) && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});
