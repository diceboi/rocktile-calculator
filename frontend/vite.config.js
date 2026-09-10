import fs from "node:fs";
import path from "node:path";
import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";
import { fileURLToPath, URL } from "node:url";

const certificateDirectory = path.join(
  process.env.APPDATA,
  "Local",
  "run",
  "router",
  "nginx",
  "certs",
);

export default defineConfig({
  plugins: [vue(), tailwindcss()],

  server: {
    host: "rocktile.local",
    port: 5173,
    strictPort: true,
    cors: true,

    https: {
      key: fs.readFileSync(
        path.join(certificateDirectory, "rocktile.local.key"),
      ),
      cert: fs.readFileSync(
        path.join(certificateDirectory, "rocktile.local.crt"),
      ),
    },

    origin: "https://rocktile.local:5173",

    hmr: {
      protocol: "wss",
      host: "rocktile.local",
      port: 5173,
    },
  },

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
