import inertia from "@inertiajs/vite";
import { lattice } from "@lattice-php/lattice/vite";
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import laravel from "laravel-vite-plugin";
import fs from "node:fs";
import path from "node:path";
import { defineConfig } from "vite";

const useLocalLattice = process.env.LATTICE_SOURCE === "1";

// Source mode compiles Lattice from the checkout its `path` repository links in.
// It has to be the real path, not the `vendor/` symlink `preserveSymlinks` would
// otherwise keep: each package's `tsconfig.json` extends a base file that lives
// at the root of the monorepo and has no counterpart under `vendor/`.
const latticeRoot = useLocalLattice
    ? fs.realpathSync(path.resolve(import.meta.dirname, "vendor/lattice-php/lattice"))
    : undefined;

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                // The rich editor and the websocket client are both large and
                // used by a minority of pages; splitting them keeps them out of
                // the entry chunk every page pays for.
                manualChunks(id) {
                    if (id.includes("prosemirror") || id.includes("@tiptap")) {
                        return "rich-editor-vendor";
                    }
                    if (id.includes("pusher-js") || id.includes("@laravel/echo")) {
                        return "echo-vendor";
                    }
                },
            },
        },
    },
    resolve: {
        alias: {
            "@": path.resolve(import.meta.dirname, "resources/js"),
        },
        // A Composer `path` repository symlinks its package into `vendor/`, and
        // Vite would resolve that to the real path outside this project — where
        // walking up for `node_modules` finds nothing. Keeping the symlinked path
        // lets a linked package's plugin resolve `@lattice-php/*` from here.
        preserveSymlinks: true,
    },
    plugins: [
        lattice({
            source: useLocalLattice,
            root: latticeRoot,
            icons: {
                dirs: ["resources/icons"],
            },
        }),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.tsx"],
            refresh: true,
        }),
        inertia(),
        react(),
        tailwindcss(),
    ],
});
