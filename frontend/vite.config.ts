import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

const apiProxyTarget = process.env.VITE_API_PROXY_TARGET ?? 'http://127.0.0.1:8000';
const allowedHosts = (process.env.VITE_ALLOWED_HOSTS ?? 'preview.altaabshor.com,10.40.0.51,localhost')
    .split(',')
    .map((host) => host.trim())
    .filter(Boolean);

export default defineConfig({
    plugins: [react(), tailwindcss()],
    server: {
        allowedHosts,
        proxy: {
            '/api': apiProxyTarget,
            '/sanctum': apiProxyTarget,
            '/broadcasting': apiProxyTarget,
        },
    },
});
