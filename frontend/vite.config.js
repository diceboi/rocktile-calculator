import fs from "node:fs";
import path from "node:path";
import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";
import { fileURLToPath, URL } from "node:url";

const certificateDirectory = process.env.APPDATA
  ? path.join(
      process.env.APPDATA,
      "Local",
      "run",
      "router",
      "nginx",
      "certs",
    )
  : null;

const keyPath = certificateDirectory ? path.join(certificateDirectory, "rocktile.local.key") : null;
const certPath = certificateDirectory ? path.join(certificateDirectory, "rocktile.local.crt") : null;
const hasHttpsCerts = keyPath && certPath && fs.existsSync(keyPath) && fs.existsSync(certPath);

const serverConfig = {
  host: "rocktile.local",
  port: 5173,
  strictPort: true,
  cors: true,
  origin: "https://rocktile.local:5173",
  hmr: {
    protocol: "wss",
    host: "rocktile.local",
    port: 5173,
  },
};

if (hasHttpsCerts) {
  serverConfig.https = {
    key: fs.readFileSync(keyPath),
    cert: fs.readFileSync(certPath),
  };
}

export default defineConfig({
  plugins: [vue(), tailwindcss()],

  server: serverConfig,

  build: {
    outDir: fileURLToPath(new URL("../dist", import.meta.url)),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: fileURLToPath(new URL("src/main.js", import.meta.url)),
      output: {
        entryFileNames: "assets/[name]-[hash].js",
        chunkFileNames: "assets/[name]-[hash].js",
        assetFileNames: "assets/[name]-[hash].[ext]",
      },
    },
  },
});
