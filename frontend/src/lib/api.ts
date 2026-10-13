import axios from "axios";

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? "http://localhost:8000/api",
  withCredentials: true, // Sanctum cookie session
  headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
});

// Backend is the permission authority — this client never hides security logic,
// it only routes UX by role after GET /me.
export async function fetchMe() {
  const { data } = await api.get("/me");
  return data;
}
