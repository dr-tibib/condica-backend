import { openDB } from 'idb';
import { syncEvents as apiSyncEvents, uploadMedia } from './api';

const DB_NAME = 'offline-sync';
const PENDING_EVENTS_STORE = 'pending_events';
const PENDING_MEDIA_STORE = 'pending_media';

const dbPromise = openDB(DB_NAME, 1, {
  upgrade(db) {
    if (!db.objectStoreNames.contains(PENDING_EVENTS_STORE)) {
      db.createObjectStore(PENDING_EVENTS_STORE, { keyPath: 'local_id', autoIncrement: true });
    }
    if (!db.objectStoreNames.contains(PENDING_MEDIA_STORE)) {
      db.createObjectStore(PENDING_MEDIA_STORE, { keyPath: 'local_id' });
    }
  },
});

export const addPendingEvent = async (event: any) => {
  const db = await dbPromise;
  return db.add(PENDING_EVENTS_STORE, event);
};

export const addPendingMedia = async (local_id: number, blob: Blob, type: 'photo' | 'video') => {
  const db = await dbPromise;
  return db.put(PENDING_MEDIA_STORE, { local_id, blob, type });
};

export const syncPendingData = async () => {
  const db = await dbPromise;
  const pendingEvents = await db.getAll(PENDING_EVENTS_STORE);

  if (pendingEvents.length === 0) {
    return;
  }

  try {
    const response = await apiSyncEvents(pendingEvents);
    const syncedEvents = response.data;

    for (const syncedEvent of syncedEvents) {
      const pendingMedia = await db.get(PENDING_MEDIA_STORE, syncedEvent.local_id);
      if (pendingMedia) {
        await uploadMedia(syncedEvent.server_id, pendingMedia.blob, pendingMedia.type);
        await db.delete(PENDING_MEDIA_STORE, syncedEvent.local_id);
      }
      await db.delete(PENDING_EVENTS_STORE, syncedEvent.local_id);
    }
  } catch (error) {
    console.error('Failed to sync pending data:', error);
  }
};

window.addEventListener('online', syncPendingData);
