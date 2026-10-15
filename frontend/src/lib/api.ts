import axios from "axios";

const API_URL = import.meta.env.VITE_API_URL ?? "http://localhost:8000/api";
const ORIGIN = API_URL.replace(/\/api\/?$/, "");

export const api = axios.create({
  baseURL: API_URL,
  withCredentials: true, // Sanctum cookie session
  headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
});

// Backend is the permission authority — this client never hides security logic,
// it only routes UX by role after GET /me.
export async function fetchMe() {
  const { data } = await api.get("/me");
  return data;
}

// Must run before the first POST: fetches the XSRF-TOKEN cookie that axios
// then sends back as X-XSRF-TOKEN automatically.
export function csrf() {
  return axios.get(`${ORIGIN}/sanctum/csrf-cookie`, { withCredentials: true });
}
