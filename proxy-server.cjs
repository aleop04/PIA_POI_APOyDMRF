/* eslint-disable @typescript-eslint/no-require-imports */

const express = require("express");
const { createProxyMiddleware } = require("http-proxy-middleware");

const app = express();

app.use(
    createProxyMiddleware({
        pathFilter: "/socket.io",
        target: "http://localhost:3000",
        changeOrigin: false,
        ws: true,
        headers: {
            "X-Forwarded-Proto": "https",
            "X-Forwarded-Host": "goon-backfield-truce.ngrok-free.dev",
        },
    }),
);

app.use(
    createProxyMiddleware({
        target: "http://localhost:8000",
        changeOrigin: false,
        headers: {
            "X-Forwarded-Proto": "https",
            "X-Forwarded-Host": "goon-backfield-truce.ngrok-free.dev",
        },
    }),
);

app.listen(8080, () => {
    console.log("Proxy corriendo en http://localhost:8080");
    console.log("Laravel: / -> http://localhost:8000");
    console.log("Socket.IO: /socket.io -> http://localhost:3000/socket.io");
});