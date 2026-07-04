export const API_BASE_URL = 'https://securecomm.mrs-d.org';

let runtimeApiBaseUrl: string | null = null;

export function setRuntimeApiBaseUrl(url: string | null) {
  runtimeApiBaseUrl = url?.trim() ? url.trim().replace(/\/+$/, '') : null;
}

export function getApiBaseUrls(): string[] {
  return [runtimeApiBaseUrl ?? API_BASE_URL].filter(Boolean) as string[];
}
