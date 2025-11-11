// src/assets/js/reportes/common/apiClient.js
export function apiBase() {
  const RAW =
    process.env.VUE_APP_API_URL ||
    (process.env.VUE_APP_API_BASE
      ? String(process.env.VUE_APP_API_BASE).replace(/\/+$/, "") + "/api"
      : "/api");
  return String(RAW).replace(/\/+$/, "");
}

function readToken() {
  const keys = ["token", "auth_token", "jwt", "access_token"];
  for (const k of keys) {
    const v = localStorage.getItem(k);
    if (v) return v.replace(/^"|"$/g, "");
  }
  return null;
}

export function authHeaders(extra = {}) {
  const t = readToken();
  return { Accept: "application/json", ...(t ? { Authorization: `Bearer ${t}` } : {}), ...extra };
}

export async function fetchJSON(path, params = "", headers = {}) {
  const url = `${apiBase()}/${path}${params ? `?${params}` : ""}`;
  const res = await fetch(url, { headers: authHeaders({ "Cache-Control": "no-cache", ...headers }) });
  if (res.status === 401) { const err = new Error("401 Unauthorized"); err.code = 401; throw err; }
  if (!res.ok)            { const err = new Error(`HTTP ${res.status}`); err.code = res.status; throw err; }
  return res.json();
}

export async function downloadBinary(url) {
  const res = await fetch(url, { headers: authHeaders({ "Cache-Control": "no-cache" }) });
  if (res.status === 401) { const err = new Error("401 Unauthorized"); err.code = 401; throw err; }
  if (!res.ok)            { const err = new Error(`HTTP ${res.status}`); err.code = res.status; throw err; }
  const cd = res.headers.get("Content-Disposition") || "";
  let filename = "";
  const m = /filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i.exec(cd);
  if (m) filename = decodeURIComponent(m[1] || m[2] || "");
  return { blob: await res.blob(), filename };
}
