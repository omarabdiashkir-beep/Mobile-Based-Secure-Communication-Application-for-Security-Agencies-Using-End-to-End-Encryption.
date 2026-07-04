import { getApiBaseUrls } from './config';

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  token?: string;
  body?: unknown;
};

function isNetworkError(error: unknown) {
  if (!(error instanceof Error)) {
    return false;
  }
  const msg = error.message.toLowerCase();
  return msg.includes('network request failed') || msg.includes('fetch failed');
}

function isWrongHostResponse(error: unknown) {
  if (!(error instanceof Error)) {
    return false;
  }
  const msg = error.message.toLowerCase();
  return (
    msg.includes('returned html instead of json') ||
    msg.includes('unexpected api response content type') ||
    msg.includes('request failed: 404')
  );
}

let lastWorkingBaseUrl: string | null = null;

export function getLastWorkingBaseUrl() {
  return lastWorkingBaseUrl;
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', token, body } = options;

  const baseCandidates = getApiBaseUrls();
  const candidates = [
    ...(lastWorkingBaseUrl ? [lastWorkingBaseUrl] : []),
    ...baseCandidates,
  ].filter((x, i, arr) => arr.indexOf(x) === i);
  let lastError: unknown;

  for (let i = 0; i < candidates.length; i += 1) {
    const base = candidates[i];
    try {
      console.log(`[API] Attempting ${method} ${base}${path}...`);
      const isFormData = body instanceof FormData;
      const response = await fetch(`${base}${path}`, {
        method,
        headers: {
          ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        ...(body ? { body: isFormData ? body : JSON.stringify(body) } : {}),
      });

      if (!response.ok) {
        console.warn(`[API] ${base} returned status ${response.status}`);
        const text = await response.text();
        const contentType = response.headers.get('content-type') || '';

        if (response.status === 401) {
          throw new Error('Unauthorized. Please login again.');
        }

        if (response.status === 404 || response.status === 422 || response.status === 413) {
          console.warn(`[API] ${response.status} body:`, text);
          try {
            return JSON.parse(text) as T;
          } catch {
            return null as T;
          }
        }

        if (contentType.includes('text/html') || text.toLowerCase().includes('<!doctype html')) {
          throw new Error('API returned HTML instead of JSON. Check API auth/session and base URL.');
        }

        throw new Error(text || `Request failed: ${response.status}`);
      }

      if (response.status === 204) {
        lastWorkingBaseUrl = base;
        console.log(`[API] Success with ${base}`);
        return {} as T;
      }

      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        const text = await response.text();
        if (text.toLowerCase().includes('<!doctype html')) {
          throw new Error('API returned HTML instead of JSON. Please login again.');
        }
        throw new Error('Unexpected API response content type.');
      }

      const data = (await response.json()) as T;
      lastWorkingBaseUrl = base;
      console.log(`[API] Success with ${base}`);
      return data;
    } catch (error) {
      console.error(`[API] Request to ${base} failed:`, error instanceof Error ? error.message : error);
      lastError = error;
      // retry only if this is a network/connectivity problem
      if (i < candidates.length - 1 && (isNetworkError(error) || isWrongHostResponse(error))) {
        console.log(`[API] Retrying with next candidate...`);
        continue;
      }
      throw error;
    }
  }

  throw lastError instanceof Error ? lastError : new Error('API request failed');
}
