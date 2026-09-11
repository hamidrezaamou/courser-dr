import { defineConfig, type Plugin } from "vite";
import react from "@vitejs/plugin-react";
// @ts-expect-error type error without @types/node package
import process from "node:process";

const host = process.env.TAURI_DEV_HOST;

function clinicProxy(): Plugin {
  return {
    name: "clinic-proxy",
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        if (!req.url?.startsWith("/__clinic/")) {
          next();
          return;
        }
        const header = req.headers["x-clinic-site"];
        const rawSite = Array.isArray(header) ? header[0] : header;
        const site = (rawSite || "https://mramo.ir").replace(/\/+$/, "");
        const rest = req.url.slice("/__clinic".length);
        const target = `${site}/api/mobile/v1${rest}`;
        const parts: Uint8Array[] = [];
        req.on("data", (chunk) => parts.push(chunk as Uint8Array));
        req.on("end", () => {
          let total = 0;
          for (const part of parts) total += part.byteLength;
          const body = new Uint8Array(total);
          let offset = 0;
          for (const part of parts) {
            body.set(part, offset);
            offset += part.byteLength;
          }
          const headers: Record<string, string> = {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
          };
          if (req.headers.authorization) headers.Authorization = String(req.headers.authorization);
          if (req.headers["content-type"]) headers["Content-Type"] = String(req.headers["content-type"]);
          void fetch(target, {
            method: req.method,
            headers,
            body: req.method === "GET" || req.method === "HEAD" ? undefined : body,
          })
            .then(async (upstream) => {
              res.statusCode = upstream.status;
              const contentType = upstream.headers.get("content-type");
              if (contentType) res.setHeader("content-type", contentType);
              res.end(new Uint8Array(await upstream.arrayBuffer()));
            })
            .catch(() => {
              res.statusCode = 502;
              res.setHeader("content-type", "application/json; charset=utf-8");
              res.end(JSON.stringify({ message: "پروکسی به هاست وصل نشد." }));
            });
        });
      });
    },
  };
}

export default defineConfig(() => ({
  plugins: [react(), clinicProxy()],
  css: { postcss: { plugins: [] } },
  clearScreen: false,
  server: {
    port: 1420,
    strictPort: true,
    host: host || "127.0.0.1",
    hmr: host
      ? {
          protocol: "ws",
          host,
          port: 1421,
        }
      : undefined,
    watch: {
      ignored: ["**/src-tauri/**"],
    },
  },
}));
