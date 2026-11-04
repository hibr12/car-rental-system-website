const entries = new Map();

/**
 * Small in-memory cache for non-transactional reference lists.
 * Values and in-flight requests are shared so route changes do not repeat the
 * same request. Bookings, payments, and availability must never use this.
 */
export const getCachedReference = (key, loader, ttlMs = 60_000) => {
  const now = Date.now();
  const cached = entries.get(key);

  if (cached?.value && cached.expiresAt > now) {
    return Promise.resolve(cached.value);
  }

  if (cached?.promise) {
    return cached.promise;
  }

  const promise = loader()
    .then((value) => {
      // A mutation may invalidate this key while the request is in flight.
      // Let the original caller receive its response, but never repopulate the
      // shared cache with data fetched before that mutation.
      if (entries.get(key)?.promise === promise) {
        entries.set(key, { value, expiresAt: Date.now() + ttlMs, promise: null });
      }
      return value;
    })
    .catch((error) => {
      if (entries.get(key)?.promise === promise) entries.delete(key);
      throw error;
    });

  entries.set(key, { value: cached?.value, expiresAt: cached?.expiresAt || 0, promise });
  return promise;
};

export const invalidateReference = (prefix) => {
  for (const key of entries.keys()) {
    if (key.startsWith(prefix)) entries.delete(key);
  }
};
