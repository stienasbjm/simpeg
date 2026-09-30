import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repository = process.env.GITHUB_REPOSITORY?.split("/")[1];
const base = process.env.GITHUB_ACTIONS === "true" && repository ? `/${repository}/` : "/";

export default defineConfig({
  root: "web",
  envDir: "..",
  publicDir: false,
  base,
  plugins: [
    react(),
    {
      name: "copy-404-for-github-pages",
      closeBundle() {
        const outDir = path.resolve(__dirname, "dist");
        const indexHtml = path.resolve(outDir, "index.html");
        const notFoundHtml = path.resolve(outDir, "404.html");
        if (fs.existsSync(indexHtml)) {
          fs.copyFileSync(indexHtml, notFoundHtml);
        }
      },
    },
  ],
  build: {
    outDir: "../dist",
    emptyOutDir: true,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes("node_modules/@supabase/")) return "supabase";
          if (id.includes("node_modules/react")) return "react";
        },
      },
    },
  },
});

